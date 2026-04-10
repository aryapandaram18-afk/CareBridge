<?php
/**
 * Get Guidance API Endpoint - FIXED
 * GET /api/get_guidance.php
 * Returns pregnancy guidance based on gestational week
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
    // Get week parameter
    $week = isset($_GET['week']) ? (int)$_GET['week'] : 28;
    
    // Validate week parameter
    if ($week < 1 || $week > 42) {
        echo json_encode(['success' => false, 'message' => 'Week parameter must be between 1 and 42']);
        exit;
    }
    
    // Guidance data based on gestational week
    $guidanceData = [
        '1-12' => [
            'title' => 'First Trimester (Weeks 1–12)',
            'items' => [
                'Take folic acid 400mcg daily',
                'Morning sickness — eat small, frequent meals',
                'Avoid alcohol, raw meat, high-mercury fish',
                'Book first prenatal appointment',
                'Rest as needed — fatigue is normal'
            ]
        ],
        '13-26' => [
            'title' => 'Second Trimester (Weeks 13–26)',
            'items' => [
                'Usually most comfortable trimester',
                'Baby movements start around week 18–20',
                'Anomaly scan: weeks 18–22',
                'Sleep on your left side',
                'Begin prenatal exercises if cleared'
            ]
        ],
        '27-36' => [
            'title' => 'Third Trimester (Weeks 27–36)',
            'items' => [
                'Kick counts: 10 movements in 2 hours',
                'Watch for: severe headache, swelling, vision changes',
                'Prenatal visits every 2 weeks now',
                'Prepare hospital bag from week 32'
            ]
        ],
        '37-40' => [
            'title' => 'Full Term (Weeks 37–40)',
            'items' => [
                'Weekly appointments now',
                'Labor signs: regular contractions, water breaking',
                'Stay near home, keep phone charged',
                'Hospital if contractions are 5 min apart'
            ]
        ]
    ];
    
    // Nutrients guidance
    $nutrients = [
        'Iron: Spinach, lentils, red meat',
        'Calcium: Milk, yogurt, paneer',
        'Protein: Eggs, dal, tofu',
        'Folate: Leafy greens, oranges',
        'DHA: Walnuts, flaxseeds'
    ];
    
    // Emergency warnings
    $emergencyWarnings = [
        'Severe headache with visual disturbances',
        'Sudden swelling of face, hands, feet',
        'No baby movement for 12+ hours',
        'Heavy bleeding at any stage',
        'Severe abdominal pain or cramping'
    ];
    
    // Select appropriate guidance based on week
    if ($week <= 12) {
        $guidance = $guidanceData['1-12'];
    } elseif ($week <= 26) {
        $guidance = $guidanceData['13-26'];
    } elseif ($week <= 36) {
        $guidance = $guidanceData['27-36'];
    } else {
        $guidance = $guidanceData['37-40'];
    }
    
    // Response data
    $responseData = [
        'week' => $week,
        'guidance' => $guidance,
        'nutrients' => $nutrients,
        'emergency_warnings' => $emergencyWarnings
    ];
    
    echo json_encode([
        'success' => true,
        'message' => 'Guidance retrieved successfully',
        'data' => $responseData,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to retrieve guidance: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
