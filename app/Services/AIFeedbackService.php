<?php

namespace App\Services;

class AIFeedbackService
{
    public function analyze($text)
    {
        $text = strtolower($text);

        // Keywords detection
        $keywords = [
            'slow' => 'System Performance Issue',
            'delay' => 'Processing Delay',
            'rude' => 'Staff Behavior Issue',
            'clean' => 'Facility Condition',
            'error' => 'System Error',
            'good' => 'Positive Experience',
            'excellent' => 'Excellent Service'
        ];

        $found = [];

        foreach ($keywords as $key => $label) {
            if (str_contains($text, $key)) {
                $found[] = $label;
            }
        }

        // Sentiment Analysis (simple AI logic)
        if (str_contains($text, 'good') || str_contains($text, 'excellent') || str_contains($text, 'great')) {
            $sentiment = 'Positive';
        } elseif (str_contains($text, 'bad') || str_contains($text, 'slow') || str_contains($text, 'rude') || str_contains($text, 'delay')) {
            $sentiment = 'Negative';
        } else {
            $sentiment = 'Neutral';
        }

        // Recommendation engine
        $recommendation = count($found) > 0
            ? "Focus on: " . implode(', ', $found)
            : "No critical issue detected";

        return [
            'keywords' => $found,
            'sentiment' => $sentiment,
            'recommendation' => $recommendation
        ];
    }
}