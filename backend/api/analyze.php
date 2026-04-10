<?php
/**
 * Analyze API Endpoint - FIXED LOGIC
 * POST /api/analyze.php
 * Analyzes symptoms and returns risk assessment with recommendations
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Use POST.',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

try {
    // Get JSON input
    $jsonInput = file_get_contents('php://input');
    
    if (empty($jsonInput)) {
        echo json_encode(['success' => false, 'message' => 'No data received. Please provide symptom data in JSON format.']);
        exit;
    }
    
    // Decode JSON
    $symptoms = json_decode($jsonInput, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON format: ' . json_last_error_msg()]);
        exit;
    }
    
    // Initialize results
    $riskLevel = 'low';
    $detectedConditions = [];
    $recommendations = [];
    
    // PRIORITY 1: CRITICAL CONDITIONS
    if (isset($symptoms['baby_movement']) && $symptoms['baby_movement'] === 'none') {
        $riskLevel = 'critical';
        $detectedConditions[] = 'No baby movement detected';
        $recommendations[] = 'Go to hospital immediately - this is a medical emergency';
        $recommendations[] = 'Call emergency services or go to nearest emergency room';
        $recommendations[] = 'Monitor fetal heart rate if possible';
    }
    
    // PRIORITY 2: HIGH RISK CONDITIONS
    if (isset($symptoms['baby_movement']) && $symptoms['baby_movement'] === 'low') {
        if ($riskLevel !== 'critical') $riskLevel = 'high';
        $detectedConditions[] = 'Reduced baby movement';
        $recommendations[] = 'Contact doctor immediately';
        $recommendations[] = 'Monitor baby movements closely';
        $recommendations[] = 'Drink cold water and lie on left side';
    }
    
    // PREECLAMPSIA RISK: headache + swelling + pain
    $hasHeadache = isset($symptoms['headache']) && in_array($symptoms['headache'], ['moderate', 'severe']);
    $hasSwelling = isset($symptoms['swelling']) && in_array($symptoms['swelling'], ['moderate', 'severe']);
    $hasPain = isset($symptoms['pain']) && in_array($symptoms['pain'], ['moderate', 'severe']);
    
    if ($hasHeadache && $hasSwelling && $hasPain) {
        if ($riskLevel !== 'critical') $riskLevel = 'high';
        $detectedConditions[] = 'Preeclampsia risk';
        $recommendations[] = 'Urgent medical evaluation required';
        $recommendations[] = 'Monitor blood pressure';
        $recommendations[] = 'Check for protein in urine';
    }
    
    // SEVERE HEADACHE
    if (isset($symptoms['headache']) && $symptoms['headache'] === 'severe') {
        if ($riskLevel === 'low') $riskLevel = 'high';
        $detectedConditions[] = 'Severe headache';
        $recommendations[] = 'Medical evaluation recommended';
        $recommendations[] = 'Monitor for vision changes';
    }
    
    // SEVERE SWELLING
    if (isset($symptoms['swelling']) && $symptoms['swelling'] === 'severe') {
        if ($riskLevel === 'low') $riskLevel = 'high';
        $detectedConditions[] = 'Severe swelling';
        $recommendations[] = 'Medical evaluation recommended';
        $recommendations[] = 'Monitor blood pressure';
    }
    
    // PRIORITY 3: MEDIUM RISK CONDITIONS
    if (isset($symptoms['dizziness']) && $symptoms['dizziness'] === 'moderate') {
        if ($riskLevel === 'low') $riskLevel = 'medium';
        $detectedConditions[] = 'Dizziness';
        $recommendations[] = 'Rest and stay hydrated';
        $recommendations[] = 'Avoid sudden movements';
    }
    
    if (isset($symptoms['vision']) && $symptoms['vision'] === 'yes') {
        if ($riskLevel === 'low') $riskLevel = 'medium';
        $detectedConditions[] = 'Vision changes';
        $recommendations[] = 'Medical evaluation recommended';
        $recommendations[] = 'Monitor for worsening symptoms';
    }
    
    // MODERATE PAIN
    if (isset($symptoms['pain']) && $symptoms['pain'] === 'moderate') {
        if ($riskLevel === 'low') $riskLevel = 'medium';
        $detectedConditions[] = 'Moderate pain';
        $recommendations[] = 'Monitor pain levels';
        $recommendations[] = 'Contact doctor if pain worsens';
    }
    
    // PRIORITY 4: LOW RISK CONDITIONS
    if (isset($symptoms['headache']) && $symptoms['headache'] === 'mild') {
        if ($riskLevel === 'low') {
            $detectedConditions[] = 'Mild headache';
            $recommendations[] = 'Rest in quiet environment';
            $recommendations[] = 'Stay hydrated';
        }
    }
    
    if (isset($symptoms['swelling']) && $symptoms['swelling'] === 'mild') {
        if ($riskLevel === 'low') {
            $detectedConditions[] = 'Mild swelling';
            $recommendations[] = 'Elevate feet when resting';
            $recommendations[] = 'Reduce salt intake';
        }
    }
    
    if (isset($symptoms['mood']) && $symptoms['mood'] !== 'happy') {
        if ($riskLevel === 'low') {
            $detectedConditions[] = 'Mood changes';
            $recommendations[] = 'Talk to healthcare provider';
            $recommendations[] = 'Seek emotional support';
        }
    }
    
    // DEFAULT IF NO CONDITIONS DETECTED
    if (empty($detectedConditions)) {
        $detectedConditions[] = 'No concerning symptoms detected';
        $recommendations[] = 'Continue regular monitoring';
        $recommendations[] = 'Maintain healthy lifestyle';
    }
    
    // Return success response
    $result = [
        'symptoms' => $symptoms,
        'risk_level' => $riskLevel,
        'detected_conditions' => $detectedConditions,
        'recommendations' => $recommendations,
        'pattern_alerts' => [],
        'patterns' => [],
        'trends' => [],
        'analysis_timestamp' => date('Y-m-d H:i:s')
    ];
    
    echo json_encode([
        'success' => true,
        'message' => 'Symptom analysis completed successfully',
        'data' => $result,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Analysis failed: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
