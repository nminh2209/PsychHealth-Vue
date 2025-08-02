<?php
// Enable CORS for Vue.js frontend
header('Access-Control-Allow-Origin: *'); // Allow all origins for development
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Error reporting for development (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable HTML error display for JSON API
ini_set('log_errors', 1); // Log errors instead

// Custom error handler for JSON responses
set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return;
    }
    http_response_code(500);
    echo json_encode([
        'error' => 'PHP Error',
        'message' => $message,
        'file' => basename($file),
        'line' => $line
    ]);
    exit;
});

// Exception handler for uncaught exceptions
set_exception_handler(function($exception) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Uncaught Exception',
        'message' => $exception->getMessage(),
        'file' => basename($exception->getFile()),
        'line' => $exception->getLine()
    ]);
    exit;
});

// Set default timezone
date_default_timezone_set('Australia/Melbourne');

// Set default charset
ini_set('default_charset', 'UTF-8');

// Basic routing - handle both direct access and subdirectory access
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);

// Remove both possible base paths
$path = str_replace('/psychhealth-vue/php/api', '', $path);
$path = str_replace('/php/api', '', $path);

// Remove leading slash and handle index.php
$path = ltrim($path, '/');
if ($path === 'index.php' || $path === '') {
    $path = '';
}

// Get controller from module parameter first, then URL path
$segments = explode('/', $path);
$controller = $_GET['module'] ?? ($segments[0] !== '' ? $segments[0] : '');
$action = $_GET['action'] ?? '';

// Debug output for troubleshooting
if (isset($_GET['debug'])) {
    echo json_encode([
        'debug' => true,
        'request_uri' => $request_uri,
        'path' => $path,
        'segments' => $segments,
        'controller' => $controller,
        'action' => $action,
        'get_params' => $_GET
    ]);
    exit;
}

try {
    switch ($controller) {
        case 'auth':
            // Auth controller needs manual routing
            require_once '../controllers/AuthController.php';
            $authController = new AuthController();
            
            switch ($action) {
                case 'register':
                    $authController->register();
                    break;
                case 'login':
                    $authController->login();
                    break;
                case 'logout':
                    $authController->logout();
                    break;
                case 'profile':
                    $authController->getProfile();
                    break;
                case 'update-profile':
                    $authController->updateProfile();
                    break;
                case 'change-password':
                    $authController->changePassword();
                    break;
                case 'verify-token':
                    $authController->verifyToken();
                    break;
                case 'user-posts':
                    $authController->getUserPosts();
                    break;
                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Auth action not found: ' . $action]);
                    break;
            }
            break;
            
        case 'tests':
            // Tests controller needs manual routing
            require_once '../controllers/TestController.php';
            $testController = new TestController();
            
            switch ($action) {
                case 'get_all':
                    $testController->getAllTests();
                    break;
                case 'get_test':
                    $testController->getTest();
                    break;
                case 'submit':
                    $testController->submitTest();
                    break;
                case 'get_results':
                    $testController->getUserResults();
                    break;
                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Tests action not found: ' . $action]);
                    break;
            }
            break;
            
        case 'community':
            // Community controller needs manual routing
            require_once '../controllers/CommunityController.php';
            $communityController = new CommunityController();
            
            switch ($action) {
                case 'create_post':
                    $communityController->createPost();
                    break;
                case 'get_posts':
                    $communityController->getPosts();
                    break;
                case 'get_post':
                    $communityController->getPost();
                    break;
                case 'update_post':
                    $communityController->updatePost();
                    break;
                case 'delete_post':
                    $communityController->deletePost();
                    break;
                case 'create_comment':
                    $communityController->createComment();
                    break;
                case 'get_comments':
                    $communityController->getComments();
                    break;
                case 'toggle_like':
                    $communityController->toggleLike();
                    break;
                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Community action not found: ' . $action]);
                    break;
            }
            break;
            
        case 'user':
            require_once '../controllers/UserController.php';
            // UserController handles its own routing
            break;
            
        case 'resources':
            require_once '../controllers/ResourceController.php';
            // ResourceController handles its own routing
            break;
            
        case 'test':
            // Database and system test endpoint
            header('Content-Type: application/json');
            try {
                if ($action === 'db') {
                    require_once '../config/Database.php';
                    $db = new Database();
                    $connection = $db->getConnection();
                    
                    // Test basic query
                    $stmt = $connection->prepare("SELECT COUNT(*) as count FROM users");
                    $stmt->execute();
                    $result = $stmt->fetch();
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Database connection successful',
                        'users_count' => $result['count'],
                        'timestamp' => date('Y-m-d H:i:s')
                    ]);
                } else {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Test endpoint working',
                        'timestamp' => date('Y-m-d H:i:s'),
                        'available_tests' => ['db']
                    ]);
                }
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'error' => $e->getMessage(),
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
            }
            break;
            
        case 'health':
            // Health check endpoint
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'healthy',
                'timestamp' => date('Y-m-d H:i:s'),
                'version' => '1.0.0'
            ]);
            break;
            
        default:
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Endpoint not found',
                'path' => $path,
                'available_endpoints' => [
                    'auth' => [
                        'register', 'login', 'logout', 'profile', 
                        'update-profile', 'change-password', 'verify-token'
                    ],
                    'tests' => [
                        'get-all', 'get-test', 'submit', 
                        'user-results', 'progress', 'stats'
                    ],
                    'community' => [
                        'posts', 'create-post', 'get-post', 
                        'update-post', 'delete-post', 'comments'
                    ],
                    'resources' => [
                        'get-all', 'get-resource', 'create', 
                        'update', 'delete', 'categories'
                    ]
                ]
            ]);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
