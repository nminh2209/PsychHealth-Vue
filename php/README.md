# PsychHealth PHP Backend

This is the PHP backend API for the PsychHealth mental health platform, built to work with the Vue.js frontend.

## Features

- **User Authentication**: JWT-based authentication system
- **Mental Health Tests**: PHQ-9, GAD-7, and Stress assessments
- **Test Results**: Score calculation and progress tracking
- **RESTful API**: Clean API endpoints for frontend integration
- **Database Integration**: MySQL database with proper relationships
- **Security**: Password hashing, input validation, CORS support

## Directory Structure

```
php/
├── api/
│   └── index.php          # Main API router
├── config/
│   └── Database.php       # Database connection class
├── controllers/
│   ├── AuthController.php # Authentication endpoints
│   └── TestController.php # Mental health test endpoints
├── models/
│   ├── User.php          # User model
│   ├── MentalHealthTest.php # Test model
│   └── TestResult.php    # Result model
├── services/
│   └── AuthService.php   # JWT and authentication utilities
└── database/
    ├── psychhealth.sql   # Database schema
    └── seed.php          # Database seeder
```

## Setup Instructions

### 1. Database Setup

1. Create a MySQL database named `psychhealth`
2. Import the database schema:
   ```bash
   mysql -u root -p psychhealth < database/psychhealth.sql
   ```
3. Update database credentials in `config/Database.php`

### 2. Web Server Configuration

**Option A: XAMPP/WAMP**
1. Copy the `php` folder to your web server root
2. Access via `http://localhost/psychhealth-vue/php/api/`

**Option B: PHP Built-in Server**
```bash
cd php/api
php -S localhost:8000
```

### 3. Seed Sample Data

Run the database seeder to add sample tests and users:

**Via Web Browser:**
```
http://localhost/psychhealth-vue/php/database/seed.php?run=seed
```

**Via Command Line:**
```bash
cd php/database
php seed.php
```

### 4. Configuration

Update the following configuration files:

**config/Database.php:**
```php
private $host = 'localhost';
private $database = 'psychhealth';
private $username = 'root';
private $password = 'your_password';
```

**services/AuthService.php:**
```php
private $secretKey = 'your-secret-key-here'; // Change in production
```

**api/index.php:**
```php
header('Access-Control-Allow-Origin: http://localhost:3000'); // Your Vue app URL
```

## API Endpoints

### Authentication
- `POST /api/auth?action=register` - User registration
- `POST /api/auth?action=login` - User login
- `POST /api/auth?action=logout` - User logout
- `GET /api/auth?action=profile` - Get user profile
- `PUT /api/auth?action=update-profile` - Update profile
- `PUT /api/auth?action=change-password` - Change password
- `POST /api/auth?action=verify-token` - Verify JWT token

### Mental Health Tests
- `GET /api/tests?action=get-all` - Get all available tests
- `GET /api/tests?action=get-test&type=depression` - Get specific test
- `POST /api/tests?action=submit` - Submit test answers
- `GET /api/tests?action=user-results` - Get user's test results
- `GET /api/tests?action=progress&test_type=depression` - Get progress data
- `GET /api/tests?action=stats` - Get user statistics

### Health Check
- `GET /api/health` - API health status

## Sample Usage

### Register a New User
```javascript
const response = await fetch('http://localhost:8000/auth?action=register', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
  },
  body: JSON.stringify({
    username: 'testuser',
    email: 'test@example.com',
    password: 'SecurePassword123',
    first_name: 'Test',
    last_name: 'User',
    date_of_birth: '1990-01-01',
    gender: 'prefer_not_to_say'
  })
});

const data = await response.json();
console.log(data.token); // JWT token for authentication
```

### Submit a Test
```javascript
const response = await fetch('http://localhost:8000/tests?action=submit', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': `Bearer ${token}`
  },
  body: JSON.stringify({
    test_id: 1,
    answers: [1, 2, 1, 3, 0, 2, 1, 0, 0] // PHQ-9 answers (0-3 scale)
  })
});

const result = await response.json();
console.log(result.result.severity_level); // e.g., "mild"
```

## Sample Test Data

The seeder creates three default tests:

1. **PHQ-9 Depression Assessment** (test_type: 'depression')
   - 9 questions, 0-3 scale each
   - Total score: 0-27
   - Levels: minimal (0-4), mild (5-9), moderate (10-14), moderately severe (15-19), severe (20-27)

2. **GAD-7 Anxiety Assessment** (test_type: 'anxiety')
   - 7 questions, 0-3 scale each
   - Total score: 0-21
   - Levels: minimal (0-4), mild (5-9), moderate (10-14), severe (15-21)

3. **Perceived Stress Scale** (test_type: 'stress')
   - 10 questions, 0-4 scale each
   - Reverse scoring for positive items
   - Levels: low (0-13), moderate (14-26), high (27-40)

## Security Features

- **Password Hashing**: Uses PHP's `password_hash()` with bcrypt
- **JWT Authentication**: Stateless token-based authentication
- **Input Validation**: Sanitization and validation of all inputs
- **SQL Injection Prevention**: Prepared statements throughout
- **CORS Support**: Configured for frontend integration
- **Error Handling**: Comprehensive error handling and logging

## Development Notes

1. **Error Reporting**: Enabled in development, disable in production
2. **CORS Configuration**: Update allowed origins for production
3. **JWT Secret**: Change the secret key in production
4. **Database Credentials**: Use environment variables in production
5. **HTTPS**: Use HTTPS in production for secure token transmission

## Integration with Vue.js

The API is designed to work seamlessly with the Vue.js frontend:

1. **CORS Headers**: Pre-configured for `localhost:3000`
2. **JSON Responses**: All endpoints return JSON
3. **RESTful Design**: Standard HTTP methods and status codes
4. **Error Handling**: Consistent error response format
5. **Token Authentication**: JWT tokens for stateless authentication

## Testing

Sample credentials for testing:
- **Email**: `test@example.com`
- **Password**: `TestPassword123`
- **Email**: `demo@psychhealth.com`
- **Password**: `DemoPassword456`

## Production Deployment

1. Update database credentials
2. Change JWT secret key
3. Configure proper CORS origins
4. Disable error reporting
5. Use HTTPS
6. Set up proper logging
7. Configure rate limiting
8. Use environment variables for sensitive data
