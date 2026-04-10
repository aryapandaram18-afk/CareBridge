<?php
/**
 * Risk Engine Service
 * Intelligent health analysis for pregnancy monitoring
 * Analyzes symptom combinations to detect health risks
 */

class RiskEngine {
    
    /**
     * Analyze symptoms and determine risk level
     * @param array $symptoms - User symptoms data
     * @return array - Analysis results with risk level and recommendations
     */
    public function analyzeSymptoms($symptoms) {
        $detectedConditions = [];
        $recommendations = [];
        $riskLevel = 'low';
        
        // Convert to lowercase for consistent comparison
        $normalizedSymptoms = array_map('strtolower', $symptoms);
        
        // Check for emergency conditions first
        $emergencyCheck = $this->checkEmergencyConditions($normalizedSymptoms);
        if ($emergencyCheck['is_emergency']) {
            return [
                'risk_level' => 'critical',
                'detected_conditions' => $emergencyCheck['conditions'],
                'recommendations' => $emergencyCheck['recommendations'],
                'pattern_alerts' => []
            ];
        }
        
        // Check for Preeclampsia Risk
        $preeclampsia = $this->checkPreeclampsia($normalizedSymptoms);
        if ($preeclampsia['detected']) {
            $detectedConditions = array_merge($detectedConditions, $preeclampsia['conditions']);
            $recommendations = array_merge($recommendations, $preeclampsia['recommendations']);
            $riskLevel = $this->updateRiskLevel($riskLevel, 'high');
        }
        
        // Check for Anemia Risk
        $anemia = $this->checkAnemia($normalizedSymptoms);
        if ($anemia['detected']) {
            $detectedConditions = array_merge($detectedConditions, $anemia['conditions']);
            $recommendations = array_merge($recommendations, $anemia['recommendations']);
            $riskLevel = $this->updateRiskLevel($riskLevel, 'medium');
        }
        
        // Check for Gestational Diabetes
        $diabetes = $this->checkGestationalDiabetes($normalizedSymptoms);
        if ($diabetes['detected']) {
            $detectedConditions = array_merge($detectedConditions, $diabetes['conditions']);
            $recommendations = array_merge($recommendations, $diabetes['recommendations']);
            $riskLevel = $this->updateRiskLevel($riskLevel, 'medium');
        }
        
        // Check for Dehydration Risk
        $dehydration = $this->checkDehydration($normalizedSymptoms);
        if ($dehydration['detected']) {
            $detectedConditions = array_merge($detectedConditions, $dehydration['conditions']);
            $recommendations = array_merge($recommendations, $dehydration['recommendations']);
            $riskLevel = $this->updateRiskLevel($riskLevel, 'medium');
        }
        
        // Check for Mental Health Concerns
        $mentalHealth = $this->checkMentalHealth($normalizedSymptoms);
        if ($mentalHealth['detected']) {
            $detectedConditions = array_merge($detectedConditions, $mentalHealth['conditions']);
            $recommendations = array_merge($recommendations, $mentalHealth['recommendations']);
            $riskLevel = $this->updateRiskLevel($riskLevel, 'medium');
        }
        
        // Add general recommendations if no specific conditions found
        if (empty($detectedConditions)) {
            $recommendations[] = "Continue regular monitoring of your symptoms";
            $recommendations[] = "Maintain a healthy lifestyle and stay hydrated";
        }
        
        return [
            'risk_level' => $riskLevel,
            'detected_conditions' => array_unique($detectedConditions),
            'recommendations' => array_unique($recommendations),
            'pattern_alerts' => []
        ];
    }
    
    /**
     * Check for emergency conditions that require immediate attention
     */
    private function checkEmergencyConditions($symptoms) {
        $conditions = [];
        $recommendations = [];
        $isEmergency = false;
        
        // No baby movement - most critical
        if (isset($symptoms['baby_movement']) && $symptoms['baby_movement'] === 'none') {
            $conditions[] = "No Baby Movement";
            $recommendations[] = "Go to hospital immediately - this requires urgent medical attention";
            $isEmergency = true;
        }
        
        // Severe pain with other symptoms
        if (isset($symptoms['pain']) && $symptoms['pain'] === 'severe') {
            if (isset($symptoms['headache']) && in_array($symptoms['headache'], ['moderate', 'severe'])) {
                $conditions[] = "Severe Pain with Headache";
                $recommendations[] = "Seek immediate medical attention";
                $isEmergency = true;
            }
            
            if (isset($symptoms['swelling']) && in_array($symptoms['swelling'], ['moderate', 'severe'])) {
                $conditions[] = "Severe Pain with Swelling";
                $recommendations[] = "Go to emergency room for evaluation";
                $isEmergency = true;
            }
        }
        
        return [
            'is_emergency' => $isEmergency,
            'conditions' => $conditions,
            'recommendations' => $recommendations
        ];
    }
    
    /**
     * Check for Preeclampsia Risk
     * Combination: headache + swelling + high pain
     */
    private function checkPreeclampsia($symptoms) {
        $conditions = [];
        $recommendations = [];
        $detected = false;
        
        $headachePresent = isset($symptoms['headache']) && in_array($symptoms['headache'], ['moderate', 'severe']);
        $swellingPresent = isset($symptoms['swelling']) && in_array($symptoms['swelling'], ['moderate', 'severe']);
        $painPresent = isset($symptoms['pain']) && in_array($symptoms['pain'], ['moderate', 'severe']);
        
        // Need at least 2 of 3 symptoms for preeclampsia risk
        $symptomCount = ($headachePresent ? 1 : 0) + ($swellingPresent ? 1 : 0) + ($painPresent ? 1 : 0);
        
        if ($symptomCount >= 2) {
            $detected = true;
            $conditions[] = "Preeclampsia Risk";
            
            if ($symptomCount === 3) {
                $recommendations[] = "High preeclampsia risk detected - consult doctor within 24 hours";
                $recommendations[] = "Monitor blood pressure regularly";
                $recommendations[] = "Watch for visual disturbances or upper abdominal pain";
            } else {
                $recommendations[] = "Possible preeclampsia risk - schedule doctor appointment soon";
                $recommendations[] = "Monitor symptoms closely";
            }
        }
        
        return [
            'detected' => $detected,
            'conditions' => $conditions,
            'recommendations' => $recommendations
        ];
    }
    
    /**
     * Check for Anemia Risk
     * Combination: fatigue + dizziness
     */
    private function checkAnemia($symptoms) {
        $conditions = [];
        $recommendations = [];
        $detected = false;
        
        $fatiguePresent = isset($symptoms['fatigue']) && in_array($symptoms['fatigue'], ['moderate', 'severe']);
        $dizzinessPresent = isset($symptoms['dizziness']) && in_array($symptoms['dizziness'], ['moderate', 'severe']);
        
        if ($fatiguePresent && $dizzinessPresent) {
            $detected = true;
            $conditions[] = "Anemia Risk";
            $recommendations[] = "Possible iron deficiency - consider iron-rich foods";
            $recommendations[] = "Consult doctor about blood tests";
            $recommendations[] = "Ensure adequate rest and nutrition";
        }
        
        return [
            'detected' => $detected,
            'conditions' => $conditions,
            'recommendations' => $recommendations
        ];
    }
    
    /**
     * Check for Gestational Diabetes
     * Combination: frequent urination + high thirst
     */
    private function checkGestationalDiabetes($symptoms) {
        $conditions = [];
        $recommendations = [];
        $detected = false;
        
        $urinationFrequent = isset($symptoms['urination']) && $symptoms['urination'] === 'frequent';
        $thirstHigh = isset($symptoms['thirst']) && in_array($symptoms['thirst'], ['increased', 'excessive']);
        
        if ($urinationFrequent && $thirstHigh) {
            $detected = true;
            $conditions[] = "Gestational Diabetes Risk";
            $recommendations[] = "Monitor blood sugar levels";
            $recommendations[] = "Reduce sugar intake and maintain balanced diet";
            $recommendations[] = "Consult doctor for glucose tolerance test";
        }
        
        return [
            'detected' => $detected,
            'conditions' => $conditions,
            'recommendations' => $recommendations
        ];
    }
    
    /**
     * Check for Dehydration Risk
     */
    private function checkDehydration($symptoms) {
        $conditions = [];
        $recommendations = [];
        $detected = false;
        
        $thirstHigh = isset($symptoms['thirst']) && in_array($symptoms['thirst'], ['increased', 'excessive']);
        $urinationReduced = isset($symptoms['urination']) && $symptoms['urination'] === 'reduced';
        $dizzinessPresent = isset($symptoms['dizziness']) && in_array($symptoms['dizziness'], ['moderate', 'severe']);
        
        if ($thirstHigh && ($urinationReduced || $dizzinessPresent)) {
            $detected = true;
            $conditions[] = "Dehydration Risk";
            $recommendations[] = "Increase water intake immediately";
            $recommendations[] = "Monitor urine color - should be light yellow";
            $recommendations[] = "Rest in cool environment";
        }
        
        return [
            'detected' => $detected,
            'conditions' => $conditions,
            'recommendations' => $recommendations
        ];
    }
    
    /**
     * Check for Mental Health Concerns
     */
    private function checkMentalHealth($symptoms) {
        $conditions = [];
        $recommendations = [];
        $detected = false;
        
        $stressIndicators = ['stressed', 'anxious'];
        $moodStressed = isset($symptoms['mood']) && in_array($symptoms['mood'], $stressIndicators);
        $fatigueHigh = isset($symptoms['fatigue']) && in_array($symptoms['fatigue'], ['moderate', 'severe']);
        
        if ($moodStressed && $fatigueHigh) {
            $detected = true;
            $conditions[] = "Mental Health Concern";
            $recommendations[] = "Consider talking to a mental health professional";
            $recommendations[] = "Practice relaxation techniques and gentle exercise";
            $recommendations[] = "Reach out to support system - family and friends";
        }
        
        return [
            'detected' => $detected,
            'conditions' => $conditions,
            'recommendations' => $recommendations
        ];
    }
    
    /**
     * Update risk level based on detected conditions
     */
    private function updateRiskLevel($currentLevel, $newLevel) {
        $riskHierarchy = [
            'low' => 1,
            'medium' => 2,
            'high' => 3,
            'critical' => 4
        ];
        
        $currentValue = $riskHierarchy[$currentLevel];
        $newValue = $riskHierarchy[$newLevel];
        
        return $newValue > $currentValue ? $newLevel : $currentLevel;
    }
}
?>
