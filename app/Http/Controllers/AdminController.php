<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Feedback;
use App\Services\OllamaService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    private OllamaService $aiService;
    
    public function __construct(OllamaService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function dashboard()
    {
        return view('admin.dashboard');
    }

    public function dashboardData(Request $request)
    {
        $start = $request->get('start');
        $end = $request->get('end');
        $forceFresh = $request->boolean('fresh');
        
        $latestId = Feedback::max('id') ?? 0;
        $cacheKey = 'dashboard_' . md5($start . $end . $latestId . Auth::id());
        
        if ($forceFresh) {
            Cache::forget($cacheKey);
        }
        
        $data = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($start, $end) {
            return $this->buildDashboardData($start, $end);
        });
        
        return response()->json($data);
    }

    public function pollUpdates(Request $request)
    {
        $lastId = (int) $request->get('last_id', 0);
        
        $query = Feedback::orderBy('id', 'desc');
        $query = $this->applyDepartmentFilter($query);
        $latestFeedback = $query->first();
        
        $latestId = $latestFeedback ? $latestFeedback->id : 0;
        $hasNew = $latestId > $lastId;
        
        $response = [
            'has_new' => $hasNew,
            'latest_id' => $latestId,
            'latest_update' => $latestFeedback ? $latestFeedback->updated_at->toIso8601String() : null
        ];
        
        if ($hasNew) {
            $countQuery = Feedback::where('id', '>', $lastId);
            $countQuery = $this->applyDepartmentFilter($countQuery);
            $newCount = $countQuery->count();
            $response['new_count'] = $newCount;
            
            if ($latestFeedback) {
                $response['latest_feedback'] = [
                    'id' => $latestFeedback->id,
                    'name' => $latestFeedback->name,
                    'role' => $latestFeedback->role,
                    'department' => $latestFeedback->department,
                    'rating' => (int) $latestFeedback->rating,
                    'type' => $latestFeedback->type,
                    'feedback' => $latestFeedback->feedback,
                    'created_at' => $latestFeedback->created_at->toIso8601String()
                ];
            }
        }
        
        return response()->json($response);
    }

    private function applyDepartmentFilter($query)
{
    $user = Auth::user();
    
    if (!$user) {
        return $query;
    }
    
    if ($user->role === 'super_admin' || $user->role === 'quality_assurance') {
        return $query;
    }
    
    if ($user->department) {
        return $query->where('department', $user->department);
    }
    
    return $query;
}

    private function buildDashboardData(?string $start, ?string $end): array
    {
        $query = Feedback::query();
        $query = $this->applyDepartmentFilter($query);
        
        if ($start && $end) {
            $query->whereBetween('created_at', [
                Carbon::parse($start)->startOfDay(),
                Carbon::parse($end)->endOfDay()
            ]);
        }
        
        $totalFeedback = $query->count();
        $avgRating = $totalFeedback > 0 ? round($query->avg('rating') ?? 0, 1) : 0;
        $flaggedCount = $query->clone()->where('rating', '<=', 2)->count();
        
        $roleCounts = $query->clone()
            ->select('role', DB::raw('count(*) as total'))
            ->groupBy('role')
            ->pluck('total', 'role')
            ->toArray();
            
        $departmentCounts = $query->clone()
            ->select('department', DB::raw('count(*) as total'))
            ->groupBy('department')
            ->pluck('total', 'department')
            ->toArray();
            
        $typeCounts = $query->clone()
            ->select('type', DB::raw('count(*) as total'))
            ->groupBy('type')
            ->pluck('total', 'type')
            ->toArray();
            
        $ratingDistribution = $this->getRatingDistribution($query);
        $sentimentBreakdown = $this->calculateSentiment($ratingDistribution, $totalFeedback);
        $submissionTrend = $this->getSubmissionTrend($query);
        
        $negativeByDepartment = $query->clone()
            ->where('rating', '<=', 2)
            ->select('department', DB::raw('count(*) as total'))
            ->groupBy('department')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->toArray();

        $outstandingByDepartment = $query->clone()
            ->where('rating', '>=', 4)
            ->select('department', DB::raw('count(*) as total'))
            ->groupBy('department')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->toArray();

        $negativeDeptNames = array_column($negativeByDepartment, 'department');

        $filteredOutstanding = array_filter($outstandingByDepartment, function($dept) use ($negativeDeptNames) {
            return !in_array($dept['department'], $negativeDeptNames);
        });

        $filteredOutstanding = array_values($filteredOutstanding);

        $outstandingDept = !empty($filteredOutstanding) ? $filteredOutstanding[0] : (!empty($outstandingByDepartment) ? $outstandingByDepartment[0] : null);
            
        $recentFeedback = $query->clone()
            ->latest()
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'role' => $item->role,
                    'department' => $item->department,
                    'rating' => (int) $item->rating,
                    'type' => $item->type,
                    'feedback' => $item->feedback,
                    'created_at' => $item->created_at->toIso8601String()
                ];
            })
            ->toArray();
            
        $feedbacksForAI = $query->clone()
            ->latest()
            ->limit(50)
            ->get(['feedback', 'department'])
            ->toArray();
    
        $texts = array_column($feedbacksForAI, 'feedback');
        $texts = array_filter($texts, fn($t) => !empty(trim($t)));

        try {
            $aiInsights = $this->aiService->smartCluster($texts);
        } catch (\Exception $e) {
            Log::error('AI Insights failed: ' . $e->getMessage());
            $aiInsights = [];
        }

        try {
            $aiNarrative = $this->aiService->generateInsights($texts);
        } catch (\Exception $e) {
            Log::error('AI Narrative failed: ' . $e->getMessage());
            $aiNarrative = [];
        }

        try {
            $aiRecommendations = $this->aiService->generateRecommendations($texts);
        } catch (\Exception $e) {
            Log::error('AI Recommendations failed: ' . $e->getMessage());
            $aiRecommendations = [];
        }

        if (empty($aiInsights)) {
            $aiInsights = [[
                'issue' => 'No issues detected',
                'title' => 'All Clear',
                'count' => $totalFeedback,
                'priority' => 'positive',
                'department' => 'All Departments'
            ]];
        }

        if (empty($aiNarrative)) {
            $aiNarrative = [[
                'title' => 'Dashboard Active',
                'description' => "Currently tracking {$totalFeedback} feedback submissions. The system is monitoring for patterns and trends.",
                'priority' => 'positive',
                'department' => 'System'
            ]];
        }

        if (empty($aiRecommendations)) {
            $aiRecommendations = [[
                'title' => 'Continue Monitoring',
                'term' => 'short-term',
                'evidence' => "{$totalFeedback} feedbacks collected",
                'action' => 'Maintain regular feedback collection to identify improvement opportunities.',
                'impact' => 'Ongoing service quality monitoring'
            ]];
        }
        
        if (empty($aiNarrative)) {
            $aiNarrative = $this->getFallbackNarrative($texts);
        }
        
        if (empty($aiRecommendations)) {
            $aiRecommendations = $this->getFallbackRecommendations($texts);
        }
        
        $recurringTerms = $this->extractTrendingTerms($feedbacksForAI);
        $growthMetrics = $this->calculateGrowthMetrics($query, $start, $end);
        
        return [
            'totalFeedback' => $totalFeedback,
            'avgRating' => $avgRating,
            'flaggedCount' => $flaggedCount,
            'roleCounts' => $roleCounts,
            'departmentCounts' => $departmentCounts,
            'typeCounts' => $typeCounts,
            'ratingDistribution' => $ratingDistribution,
            'sentimentBreakdown' => $sentimentBreakdown,
            'submissionTrend' => $submissionTrend,
            'negativeByDepartment' => $negativeByDepartment,
            'outstandingByDepartment' => $filteredOutstanding,
            'outstandingOffice' => $outstandingDept ? $outstandingDept['department'] : 'N/A',
            'outstandingOfficeScore' => $outstandingDept ? $outstandingDept['total'] : 0,
            'recentFeedback' => $recentFeedback,
            'aiInsights' => $aiInsights,
            'aiNarrative' => $aiNarrative,
            'aiRecommendations' => $aiRecommendations,
            'recurringTerms' => $recurringTerms,
            'totalGrowth' => $growthMetrics['totalGrowth'],
            'avgChange' => $growthMetrics['avgChange'],
            'flaggedChange' => $growthMetrics['flaggedChange'],
            'latestFeedbackTime' => Feedback::max('updated_at') ? Feedback::max('updated_at') : now()->toIso8601String(),
            'latestId' => Feedback::max('id') ?? 0,
            'currentMonthSubmissions' => $query->clone()->whereMonth('created_at', now()->month)->count(),
            'flaggedOffice' => !empty($negativeByDepartment) ? $negativeByDepartment[0]['department'] : null,
            'flaggedOfficeScore' => !empty($negativeByDepartment) ? $negativeByDepartment[0]['total'] : 0,
        ];
    }

    private function getRatingDistribution($query): array
    {
        $ratingRaw = $query->clone()
            ->select('rating', DB::raw('count(*) as total'))
            ->groupBy('rating')
            ->pluck('total', 'rating');
            
        return [
            1 => (int) ($ratingRaw[1] ?? 0),
            2 => (int) ($ratingRaw[2] ?? 0),
            3 => (int) ($ratingRaw[3] ?? 0),
            4 => (int) ($ratingRaw[4] ?? 0),
            5 => (int) ($ratingRaw[5] ?? 0),
        ];
    }

    private function calculateSentiment(array $ratingDist, int $total): array
    {
        if ($total === 0) return ['Positive' => 0, 'Neutral' => 0, 'Negative' => 0];
        
        $positive = ($ratingDist[4] ?? 0) + ($ratingDist[5] ?? 0);
        $neutral = $ratingDist[3] ?? 0;
        $negative = ($ratingDist[1] ?? 0) + ($ratingDist[2] ?? 0);
        
        return [
            'Positive' => round(($positive / $total) * 100, 1),
            'Neutral' => round(($neutral / $total) * 100, 1),
            'Negative' => round(($negative / $total) * 100, 1),
        ];
    }

    private function getSubmissionTrend($query): array
    {
        $trend = $query->clone()
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as total'))
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();
            
        return [
            'labels' => $trend->map(fn($r) => Carbon::parse($r->date)->format('M d'))->values()->toArray(),
            'values' => $trend->map(fn($r) => (int) $r->total)->values()->toArray()
        ];
    }

    private function calculateGrowthMetrics($query, ?string $start, ?string $end): array
    {
        $currentTotal = $query->count();
        $currentAvg = $currentTotal > 0 ? ($query->clone()->avg('rating') ?? 0) : 0;
        $currentFlagged = $query->clone()->where('rating', '<=', 2)->count();
        
        $previousQuery = Feedback::query();
        $previousQuery = $this->applyDepartmentFilter($previousQuery);
        
        if ($start && $end) {
            $currentStart = Carbon::parse($start);
            $currentEnd = Carbon::parse($end);
            $days = $currentStart->diffInDays($currentEnd) + 1;
            $previousStart = (clone $currentStart)->subDays($days);
            $previousEnd = (clone $currentStart)->subDay();
            $previousQuery->whereBetween('created_at', [$previousStart, $previousEnd]);
        } else {
            $previousQuery->whereMonth('created_at', now()->subMonth()->month);
        }
        
        $previousTotal = $previousQuery->count();
        $previousAvg = $previousTotal > 0 ? ($previousQuery->clone()->avg('rating') ?? 0) : 0;
        $previousFlagged = $previousQuery->clone()->where('rating', '<=', 2)->count();
        
        return [
            'totalGrowth' => $previousTotal > 0 ? round((($currentTotal - $previousTotal) / $previousTotal) * 100, 1) : 0,
            'avgChange' => round($currentAvg - $previousAvg, 1),
            'flaggedChange' => $currentFlagged - $previousFlagged
        ];
    }

    private function extractTrendingTerms(array $feedbacks): array
    {
        $phrases = [];
        $stopWords = ['the','and','you','your','with','for','this','that','are','was','were','has','have'];
        
        foreach ($feedbacks as $item) {
            $text = strtolower($item['feedback'] ?? '');
            $text = preg_replace('/[^\w\s]/', '', $text);
            $words = explode(' ', $text);
            $words = array_filter($words, fn($w) => strlen($w) > 2 && !in_array($w, $stopWords));
            $words = array_values($words);
            
            for ($i = 0; $i < count($words) - 1; $i++) {
                $phrase = $words[$i] . ' ' . $words[$i + 1];
                $phrases[$phrase] = ($phrases[$phrase] ?? 0) + 1;
            }
            
            for ($i = 0; $i < count($words) - 2; $i++) {
                $phrase = $words[$i] . ' ' . $words[$i + 1] . ' ' . $words[$i + 2];
                if (strlen($phrase) <= 40) {
                    $phrases[$phrase] = ($phrases[$phrase] ?? 0) + 1;
                }
            }
        }
        
        arsort($phrases);
        $terms = [];
        
        foreach (array_slice($phrases, 0, 15) as $term => $count) {
            if ($count >= 2) {
                $terms[] = ['term' => $term, 'count' => $count];
            }
        }
        
        return $terms;
    }

    private function getFallbackNarrative(array $texts): array
    {
        $counts = array_count_values($texts);
        arsort($counts);
        $topIssues = array_slice($counts, 0, 3, true);
        
        $narratives = [];
        foreach ($topIssues as $issue => $count) {
            $priority = $count >= 4 ? 'critical' : ($count >= 3 ? 'warning' : 'positive');
            $narratives[] = [
                'title' => ucfirst($issue) . ' reported in feedback',
                'description' => "This issue was mentioned {$count} times in recent feedback submissions. " . 
                    ($count >= 4 ? 'It requires immediate attention.' : 'It should be monitored.'),
                'priority' => $priority,
                'department' => 'Multiple Departments'
            ];
        }
        
        return $narratives;
    }

    private function getFallbackRecommendations(array $texts): array
    {
        $counts = array_count_values($texts);
        arsort($counts);
        $topIssues = array_slice($counts, 0, 3, true);
        
        $recommendations = [];
        foreach ($topIssues as $issue => $count) {
            $recommendations[] = [
                'title' => "Address {$issue}",
                'term' => $count >= 4 ? 'short-term' : 'medium-term',
                'evidence' => "Mentioned {$count} times in feedback",
                'action' => "Review and implement improvements for {$issue}",
                'impact' => "Improved satisfaction and reduced complaints"
            ];
        }
        
        return $recommendations;
    }

    public function feedbacks()
    {
        $query = Feedback::latest();
        $query = $this->applyDepartmentFilter($query);
        $feedbacks = $query->paginate(20);
        return view('admin.feedbacks', compact('feedbacks'));
    }

    public function show($id)
    {
        $feedback = Feedback::findOrFail($id);
        return view('admin.feedback-show', compact('feedback'));
    }

    public function delete($id)
    {
        Feedback::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    public function flagged(Request $request)
    {
        $query = Feedback::where('rating', '<=', 2);
        $query = $this->applyDepartmentFilter($query);
        
        if ($request->has('sort')) {
            $direction = $request->get('direction', 'asc');
            $query->orderBy($request->sort, $direction);
        } else {
            $query->orderBy('rating', 'asc')->orderBy('created_at', 'desc');
        }
        
        $flaggedFeedbacks = $query->paginate(15);
        return view('admin.flagged', compact('flaggedFeedbacks'));
    }

    public function resolveFlagged($id)
    {
        $feedback = Feedback::findOrFail($id);
        $feedback->update(['rating' => 3]);
        
        return response()->json([
            'success' => true,
            'message' => 'Feedback resolved successfully'
        ]);
    }

    public function settings()
    {
        return view('admin.settings');
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'system_name' => 'required|string|max:255',
            'maintenance_mode' => 'boolean',
        ]);
        
        return back()->with('success', 'Settings updated successfully');
    }
}