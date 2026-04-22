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
    
    private function isServiceAvailable(): bool
    {
        try {
            $response = Http::timeout(2)->get('http://103.107.82.222:11434/api/tags');
            return $response->successful();
        } catch (\Exception $e) {
            Log::warning('Ollama service unavailable: ' . $e->getMessage());
            return false;
        }
    }
    
    private function callModel(string $prompt, int $timeout = 5, array $options = []): ?string
    {
        if (!$this->isServiceAvailable()) {
            Log::info('Ollama service not available, using fallback analysis');
            return null;
        }
        
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
            Log::error('Ollama error: ' . $e->getMessage());
            return null;
        }
    }

    public function smartCluster(array $texts): array
    {
        if (empty($texts)) return [];

        $cacheKey = 'cluster_' . md5(implode('|', $texts));
        
        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($texts) {
            $map = [];
            $stopWords = ['the','and','you','your','with','for','this','that','are','was','were','has','have','from','they','what','when','where','just','like','very','really'];
            
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
            
            if (empty($result) && !empty($texts)) {
                $result = [[
                    'issue' => 'Feedback Analysis',
                    'title' => 'Processing Feedback',
                    'count' => count($texts),
                    'priority' => 'medium',
                    'department' => 'Multiple Departments'
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
        
        if (empty($clusters)) {
            return [[
                'title' => 'Feedback Analysis',
                'description' => 'Analyzing ' . count($texts) . ' feedback submissions. More data will provide better insights.',
                'priority' => 'positive',
                'department' => 'Multiple Departments'
            ]];
        }
        
        $insights = [];
        foreach (array_slice($clusters, 0, 4) as $c) {
            $insights[] = [
                'title' => ucfirst($c['issue']) . ' Reported',
                'description' => "This issue was mentioned {$c['count']} times in recent feedback. " . 
                    ($c['count'] >= 4 ? 'It requires attention from ' . $c['department'] . '.' : 'Continue monitoring.'),
                'priority' => $c['count'] >= 5 ? 'critical' : ($c['count'] >= 3 ? 'warning' : 'positive'),
                'department' => $c['department']
            ];
        }
        
        return $insights;
    }

    public function generateRecommendations(array $texts): array
    {
        if (empty($texts)) return [];
        
        $clusters = $this->smartCluster($texts);
        
        if (empty($clusters)) {
            return [[
                'title' => 'Gather More Feedback',
                'term' => 'short-term',
                'evidence' => 'Initial feedback collection phase',
                'action' => 'Continue collecting feedback to identify clear patterns and trends.',
                'impact' => 'Better data-driven decisions for service improvement'
            ]];
        }
        
        $recommendations = [];
        foreach (array_slice($clusters, 0, 3) as $c) {
            $term = $c['count'] >= 5 ? 'short-term' : ($c['count'] >= 3 ? 'medium-term' : 'long-term');
            $recommendations[] = [
                'title' => "Address " . ucfirst($c['issue']),
                'term' => $term,
                'evidence' => "Mentioned {$c['count']} times in feedback",
                'action' => "Review and improve processes related to {$c['issue']} at {$c['department']}.",
                'impact' => "Improved satisfaction and reduced complaints at {$c['department']}"
            ];
        }
        
        return $recommendations;
    }
}