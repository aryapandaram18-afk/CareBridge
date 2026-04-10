<?php
/**
 * Patient Profile API Endpoint - FIXED
 * GET/POST /api/patient_profile.php
 * Handles patient profile retrieval and updates
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    // Get session token from header or POST data
    $sessionToken = $_SERVER['HTTP_AUTHORIZATION'] ?? $_POST['session_token'] ?? '';
    
    if (empty($sessionToken)) {
        echo json_encode(['success' => false, 'message' => 'Session token required']);
        exit;
    }
    
    // Simple database connection
    $host = 'localhost';
    $username = 'root';
    $password = 'Bs$230331';
    $dbname = 'carebridge';
    
    $conn = new mysqli($host, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }
    
    // Validate session (simplified)
    $sessionStmt = $conn->prepare("SELECT user_id, user_type FROM user_sessions WHERE session_token = ? AND expires_at > NOW()");
    $sessionStmt->bind_param('s', $sessionToken);
    $sessionStmt->execute();
    $sessionResult = $sessionStmt->get_result();
    
    if ($sessionResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid or expired session']);
        exit;
    }
    }
    
    $session = $sessionResult->fetch_assoc();
    
    if ($session['user_type'] !== 'patient') {
        echo json_encode(['success' => false, 'message' => 'Access denied. Patient access required.']);
        exit;
    }
    
    $patientId = $session['user_id'];
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get patient profile
        $stmt = $conn->prepare("SELECT id, name, email, phone, age, pregnancy_week, doctor_assigned, created_at FROM patients WHERE id = ?");
        $stmt->bind_param('i', $patientId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Patient not found']);
            exit;
        }
        
        $patient = $result->fetch_assoc();
        
        // Remove sensitive data
        unset($patient['id']);
        
        echo json_encode([
            'success' => true,
            'message' => 'Patient profile retrieved successfully',
            'data' => $patient,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Update patient profile
        $jsonInput = file_get_contents('php://input');
        $updateData = json_decode($jsonInput, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo json_encode(['success' => false, 'message' => 'Invalid JSON format: ' . json_last_error_msg()]);
            exit;
        }
        
        // Build update query dynamically
        $updateFields = [];
        $params = [];
        $types = '';
        
        if (isset($updateData['name']) && !empty(trim($updateData['name']))) {
            $updateFields[] = "name = ?";
            $params[] = trim($updateData['name']);
            $types .= 's';
        }
        
        if (isset($updateData['phone']) && !empty(trim($updateData['phone']))) {
            $updateFields[] = "phone = ?";
            $params[] = trim($updateData['phone']);
            $types .= 's';
        }
        
        if (isset($updateData['age']) && is_numeric($updateData['age'])) {
            $updateFields[] = "age = ?";
            $params[] = (int)$updateData['age'];
            $types .= 'i';
        }
        
        if (isset($updateData['pregnancy_week']) && is_numeric($updateData['pregnancy_week'])) {
            $updateFields[] = "pregnancy_week = ?";
            $params[] = (int)$updateData['pregnancy_week'];
            $types .= 'i';
        }
        
        if (empty($updateFields)) {
            echo json_encode(['success' => false, 'message' => 'No valid fields to update']);
            exit;
        }
        
        // Add patient ID to params
        $params[] = $patientId;
        $types .= 'i';
        
        $sql = "UPDATE patients SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            // Get updated profile
            $getStmt = $conn->prepare("SELECT name, email, phone, age, pregnancy_week FROM patients WHERE id = ?");
            $getStmt->bind_param('i', $patientId);
            $getStmt->execute();
            $updatedProfile = $getStmt->get_result()->fetch_assoc();
            
            echo json_encode([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => $updatedProfile,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
        }
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Profile operation failed: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
