<?php
/**
 * Doctor Recommendation API Endpoint
 * POST /api/doctor_recommend.php
 * Provides doctor visit recommendations based on risk and symptoms
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

// Include required files
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../services/pattern_engine.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/input_validator.php';

try {
    // Get JSON input
    $jsonInput = file_get_contents('php://input');
    
    if (empty($jsonInput)) {
        Response::error('No data received. Please provide risk and symptom data in JSON format.');
    }
    
    // Decode JSON
    $inputData = json_decode($jsonInput, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        Response::error('Invalid JSON format: ' . json_last_error_msg());
    }
    
    // Extract data
    $riskLevel = $inputData['risk_level'] ?? 'low';
    $symptoms = $inputData['symptoms'] ?? [];
    $detectedConditions = $inputData['detected_conditions'] ?? [];
    $patterns = $inputData['patterns'] ?? [];
    
    // Sanitize input
    $riskLevel = InputValidator::sanitize($riskLevel);
    $symptoms = InputValidator::sanitize($symptoms);
    $detectedConditions = InputValidator::sanitize($detectedConditions);
    
    // Generate doctor recommendation
    $recommendation = generateDoctorRecommendation($riskLevel, $symptoms, $detectedConditions, $patterns);
    
    // Log recommendation
    logDoctorRecommendation($riskLevel, $symptoms, $recommendation);
    
    // Return recommendation
    Response::success($recommendation, 'Doctor recommendation generated successfully');
    
} catch (Exception $e) {
    error_log('Doctor Recommend API Error: ' . $e->getMessage());
    Response::serverError('Failed to generate doctor recommendation. Please try again later.', $e->getMessage());
}

/**
 * Generate doctor recommendation based on risk and symptoms
 */
function generateDoctorRecommendation($riskLevel, $symptoms, $detectedConditions, $patterns) {
    $recommendation = [
        'advice' => '',
        'urgency' => 'low',
        'timeline' => '',
        'reason' => '',
        'specific_concerns' => [],
        'follow_up_actions' => []
    ];
    
    // Critical risk level
    if ($riskLevel === 'critical') {
        $recommendation['advice'] = 'Go to hospital immediately or call emergency services';
        $recommendation['urgency'] = 'immediate';
        $recommendation['timeline'] = 'Now';
        $recommendation['reason'] = 'Critical symptoms detected that require immediate medical attention';
        $recommendation['specific_concerns'] = $detectedConditions;
        $recommendation['follow_up_actions'] = [
            'Do not wait - seek emergency care now',
            'Have someone drive you to the hospital',
            'Call emergency services if alone'
        ];
        return $recommendation;
    }
    
    // High risk level
    if ($riskLevel === 'high') {
        $recommendation['advice'] = 'Consult doctor within 24 hours';
        $recommendation['urgency'] = 'high';
        $recommendation['timeline'] = 'Within 24 hours';
        $recommendation['reason'] = 'High-risk symptoms detected that need medical evaluation';
        $recommendation['specific_concerns'] = $detectedConditions;
        $recommendation['follow_up_actions'] = [
            'Call your doctor immediately to schedule an appointment',
            'Monitor symptoms closely until appointment',
            'Go to emergency room if symptoms worsen'
        ];
        return $recommendation;
    }
    
    // Medium risk level
    if ($riskLevel === 'medium') {
        // Check for specific concerning patterns
        $hasConcerningPatterns = false;
        
        if (isset($patterns['repeated_symptoms']) && !empty($patterns['repeated_symptoms'])) {
            foreach ($patterns['repeated_symptoms'] as $symptom) {
                if ($symptom['days_count'] >= 5) {
                    $hasConcerningPatterns = true;
                    $recommendation['specific_concerns'][] = "Persistent {$symptom['symptom']} for {$symptom['days_count']} days";
                }
            }
        }
        
        if (isset($patterns['worsening_trends']) && !empty($patterns['worsening_trends'])) {
            foreach ($patterns['worsening_trends'] as $trend) {
                if ($trend['severity'] === 'high') {
                    $hasConcerningPatterns = true;
                    $recommendation['specific_concerns'][] = "Rapidly worsening {$trend['symptom']}";
                }
            }
        }
        
        if ($hasConcerningPatterns) {
            $recommendation['advice'] = 'Schedule doctor appointment within 3-5 days';
            $recommendation['urgency'] = 'medium';
            $recommendation['timeline'] = 'Within 3-5 days';
            $recommendation['reason'] = 'Concerning symptom patterns detected';
            $recommendation['follow_up_actions'] = [
                'Call doctor to schedule appointment',
                'Continue monitoring symptoms daily',
                'Note any changes or worsening'
            ];
        } else {
            $recommendation['advice'] = 'Consider doctor visit within 1-2 weeks';
            $recommendation['urgency'] = 'low';
            $recommendation['timeline'] = 'Within 1-2 weeks';
            $recommendation['reason'] = 'Moderate-risk symptoms that should be evaluated';
            $recommendation['follow_up_actions'] = [
                'Schedule routine check-up',
                'Monitor symptoms',
                'Contact doctor if symptoms change'
            ];
        }
        
        return $recommendation;
    }
    
    // Low risk level but with concerning patterns
    if ($riskLevel === 'low') {
        $hasRepeatedSymptoms = false;
        
        if (isset($patterns['repeated_symptoms']) && !empty($patterns['repeated_symptoms'])) {
            foreach ($patterns['repeated_symptoms'] as $symptom) {
                if ($symptom['days_count'] >= 7) {
                    $hasRepeatedSymptoms = true;
                    $recommendation['specific_concerns'][] = "Chronic {$symptom['symptom']} for {$symptom['days_count']} days";
                }
            }
        }
        
        if ($hasRepeatedSymptoms) {
            $recommendation['advice'] = 'Consider doctor visit within 1-2 weeks';
            $recommendation['urgency'] = 'low';
            $recommendation['timeline'] = 'Within 1-2 weeks';
            $recommendation['reason'] = 'Persistent mild symptoms that should be evaluated';
            $recommendation['follow_up_actions'] = [
                'Schedule routine appointment',
                'Document symptom frequency',
                'Discuss with doctor at next visit'
            ];
        } else {
            $recommendation['advice'] = 'Continue regular monitoring';
            $recommendation['urgency'] = 'low';
            $recommendation['timeline'] = 'At next routine appointment';
            $recommendation['reason'] = 'Low-risk symptoms with no concerning patterns';
            $recommendation['follow_up_actions'] = [
                'Continue daily symptom tracking',
                'Maintain healthy lifestyle',
                'Contact doctor if new symptoms develop'
            ];
        }
        
        return $recommendation;
    }
    
    return $recommendation;
}

/**
 * Log doctor recommendation to database
 */
function logDoctorRecommendation($riskLevel, $symptoms, $recommendation) {
    try {
        $db = new Database();
        
        $sql = "INSERT INTO doctor_recommendations (risk_level, symptoms_summary, recommendation) VALUES (?, ?, ?)";
        $stmt = $db->prepare($sql);
        
        $symptomsSummary = json_encode([
            'risk_level' => $riskLevel,
            'symptoms' => $symptoms,
            'advice' => $recommendation['advice'],
            'urgency' => $recommendation['urgency']
        ]);
        
        $recommendationText = $recommendation['advice'] . '. ' . $recommendation['reason'];
        
        $stmt->bind_param('sss', $riskLevel, $symptomsSummary, $recommendationText);
        $stmt->execute();
        
    } catch (Exception $e) {
        // Log error but don't fail the response
        error_log('Failed to log doctor recommendation: ' . $e->getMessage());
    }
}
?>
