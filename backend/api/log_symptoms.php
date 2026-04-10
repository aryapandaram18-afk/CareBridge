<?php
/**
 * Log Symptoms API Endpoint - FIXED
 * POST /api/log_symptoms.php
 * Stores symptom data with analysis results in database
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
    $inputData = json_decode($jsonInput, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON format: ' . json_last_error_msg()]);
        exit;
    }
    
    // Extract symptoms and analysis results
    $symptoms = $inputData['symptoms'] ?? [];
    $analysisResults = $inputData['analysis_results'] ?? null;
    
    // Database connection
    $host = 'localhost';
    $username = 'root';
    $password = 'Bs$230331';
    $dbname = 'carebridge';
    
    $conn = new mysqli($host, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }
    
    // Insert into symptom_logs table
    $stmt = $conn->prepare("INSERT INTO symptom_logs (headache, swelling, dizziness, fatigue, baby_movement, mood, urination, thirst, pain, notes, date, risk_level, detected_conditions, recommendations) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $headache = $symptoms['headache'] ?? 'none';
    $swelling = $symptoms['swelling'] ?? 'none';
    $dizziness = $symptoms['dizziness'] ?? 'none';
    $fatigue = $symptoms['fatigue'] ?? 'none';
    $babyMovement = $symptoms['baby_movement'] ?? 'normal';
    $mood = $symptoms['mood'] ?? 'happy';
    $urination = $symptoms['urination'] ?? 'normal';
    $thirst = $symptoms['thirst'] ?? 'normal';
    $pain = $symptoms['pain'] ?? 'none';
    $notes = $symptoms['notes'] ?? '';
    $date = $symptoms['date'] ?? date('Y-m-d H:i:s');
    $riskLevel = $analysisResults['risk_level'] ?? 'low';
    $detectedConditions = json_encode($analysisResults['detected_conditions'] ?? []);
    $recommendations = json_encode($analysisResults['recommendations'] ?? []);
    
    $stmt->bind_param('sssssssssssssss', $headache, $swelling, $dizziness, $fatigue, $babyMovement, $mood, $urination, $thirst, $pain, $notes, $date, $riskLevel, $detectedConditions, $recommendations);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Symptoms logged successfully',
            'data' => [
                'logged_symptoms' => $symptoms,
                'analysis_results' => $analysisResults,
                'timestamp' => date('Y-m-d H:i:s')
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to log symptoms: ' . $stmt->error,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to log symptoms: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
