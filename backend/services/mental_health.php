<?php
/**
 * Mental Health Service
 * Tracks mood and provides mental health recommendations
 * Detects stress patterns and provides support suggestions
 */

require_once __DIR__ . '/../config/db.php';

class MentalHealth {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * Log mood entry
     * @param array $moodData - Mood data including mood, stress_level, notes
     * @return array - Result with success status and message
     */
    public function logMood($moodData) {
        try {
            $sql = "INSERT INTO mood_logs (mood, stress_level, notes) VALUES (?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            
            $mood = $moodData['mood'] ?? 'neutral';
            $stressLevel = $moodData['stress_level'] ?? 5;
            $notes = $moodData['notes'] ?? '';
            
            $stmt->bind_param('sis', $mood, $stressLevel, $notes);
            $result = $stmt->execute();
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Mood logged successfully',
                    'id' => $this->db->getLastInsertId()
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to log mood'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error logging mood: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Analyze mental health patterns
     * @param int $days - Number of days to analyze (default: 7)
     * @return array - Mental health analysis with recommendations
     */
    public function analyzeMentalHealth($days = 7) {
        $moodHistory = $this->getMoodHistory($days);
        
        if (empty($moodHistory)) {
            return [
                'patterns' => [],
                'recommendations' => ['Start tracking your mood regularly for better insights'],
                'stress_level' => 'unknown',
                'trend' => 'no_data'
            ];
        }
        
        $patterns = $this->detectMentalHealthPatterns($moodHistory);
        $recommendations = $this->generateMentalHealthRecommendations($patterns, $moodHistory);
        $stressLevel = $this->calculateOverallStressLevel($moodHistory);
        $trend = $this->calculateMoodTrend($moodHistory);
        
        return [
            'patterns' => $patterns,
            'recommendations' => $recommendations,
            'stress_level' => $stressLevel,
            'trend' => $trend,
            'mood_history' => $this->formatMoodHistory($moodHistory)
        ];
    }
    
    /**
     * Get mood history for specified number of days
     */
    private function getMoodHistory($days) {
        $sql = "SELECT * FROM mood_logs 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY) 
                ORDER BY created_at ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $days);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $history = [];
        while ($row = $result->fetch_assoc()) {
            $history[] = $row;
        }
        
        return $history;
    }
    
    /**
     * Detect mental health patterns from mood history
     */
    private function detectMentalHealthPatterns($history) {
        $patterns = [];
        
        // Count mood occurrences
        $moodCounts = [];
        $stressSum = 0;
        $stressCount = 0;
        
        foreach ($history as $entry) {
            $mood = $entry['mood'];
            $moodCounts[$mood] = ($moodCounts[$mood] ?? 0) + 1;
            
            if (isset($entry['stress_level'])) {
                $stressSum += $entry['stress_level'];
                $stressCount++;
            }
        }
        
        // Most frequent mood
        if (!empty($moodCounts)) {
            $patterns['most_common_mood'] = array_keys($moodCounts, max($moodCounts))[0];
            $patterns['mood_distribution'] = $moodCounts;
        }
        
        // Average stress level
        if ($stressCount > 0) {
            $patterns['average_stress'] = round($stressSum / $stressCount, 1);
        }
        
        // Check for negative mood patterns
        $negativeMoods = ['stressed', 'anxious', 'depressed'];
        $negativeCount = 0;
        foreach ($moodCounts as $mood => $count) {
            if (in_array($mood, $negativeMoods)) {
                $negativeCount += $count;
            }
        }
        
        $totalEntries = count($history);
        if ($totalEntries > 0) {
            $negativePercentage = ($negativeCount / $totalEntries) * 100;
            if ($negativePercentage > 60) {
                $patterns['negative_mood_pattern'] = 'high';
            } elseif ($negativePercentage > 30) {
                $patterns['negative_mood_pattern'] = 'moderate';
            } else {
                $patterns['negative_mood_pattern'] = 'low';
            }
        }
        
        // Check for high stress patterns
        $highStressDays = 0;
        foreach ($history as $entry) {
            if (isset($entry['stress_level']) && $entry['stress_level'] >= 7) {
                $highStressDays++;
            }
        }
        
        if ($totalEntries > 0) {
            $highStressPercentage = ($highStressDays / $totalEntries) * 100;
            if ($highStressPercentage > 40) {
                $patterns['high_stress_pattern'] = true;
            }
        }
        
        return $patterns;
    }
    
    /**
     * Generate mental health recommendations based on patterns
     */
    private function generateMentalHealthRecommendations($patterns, $history) {
        $recommendations = [];
        
        // Recommendations based on negative mood patterns
        if (isset($patterns['negative_mood_pattern'])) {
            if ($patterns['negative_mood_pattern'] === 'high') {
                $recommendations[] = "You may be experiencing significant stress - consider talking to a mental health professional";
                $recommendations[] = "Practice daily relaxation techniques like deep breathing or meditation";
                $recommendations[] = "Reach out to your support system - don't go through this alone";
            } elseif ($patterns['negative_mood_pattern'] === 'moderate') {
                $recommendations[] = "You may be stressed - consider rest and support";
                $recommendations[] = "Try gentle exercise and outdoor activities";
                $recommendations[] = "Consider journaling to express your feelings";
            }
        }
        
        // Recommendations based on stress levels
        if (isset($patterns['high_stress_pattern']) && $patterns['high_stress_pattern']) {
            $recommendations[] = "High stress levels detected - prioritize self-care";
            $recommendations[] = "Consider reducing commitments and getting more rest";
            $recommendations[] = "Pregnancy can be emotionally challenging - be kind to yourself";
        }
        
        // Recommendations based on most common mood
        if (isset($patterns['most_common_mood'])) {
            switch ($patterns['most_common_mood']) {
                case 'stressed':
                    $recommendations[] = "Stress is your most common mood - explore stress management techniques";
                    break;
                case 'anxious':
                    $recommendations[] = "Anxiety is frequent - consider mindfulness practices";
                    break;
                case 'happy':
                    $recommendations[] = "You're generally happy - maintain positive routines";
                    break;
            }
        }
        
        // General recommendations
        if (empty($recommendations)) {
            $recommendations[] = "Continue monitoring your emotional wellbeing";
            $recommendations[] = "Maintain a healthy work-life balance";
        }
        
        return $recommendations;
    }
    
    /**
     * Calculate overall stress level
     */
    private function calculateOverallStressLevel($history) {
        if (empty($history)) {
            return 'unknown';
        }
        
        $stressSum = 0;
        $stressCount = 0;
        
        foreach ($history as $entry) {
            if (isset($entry['stress_level'])) {
                $stressSum += $entry['stress_level'];
                $stressCount++;
            }
        }
        
        if ($stressCount === 0) {
            return 'unknown';
        }
        
        $averageStress = $stressSum / $stressCount;
        
        if ($averageStress >= 8) {
            return 'very_high';
        } elseif ($averageStress >= 6) {
            return 'high';
        } elseif ($averageStress >= 4) {
            return 'moderate';
        } else {
            return 'low';
        }
    }
    
    /**
     * Calculate mood trend
     */
    private function calculateMoodTrend($history) {
        if (count($history) < 3) {
            return 'insufficient_data';
        }
        
        $moodScores = [
            'happy' => 4,
            'neutral' => 3,
            'stressed' => 2,
            'anxious' => 1,
            'depressed' => 0
        ];
        
        $scores = [];
        foreach ($history as $entry) {
            if (isset($moodScores[$entry['mood']])) {
                $scores[] = $moodScores[$entry['mood']];
            }
        }
        
        if (count($scores) < 3) {
            return 'insufficient_data';
        }
        
        // Simple trend calculation
        $firstHalf = array_slice($scores, 0, floor(count($scores) / 2));
        $secondHalf = array_slice($scores, floor(count($scores) / 2));
        
        $firstAvg = array_sum($firstHalf) / count($firstHalf);
        $secondAvg = array_sum($secondHalf) / count($secondHalf);
        
        $difference = $secondAvg - $firstAvg;
        
        if ($difference > 0.5) {
            return 'improving';
        } elseif ($difference < -0.5) {
            return 'declining';
        } else {
            return 'stable';
        }
    }
    
    /**
     * Format mood history for API response
     */
    private function formatMoodHistory($history) {
        $formatted = [];
        
        foreach ($history as $entry) {
            $formatted[] = [
                'date' => date('Y-m-d', strtotime($entry['created_at'])),
                'mood' => $entry['mood'],
                'stress_level' => $entry['stress_level'],
                'notes' => $entry['notes']
            ];
        }
        
        return $formatted;
    }
    
    /**
     * Get mood statistics for dashboard
     */
    public function getMoodStatistics($days = 30) {
        $history = $this->getMoodHistory($days);
        
        if (empty($history)) {
            return [
                'total_entries' => 0,
                'average_stress' => 0,
                'most_common_mood' => 'none',
                'stress_trend' => 'no_data'
            ];
        }
        
        $patterns = $this->detectMentalHealthPatterns($history);
        $trend = $this->calculateMoodTrend($history);
        
        return [
            'total_entries' => count($history),
            'average_stress' => $patterns['average_stress'] ?? 0,
            'most_common_mood' => $patterns['most_common_mood'] ?? 'none',
            'stress_trend' => $trend,
            'mood_distribution' => $patterns['mood_distribution'] ?? []
        ];
    }
}
?>
