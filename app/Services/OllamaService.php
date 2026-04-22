<?php
// app/Services/OllamaService.php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class OllamaService
{
    private const MODEL = 'llama3:8b';
    private const BASE_URL = 'http://103.107.82.222:11434/api/generate';
    
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
            
            foreach ($texts as $text) {
                $clean = strtolower(trim($text));
                $clean = preg_replace('/[^\w\s]/', '', $clean);
                $clean = preg_replace('/\s+/', ' ', $clean);
                
                if (strlen($clean) < 3) continue;
                
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
            foreach ($map as $issue => $count) {
                if ($count < 2) continue;
                $priority = $count >= 5 ? 'high' : ($count >= 3 ? 'medium' : 'low');
                $result[] = [
                    'issue' => $issue,
                    'title' => ucfirst($issue),
                    'count' => $count,
                    'priority' => $priority,
                    'department' => $this->detectDepartment($issue)
                ];
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
        
        $input = "";
        foreach (array_slice($clusters, 0, 6) as $c) {
            $input .= "- \"{$c['issue']}\" ({$c['count']} times, {$c['department']})\n";
        }

        $prompt = "You are a senior university feedback analyst. Analyze these recurring feedback themes and provide meaningful insights.\n\nFeedback themes with frequency:\n{$input}\n\nGenerate 3-5 detailed insights. Return ONLY valid JSON array in this exact format:\n[\n  {\n    \"title\": \"Clear, specific headline describing the issue or pattern\",\n    \"description\": \"Detailed 3-4 sentence analysis. Include specific numbers from the data. Explain why this matters. Mention which departments are affected. Be specific and actionable.\",\n    \"priority\": \"critical|warning|positive\",\n    \"department\": \"Most relevant department name\"\n  }\n]\n\nBe specific, data-driven, and professional. Critical = 5+ mentions or serious issues.";

        $raw = $this->callModel($prompt, 20, ['num_predict' => 350]);
        $result = $this->parseJsonResponse($raw);
        
        if (empty($result)) {
            return $this->generateFallbackInsights($clusters);
        }
        
        return $result;
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

        $input = "";
        foreach ($topClusters as $c) {
            $input .= "- {$c['issue']} (mentioned {$c['count']} times, {$c['priority']} priority)\n";
        }

        $prompt = "You are a university systems and process improvement expert. Based on these feedback issues, provide actionable recommendations.\n\nTop issues identified:\n{$input}\n\nGenerate 3-5 specific, actionable recommendations. Return ONLY valid JSON array in this exact format:\n[\n  {\n    \"title\": \"Clear, action-oriented title\",\n    \"term\": \"short-term|medium-term|long-term\",\n    \"evidence\": \"Specific quote or summary of feedback that supports this recommendation\",\n    \"action\": \"Detailed 2-3 sentence action plan. Be specific about what should be done, who should do it, and how. Include concrete steps.\",\n    \"impact\": \"Specific measurable outcome expected. Include metrics or qualitative improvements anticipated.\"\n  }\n]\n\nGuidelines: short-term = days to 2 weeks, medium-term = 2 weeks-2 months, long-term = 2+ months. Realistic for university setting.";

        $raw = $this->callModel($prompt, 20, ['num_predict' => 400]);
        $result = $this->parseJsonResponse($raw);
        
        if (empty($result)) {
            return $this->generateFallbackRecommendations($topClusters);
        }
        
        return $result;
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