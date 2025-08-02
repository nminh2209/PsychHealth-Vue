<?php
require_once __DIR__ . '/../models/User.php';

class AuthService {
    private $userModel;
    private $secretKey = 'your-secret-key-change-this-in-production'; // Change this in production

    public function __construct() {
        $this->userModel = new User();
    }

    public function generateToken($user) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode([
            'iss' => 'psychhealth-vue',
            'aud' => 'psychhealth-vue',
            'iat' => time(),
            'exp' => time() + (24 * 60 * 60), // 24 hours
            'user_id' => $user['id'],
            'email' => $user['email'],
            'name' => $user['name']
        ]);

        $headerEncoded = $this->base64UrlEncode($header);
        $payloadEncoded = $this->base64UrlEncode($payload);

        $signature = hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, $this->secretKey, true);
        $signatureEncoded = $this->base64UrlEncode($signature);

        return $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
    }

    public function verifyToken($token) {
        if (!$token) {
            return false;
        }

        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }

        list($headerEncoded, $payloadEncoded, $signatureEncoded) = $parts;

        // Verify signature
        $signature = hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, $this->secretKey, true);
        $expectedSignature = $this->base64UrlEncode($signature);

        if ($signatureEncoded !== $expectedSignature) {
            return false;
        }

        // Decode payload
        $payload = json_decode($this->base64UrlDecode($payloadEncoded), true);

        // Check expiration
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return false;
        }

        return $payload;
    }

    public function getTokenFromRequest() {
        // Try multiple ways to get the token
        
        // Method 1: Authorization header
        $headers = $this->getAllHeaders();
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
            if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                return trim($matches[1]);
            }
        }
        
        // Method 2: Check HTTP_AUTHORIZATION
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
            if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                return trim($matches[1]);
            }
        }

        // Method 3: Check for token in GET/POST parameters as fallback
        return $_GET['token'] ?? $_POST['token'] ?? null;
    }

    public function getCurrentUser() {
        $token = $this->getTokenFromRequest();
        
        if (!$token) {
            error_log("No token found in request");
            return null;
        }

        $payload = $this->verifyToken($token);
        
        if (!$payload) {
            error_log("Token verification failed");
            return null;
        }

        // Get fresh user data from database
        try {
            $user = $this->userModel->findById($payload['user_id']);
            if (!$user) {
                error_log("User not found for ID: " . $payload['user_id']);
                return null;
            }
            return $user;
        } catch (Exception $e) {
            error_log("Error fetching user: " . $e->getMessage());
            return null;
        }
    }

    public function requireAuth() {
        $user = $this->getCurrentUser();
        
        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            exit;
        }

        return $user;
    }

    private function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode($data) {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }

    private function getAllHeaders() {
        if (function_exists('getallheaders')) {
            return getallheaders();
        }

        // Fallback for servers that don't support getallheaders()
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }

    public function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }

    public function generateSecureToken($length = 32) {
        return bin2hex(random_bytes($length));
    }

    public function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public function isStrongPassword($password) {
        // At least 8 characters, contains uppercase, lowercase, number
        return strlen($password) >= 8 &&
               preg_match('/[A-Z]/', $password) &&
               preg_match('/[a-z]/', $password) &&
               preg_match('/\d/', $password);
    }

    public function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map([$this, 'sanitizeInput'], $input);
        }
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }

    public function validateUserData($data, $isUpdate = false) {
        $errors = [];

        // Required fields for registration
        if (!$isUpdate) {
            $required = ['username', 'email', 'password', 'first_name', 'last_name'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    $errors[] = "Field '{$field}' is required";
                }
            }
        }

        // Email validation
        if (!empty($data['email']) && !$this->isValidEmail($data['email'])) {
            $errors[] = 'Invalid email format';
        }

        // Password validation (only for registration or password change)
        if (!empty($data['password']) && !$this->isStrongPassword($data['password'])) {
            $errors[] = 'Password must be at least 8 characters long and contain uppercase, lowercase, and numeric characters';
        }

        // Username validation
        if (!empty($data['username'])) {
            if (strlen($data['username']) < 3) {
                $errors[] = 'Username must be at least 3 characters long';
            }
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $data['username'])) {
                $errors[] = 'Username can only contain letters, numbers, and underscores';
            }
        }

        // Date of birth validation
        if (!empty($data['date_of_birth'])) {
            $date = DateTime::createFromFormat('Y-m-d', $data['date_of_birth']);
            if (!$date || $date->format('Y-m-d') !== $data['date_of_birth']) {
                $errors[] = 'Invalid date of birth format (YYYY-MM-DD required)';
            } else {
                $age = $date->diff(new DateTime())->y;
                if ($age < 13) {
                    $errors[] = 'User must be at least 13 years old';
                }
                if ($age > 120) {
                    $errors[] = 'Invalid date of birth';
                }
            }
        }

        // Gender validation
        if (!empty($data['gender']) && !in_array($data['gender'], ['male', 'female', 'other', 'prefer_not_to_say'])) {
            $errors[] = 'Invalid gender value';
        }

        return $errors;
    }
}
?>
