<?php
/**
 * Response Utility Functions
 * Standardized JSON response formatting for CareBridge API
 */

class Response {
    
    /**
     * Send success response
     * @param mixed $data - Response data
     * @param string $message - Success message
     * @param int $statusCode - HTTP status code
     */
    public static function success($data = null, $message = 'Success', $statusCode = 200) {
        self::sendJson([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ], $statusCode);
    }
    
    /**
     * Send error response
     * @param string $message - Error message
     * @param int $statusCode - HTTP status code
     * @param mixed $details - Additional error details
     */
    public static function error($message = 'Error occurred', $statusCode = 400, $details = null) {
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        if ($details !== null) {
            $response['details'] = $details;
        }
        
        self::sendJson($response, $statusCode);
    }
    
    /**
     * Send validation error response
     * @param array $errors - Validation errors
     * @param string $message - General error message
     */
    public static function validationError($errors = [], $message = 'Validation failed') {
        self::sendJson([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'timestamp' => date('Y-m-d H:i:s')
        ], 422);
    }
    
    /**
     * Send not found response
     * @param string $message - Not found message
     */
    public static function notFound($message = 'Resource not found') {
        self::sendJson([
            'success' => false,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ], 404);
    }
    
    /**
     * Send unauthorized response
     * @param string $message - Unauthorized message
     */
    public static function unauthorized($message = 'Unauthorized access') {
        self::sendJson([
            'success' => false,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ], 401);
    }
    
    /**
     * Send server error response
     * @param string $message - Server error message
     * @param mixed $details - Error details
     */
    public static function serverError($message = 'Internal server error', $details = null) {
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        if ($details !== null) {
            $response['details'] = $details;
        }
        
        self::sendJson($response, 500);
    }
    
    /**
     * Send JSON response with proper headers
     * @param array $data - Response data
     * @param int $statusCode - HTTP status code
     */
    private static function sendJson($data, $statusCode) {
        header_remove();
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        
        http_response_code($statusCode);
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Handle OPTIONS request for CORS
     */
    public static function handleOptions() {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization');
            header('Access-Control-Max-Age: 86400');
            header('Content-Length: 0');
            header('Content-Type: text/plain');
            exit(0);
        }
    }
}

/**
 * Input validation helper functions
 */
class InputValidator {
    
    /**
     * Validate required fields
     * @param array $data - Input data
     * @param array $requiredFields - Required field names
     * @return array - Validation errors
     */
    public static function validateRequired($data, $requiredFields) {
        $errors = [];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
                $errors[$field] = ucfirst($field) . ' is required';
            }
        }
        
        return $errors;
    }
    
    /**
     * Validate symptom data
     * @param array $symptoms - Symptom data
     * @return array - Validation errors
     */
    public static function validateSymptoms($symptoms) {
        $errors = [];
        
        $validValues = [
            'headache' => ['none', 'mild', 'moderate', 'severe'],
            'swelling' => ['none', 'mild', 'moderate', 'severe'],
            'dizziness' => ['none', 'mild', 'moderate', 'severe'],
            'fatigue' => ['none', 'mild', 'moderate', 'severe'],
            'baby_movement' => ['normal', 'reduced', 'none'],
            'mood' => ['happy', 'neutral', 'stressed', 'anxious'],
            'urination' => ['normal', 'frequent', 'reduced'],
            'thirst' => ['normal', 'increased', 'excessive'],
            'pain' => ['none', 'mild', 'moderate', 'severe']
        ];
        
        foreach ($validValues as $field => $allowedValues) {
            if (isset($symptoms[$field])) {
                if (!in_array($symptoms[$field], $allowedValues)) {
                    $errors[$field] = 'Invalid value for ' . $field . '. Allowed: ' . implode(', ', $allowedValues);
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * Validate mood data
     * @param array $moodData - Mood data
     * @return array - Validation errors
     */
    public static function validateMoodData($moodData) {
        $errors = [];
        
        // Validate mood
        if (isset($moodData['mood'])) {
            $validMoods = ['happy', 'neutral', 'stressed', 'anxious', 'depressed'];
            if (!in_array($moodData['mood'], $validMoods)) {
                $errors['mood'] = 'Invalid mood. Allowed: ' . implode(', ', $validMoods);
            }
        }
        
        // Validate stress level
        if (isset($moodData['stress_level'])) {
            if (!is_numeric($moodData['stress_level']) || $moodData['stress_level'] < 1 || $moodData['stress_level'] > 10) {
                $errors['stress_level'] = 'Stress level must be a number between 1 and 10';
            }
        }
        
        return $errors;
    }
    
    /**
     * Sanitize input data
     * @param array $data - Input data
     * @return array - Sanitized data
     */
    public static function sanitize($data) {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = trim(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }
}

/**
 * Database helper functions
 */
class DatabaseHelper {
    
    /**
     * Execute prepared statement with error handling
     * @param mysqli $stmt - Prepared statement
     * @param string $types - Parameter types
     * @param array $params - Parameters
     * @return mysqli_result|bool - Query result
     */
    public static function executeStatement($stmt, $types = '', $params = []) {
        try {
            if (!empty($types) && !empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            
            $result = $stmt->execute();
            
            if (!$result) {
                throw new Exception("Statement execution failed: " . $stmt->error);
            }
            
            return $stmt->get_result();
        } catch (Exception $e) {
            error_log("Database error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Fetch all results from query
     * @param mysqli_result $result - Query result
     * @return array - Fetched data
     */
    public static function fetchAll($result) {
        $data = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        
        return $data;
    }
    
    /**
     * Begin transaction
     * @param mysqli $connection - Database connection
     */
    public static function beginTransaction($connection) {
        $connection->begin_transaction();
    }
    
    /**
     * Commit transaction
     * @param mysqli $connection - Database connection
     */
    public static function commitTransaction($connection) {
        $connection->commit();
    }
    
    /**
     * Rollback transaction
     * @param mysqli $connection - Database connection
     */
    public static function rollbackTransaction($connection) {
        $connection->rollback();
    }
}
?>
