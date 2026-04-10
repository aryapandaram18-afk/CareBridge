<?php
/**
 * Get History API Endpoint - FIXED
 * GET /api/get_history.php
 * Retrieves symptom history and pattern analysis
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

try {
    // Get query parameters
    $days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
    $includeMood = isset($_GET['include_mood']) ? filter_var($_GET['include_mood'], FILTER_VALIDATE_BOOLEAN) : true;
    $includePatterns = isset($_GET['include_patterns']) ? filter_var($_GET['include_patterns'], FILTER_VALIDATE_BOOLEAN) : true;
    
    // Validate days parameter
    if ($days < 1 || $days > 365) {
        echo json_encode(['success' => false, 'message' => 'Days parameter must be between 1 and 365']);
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
    
    // Get symptom history
    $sql = "SELECT * FROM symptom_logs 
            WHERE date >= DATE_SUB(NOW(), INTERVAL ? DAY) 
            ORDER BY date DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $days);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $history = [];
    while ($row = $result->fetch_assoc()) {
        $entry = [
            'id' => $row['id'],
            'date' => date('Y-m-d', strtotime($row['date'])),
            'time' => date('H:i', strtotime($row['date'])),
            'symptoms' => [
                'headache' => $row['headache'],
                'swelling' => $row['swelling'],
                'dizziness' => $row['dizziness'],
                'fatigue' => $row['fatigue'],
                'baby_movement' => $row['baby_movement'],
                'mood' => $row['mood'],
                'urination' => $row['urination'],
                'thirst' => $row['thirst'],
                'pain' => $row['pain']
            ],
            'risk_level' => $row['risk_level'],
            'detected_conditions' => json_decode($row['detected_conditions'] ?? '[]', true),
            'recommendations' => json_decode($row['recommendations'] ?? '[]', true),
            'pattern_alerts' => json_decode($row['pattern_alerts'] ?? '[]', true)
        ];
        $history[] = $entry;
    }
    
    $stmt->close();
    $conn->close();
    
    // Prepare response data
    $responseData = [
        'history' => $history,
        'summary' => [
            'total_entries' => count($history),
            'date_range' => [
                'start' => !empty($history) ? $history[count($history) - 1]['date'] : null,
                'end' => !empty($history) ? $history[0]['date'] : null
            ]
        ]
    ];
    
    // Include mood data if requested
    if ($includeMood) {
        $moodData = [];
        foreach ($history as $entry) {
            if (isset($entry['symptoms']['mood'])) {
                $moodData[] = [
                    'date' => $entry['date'],
                    'mood' => $entry['symptoms']['mood']
                ];
            }
        }
        $responseData['mood_analysis'] = [
            'mood_trend' => $moodData,
            'summary' => [
                'total_entries' => count($moodData),
                'happy_count' => count(array_filter($moodData, fn($m) => $m['mood'] === 'happy')),
                'stressed_count' => count(array_filter($moodData, fn($m) => $m['mood'] === 'stressed')),
                'anxious_count' => count(array_filter($moodData, fn($m) => $m['mood'] === 'anxious'))
            ]
        ];
    }
    
    // Calculate risk distribution
    $riskDistribution = [
        'low' => 0,
        'medium' => 0,
        'high' => 0,
        'critical' => 0
    ];
    
    foreach ($history as $entry) {
        $riskLevel = $entry['risk_level'];
        if (isset($riskDistribution[$riskLevel])) {
            $riskDistribution[$riskLevel]++;
        }
    }
    
    $responseData['summary']['risk_distribution'] = $riskDistribution;
    
    // Calculate most common symptoms
    $symptomCounts = [
        'headache' => ['none' => 0, 'mild' => 0, 'moderate' => 0, 'severe' => 0],
        'swelling' => ['none' => 0, 'mild' => 0, 'moderate' => 0, 'severe' => 0],
        'dizziness' => ['none' => 0, 'mild' => 0, 'moderate' => 0, 'severe' => 0],
        'fatigue' => ['none' => 0, 'mild' => 0, 'moderate' => 0, 'severe' => 0],
        'pain' => ['none' => 0, 'mild' => 0, 'moderate' => 0, 'severe' => 0]
    ];
    
    foreach ($history as $entry) {
        foreach ($symptomCounts as $symptom => &$counts) {
            $severity = $entry['symptoms'][$symptom];
            if (isset($counts[$severity])) {
                $counts[$severity]++;
            }
        }
    }
    
    $responseData['summary']['symptom_distribution'] = $symptomCounts;
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'History retrieved successfully',
        'data' => $responseData,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to retrieve history: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
