<?php
require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/Comment.php';

class CommunityController {
    private $authService;
    private $db;
    private $commentModel;

    public function __construct() {
        $this->authService = new AuthService();
        $this->db = new Database();
        $this->commentModel = new Comment();
    }

    public function createPost() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        // Check authentication
        $user = $this->authService->getCurrentUser();
        if (!$user) {
            error_log("Authentication failed in createPost");
            http_response_code(401);
            echo json_encode([
                'error' => 'Unauthorized', 
                'message' => 'Please log in to create posts'
            ]);
            return;
        }

        error_log("User authenticated: " . json_encode($user));

        $input = json_decode(file_get_contents('php://input'), true);
        
        if (empty($input['title']) || empty($input['content']) || empty($input['category'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Title, content, and category are required']);
            return;
        }

        try {
            // Map category string to category_id if needed, otherwise use the category column
            $categoryMapping = [
                'anxiety' => 1,
                'depression' => 2, 
                'stress' => 3,
                'support' => 4,
                'success' => 5,
                'resources' => 6
            ];
            
            $categoryId = $categoryMapping[$input['category']] ?? null;
            
            // Insert with both category_id (if mapped) and category string for compatibility
            $sql = "INSERT INTO posts (user_id, category_id, title, content, category) VALUES (:user_id, :category_id, :title, :content, :category)";
            $this->db->query($sql, [
                ':user_id' => $user['id'],
                ':category_id' => $categoryId,
                ':title' => $input['title'],
                ':content' => $input['content'],
                ':category' => $input['category']
            ]);

            $postId = $this->db->getConnection()->lastInsertId();

            echo json_encode([
                'success' => true,
                'post_id' => $postId,
                'message' => 'Post created successfully'
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create post: ' . $e->getMessage()]);
        }
    }

    public function getPosts() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        try {
            // Get posts with accurate comment counts
            $sql = "SELECT p.*, u.name as author_name, 
                           (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) as comments_count
                    FROM posts p 
                    LEFT JOIN users u ON p.user_id = u.id 
                    ORDER BY p.created_at DESC 
                    LIMIT 50";
            
            $posts = $this->db->fetchAll($sql);

            echo json_encode([
                'success' => true,
                'posts' => $posts
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch posts: ' . $e->getMessage()]);
        }
    }

    public function createComment() {
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
        
        if (empty($input['post_id']) || empty($input['content'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Post ID and content are required']);
            return;
        }

        try {
            $commentId = $this->commentModel->create([
                'post_id' => $input['post_id'],
                'user_id' => $user['id'],
                'content' => $input['content'],
                'is_anonymous' => $input['is_anonymous'] ?? false
            ]);

            echo json_encode([
                'success' => true,
                'comment_id' => $commentId,
                'message' => 'Comment created successfully'
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create comment: ' . $e->getMessage()]);
        }
    }

    public function getComments() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        $postId = $_GET['post_id'] ?? null;
        
        if (!$postId) {
            http_response_code(400);
            echo json_encode(['error' => 'Post ID is required']);
            return;
        }

        try {
            $comments = $this->commentModel->getByPostId($postId);

            echo json_encode([
                'success' => true,
                'comments' => $comments
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch comments: ' . $e->getMessage()]);
        }
    }

    public function toggleLike() {
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
        
        if (empty($input['post_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Post ID is required']);
            return;
        }

        try {
            // For now, just return success - you can implement likes table later
            echo json_encode([
                'success' => true,
                'message' => 'Like toggled successfully'
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to toggle like: ' . $e->getMessage()]);
        }
    }
}
?>
