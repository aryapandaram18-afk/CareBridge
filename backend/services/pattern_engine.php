<?php
/**
 * Pattern Engine Service
 * Analyzes historical symptom data to detect patterns and trends
 * Provides insights based on past 3-7 days of data
 */

require_once __DIR__ . '/../config/db.php';

class PatternEngine {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * Analyze patterns from past 7 days of symptom data
     * @param int $days - Number of days to analyze (default: 7)
     * @return array - Pattern analysis results
     */
    public function analyzePatterns($days = 7) {
        $history = $this->getSymptomHistory($days);
        $patterns = [];
        
        if (empty($history)) {
            return [
                'patterns' => [],
                'alerts' => ['No historical data available for pattern analysis'],
                'trends' => []
            ];
        }
        
        // Analyze repeated symptoms
        $repeatedSymptoms = $this->detectRepeatedSymptoms($history);
        if (!empty($repeatedSymptoms)) {
            $patterns['repeated_symptoms'] = $repeatedSymptoms;
        }
        
        // Analyze worsening trends
        $worseningTrends = $this->detectWorseningTrends($history);
        if (!empty($worseningTrends)) {
            $patterns['worsening_trends'] = $worseningTrends;
        }
        
        // Analyze risk level trends
        $riskTrends = $this->analyzeRiskTrends($history);
        if (!empty($riskTrends)) {
            $patterns['risk_trends'] = $riskTrends;
        }
        
        // Generate alerts based on patterns
        $alerts = $this->generatePatternAlerts($patterns);
        
        return [
            'patterns' => $patterns,
            'alerts' => $alerts,
            'trends' => $this->calculateTrendMetrics($history)
        ];
    }
    
    /**
     * Get symptom history for specified number of days
     */
    private function getSymptomHistory($days) {
        $sql = "SELECT * FROM symptom_logs 
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
     * Detect symptoms that have been repeated over multiple days
     */
    private function detectRepeatedSymptoms($history) {
        $repeatedSymptoms = [];
        $symptomCounts = [];
        
        // Count occurrences of each symptom
        foreach ($history as $entry) {
            $symptoms = [
                'headache' => $entry['headache'],
                'swelling' => $entry['swelling'],
                'dizziness' => $entry['dizziness'],
                'fatigue' => $entry['fatigue'],
                'pain' => $entry['pain']
            ];
            
            foreach ($symptoms as $symptomName => $severity) {
                if ($severity !== 'none' && $severity !== 'normal') {
                    $key = $symptomName . '_' . $severity;
                    if (!isset($symptomCounts[$key])) {
                        $symptomCounts[$key] = [
                            'symptom' => $symptomName,
                            'severity' => $severity,
                            'count' => 0,
                            'days' => []
                        ];
                    }
                    $symptomCounts[$key]['count']++;
                    $symptomCounts[$key]['days'][] = date('Y-m-d', strtotime($entry['created_at']));
                }
            }
        }
        
        // Find symptoms repeated 3+ days
        foreach ($symptomCounts as $key => $data) {
            if ($data['count'] >= 3) {
                $repeatedSymptoms[] = [
                    'symptom' => $data['symptom'],
                    'severity' => $data['severity'],
                    'days_count' => $data['count'],
                    'days' => array_unique($data['days']),
                    'alert_level' => $data['count'] >= 5 ? 'high' : 'medium'
                ];
            }
        }
        
        return $repeatedSymptoms;
    }
    
    /**
     * Detect worsening trends in symptoms
     */
    private function detectWorseningTrends($history) {
        $worseningTrends = [];
        
        if (count($history) < 3) {
            return $worseningTrends;
        }
        
        $severityLevels = [
            'none' => 0,
            'normal' => 0,
            'mild' => 1,
            'moderate' => 2,
            'severe' => 3,
            'reduced' => 1,
            'frequent' => 2,
            'increased' => 2,
            'excessive' => 3,
            'happy' => 0,
            'neutral' => 1,
            'stressed' => 2,
            'anxious' => 3
        ];
        
        $symptomsToCheck = ['headache', 'swelling', 'dizziness', 'fatigue', 'pain'];
        
        foreach ($symptomsToCheck as $symptom) {
            $recentValues = [];
            
            // Get last 5 entries for this symptom
            $recentEntries = array_slice($history, -5);
            foreach ($recentEntries as $entry) {
                if (isset($entry[$symptom]) && isset($severityLevels[$entry[$symptom]])) {
                    $recentValues[] = $severityLevels[$entry[$symptom]];
                }
            }
            
            // Check if trend is worsening
            if (count($recentValues) >= 3) {
                $trend = $this->calculateTrend($recentValues);
                if ($trend > 0.5) { // Worsening trend
                    $worseningTrends[] = [
                        'symptom' => $symptom,
                        'trend' => 'worsening',
                        'trend_score' => $trend,
                        'recent_values' => $recentValues,
                        'severity' => $trend > 1.0 ? 'high' : 'medium'
                    ];
                }
            }
        }
        
        return $worseningTrends;
    }
    
    /**
     * Analyze risk level trends over time
     */
    private function analyzeRiskTrends($history) {
        $riskTrends = [];
        
        if (count($history) < 3) {
            return $riskTrends;
        }
        
        $riskLevels = ['low' => 1, 'medium' => 2, 'high' => 3, 'critical' => 4];
        $recentRisks = [];
        
        foreach ($history as $entry) {
            if (isset($entry['risk_level']) && isset($riskLevels[$entry['risk_level']])) {
                $recentRisks[] = $riskLevels[$entry['risk_level']];
            }
        }
        
        if (count($recentRisks) >= 3) {
            $trend = $this->calculateTrend($recentRisks);
            
            if ($trend > 0.3) {
                $riskTrends[] = [
                    'trend' => 'increasing_risk',
                    'score' => $trend,
                    'recent_levels' => $recentRisks
                ];
            } elseif ($trend < -0.3) {
                $riskTrends[] = [
                    'trend' => 'decreasing_risk',
                    'score' => $trend,
                    'recent_levels' => $recentRisks
                ];
            }
        }
        
        return $riskTrends;
    }
    
    /**
     * Calculate trend using linear regression
     */
    private function calculateTrend($values) {
        if (count($values) < 2) {
            return 0;
        }
        
        $n = count($values);
        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumX2 = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $sumX += $i;
            $sumY += $values[$i];
            $sumXY += $i * $values[$i];
            $sumX2 += $i * $i;
        }
        
        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        
        return $slope;
    }
    
    /**
     * Generate alerts based on detected patterns
     */
    private function generatePatternAlerts($patterns) {
        $alerts = [];
        
        // Alerts for repeated symptoms
        if (isset($patterns['repeated_symptoms'])) {
            foreach ($patterns['repeated_symptoms'] as $symptom) {
                if ($symptom['alert_level'] === 'high') {
                    $alerts[] = "Persistent {$symptom['symptom']} detected for {$symptom['days_count']} days - Consult doctor immediately";
                } else {
                    $alerts[] = "Repeated {$symptom['symptom']} for {$symptom['days_count']} days - Monitor closely";
                }
            }
        }
        
        // Alerts for worsening trends
        if (isset($patterns['worsening_trends'])) {
            foreach ($patterns['worsening_trends'] as $trend) {
                if ($trend['severity'] === 'high') {
                    $alerts[] = "Rapidly worsening {$trend['symptom']} - Seek medical attention";
                } else {
                    $alerts[] = "{$trend['symptom']} is worsening - Schedule doctor appointment";
                }
            }
        }
        
        // Alerts for risk trends
        if (isset($patterns['risk_trends'])) {
            foreach ($patterns['risk_trends'] as $riskTrend) {
                if ($riskTrend['trend'] === 'increasing_risk') {
                    $alerts[] = "Risk level is increasing over time - Close monitoring required";
                }
            }
        }
        
        return $alerts;
    }
    
    /**
     * Calculate general trend metrics
     */
    private function calculateTrendMetrics($history) {
        $metrics = [];
        
        if (empty($history)) {
            return $metrics;
        }
        
        // Total entries analyzed
        $metrics['total_entries'] = count($history);
        
        // Date range
        $firstDate = $history[0]['created_at'];
        $lastDate = $history[count($history) - 1]['created_at'];
        $metrics['date_range'] = [
            'start' => date('Y-m-d', strtotime($firstDate)),
            'end' => date('Y-m-d', strtotime($lastDate))
        ];
        
        // Most common risk level
        $riskCounts = [];
        foreach ($history as $entry) {
            $risk = $entry['risk_level'];
            $riskCounts[$risk] = ($riskCounts[$risk] ?? 0) + 1;
        }
        $metrics['most_common_risk'] = array_keys($riskCounts, max($riskCounts))[0] ?? 'unknown';
        
        // Average risk score
        $riskScores = ['low' => 1, 'medium' => 2, 'high' => 3, 'critical' => 4];
        $totalScore = 0;
        $count = 0;
        foreach ($history as $entry) {
            if (isset($riskScores[$entry['risk_level']])) {
                $totalScore += $riskScores[$entry['risk_level']];
                $count++;
            }
        }
        $metrics['average_risk_score'] = $count > 0 ? round($totalScore / $count, 2) : 0;
        
        return $metrics;
    }
}
?>
