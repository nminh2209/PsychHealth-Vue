<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../services/AuthService.php';

class AuthController {
    private $userModel;
    private $authService;

    public function __construct() {
        $this->userModel = new User();
        $this->authService = new AuthService();
    }

    public function register() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields - Updated to match Vue.js frontend
        $required = ['name', 'email', 'password'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                http_response_code(400);
                echo json_encode(['error' => "Field '{$field}' is required"]);
                return;
            }
        }

        // Check if user already exists
        if ($this->userModel->emailExists($input['email'])) {
            http_response_code(409);
            echo json_encode(['error' => 'Email already registered']);
            return;
        }

        // Validate password strength
        if (strlen($input['password']) < 6) { // Changed from 8 to 6 to match Vue validation
            http_response_code(400);
            echo json_encode(['error' => 'Password must be at least 6 characters long']);
            return;
        }

        // Validate email format
        if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid email format']);
            return;
        }

        try {
            $userId = $this->userModel->create($input);
            $user = $this->userModel->findById($userId);
            
            // Remove password hash from response
            unset($user['password']);
            
            // Generate JWT token
            $token = $this->authService->generateToken($user);
            
            // Create user session in database
            $this->createUserSession($user['id'], $token);
            
            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => 'User registered successfully',
                'user' => $user,
                'token' => $token
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Registration failed: ' . $e->getMessage()]);
        }
    }

    public function login() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['email']) || empty($input['password'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Email and password are required']);
            return;
        }

        try {
            // Debug: Log the login attempt
            error_log("Login attempt for email: " . $input['email']);
            
            $user = $this->userModel->findByEmail($input['email']);
            
            // Debug: Log if user was found
            error_log("User found: " . ($user ? 'Yes' : 'No'));
            
            if (!$user) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'error' => 'Invalid credentials - user not found'
                ]);
                return;
            }
            
            if (!$this->userModel->verifyPassword($input['password'], $user['password'])) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'error' => 'Invalid credentials - wrong password'
                ]);
                return;
            }

            // Update last login
            $this->userModel->updateLastLogin($user['id']);
            
            // Remove password from response
            unset($user['password']);
            
            // Generate JWT token
            $token = $this->authService->generateToken($user);
            
            // Create user session in database
            $this->createUserSession($user['id'], $token);
            
            // Debug: Log successful login
            error_log("Login successful for user ID: " . $user['id']);
            
            echo json_encode([
                'success' => true,
                'message' => 'Login successful',
                'user' => $user,
                'token' => $token
            ]);
            
        } catch (Exception $e) {
            // Debug: Log the error
            error_log("Login error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Login failed: ' . $e->getMessage()
            ]);
        }
    }

    private function createUserSession($userId, $token) {
        try {
            // Clean up old sessions for this user
            $this->userModel->db->query(
                "DELETE FROM user_sessions WHERE user_id = :user_id", 
                [':user_id' => $userId]
            );
            
            // Create new session
            $this->userModel->db->query(
                "INSERT INTO user_sessions (user_id, session_token, expires_at) VALUES (:user_id, :token, :expires_at)",
                [
                    ':user_id' => $userId,
                    ':token' => $token,
                    ':expires_at' => date('Y-m-d H:i:s', time() + (24 * 60 * 60)) // 24 hours
                ]
            );
        } catch (Exception $e) {
            // Log error but don't fail the login
            error_log('Failed to create user session: ' . $e->getMessage());
        }
    }

    public function logout() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        // In a more sophisticated system, you might invalidate the token
        // For now, we'll just return success as JWT tokens are stateless
        echo json_encode([
            'success' => true,
            'message' => 'Logout successful'
        ]);
    }

    public function getProfile() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        try {
            $user = $this->authService->getCurrentUser();
            
            if (!$user) {
                http_response_code(401);
                echo json_encode(['error' => 'Unauthorized']);
                return;
            }

            // Remove password
            unset($user['password']);
            
            // Get user statistics from database
            $userId = $user['id'];
            
            // Get posts count
            $postsCountResult = $this->userModel->db->fetch(
                "SELECT COUNT(*) as count FROM posts WHERE user_id = :user_id",
                [':user_id' => $userId]
            );
            $postsCount = $postsCountResult ? $postsCountResult['count'] : 0;
            
            // Get comments count
            $commentsCountResult = $this->userModel->db->fetch(
                "SELECT COUNT(*) as count FROM comments WHERE user_id = :user_id",
                [':user_id' => $userId]
            );
            $commentsCount = $commentsCountResult ? $commentsCountResult['count'] : 0;
            
            // Get test results count (from test_results table)
            $testsCountResult = $this->userModel->db->fetch(
                "SELECT COUNT(*) as count FROM test_results WHERE user_id = :user_id",
                [':user_id' => $userId]
            );
            $testsCount = $testsCountResult ? $testsCountResult['count'] : 0;
            
            // Add statistics to user object
            $user['posts_count'] = (int)$postsCount;
            $user['comments_count'] = (int)$commentsCount;
            $user['tests_taken'] = (int)$testsCount;
            
            echo json_encode([
                'success' => true,
                'user' => $user
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to get profile: ' . $e->getMessage()]);
        }
    }

    public function updateProfile() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        try {
            $user = $this->authService->getCurrentUser();
            
            if (!$user) {
                http_response_code(401);
                echo json_encode(['error' => 'Unauthorized']);
                return;
            }

            $input = json_decode(file_get_contents('php://input'), true);
            
            // Validate required fields
            $required = ['first_name', 'last_name', 'date_of_birth', 'gender'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    http_response_code(400);
                    echo json_encode(['error' => "Field '{$field}' is required"]);
                    return;
                }
            }

            $this->userModel->updateProfile($user['user_id'], $input);
            
            // Get updated user data
            $updatedUser = $this->userModel->findById($user['user_id']);
            unset($updatedUser['password']);
            
            echo json_encode([
                'success' => true,
                'message' => 'Profile updated successfully',
                'user' => $updatedUser
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Profile update failed: ' . $e->getMessage()]);
        }
    }

    public function changePassword() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        try {
            $user = $this->authService->getCurrentUser();
            
            if (!$user) {
                http_response_code(401);
                echo json_encode(['error' => 'Unauthorized']);
                return;
            }

            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['current_password']) || empty($input['new_password'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Current password and new password are required']);
                return;
            }

            // Verify current password
            if (!$this->userModel->verifyPassword($input['current_password'], $user['password'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Current password is incorrect']);
                return;
            }

            // Validate new password
            if (strlen($input['new_password']) < 8) {
                http_response_code(400);
                echo json_encode(['error' => 'New password must be at least 8 characters long']);
                return;
            }

            $this->userModel->changePassword($user['user_id'], $input['new_password']);
            
            echo json_encode([
                'success' => true,
                'message' => 'Password changed successfully'
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Password change failed: ' . $e->getMessage()]);
        }
    }

    public function verifyToken() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        try {
            $user = $this->authService->getCurrentUser();
            
            if (!$user) {
                http_response_code(401);
                echo json_encode(['error' => 'Invalid token']);
                return;
            }

            unset($user['password']);
            
            echo json_encode([
                'success' => true,
                'valid' => true,
                'user' => $user
            ]);
            
        } catch (Exception $e) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'valid' => false,
                'error' => 'Token verification failed'
            ]);
        }
    }

    public function getUserPosts() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        try {
            $user = $this->authService->getCurrentUser();
            
            if (!$user) {
                http_response_code(401);
                echo json_encode(['error' => 'Unauthorized']);
                return;
            }

            // Get user's posts with comment counts
            $posts = $this->userModel->db->fetchAll(
                "SELECT p.id, p.title, p.content, p.category, p.created_at,
                        COUNT(c.id) as comments_count
                 FROM posts p 
                 LEFT JOIN comments c ON p.id = c.post_id 
                 WHERE p.user_id = :user_id 
                 GROUP BY p.id, p.title, p.content, p.category, p.created_at
                 ORDER BY p.created_at DESC",
                [':user_id' => $user['id']]
            );
            
            echo json_encode([
                'success' => true,
                'posts' => $posts ?: []
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to get user posts: ' . $e->getMessage()]);
        }
    }
}
?>
