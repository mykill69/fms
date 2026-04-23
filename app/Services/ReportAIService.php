<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ReportAIService
{
    private const MODEL = 'llama3:8b';
    private const BASE_URL = 'http://127.0.0.1:11434/api/generate';

    private function callModel(string $prompt, int $timeout = 25, array $options = []): ?string
    {
        try {
            $payload = [
                'model' => self::MODEL,
                'prompt' => $prompt,
                'stream' => false,
                'options' => array_merge([
                    'temperature' => 0.3,
                    'num_predict' => 800,
                    'top_k' => 20,
                    'top_p' => 0.8,
                    'repeat_penalty' => 1.15,
                ], $options)
            ];

            $response = Http::timeout($timeout)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post(self::BASE_URL, $payload);

            if (!$response->successful()) {
                Log::error('Report AI HTTP error', ['status' => $response->status()]);
                return null;
            }

            return trim($response->json()['response'] ?? '');
        } catch (\Exception $e) {
            Log::error('Report AI error', ['message' => $e->getMessage()]);
            return null;
        }
    }

    public function generateReportAnalysis(array $feedbacks, array $statistics, array $dates, array $filters): array
    {
        if (empty($feedbacks)) {
            return $this->getEmptyAnalysis();
        }

        $cacheKey = 'report_analysis_' . md5(serialize($feedbacks) . serialize($dates));
        
        return Cache::remember($cacheKey, now()->addHours(1), function () use ($feedbacks, $statistics, $dates, $filters) {
            $summary = $this->generateSummary($feedbacks, $statistics, $dates, $filters);
            $recommendations = $this->generateRecommendationsWithEAI($feedbacks, $statistics, $dates);
            
            return [
                'summary' => $summary,
                'recommendations' => $recommendations,
                'generated_at' => now()->toDateTimeString()
            ];
        });
    }

    private function generateSummary(array $feedbacks, array $statistics, array $dates, array $filters): string
    {
        $sampleFeedbacks = array_slice($feedbacks, 0, 10);
        $feedbackTexts = array_map(function ($f) {
            return "- {$f['feedback']} (Rating: {$f['rating']}/5, Dept: {$f['department']})";
        }, $sampleFeedbacks);
        
        $context = implode("\n", $feedbackTexts);
        
        $filterContext = "";
        if (!empty($filters['department'])) {
            $filterContext .= "\nFiltered by Department: {$filters['department']}";
        }
        if (!empty($filters['type'])) {
            $filterContext .= "\nFiltered by Type: {$filters['type']}";
        }
        if (!empty($filters['role'])) {
            $filterContext .= "\nFiltered by Role: {$filters['role']}";
        }

        $prompt = "You are an expert data analyst for a university feedback system. Generate a concise, data-driven executive summary based on the following metrics and feedback samples.

REPORT PERIOD: {$dates['label']}
TOTAL FEEDBACKS: {$statistics['total_feedbacks']}
AVERAGE RATING: {$statistics['average_rating']}/5 ({$statistics['rating_percentage']}%)
SATISFACTION RATE: {$statistics['satisfaction_rate']}% ({$statistics['positive_feedbacks']} positive out of {$statistics['total_feedbacks']})
RATING TREND: {$statistics['rating_trend']} by {$statistics['rating_change']}% compared to previous period
{$filterContext}

SAMPLE FEEDBACKS:
{$context}

Write a professional 3-4 paragraph executive summary that:
1. Opens with overall assessment of the period
2. Highlights key metrics and what they indicate
3. Identifies notable patterns or concerns from feedback samples
4. Concludes with a forward-looking statement

Be specific, reference actual numbers, and maintain a professional tone. Do not use markdown formatting.";

        $summary = $this->callModel($prompt, 20, ['num_predict' => 400, 'temperature' => 0.4]);
        
        return $summary ?: $this->generateFallbackSummary($statistics, $dates);
    }

    private function generateRecommendationsWithEAI(array $feedbacks, array $statistics, array $dates): array
    {
        // Group feedbacks by department for analysis
        $deptGroups = [];
        foreach ($feedbacks as $f) {
            $dept = $f['department'];
            if (!isset($deptGroups[$dept])) {
                $deptGroups[$dept] = [];
            }
            $deptGroups[$dept][] = $f;
        }

        $topIssues = $this->identifyTopIssues($feedbacks);
        
        $sampleFeedbacks = array_slice($feedbacks, 0, 8);
        $feedbackContext = "";
        foreach ($sampleFeedbacks as $f) {
            if (strlen($f['feedback']) > 10) {
                $feedbackContext .= "- \"{$f['feedback']}\" (Dept: {$f['department']}, Rating: {$f['rating']}/5)\n";
            }
        }

        $issuesContext = "";
        foreach ($topIssues as $issue) {
            $issuesContext .= "- {$issue}\n";
        }

        $prompt = "You are a strategic advisor for university administration. Based on the feedback analysis below, generate 3-5 actionable recommendations using the EAI (Evidence, Action, Impact) framework.

REPORT PERIOD: {$dates['label']}
TOTAL FEEDBACKS: {$statistics['total_feedbacks']}
AVERAGE RATING: {$statistics['average_rating']}/5
SATISFACTION RATE: {$statistics['satisfaction_rate']}%

TOP IDENTIFIED ISSUES/PATTERNS:
{$issuesContext}

SAMPLE FEEDBACKS:
{$feedbackContext}

Return ONLY valid JSON array in this exact format:
[
  {
    \"title\": \"Clear, action-oriented recommendation title\",
    \"evidence\": \"Specific data points and feedback quotes that justify this recommendation. Include numbers, percentages, and actual quotes from the feedback. Be precise and thorough.\",
    \"action\": \"Detailed 3-4 sentence action plan. Specify WHO should act, WHAT exactly should be done, and HOW to implement. Include timeline and specific steps.\",
    \"impact\": \"Quantifiable expected outcomes with metrics. Project percentage improvements, timeline for results, and how success will be measured.\"
  }
]

GUIDELINES:
- Evidence must reference actual data from the provided statistics or feedback quotes
- Actions must be specific, assignable, and realistic for a university setting
- Impact must be measurable with concrete metrics
- Focus on high-impact recommendations that address patterns in the data
- If satisfaction rate is high, include recommendations to sustain and scale success

Make each recommendation substantive and actionable. Avoid generic advice.";

        $raw = $this->callModel($prompt, 30, ['num_predict' => 800, 'temperature' => 0.3]);
        $result = $this->parseJsonResponse($raw);
        
        if (empty($result)) {
            return $this->generateFallbackRecommendations($statistics, $feedbacks);
        }
        
        return $result;
    }

    private function identifyTopIssues(array $feedbacks): array
    {
        $issues = [];
        $keywords = [
            'slow' => 'Slow service/delays',
            'wait' => 'Long wait times',
            'staff' => 'Staff attitude/behavior',
            'system' => 'System/portal issues',
            'online' => 'Online services',
            'confusing' => 'Confusing processes',
            'unclear' => 'Unclear information',
            'helpful' => 'Helpful staff',
            'friendly' => 'Friendly service',
            'efficient' => 'Efficient service',
            'clean' => 'Cleanliness',
            'organized' => 'Organization'
        ];
        
        $counts = [];
        foreach ($feedbacks as $f) {
            $text = strtolower($f['feedback']);
            foreach ($keywords as $keyword => $label) {
                if (strpos($text, $keyword) !== false) {
                    $counts[$label] = ($counts[$label] ?? 0) + 1;
                }
            }
        }
        
        arsort($counts);
        $topIssues = array_slice($counts, 0, 5);
        
        $result = [];
        foreach ($topIssues as $issue => $count) {
            $result[] = "{$issue} (mentioned {$count} times)";
        }
        
        return $result;
    }

    private function generateFallbackSummary(array $statistics, array $dates): string
    {
        $trend = $statistics['rating_trend'] === 'up' ? 'improved' : 'declined';
        $change = abs($statistics['rating_change']);
        
        return "During the period of {$dates['label']}, a total of {$statistics['total_feedbacks']} feedback submissions were received with an average rating of {$statistics['average_rating']} out of 5 stars. " .
               "The satisfaction rate stands at {$statistics['satisfaction_rate']}%, with {$statistics['positive_feedbacks']} positive ratings, {$statistics['neutral_feedbacks']} neutral, and {$statistics['negative_feedbacks']} negative. " .
               "Compared to the previous period, ratings have {$trend} by {$change}%. " .
               "This data indicates " . ($statistics['satisfaction_rate'] >= 70 ? "generally positive" : "room for improvement in") . " service delivery across departments. " .
               "Key areas of feedback highlight the importance of continued monitoring and targeted improvements to enhance overall satisfaction. " .
               "Moving forward, focusing on areas with lower ratings will be crucial for maintaining and improving service quality standards.";
    }

    private function generateFallbackRecommendations(array $statistics, array $feedbacks): array
    {
        $recommendations = [];
        
        if ($statistics['satisfaction_rate'] < 70) {
            $recommendations[] = [
                'title' => 'Improve Overall Service Satisfaction',
                'evidence' => "Current satisfaction rate is {$statistics['satisfaction_rate']}% with {$statistics['negative_feedbacks']} negative ratings out of {$statistics['total_feedbacks']} total feedbacks.",
                'action' => "Conduct a focused review of departments with below-average ratings. Schedule service improvement workshops within 2 weeks. Implement weekly check-ins with department heads to monitor progress. Create a task force to address recurring complaints identified in feedback.",
                'impact' => "Expected 15-20% increase in satisfaction rate within 60 days. Reduction in negative feedback by 30% and improvement in average rating from {$statistics['average_rating']} to at least 4.0."
            ];
        }
        
        if ($statistics['rating_trend'] === 'down') {
            $recommendations[] = [
                'title' => 'Address Declining Rating Trend',
                'evidence' => "Average rating has declined by {$statistics['rating_change']}% compared to the previous period, indicating emerging service issues.",
                'action' => "Review feedback from the last 30 days to identify specific pain points. Schedule immediate meetings with departments showing significant rating drops. Implement a 30-day rapid improvement plan with daily monitoring.",
                'impact' => "Reverse the declining trend within 30 days. Target average rating improvement of at least 0.5 points. Prevent further decline and stabilize satisfaction metrics."
            ];
        }
        
        $recommendations[] = [
            'title' => 'Enhance Feedback Response System',
            'evidence' => "Analysis of " . count($feedbacks) . " feedback submissions reveals opportunities to better address specific concerns in real-time.",
            'action' => "Implement a 24-hour acknowledgment system for all feedback submissions. Create a feedback response template library for common issues. Assign department liaisons to personally respond to ratings below 3 stars within 48 hours.",
            'impact' => "Expected 25% increase in positive feedback mentioning 'responsive service'. Improved trust in feedback system leading to 20% more detailed submissions. Better insights for continuous improvement."
        ];
        
        if ($statistics['satisfaction_rate'] >= 80) {
            $recommendations[] = [
                'title' => 'Scale and Document Successful Practices',
                'evidence' => "High satisfaction rate of {$statistics['satisfaction_rate']}% indicates effective service delivery practices exist within the organization.",
                'action' => "Identify top-performing departments and document their best practices. Create a 'Service Excellence Playbook' for distribution across all departments. Recognize high-performing teams with quarterly awards. Implement peer mentoring between high and lower-performing departments.",
                'impact' => "Sustain satisfaction rates above 80% while elevating lower-performing departments by 15-20%. Create culture of continuous improvement and knowledge sharing."
            ];
        }
        
        return array_slice($recommendations, 0, 4);
    }

    private function parseJsonResponse(?string $raw): array
    {
        if (!$raw) return [];
        
        $raw = str_replace(['```json', '```', "\r\n", "\r"], "\n", $raw);
        $raw = trim($raw);
        
        preg_match('/(\[[\s\S]*\]|\{[\s\S]*\})/', $raw, $matches);
        $jsonString = $matches[0] ?? $raw;
        
        $jsonString = preg_replace('/,\s*([\]}])/m', '$1', $jsonString);
        
        $decoded = json_decode($jsonString, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Report AI JSON parse failed', ['raw' => substr($raw, 0, 200)]);
            return [];
        }
        
        if (isset($decoded['title'])) {
            $decoded = [$decoded];
        }
        
        return array_values(array_filter($decoded, fn($item) => is_array($item) && !empty($item['title'])));
    }

    private function getEmptyAnalysis(): array
    {
        return [
            'summary' => 'No feedback data available for the selected period. Please adjust your date range or filters to generate an analysis.',
            'recommendations' => [],
            'generated_at' => now()->toDateTimeString()
        ];
    }
}