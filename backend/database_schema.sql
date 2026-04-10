-- CareBridge Database Schema
-- MySQL Database Schema for Pregnancy Health Monitoring System

-- Create Database if not exists
CREATE DATABASE IF NOT EXISTS carebridge CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE carebridge;

-- Symptom Logs Table
-- Stores daily symptom entries from users
CREATE TABLE IF NOT EXISTS symptom_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    headache VARCHAR(20) NOT NULL COMMENT 'none, mild, moderate, severe',
    swelling VARCHAR(20) NOT NULL COMMENT 'none, mild, moderate, severe',
    dizziness VARCHAR(20) NOT NULL COMMENT 'none, mild, moderate, severe',
    fatigue VARCHAR(20) NOT NULL COMMENT 'none, mild, moderate, severe',
    baby_movement VARCHAR(20) NOT NULL COMMENT 'normal, reduced, none',
    mood VARCHAR(20) NOT NULL COMMENT 'happy, neutral, stressed, anxious',
    urination VARCHAR(20) NOT NULL COMMENT 'normal, frequent, reduced',
    thirst VARCHAR(20) NOT NULL COMMENT 'normal, increased, excessive',
    pain VARCHAR(20) NOT NULL COMMENT 'none, mild, moderate, severe',
    risk_level VARCHAR(20) NOT NULL COMMENT 'low, medium, high, critical',
    detected_conditions TEXT COMMENT 'JSON array of detected conditions',
    recommendations TEXT COMMENT 'JSON array of recommendations',
    pattern_alerts TEXT COMMENT 'JSON array of pattern alerts',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created_at (created_at),
    INDEX idx_risk_level (risk_level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mood Logs Table
-- Dedicated table for mental health tracking
CREATE TABLE IF NOT EXISTS mood_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mood VARCHAR(20) NOT NULL COMMENT 'happy, neutral, stressed, anxious, depressed',
    stress_level INT NOT NULL COMMENT '1-10 scale',
    notes TEXT COMMENT 'Optional user notes',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created_at (created_at),
    INDEX idx_stress_level (stress_level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Emergency Logs Table
-- Track emergency situations and responses
CREATE TABLE IF NOT EXISTS emergency_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    symptoms TEXT NOT NULL COMMENT 'JSON of emergency symptoms',
    status VARCHAR(20) NOT NULL COMMENT 'critical, monitor',
    action TEXT NOT NULL COMMENT 'Recommended action',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created_at (created_at),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Doctors Table
-- Store doctor information for the system
CREATE TABLE IF NOT EXISTS doctors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    specialty VARCHAR(100) NOT NULL,
    experience_years INT NOT NULL,
    hospital VARCHAR(255) NOT NULL,
    location VARCHAR(255) NOT NULL,
    contact VARCHAR(100) NOT NULL,
    rating DECIMAL(2,1) DEFAULT 0.0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_specialty (specialty),
    INDEX idx_experience (experience_years),
    INDEX idx_location (location)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Doctor Recommendations Table
-- Track doctor visit recommendations
CREATE TABLE IF NOT EXISTS doctor_recommendations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    risk_level VARCHAR(20) NOT NULL,
    symptoms_summary TEXT NOT NULL,
    recommendation TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created_at (created_at),
    INDEX idx_risk_level (risk_level)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample data for testing (optional)
-- This can be removed in production

-- Sample symptom log
INSERT INTO symptom_logs (headache, swelling, dizziness, fatigue, baby_movement, mood, urination, thirst, pain, risk_level, detected_conditions, recommendations, pattern_alerts) VALUES
('mild', 'none', 'none', 'mild', 'normal', 'happy', 'normal', 'normal', 'none', 'low', '[]', '["Continue regular monitoring"]', '[]');

-- Sample mood log
INSERT INTO mood_logs (mood, stress_level, notes) VALUES
('happy', 3, 'Feeling good today');

-- Sample emergency log
INSERT INTO emergency_logs (symptoms, status, action) VALUES
('{"baby_movement": "none"}', 'critical', 'Go to hospital immediately');

-- Sample doctor recommendation
INSERT INTO doctor_recommendations (risk_level, symptoms_summary, recommendation) VALUES
('high', 'Severe headache with swelling', 'Consult doctor within 24 hours');
