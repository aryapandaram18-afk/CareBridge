<?php
/**
 * Emergency API Endpoint - FIXED
 * POST /api/emergency.php
 * Fast emergency assessment for critical symptoms
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
    
    // Simple emergency assessment logic
    $isEmergency = false;
    $emergencyReasons = [];
    $actionRequired = 'Monitor symptoms';
    
    // Check for critical conditions
    if (isset($symptoms['baby_movement']) && $symptoms['baby_movement'] === 'none') {
        $isEmergency = true;
        $emergencyReasons[] = 'No baby movement detected';
        $actionRequired = 'Go to hospital immediately';
    }
    
    if (isset($symptoms['headache']) && $symptoms['headache'] === 'severe') {
        $isEmergency = true;
        $emergencyReasons[] = 'Severe headache';
        $actionRequired = 'Seek immediate medical attention';
    }
    
    if (isset($symptoms['swelling']) && $symptoms['swelling'] === 'severe') {
        $isEmergency = true;
        $emergencyReasons[] = 'Severe swelling';
        $actionRequired = 'Monitor for preeclampsia';
    }
    
    if (isset($symptoms['vision']) && $symptoms['vision'] === 'yes') {
        $isEmergency = true;
        $emergencyReasons[] = 'Vision changes';
        $actionRequired = 'Urgent medical evaluation';
    }
    
    if (isset($symptoms['pain']) && $symptoms['pain'] === 'severe') {
        $isEmergency = true;
        $emergencyReasons[] = 'Severe pain';
        $actionRequired = 'Seek medical attention';
    }
    
    // Prepare response
    $emergencyResult = [
        'is_emergency' => $isEmergency,
        'reasons' => $emergencyReasons,
        'action_required' => $actionRequired,
        'severity' => $isEmergency ? 'critical' : 'monitor',
        'symptoms' => $symptoms,
        'assessment_timestamp' => date('Y-m-d H:i:s')
    ];
    
    // Return emergency assessment
    echo json_encode([
        'success' => true,
        'message' => 'Emergency assessment completed',
        'data' => $emergencyResult,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Emergency assessment failed: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
