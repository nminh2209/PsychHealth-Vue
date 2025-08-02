<?php
require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../config/Database.php';

class UserController {
    private $authService;
    private $db;

    public function __construct() {
        $this->authService = new AuthService();
        $this->db = new Database();
    }

    public function getProfile() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        // Check authentication
        $user = $this->authService->getCurrentUser();
        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        try {
            // Get user details with statistics
            $sql = "SELECT u.*, 
                           (SELECT COUNT(*) FROM posts WHERE user_id = u.id) as posts_count,
                           (SELECT COUNT(*) FROM comments WHERE user_id = u.id) as comments_count,
                           (SELECT COUNT(*) FROM test_results WHERE user_id = u.id) as tests_taken
                    FROM users u 
                    WHERE u.id = :user_id";
            
            $profile = $this->db->fetch($sql, [':user_id' => $user['id']]);
            
            // Remove sensitive information
            unset($profile['password']);

            echo json_encode([
                'success' => true,
                'profile' => $profile
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch profile: ' . $e->getMessage()]);
        }
    }

    public function getUserSessions() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        // Check authentication
        $user = $this->authService->getCurrentUser();
        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        try {
            $sql = "SELECT us.*, 
                           CASE WHEN us.expires_at > NOW() THEN 'active' ELSE 'expired' END as status
                    FROM user_sessions us 
                    WHERE us.user_id = :user_id 
                    ORDER BY us.created_at DESC 
                    LIMIT 10";
            
            $sessions = $this->db->fetchAll($sql, [':user_id' => $user['id']]);

            echo json_encode([
                'success' => true,
                'sessions' => $sessions
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch sessions: ' . $e->getMessage()]);
        }
    }

    public function updateProfile() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        // Check authentication
        $user = $this->authService->getCurrentUser();
        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        try {
            $updateFields = [];
            $params = [':user_id' => $user['id']];

            if (!empty($input['name'])) {
                $updateFields[] = "name = :name";
                $params[':name'] = $input['name'];
            }

            if (!empty($input['age'])) {
                $updateFields[] = "age = :age";
                $params[':age'] = $input['age'];
            }

            if (!empty($input['gender'])) {
                $updateFields[] = "gender = :gender";
                $params[':gender'] = $input['gender'];
            }

            if (empty($updateFields)) {
                http_response_code(400);
                echo json_encode(['error' => 'No valid fields to update']);
                return;
            }

            $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = :user_id";
            $this->db->query($sql, $params);

            echo json_encode([
                'success' => true,
                'message' => 'Profile updated successfully'
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update profile: ' . $e->getMessage()]);
        }
    }

    public function getUserPosts() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        // Check authentication
        $user = $this->authService->getCurrentUser();
        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        try {
            $sql = "SELECT p.*, 
                           (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count
                    FROM posts p 
                    WHERE p.user_id = :user_id 
                    ORDER BY p.created_at DESC";
            
            $posts = $this->db->fetchAll($sql, [':user_id' => $user['id']]);

            echo json_encode([
                'success' => true,
                'posts' => $posts
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch user posts: ' . $e->getMessage()]);
        }
    }

    public function revokeSession() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        // Check authentication
        $user = $this->authService->getCurrentUser();
        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['session_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Session ID is required']);
            return;
        }

        try {
            $sql = "UPDATE user_sessions SET expires_at = NOW() 
                    WHERE id = :session_id AND user_id = :user_id";
            
            $this->db->query($sql, [
                ':session_id' => $input['session_id'],
                ':user_id' => $user['id']
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Session revoked successfully'
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to revoke session: ' . $e->getMessage()]);
        }
    }
}

// Handle routing
$action = $_GET['action'] ?? '';

$controller = new UserController();

switch ($action) {
    case 'get_profile':
        $controller->getProfile();
        break;
    case 'update_profile':
        $controller->updateProfile();
        break;
    case 'get_sessions':
        $controller->getUserSessions();
        break;
    case 'revoke_session':
        $controller->revokeSession();
        break;
    case 'get_user_posts':
        $controller->getUserPosts();
        break;
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}
?>
