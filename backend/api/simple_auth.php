<?php
/**
 * Simple Working Authentication - Fixed for JSON
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
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

// Get JSON data
$jsonInput = file_get_contents('php://input');
$data = json_decode($jsonInput, true);

$action = $data['action'] ?? '';
$userType = $data['user_type'] ?? '';
$email = $data['email'] ?? '';
$pass = $data['password'] ?? '';
$name = $data['name'] ?? '';

if ($action === 'signup') {
    // Check if user exists
    if ($userType === 'patient') {
        $check = $conn->prepare("SELECT id FROM patients WHERE email = ?");
    } else {
        $check = $conn->prepare("SELECT id FROM doctors WHERE email = ?");
    }
    $check->bind_param('s', $email);
    $check->execute();
    
    if ($check->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already exists']);
        exit;
    }
    
    // Insert new user
    if ($userType === 'patient') {
        $week = $_POST['pregnancy_week'] ?? 28;
        $insert = $conn->prepare("INSERT INTO patients (name, email, password, pregnancy_week) VALUES (?, ?, ?, ?)");
        $insert->bind_param('sss', $name, $email, $pass, $week);
    } else {
        $insert = $conn->prepare("INSERT INTO doctors (name, email, password) VALUES (?, ?, ?)");
        $insert->bind_param('sss', $name, $email, $pass);
    }
    
    if ($insert->execute()) {
        $userId = $conn->insert_id;
        echo json_encode([
            'success' => true,
            'user' => ['id' => $userId, 'name' => $name, 'email' => $email],
            'user_type' => $userType,
            'session_token' => 'simple_token_' . $userId,
            'message' => 'Account created successfully'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create account']);
    }
    
} elseif ($action === 'login') {
    // Get user from database
    if ($userType === 'patient') {
        $get = $conn->prepare("SELECT id, name, email, password, pregnancy_week FROM patients WHERE email = ?");
    } else {
        $get = $conn->prepare("SELECT id, name, email, password FROM doctors WHERE email = ?");
    }
    $get->bind_param('s', $email);
    $get->execute();
    $result = $get->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
        exit;
    }
    
    $user = $result->fetch_assoc();
    
    // Simple password check
    if ($pass !== $user['password']) {
        echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'user' => ['id' => $user['id'], 'name' => $user['name'], 'email' => $user['email']],
        'user_type' => $userType,
        'session_token' => 'simple_token_' . $user['id'],
        'message' => 'Login successful'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

$conn->close();
?>
