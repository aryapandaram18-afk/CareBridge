<?php
/**
 * Get Patients API Endpoint
 * GET /api/get_patients.php
 * Returns patient data for doctor dashboard
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Use GET.',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

// Include required files
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/response.php';

try {
    // Get query parameters
    $riskFilter = isset($_GET['risk']) ? $_GET['risk'] : '';
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    
    // Connect to database
    $db = new Database();
    
    // Get patients from symptom_logs (group by patient - for demo purposes)
    $sql = "SELECT 
                id,
                DATE(created_at) as entry_date,
                headache,
                swelling,
                dizziness,
                fatigue,
                baby_movement,
                mood,
                urination,
                thirst,
                pain,
                risk_level,
                detected_conditions,
                created_at
            FROM symptom_logs 
            ORDER BY created_at DESC 
            LIMIT ?";
    
    $stmt = $db->prepare($sql);
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $patients = [];
    $patientMap = [];
    
    // Group entries by date to simulate different patients
    while ($row = $result->fetch_assoc()) {
        $date = date('Y-m-d', strtotime($row['created_at']));
        $patientId = crc32($date) % 1000; // Generate consistent ID
        
        if (!isset($patientMap[$patientId])) {
            $patientMap[$patientId] = [
                'id' => $patientId,
                'name' => 'Patient ' . $patientId,
                'week' => rand(20, 40), // Random week for demo
                'daysAgo' => 0,
                'lastEntry' => [
                    'headache' => $row['headache'],
                    'swelling' => $row['swelling'],
                    'dizzy' => $row['dizziness'],
                    'vision' => $row['dizziness'] === 'moderate' || $row['dizziness'] === 'severe' ? 'yes' : 'no',
                    'baby' => $row['baby_movement'],
                    'mood' => $row['mood'],
                    'pain' => (int)$row['pain'],
                    'urine' => $row['urination']
                ],
                'risk_level' => $row['risk_level'],
                'detected_conditions' => json_decode($row['detected_conditions'] ?? '[]', true),
                'entry_date' => $date
            ];
        }
    }
    
    $patients = array_values($patientMap);
    
    // If no real data, return sample data
    if (empty($patients)) {
        $patients = [
            [
                'id' => 1,
                'name' => 'Priya Sharma',
                'week' => 32,
                'daysAgo' => 0,
                'lastEntry' => [
                    'headache' => 'severe',
                    'swelling' => 'severe',
                    'dizzy' => 'yes',
                    'vision' => 'yes',
                    'baby' => 'low',
                    'mood' => 'stressed',
                    'pain' => 8,
                    'urine' => 'high'
                ],
                'risk_level' => 'high',
                'detected_conditions' => ['Preeclampsia Risk', 'High BP Risk']
            ],
            [
                'id' => 2,
                'name' => 'Ananya Reddy',
                'week' => 28,
                'daysAgo' => 0,
                'lastEntry' => [
                    'headache' => 'no',
                    'swelling' => 'mild',
                    'dizzy' => 'no',
                    'vision' => 'no',
                    'baby' => 'normal',
                    'mood' => 'happy',
                    'pain' => 2,
                    'urine' => 'normal'
                ],
                'risk_level' => 'low',
                'detected_conditions' => []
            ],
            [
                'id' => 3,
                'name' => 'Sunita Rao',
                'week' => 36,
                'daysAgo' => 1,
                'lastEntry' => [
                    'headache' => 'mild',
                    'swelling' => 'mild',
                    'dizzy' => 'yes',
                    'vision' => 'no',
                    'baby' => 'normal',
                    'mood' => 'neutral',
                    'pain' => 4,
                    'urine' => 'normal'
                ],
                'risk_level' => 'medium',
                'detected_conditions' => ['Anemia Risk']
            ],
            [
                'id' => 4,
                'name' => 'Meera Patel',
                'week' => 24,
                'daysAgo' => 0,
                'lastEntry' => [
                    'headache' => 'no',
                    'swelling' => 'no',
                    'dizzy' => 'no',
                    'vision' => 'no',
                    'baby' => 'none',
                    'mood' => 'sad',
                    'pain' => 1,
                    'urine' => 'normal'
                ],
                'risk_level' => 'critical',
                'detected_conditions' => ['No Baby Movement', 'Emergency Condition']
            ],
            [
                'id' => 5,
                'name' => 'Kavitha Nair',
                'week' => 30,
                'daysAgo' => 0,
                'lastEntry' => [
                    'headache' => 'severe',
                    'swelling' => 'severe',
                    'dizzy' => 'yes',
                    'vision' => 'yes',
                    'baby' => 'normal',
                    'mood' => 'stressed',
                    'pain' => 9,
                    'urine' => 'high'
                ],
                'risk_level' => 'critical',
                'detected_conditions' => ['Preeclampsia Risk', 'Emergency Condition']
            ]
        ];
    }
    
    // Apply risk filter
    if (!empty($riskFilter)) {
        $patients = array_filter($patients, function($p) use ($riskFilter) {
            return $p['risk_level'] === $riskFilter;
        });
        $patients = array_values($patients);
    }
    
    // Calculate statistics
    $totalPatients = count($patients);
    $alertCount = count(array_filter($patients, function($p) {
        return in_array($p['risk_level'], ['high', 'critical']);
    }));
    $normalCount = count(array_filter($patients, function($p) {
        return $p['risk_level'] === 'low';
    }));
    
    Response::success([
        'patients' => $patients,
        'statistics' => [
            'total' => $totalPatients,
            'alerts' => $alertCount,
            'normal' => $normalCount,
            'medium' => $totalPatients - $alertCount - $normalCount
        ]
    ], 'Patients retrieved successfully');
    
} catch (Exception $e) {
    error_log('Get Patients API Error: ' . $e->getMessage());
    Response::serverError('Failed to retrieve patients. Please try again later.', $e->getMessage());
}
?>
