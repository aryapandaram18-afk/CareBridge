<?php
/**
 * Input Validator Utility
 * Provides validation and sanitization for user inputs
 */

class InputValidator {
    
    /**
     * Validate symptom input data
     */
    public static function validateSymptoms($data) {
        $errors = [];
        
        // Required fields
        $requiredFields = ['headache', 'swelling', 'dizziness', 'fatigue', 'baby_movement', 'mood', 'urination', 'thirst', 'pain'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
            }
        }
        
        // Validate headache
        if (isset($data['headache']) && !in_array($data['headache'], ['none', 'mild', 'moderate', 'severe'])) {
            $errors['headache'] = 'Invalid headache value';
        }
        
        // Validate swelling
        if (isset($data['swelling']) && !in_array($data['swelling'], ['none', 'mild', 'moderate', 'severe'])) {
            $errors['swelling'] = 'Invalid swelling value';
        }
        
        // Validate dizziness
        if (isset($data['dizziness']) && !in_array($data['dizziness'], ['none', 'mild', 'moderate', 'severe'])) {
            $errors['dizziness'] = 'Invalid dizziness value';
        }
        
        // Validate fatigue
        if (isset($data['fatigue']) && !in_array($data['fatigue'], ['none', 'mild', 'moderate', 'severe'])) {
            $errors['fatigue'] = 'Invalid fatigue value';
        }
        
        // Validate baby movement
        if (isset($data['baby_movement']) && !in_array($data['baby_movement'], ['normal', 'reduced', 'none'])) {
            $errors['baby_movement'] = 'Invalid baby movement value';
        }
        
        // Validate mood
        if (isset($data['mood']) && !in_array($data['mood'], ['happy', 'neutral', 'stressed', 'anxious', 'depressed'])) {
            $errors['mood'] = 'Invalid mood value';
        }
        
        // Validate urination
        if (isset($data['urination']) && !in_array($data['urination'], ['normal', 'frequent', 'reduced'])) {
            $errors['urination'] = 'Invalid urination value';
        }
        
        // Validate thirst
        if (isset($data['thirst']) && !in_array($data['thirst'], ['normal', 'increased', 'excessive'])) {
            $errors['thirst'] = 'Invalid thirst value';
        }
        
        // Validate pain
        if (isset($data['pain']) && !in_array($data['pain'], ['none', 'mild', 'moderate', 'severe'])) {
            $errors['pain'] = 'Invalid pain value';
        }
        
        // Validate notes if present
        if (isset($data['notes']) && !is_string($data['notes'])) {
            $errors['notes'] = 'Notes must be a string';
        }
        
        return $errors;
    }
    
    /**
     * Sanitize input data
     */
    public static function sanitize($data) {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Validate email
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate phone number
     */
    public static function validatePhone($phone) {
        // Basic phone validation - adjust regex as needed
        return preg_match('/^[\d\s\-\+\(\)]{10,}$/', $phone);
    }
    
    /**
     * Validate name
     */
    public static function validateName($name) {
        return !empty(trim($name)) && strlen(trim($name)) >= 2 && strlen(trim($name)) <= 100;
    }
    
    /**
     * Validate age
     */
    public static function validateAge($age) {
        return is_numeric($age) && $age >= 18 && $age <= 50;
    }
    
    /**
     * Validate pregnancy week
     */
    public static function validatePregnancyWeek($week) {
        return is_numeric($week) && $week >= 1 && $week <= 42;
    }
}
?>
