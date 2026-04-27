<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Feedback;
use App\Models\SubmissionLog;
use App\Models\Office;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FeedbackController extends Controller
{
    public function create()
    {
        $offices = Office::orderBy('office_name', 'asc')->get();
        return view('feedback', compact('offices'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'role' => 'required',
            'department' => 'required',
            'feedback' => 'required',
            'type' => 'required',
            'rating' => 'nullable|integer|min:1|max:5'
        ]);

        $ip = $request->ip();
        $userAgent = $request->userAgent();
        $sessionId = $request->session()->getId();
        $department = $request->department;
        $deviceFingerprint = md5($ip . $userAgent);

        $this->logSubmission($ip, $userAgent, $sessionId, $department);

        $dailyTotal = SubmissionLog::where('ip_address', $ip)
            ->whereDate('created_at', today())
            ->count();

        if ($dailyTotal >= 3) {
            $this->logBlockedAttempt($ip, $department, 'daily_limit_exceeded');
            return back()->with('error', 'You have reached the maximum number of feedback submissions for today. Please try again tomorrow.');
        }

        $hourlyTotal = SubmissionLog::where('ip_address', $ip)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($hourlyTotal >= 2) {
            $this->logBlockedAttempt($ip, $department, 'hourly_limit_exceeded');
            return back()->with('error', 'Please wait before submitting another feedback.');
        }

        $departmentDailyCount = SubmissionLog::where('ip_address', $ip)
            ->where('department', $department)
            ->whereDate('created_at', today())
            ->count();

        if ($departmentDailyCount >= 1) {
            $this->logBlockedAttempt($ip, $department, 'department_limit_exceeded');
            return back()->with('error', 'You have already submitted feedback for this office today. To ensure fairness, please provide feedback for other offices.');
        }

        $deviceCount = SubmissionLog::where('device_fingerprint', $deviceFingerprint)
            ->whereDate('created_at', today())
            ->count();

        if ($deviceCount >= 3) {
            $this->logBlockedAttempt($ip, $department, 'device_limit_exceeded');
            return back()->with('error', 'Maximum submissions reached for this device.');
        }

        $duplicateCheck = Feedback::where('department', $department)
            ->where('feedback', $request->feedback)
            ->where('created_at', '>=', now()->subHours(24))
            ->exists();

        if ($duplicateCheck) {
            $this->logBlockedAttempt($ip, $department, 'duplicate_content');
            return back()->with('error', 'This exact feedback has already been submitted recently.');
        }

        $similarityCheck = $this->checkSimilarFeedback($request->feedback, $department);
        if ($similarityCheck) {
            $this->logBlockedAttempt($ip, $department, 'similar_content');
            return back()->with('error', 'Similar feedback has already been submitted. Please provide unique feedback.');
        }

        $suspiciousActivity = $this->detectCoordinatedAttack($department, $ip);
        if ($suspiciousActivity) {
            $this->logBlockedAttempt($ip, $department, 'suspicious_pattern');
            Cache::put('department_blocked_' . $department, true, now()->addHours(2));
            return back()->with('error', 'Unusual activity detected for this office. Submissions are temporarily limited.');
        }

        $feedback = Feedback::create([
            'name' => null,
            'role' => $request->role,
            'department' => $department,
            'feedback' => $request->feedback,
            'rating' => (int) $request->rating,
            'type' => $request->type,
        ]);

        SubmissionLog::create([
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'session_id' => $sessionId,
            'department' => $department,
            'device_fingerprint' => $deviceFingerprint,
            'feedback_id' => $feedback->id,
            'rating' => $request->rating,
            'status' => 'allowed'
        ]);

        Cache::put('last_submission_' . $ip, now(), now()->addMinutes(30));
        Cache::put('dept_submission_' . $ip . '_' . md5($department), true, now()->addHours(24));

        return back()->with('success', 'Feedback submitted successfully');
    }

    private function logSubmission($ip, $userAgent, $sessionId, $department)
    {
        Log::info('Feedback submission attempt', [
            'ip' => $ip,
            'user_agent' => substr($userAgent, 0, 100),
            'session' => $sessionId,
            'department' => $department,
            'timestamp' => now()
        ]);
    }

    private function logBlockedAttempt($ip, $department, $reason)
    {
        SubmissionLog::create([
            'ip_address' => $ip,
            'user_agent' => request()->userAgent(),
            'session_id' => request()->session()->getId(),
            'department' => $department,
            'device_fingerprint' => md5($ip . request()->userAgent()),
            'feedback_id' => null,
            'rating' => request()->rating ?? 0,
            'status' => 'blocked',
            'block_reason' => $reason
        ]);

        Log::warning('Feedback blocked', [
            'ip' => $ip,
            'department' => $department,
            'reason' => $reason
        ]);
    }

    private function checkSimilarFeedback($text, $department)
    {
        $recentFeedbacks = Feedback::where('department', $department)
            ->where('created_at', '>=', now()->subHours(24))
            ->pluck('feedback');

        $input = strtolower(trim($text));
        $inputWords = explode(' ', $input);
        $inputWordCount = count($inputWords);

        foreach ($recentFeedbacks as $existing) {
            $existing = strtolower(trim($existing));
            $existingWords = explode(' ', $existing);
            
            similar_text($input, $existing, $percent);
            
            if ($percent > 70) {
                return true;
            }

            $commonWords = array_intersect($inputWords, $existingWords);
            $similarityRatio = count($commonWords) / max($inputWordCount, 1);
            
            if ($similarityRatio > 0.8 && $inputWordCount > 3) {
                return true;
            }
        }

        return false;
    }

    private function detectCoordinatedAttack($department, $currentIp)
    {
        $recentCount = Feedback::where('department', $department)
            ->where('created_at', '>=', now()->subMinutes(15))
            ->count();

        if ($recentCount >= 5) {
            return true;
        }

        $highRatingCount = Feedback::where('department', $department)
            ->where('rating', '>=', 4)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        $totalCount = Feedback::where('department', $department)
            ->where('created_at', '>=', now()->subHour())
            ->count();

        if ($totalCount >= 4 && $highRatingCount / $totalCount > 0.8) {
            return true;
        }

        $subnet = substr($currentIp, 0, strrpos($currentIp, '.'));
        $sameSubnetCount = SubmissionLog::where('ip_address', 'like', $subnet . '.%')
            ->where('department', $department)
            ->where('created_at', '>=', now()->subMinutes(30))
            ->count();

        if ($sameSubnetCount >= 3) {
            return true;
        }

        if (Cache::has('department_blocked_' . $department)) {
            return true;
        }

        return false;
    }
}