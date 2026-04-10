# CareBridge Backend System

A comprehensive pregnancy health monitoring backend system built with core PHP, providing intelligent health analysis, pattern recognition, and emergency support.

## 🏗️ Architecture

```
/backend
├── config/
│   └── db.php                 # Database configuration
├── api/
│   ├── analyze.php            # Symptom analysis endpoint
│   ├── log_symptoms.php       # Symptom logging endpoint
│   ├── get_history.php        # History retrieval endpoint
│   ├── emergency.php          # Emergency assessment endpoint
│   └── doctor_recommend.php   # Doctor recommendation endpoint
├── services/
│   ├── risk_engine.php        # Risk analysis engine
│   ├── pattern_engine.php     # Pattern detection engine
│   └── mental_health.php      # Mental health tracking
├── utils/
│   └── response.php           # Response utilities
└── database_schema.sql        # Database schema
```

## 🚀 Setup Instructions

### 1. Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- XAMPP/WAMP (for local development)

### 2. Database Setup

1. Create a new MySQL database:
   ```sql
   CREATE DATABASE carebridge;
   ```

2. Import the database schema:
   ```bash
   mysql -u root -p carebridge < database_schema.sql
   ```

3. Update database configuration in `config/db.php` if needed:
   ```php
   private $host = 'localhost';
   private $username = 'root';
   private $password = '';
   private $database = 'carebridge';
   ```

### 3. Web Server Configuration

#### Apache (.htaccess)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^api/(.*)$ api/$1.php [L,QSA]

# Enable CORS
Header always set Access-Control-Allow-Origin "*"
Header always set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
Header always set Access-Control-Allow-Headers "Content-Type, Authorization"
```

#### Nginx
```nginx
location /api/ {
    try_files $uri $uri/ /api$1.php;
    add_header Access-Control-Allow-Origin "*";
    add_header Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS";
    add_header Access-Control-Allow-Headers "Content-Type, Authorization";
}
```

### 4. Testing the Setup

1. Place the backend folder in your web server's document root
2. Access the API endpoints via your browser or API client
3. Test with: `http://localhost/backend/api/analyze.php`

## 📡 API Endpoints

### 1. Symptom Analysis
**POST** `/api/analyze.php`

Analyzes symptoms and returns risk assessment with recommendations.

**Request Body:**
```json
{
  "headache": "moderate",
  "swelling": "mild",
  "dizziness": "none",
  "fatigue": "moderate",
  "baby_movement": "normal",
  "mood": "stressed",
  "urination": "normal",
  "thirst": "normal",
  "pain": "mild"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Symptom analysis completed successfully",
  "data": {
    "symptoms": { ... },
    "risk_level": "medium",
    "detected_conditions": ["Anemia Risk"],
    "recommendations": ["Possible iron deficiency - consider iron-rich foods"],
    "pattern_alerts": ["Repeated headache for 3 days - Monitor closely"],
    "patterns": { ... },
    "trends": { ... },
    "analysis_timestamp": "2026-04-10 11:43:00"
  }
}
```

### 2. Log Symptoms
**POST** `/api/log_symptoms.php`

Stores symptom data with analysis results in the database.

**Request Body:**
```json
{
  "symptoms": {
    "headache": "moderate",
    "swelling": "mind",
    "dizziness": "none",
    "fatigue": "moderate",
    "baby_movement": "normal",
    "mood": "stressed",
    "urination": "normal",
    "thirst": "normal",
    "pain": "mild"
  },
  "analysis_results": {
    "risk_level": "medium",
    "detected_conditions": ["Anemia Risk"],
    "recommendations": ["Consider iron-rich foods"]
  }
}
```

**Response:**
```json
{
  "success": true,
  "message": "Symptoms logged successfully",
  "data": {
    "log_id": 123,
    "symptoms_logged": { ... },
    "risk_level": "medium",
    "detected_conditions": ["Anemia Risk"],
    "recommendations": ["Consider iron-rich foods"],
    "logged_at": "2026-04-10 11:43:00"
  }
}
```

### 3. Get History
**GET** `/api/get_history.php`

Retrieves symptom history and pattern analysis.

**Query Parameters:**
- `days` (optional): Number of days to retrieve (default: 7)
- `include_mood` (optional): Include mood analysis (default: true)
- `include_patterns` (optional): Include pattern analysis (default: true)

**Example:** `GET /api/get_history.php?days=7&include_mood=true`

**Response:**
```json
{
  "success": true,
  "message": "History retrieved successfully",
  "data": {
    "history": [
      {
        "id": 123,
        "date": "2026-04-10",
        "time": "11:43:00",
        "symptoms": { ... },
        "risk_level": "medium",
        "detected_conditions": ["Anemia Risk"],
        "recommendations": ["Consider iron-rich foods"],
        "pattern_alerts": []
      }
    ],
    "summary": {
      "total_entries": 5,
      "date_range": {
        "start": "2026-04-04",
        "end": "2026-04-10"
      },
      "risk_distribution": {
        "low": 2,
        "medium": 2,
        "high": 1,
        "critical": 0
      },
      "symptom_distribution": { ... }
    },
    "patterns": { ... },
    "mood_analysis": { ... }
  }
}
```

### 4. Emergency Assessment
**POST** `/api/emergency.php`

Fast emergency assessment for critical symptoms.

**Request Body:**
```json
{
  "baby_movement": "none",
  "pain": "severe",
  "headache": "severe"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Emergency assessment completed",
  "data": {
    "status": "critical",
    "action": "Go to hospital immediately - this requires urgent medical attention",
    "conditions": ["No Baby Movement", "Severe Pain with Headache"],
    "urgency": "immediate",
    "assessed_at": "2026-04-10 11:43:00",
    "follow_up_needed": true
  }
}
```

### 5. Doctor Recommendation
**POST** `/api/doctor_recommend.php`

Provides doctor visit recommendations based on risk and symptoms.

**Request Body:**
```json
{
  "risk_level": "high",
  "symptoms": {
    "headache": "severe",
    "swelling": "moderate",
    "pain": "moderate"
  },
  "detected_conditions": ["Preeclampsia Risk"],
  "patterns": {
    "repeated_symptoms": [...],
    "worsening_trends": [...]
  }
}
```

**Response:**
```json
{
  "success": true,
  "message": "Doctor recommendation generated successfully",
  "data": {
    "advice": "Consult doctor within 24 hours",
    "urgency": "high",
    "timeline": "Within 24 hours",
    "reason": "High-risk symptoms detected that need medical evaluation",
    "specific_concerns": ["Preeclampsia Risk"],
    "follow_up_actions": [
      "Call your doctor immediately to schedule an appointment",
      "Monitor symptoms closely until appointment",
      "Go to emergency room if symptoms worsen"
    ]
  }
}
```

## 🔧 Core Services

### Risk Engine (`services/risk_engine.php`)

Intelligent health analysis that:
- Analyzes symptom combinations (not individual checks)
- Detects preeclampsia, anemia, gestational diabetes, dehydration
- Provides contextual recommendations
- Handles emergency conditions

**Key Features:**
- Symptom combination analysis
- Emergency condition detection
- Risk level calculation
- Personalized recommendations

### Pattern Engine (`services/pattern_engine.php`)

Historical data analysis that:
- Detects repeated symptoms over time
- Identifies worsening trends
- Analyzes risk level patterns
- Provides trend insights

**Key Features:**
- 3-7 day historical analysis
- Linear regression trend calculation
- Pattern alert generation
- Statistical metrics

### Mental Health Service (`services/mental_health.php`)

Mental health tracking that:
- Logs mood and stress levels
- Detects stress patterns
- Provides mental health recommendations
- Analyzes mood trends

**Key Features:**
- Mood tracking (1-10 stress scale)
- Pattern detection
- Trend analysis
- Support recommendations

## 📊 Database Schema

### Symptom Logs Table
```sql
CREATE TABLE symptom_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    headache VARCHAR(20) NOT NULL,
    swelling VARCHAR(20) NOT NULL,
    dizziness VARCHAR(20) NOT NULL,
    fatigue VARCHAR(20) NOT NULL,
    baby_movement VARCHAR(20) NOT NULL,
    mood VARCHAR(20) NOT NULL,
    urination VARCHAR(20) NOT NULL,
    thirst VARCHAR(20) NOT NULL,
    pain VARCHAR(20) NOT NULL,
    risk_level VARCHAR(20) NOT NULL,
    detected_conditions TEXT,
    recommendations TEXT,
    pattern_alerts TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Mood Logs Table
```sql
CREATE TABLE mood_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mood VARCHAR(20) NOT NULL,
    stress_level INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## 🛡️ Security Features

- Input validation and sanitization
- SQL injection prevention (prepared statements)
- XSS prevention (output encoding)
- CORS headers for frontend integration
- Error handling and logging

## 🧪 Testing

### Sample API Calls

1. **Test Symptom Analysis:**
   ```bash
   curl -X POST http://localhost/backend/api/analyze.php \
   -H "Content-Type: application/json" \
   -d '{"headache":"moderate","swelling":"mild","dizziness":"none","fatigue":"moderate","baby_movement":"normal","mood":"stressed","urination":"normal","thirst":"normal","pain":"mild"}'
   ```

2. **Test Emergency Assessment:**
   ```bash
   curl -X POST http://localhost/backend/api/emergency.php \
   -H "Content-Type: application/json" \
   -d '{"baby_movement":"none","pain":"severe"}'
   ```

3. **Get History:**
   ```bash
   curl -X GET "http://localhost/backend/api/get_history.php?days=7"
   ```

## 🚨 Emergency Conditions

The system detects these critical conditions:
- **No baby movement** - Immediate hospital visit required
- **Severe pain with headache/swelling** - Emergency room required
- **Severe headache with swelling** - Possible preeclampsia
- **Reduced baby movement** - Contact healthcare provider immediately
- **Severe dizziness** - Medical attention required

## 📈 Risk Levels

- **Low** - Continue regular monitoring
- **Medium** - Schedule doctor appointment within 1-2 weeks
- **High** - Consult doctor within 24 hours
- **Critical** - Seek immediate medical attention

## 🔍 Pattern Detection

The system identifies:
- **Repeated symptoms** - Same symptom for 3+ days
- **Worsening trends** - Increasing severity over time
- **Risk trends** - Increasing/decreasing risk levels
- **Stress patterns** - High stress over multiple days

## 🌐 Frontend Integration

The backend is designed to work seamlessly with the CareBridge frontend:

1. **CORS enabled** for cross-origin requests
2. **JSON responses** for easy frontend consumption
3. **Standardized error format** for consistent handling
4. **Input validation** for data integrity

## 📝 Error Handling

All API endpoints return consistent error responses:

```json
{
  "success": false,
  "message": "Error description",
  "timestamp": "2026-04-10 11:43:00",
  "details": "Additional error details (optional)"
}
```

## 🔄 Response Format

Success responses follow this structure:

```json
{
  "success": true,
  "message": "Operation completed successfully",
  "data": { ... },
  "timestamp": "2026-04-10 11:43:00"
}
```

## 📞 Support

For technical support or questions:
- Check the error logs in your web server
- Verify database connection and permissions
- Ensure all PHP files have correct permissions
- Test with sample API calls provided above

---

**Note:** This backend is designed for educational and demonstration purposes. Always consult with qualified healthcare professionals for medical advice and emergencies.
