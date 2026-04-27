<?php
// app/Services/OllamaService.php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class OllamaService
{
    private const MODEL = 'llama3:8b';
    // private const BASE_URL = 'http://103.107.82.222:11434/api/generate';
     private const BASE_URL = 'http://127.0.0.1:11434:11434/api/generate';
    
    private function callModel(string $prompt, int $timeout = 12, array $options = []): ?string
    {
        try {
            $payload = [
                'model' => self::MODEL,
                'prompt' => $prompt,
                'stream' => false,
                'options' => array_merge([
                    'temperature' => 0.2,
                    'num_predict' => 200,
                    'top_k' => 20,
                    'top_p' => 0.8,
                    'repeat_penalty' => 1.15,
                ], $options)
            ];

            $response = Http::timeout($timeout)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post(self::BASE_URL, $payload);

            if (!$response->successful()) {
                Log::error('Ollama HTTP error', ['status' => $response->status()]);
                return null;
            }

            return trim($response->json()['response'] ?? '');
        } catch (\Exception $e) {
            Log::error('Ollama error', ['message' => $e->getMessage()]);
            return null;
        }
    }

    public function smartAnalyze(string $text): ?array
    {
        $prompt = "Return ONLY valid JSON: {\"issue\":\"short label\",\"priority\":\"low|medium|high\",\"department\":\"IT|Registrar|Library|Other\"}\nFeedback: {$text}";
        $raw = $this->callModel($prompt, 10, ['num_predict' => 60]);
        
        if (!$raw) return null;
        
        $raw = str_replace(['```json', '```', "\r\n", "\r"], "\n", $raw);
        preg_match('/\{[\s\S]*\}/', $raw, $m);
        return json_decode($m[0] ?? '{}', true);
    }

    public function analyzeBatch(array $feedbacks): array
    {
        if (empty($feedbacks)) {
            return ['insights' => [], 'recommendations' => [], 'clusters' => []];
        }

        $texts = array_column($feedbacks, 'feedback');
        $texts = array_filter($texts, fn($t) => strlen(trim($t)) > 3);
        
        if (empty($texts)) {
            return ['insights' => [], 'recommendations' => [], 'clusters' => []];
        }

        $cacheKey = 'ai_analysis_' . md5(implode('|', $texts));
        
        return Cache::remember($cacheKey, now()->addHours(2), function () use ($texts) {
            $clusters = $this->smartCluster($texts);
            $insights = $this->generateInsights($texts);
            $recommendations = $this->generateRecommendations($texts);
            
            return [
                'insights' => $clusters,
                'aiNarrative' => $insights,
                'recommendations' => $recommendations,
                'clusters' => $clusters
            ];
        });
    }

    public function smartCluster(array $texts): array
{
    if (empty($texts)) return [];

    $cacheKey = 'cluster_' . md5(implode('|', $texts));
    
    return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($texts) {
        $map = [];
        $stopWords = ['the','and','you','your','with','for','this','that','are','was','were','has','have','from','they','what','when','where'];
        $negativeWords = ['slow', 'bad', 'poor', 'problem', 'issue', 'complaint', 'not', 'never', 'terrible', 'worst', 'delay', 'wait', 'long', 'rude', 'unfriendly', 'difficult', 'confusing', 'complicated', 'broken', 'error', 'fail', 'lack', 'missing', 'need', 'improve', 'enhance', 'upgrade', 'fix', 'no', 'damn'];
        
        foreach ($texts as $text) {
            $clean = strtolower(trim($text));
            $clean = preg_replace('/[^\w\s]/', '', $clean);
            $clean = preg_replace('/\s+/', ' ', $clean);
            
            if (strlen($clean) < 3) continue;
            
            $hasNegative = false;
            foreach ($negativeWords as $nw) {
                if (stripos($clean, $nw) !== false) {
                    $hasNegative = true;
                    break;
                }
            }
            
            if (!$hasNegative) continue;
            
            $words = explode(' ', $clean);
            $words = array_filter($words, fn($w) => strlen($w) > 2 && !in_array($w, $stopWords));
            $words = array_values($words);
            
            for ($i = 0; $i < count($words) - 1; $i++) {
                $phrase = $words[$i] . ' ' . $words[$i + 1];
                $map[$phrase] = ($map[$phrase] ?? 0) + 1;
            }
            
            for ($i = 0; $i < count($words) - 2; $i++) {
                $phrase = $words[$i] . ' ' . $words[$i + 1] . ' ' . $words[$i + 2];
                $map[$phrase] = ($map[$phrase] ?? 0) + 1;
            }
        }
        
        arsort($map);
        
        $result = [];
        $totalMentions = array_sum($map);
        
        foreach ($map as $issue => $count) {
            if ($count < 2) continue;
            
            $percentage = $totalMentions > 0 ? round(($count / $totalMentions) * 100) : 0;
            
            if ($percentage >= 20 || $count >= 5) {
                $priority = 'critical';
            } elseif ($percentage >= 10 || $count >= 3) {
                $priority = 'high';
            } else {
                $priority = 'medium';
            }
            
            $result[] = [
                'issue' => $issue,
                'title' => ucfirst($issue),
                'count' => $count,
                'percentage' => $percentage,
                'priority' => $priority,
                'department' => $this->detectDepartment($issue)
            ];
        }
        
        if (empty($result)) {
            return [[
                'issue' => 'No significant concerns',
                'title' => 'All Clear',
                'count' => 0,
                'priority' => 'positive',
                'department' => 'All Departments'
            ]];
        }
        
        return array_slice($result, 0, 8);
    });
}

    private function detectDepartment(string $text): string
    {
        $text = strtolower($text);
        $keywords = [
            'IT Department' => ['wifi', 'internet', 'connection', 'network', 'computer', 'system', 'portal', 'online', 'website', 'signal'],
            'Registrar Office' => ['enroll', 'registration', 'grades', 'transcript', 'record', 'schedule', 'course', 'subject'],
            'Library Services' => ['book', 'library', 'borrow', 'return', 'study', 'research', 'reference', 'reading'],
            'Accounting Office' => ['payment', 'fee', 'tuition', 'billing', 'receipt', 'finance', 'cash', 'pay'],
            'Student Affairs' => ['student', 'counsel', 'advise', 'guidance', 'activity', 'event', 'organization'],
            'MANAGEMENT INFORMATION SYSTEM OFFICE' => ['mis', 'information', 'system', 'data', 'software', 'hardware'],
            'GENERAL SERVICES OFFICE' => ['general', 'service', 'maintenance', 'repair', 'facility', 'clean']
        ];
        
        foreach ($keywords as $dept => $terms) {
            foreach ($terms as $term) {
                if (str_contains($text, $term)) return $dept;
            }
        }
        
        return 'Multiple Departments';
    }

    public function generateInsights(array $texts): array
{
    if (empty($texts)) return [];

    $clusters = $this->smartCluster($texts);
    
    if (empty($clusters)) return [];
    
    $sampleFeedbacks = array_slice($texts, 0, 15);
    $feedbackContext = "";
    foreach ($sampleFeedbacks as $feedback) {
        if (strlen($feedback) > 10) {
            $feedbackContext .= "- \"{$feedback}\"\n";
        }
    }
    
    $input = "";
    foreach (array_slice($clusters, 0, 6) as $c) {
        $input .= "- Theme: \"{$c['issue']}\" | Frequency: {$c['count']} mentions | Department: {$c['department']}\n";
    }

    $prompt = "You are a senior university feedback analyst specializing in higher education institutional research. Analyze this feedback data and provide meaningful, data-driven insights.

RECENT FEEDBACK SAMPLES:
{$feedbackContext}

IDENTIFIED THEMES WITH FREQUENCY:
{$input}

Generate 3-5 detailed insights. Return ONLY valid JSON array in this exact format:
[
  {
    \"title\": \"Compelling insight headline (e.g., 'Registration Delays Emerge as Top Student Pain Point')\",
    \"description\": \"Detailed 4-5 sentence analysis. Include: (1) What the data shows with specific numbers, (2) Why this pattern matters for the university, (3) Which departments are affected and how, (4) Potential root causes based on the feedback language, (5) The urgency level based on frequency and sentiment. Be specific and reference actual feedback quotes.\",
    \"priority\": \"critical|warning|positive\",
    \"department\": \"Specific department name\"
  }
]

GUIDELINES:
- critical = 5+ mentions of serious issues OR negative sentiment with high frequency
- warning = 3-4 mentions of issues OR emerging negative patterns
- positive = strengths to celebrate and build upon
- Reference actual numbers from the data provided
- Connect insights to operational impact (student experience, staff efficiency, etc.)
- If positive patterns exist, highlight them as institutional strengths

Example good description: 'The data reveals 8 distinct complaints about slow service at the Registrar Office, representing 40% of all negative feedback this period. Students specifically mention waiting 30+ minutes for simple transactions like transcript requests. This bottleneck affects approximately 200 students weekly during peak periods and creates cascading delays in enrollment verification and graduation processing. The consistency of 'short-staffed' mentions suggests a resource allocation issue rather than individual performance problems.'";

    $raw = $this->callModel($prompt, 25, ['num_predict' => 500, 'temperature' => 0.3]);
    $result = $this->parseJsonResponse($raw);
    
    if (empty($result)) {
        return $this->generateEnhancedFallbackInsights($clusters, $texts);
    }
    
    return $result;
}

private function generateEnhancedFallbackInsights(array $clusters, array $texts): array
{
    $insights = [];
    $feedbackSamples = array_slice($texts, 0, 10);
    
    foreach (array_slice($clusters, 0, 4) as $c) {
        $issue = $c['issue'];
        $count = $c['count'];
        $dept = $c['department'];
        
        $relevantSamples = array_filter($feedbackSamples, function($sample) use ($issue) {
            return stripos($sample, $issue) !== false;
        });
        
        $sampleText = !empty($relevantSamples) 
            ? '"' . reset($relevantSamples) . '"'
            : "various feedback submissions";
        
        $isNegative = stripos($issue, 'slow') !== false || 
                     stripos($issue, 'bad') !== false || 
                     stripos($issue, 'poor') !== false ||
                     stripos($issue, 'problem') !== false ||
                     stripos($issue, 'complaint') !== false;
        
        $isPositive = stripos($issue, 'good') !== false || 
                     stripos($issue, 'great') !== false || 
                     stripos($issue, 'excellent') !== false ||
                     stripos($issue, 'amazing') !== false ||
                     stripos($issue, 'nice') !== false;
        
        if ($isPositive) {
            $priority = 'positive';
            $description = "Positive feedback pattern identified: {$count} separate mentions praising {$issue} at {$dept}. A representative comment stated: {$sampleText}. This indicates a strength worth recognizing and potentially replicating across other departments. Consistent positive feedback in this area correlates with higher overall satisfaction scores and suggests effective practices are in place.";
        } elseif ($isNegative) {
            $priority = $count >= 5 ? 'critical' : 'warning';
            $description = "Concerning pattern: {$count} complaints about {$issue} at {$dept}. Sample feedback: {$sampleText}. This issue appears repeatedly in recent submissions, suggesting a systemic problem rather than isolated incidents. If unaddressed, this could lead to decreased satisfaction, increased complaint volume, and potential reputational impact. The frequency indicates this should be prioritized for immediate review.";
        } else {
            $priority = $count >= 4 ? 'warning' : 'positive';
            $description = "Notable pattern: {$count} mentions related to {$issue} at {$dept}. Sample feedback: {$sampleText}. While not overtly negative, the recurrence suggests this is top-of-mind for stakeholders. Monitoring this trend and proactively addressing underlying needs could prevent future complaints and demonstrate responsiveness.";
        }
        
        $insights[] = [
            'title' => ucfirst($issue) . ' Pattern Detected at ' . $dept,
            'description' => $description,
            'priority' => $priority,
            'department' => $dept
        ];
    }
    
    return $insights;
}

    public function generateRecommendations(array $texts): array
{
    if (empty($texts)) return [];

    $clusters = $this->smartCluster($texts);
    $topClusters = array_slice($clusters, 0, 5);

    if (empty($topClusters)) {
        $counts = array_count_values($texts);
        arsort($counts);
        $topCounts = array_slice($counts, 0, 5, true);
        foreach ($topCounts as $text => $count) {
            $topClusters[] = [
                'issue' => $text,
                'count' => $count,
                'priority' => $count >= 5 ? 'high' : ($count >= 3 ? 'medium' : 'low'),
                'department' => $this->detectDepartment($text)
            ];
        }
    }

    $sampleFeedbacks = array_slice($texts, 0, 15);
    $feedbackContext = "";
    foreach ($sampleFeedbacks as $feedback) {
        if (strlen($feedback) > 10) {
            $feedbackContext .= "- \"{$feedback}\"\n";
        }
    }

    $input = "";
    foreach ($topClusters as $c) {
        $input .= "- Issue: \"{$c['issue']}\" | Frequency: {$c['count']} mentions | Priority: {$c['priority']} | Department: {$c['department']}\n";
    }

    $prompt = "You are a senior university administrator and process improvement consultant with 20 years experience in higher education operations. Analyze this feedback data and provide strategic, actionable recommendations.

RECENT FEEDBACK SAMPLES:
{$feedbackContext}

IDENTIFIED ISSUES WITH METRICS:
{$input}

Based on this data, provide 3-5 strategic recommendations. Focus on actionable improvements that address root causes. For positive feedback, recommend reinforcement strategies.

Return ONLY valid JSON array in this exact format:
[
  {
    \"title\": \"Specific, compelling recommendation title (e.g., 'Implement Queue Management System at Registrar Office')\",
    \"term\": \"short-term|medium-term|long-term\",
    \"evidence\": \"Quote 2-3 specific feedback examples that justify this recommendation. Be precise: 'Slow service' mentioned 8 times; 'Long lines' mentioned 5 times\",
    \"action\": \"Detailed 3-4 sentence action plan. Include: WHO (specific department/role), WHAT (concrete steps), HOW (implementation method), WHEN (timeline). Example: 'The Registrar Office should implement a digital queuing system within 2 weeks. Staff should be trained on the new system by the IT department. Students can book appointments online to reduce wait times. Monitor wait times weekly for the first month.'\",
    \"impact\": \"Specific measurable outcomes. Include metrics: 'Expected 40% reduction in wait times, 25% decrease in complaint volume, and improved student satisfaction scores from 3.2 to 4.0 within 60 days.'\"
  }
]

IMPORTANT GUIDELINES:
- short-term = immediate to 2 weeks
- medium-term = 2 weeks to 2 months  
- long-term = 2+ months
- For POSITIVE feedback patterns, recommend how to SUSTAIN and SCALE the success (e.g., 'Document best practices from X department and share across all offices')
- Be SPECIFIC with department names, timelines, and metrics
- Base ALL recommendations on the actual feedback data provided
- If feedback mentions specific pain points (slow, confusing, unfriendly), address them directly
- Recommendations should be realistic for a university budget and bureaucracy

Think step by step:
1. What is the root cause based on the feedback?
2. Which department needs to act?
3. What specific change will address this?
4. How will we measure success?";

    $raw = $this->callModel($prompt, 30, ['num_predict' => 600, 'temperature' => 0.3]);
    $result = $this->parseJsonResponse($raw);
    
    if (empty($result)) {
        return $this->generateEnhancedFallbackRecommendations($topClusters, $texts);
    }
    
    $result = $this->filterRecommendationsByRelevance($result, $topClusters);
    
    return $result;
}

private function filterRecommendationsByRelevance(array $recommendations, array $clusters): array
{
    $filtered = [];
    $clusterIssues = array_column($clusters, 'issue');
    
    foreach ($recommendations as $rec) {
        $title = strtolower($rec['title'] ?? '');
        $evidence = strtolower($rec['evidence'] ?? '');
        $action = strtolower($rec['action'] ?? '');
        
        $isRelevant = false;
        foreach ($clusterIssues as $issue) {
            if (str_contains($title, strtolower($issue)) || 
                str_contains($evidence, strtolower($issue)) || 
                str_contains($action, strtolower($issue))) {
                $isRelevant = true;
                break;
            }
        }
        
        $isVague = preg_match('/address|improve|enhance|review processes/', $title) && strlen($title) < 30;
        
        if ($isRelevant && !$isVague) {
            $filtered[] = $rec;
        }
    }
    
    return !empty($filtered) ? $filtered : $recommendations;
}

private function generateEnhancedFallbackRecommendations(array $clusters, array $texts): array
{
    $recommendations = [];
    $feedbackSamples = array_slice($texts, 0, 10);
    
    foreach (array_slice($clusters, 0, 4) as $c) {
        $issue = $c['issue'];
        $count = $c['count'];
        $dept = $c['department'];
        
        $relevantSamples = array_filter($feedbackSamples, function($sample) use ($issue) {
            return stripos($sample, $issue) !== false;
        });
        
        $evidenceText = !empty($relevantSamples) 
            ? '"' . implode('", "', array_slice($relevantSamples, 0, 2)) . '"'
            : "Mentioned {$count} times in feedback";
        
        $isNegative = stripos($issue, 'slow') !== false || 
                     stripos($issue, 'bad') !== false || 
                     stripos($issue, 'poor') !== false ||
                     stripos($issue, 'problem') !== false ||
                     stripos($issue, 'issue') !== false ||
                     stripos($issue, 'complaint') !== false;
        
        $isPositive = stripos($issue, 'good') !== false || 
                     stripos($issue, 'great') !== false || 
                     stripos($issue, 'excellent') !== false ||
                     stripos($issue, 'amazing') !== false ||
                     stripos($issue, 'nice') !== false ||
                     stripos($issue, 'helpful') !== false ||
                     stripos($issue, 'friendly') !== false;
        
        if ($isPositive) {
            $term = $count >= 5 ? 'short-term' : 'medium-term';
            $recommendations[] = [
                'title' => "Scale and Sustain: " . ucwords(str_replace(['good', 'great', 'excellent', 'amazing', 'nice', 'very'], '', $issue)) . "Excellence at {$dept}",
                'term' => $term,
                'evidence' => "Positive feedback received {$count} times. Sample: {$evidenceText}",
                'action' => "Document the specific practices and behaviors that led to this positive feedback from {$dept}. Create a brief best-practices guide and share with similar departments. Recognize and reward the staff members involved to reinforce these positive behaviors. Consider having {$dept} staff mentor other departments during monthly meetings.",
                'impact' => "Sustained high satisfaction ratings, increased staff morale, and potential 15-20% improvement in similar departments' ratings within 60 days of implementing best practices."
            ];
        } else {
            $term = $count >= 5 ? 'short-term' : ($count >= 3 ? 'medium-term' : 'long-term');
            
            if (stripos($issue, 'slow') !== false || stripos($issue, 'wait') !== false || stripos($issue, 'line') !== false) {
                $recommendations[] = [
                    'title' => "Reduce Wait Times and Streamline Service at {$dept}",
                    'term' => $term,
                    'evidence' => "{$count} complaints about slow service/delays. Sample: {$evidenceText}",
                    'action' => "Conduct a time-motion study at {$dept} to identify bottlenecks. Implement a ticketing or queuing system within 2 weeks. Cross-train staff to handle peak periods. Consider adding self-service options or online pre-processing for common transactions. Set a target of maximum 10-minute wait time and monitor daily for the first month.",
                    'impact' => "Expected 30-50% reduction in wait times, 40% decrease in 'slow service' complaints within 30 days, and improved student/staff satisfaction scores by at least 1 point on 5-point scale."
                ];
            } elseif (stripos($issue, 'staff') !== false || stripos($issue, 'rude') !== false || stripos($issue, 'unfriendly') !== false) {
                $recommendations[] = [
                    'title' => "Enhance Customer Service Training at {$dept}",
                    'term' => $term,
                    'evidence' => "{$count} mentions of staff attitude/behavior issues. Sample: {$evidenceText}",
                    'action' => "Schedule mandatory customer service refresher training for all {$dept} staff within 30 days. Implement a mystery shopper program to evaluate service quality monthly. Create a recognition program for positive customer feedback. Assign a service quality champion within the department to monitor and coach staff daily.",
                    'impact' => "Measurable improvement in staff courtesy ratings from current levels to 4.0+ within 60 days. Reduction in staff-related complaints by 50% and increase in positive feedback mentions by 30%."
                ];
            } elseif (stripos($issue, 'system') !== false || stripos($issue, 'online') !== false || stripos($issue, 'portal') !== false) {
                $recommendations[] = [
                    'title' => "Optimize Digital Systems and User Experience at {$dept}",
                    'term' => $term,
                    'evidence' => "{$count} reports of system/portal issues. Sample: {$evidenceText}",
                    'action' => "Conduct a usability audit of the current system with actual users. Prioritize top 3 pain points for immediate fix within 2 weeks. Create simple video tutorials or FAQ guides for common tasks. Establish a feedback button on the portal for real-time issue reporting. Schedule bi-weekly check-ins with IT to address recurring problems.",
                    'impact' => "40% reduction in system-related support tickets, improved task completion rates, and increased user satisfaction scores from current levels to 4.2+ within 45 days."
                ];
            } else {
                $recommendations[] = [
                    'title' => "Address {$issue} Issue at {$dept}",
                    'term' => $term,
                    'evidence' => "Mentioned {$count} times. Sample feedback: {$evidenceText}",
                    'action' => "Schedule a meeting with {$dept} leadership to review specific feedback examples. Conduct root cause analysis using the 5 Whys method. Develop a 30-day action plan with clear milestones. Assign an owner for each action item and schedule weekly progress reviews. Communicate planned improvements to stakeholders.",
                    'impact' => "Targeted improvement in the specific issue area, with goal of reducing related complaints by 50% within 60 days. Improved overall satisfaction scores for {$dept} by at least 0.5 points."
                ];
            }
        }
    }
    
    return $recommendations;
}
    private function parseJsonArray($raw): array
    {
        return $this->parseJsonResponse($raw);
    }

    private function parseJsonResponse(?string $raw): array
    {
        if (!$raw) return [];
        
        $raw = str_replace(['```json', '```', "\r\n", "\r"], "\n", $raw);
        $raw = trim($raw);
        
        preg_match('/(\[[\s\S]*\]|\{[\s\S]*\})/', $raw, $matches);
        $jsonString = $matches[0] ?? $raw;
        
        $jsonString = preg_replace('/,\s*([\]}])/m', '$1', $jsonString);
        $jsonString = preg_replace('/\}\s*\{/', '},{', $jsonString);
        
        $decoded = json_decode($jsonString, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $jsonString = $this->repairJson($jsonString);
            $decoded = json_decode($jsonString, true);
        }
        
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            Log::error('AI JSON parse failed', ['raw' => substr($raw, 0, 200)]);
            return [];
        }
        
        if (isset($decoded['title']) || isset($decoded['issue'])) {
            $decoded = [$decoded];
        }
        
        return array_values(array_filter($decoded, fn($item) => is_array($item) && (!empty($item['title']) || !empty($item['issue']))));
    }

    private function repairJson(string $json): string
    {
        $json = preg_replace('/,\s*([}\]])/', '$1', $json);
        
        $openBrackets = substr_count($json, '[');
        $closeBrackets = substr_count($json, ']');
        if ($openBrackets > $closeBrackets) {
            $json .= str_repeat(']', $openBrackets - $closeBrackets);
        }
        
        $openBraces = substr_count($json, '{');
        $closeBraces = substr_count($json, '}');
        if ($openBraces > $closeBraces) {
            $json .= str_repeat('}', $openBraces - $closeBraces);
        }
        
        return $json;
    }

    private function generateFallbackInsights(array $clusters): array
    {
        $insights = [];
        foreach (array_slice($clusters, 0, 4) as $c) {
            $priority = $c['count'] >= 5 ? 'critical' : ($c['count'] >= 3 ? 'warning' : 'positive');
            $insights[] = [
                'title' => ucfirst($c['issue']) . ' Reported',
                'description' => "This issue was mentioned {$c['count']} times in recent feedback submissions. " . 
                    ($c['count'] >= 4 ? 'It requires immediate attention from ' . $c['department'] . '.' : 'It should be monitored by ' . $c['department'] . '.'),
                'priority' => $priority,
                'department' => $c['department']
            ];
        }
        return $insights;
    }

    private function generateFallbackRecommendations(array $clusters): array
    {
        $recommendations = [];
        foreach (array_slice($clusters, 0, 3) as $c) {
            $term = $c['count'] >= 5 ? 'short-term' : ($c['count'] >= 3 ? 'medium-term' : 'long-term');
            $recommendations[] = [
                'title' => "Address " . ucfirst($c['issue']),
                'term' => $term,
                'evidence' => "Mentioned {$c['count']} times in recent feedback",
                'action' => "Review processes related to {$c['issue']} with {$c['department']} and implement necessary improvements.",
                'impact' => "Reduced complaints and improved satisfaction scores for {$c['department']}"
            ];
        }
        return $recommendations;
    }
}