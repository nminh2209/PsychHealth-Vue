<?php
require_once __DIR__ . '/../models/MentalHealthTest.php';
require_once __DIR__ . '/../models/TestResult.php';
require_once __DIR__ . '/../services/AuthService.php';

class TestController {
    private $testModel;
    private $resultModel;
    private $authService;

    public function __construct() {
        $this->testModel = new MentalHealthTest();
        $this->resultModel = new TestResult();
        $this->authService = new AuthService();
    }

    public function getAllTests() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        try {
            $tests = $this->testModel->getAllTests();
            
            // Parse JSON fields for frontend
            foreach ($tests as &$test) {
                $test['questions'] = json_decode($test['questions'], true);
                $test['interpretation_guide'] = json_decode($test['interpretation_guide'], true);
            }
            
            echo json_encode([
                'success' => true,
                'tests' => $tests
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch tests: ' . $e->getMessage()]);
        }
    }

    public function getTest() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            return;
        }

        $testId = $_GET['id'] ?? null;
        $testType = $_GET['type'] ?? null;

        if (!$testId && !$testType) {
            http_response_code(400);
            echo json_encode(['error' => 'Test ID or type is required']);
            return;
        }

        try {
            if ($testId) {
                $test = $this->testModel->getTestById($testId);
            } else {
                $test = $this->testModel->getTestByType($testType);
            }

            if (!$test) {
                http_response_code(404);
                echo json_encode(['error' => 'Test not found']);
                return;
            }

            // Parse JSON fields
            $test['questions'] = json_decode($test['questions'], true);
            $test['interpretation_guide'] = json_decode($test['interpretation_guide'], true);
            
            echo json_encode([
                'success' => true,
                'test' => $test
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch test: ' . $e->getMessage()]);
        }
    }

    public function submitTest() {
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
                echo json_encode(['error' => 'Unauthorized']);
                return;
            }

            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['test_id']) || empty($input['answers'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Test ID and answers are required']);
                return;
            }

            // Get test details
            $test = $this->testModel->getTestById($input['test_id']);
            if (!$test) {
                http_response_code(404);
                echo json_encode(['error' => 'Test not found']);
                return;
            }

            // Debug logging
            error_log("Test found: " . json_encode($test));
            error_log("Input data: " . json_encode($input));

            // Calculate score and interpretation
            $totalScore = $this->resultModel->calculateScore($input['answers'], $test['type']);
            error_log("Calculated score: " . $totalScore);
            
            $severityLevel = $this->resultModel->interpretScore($totalScore, $test['type']);
            error_log("Severity level: " . $severityLevel);
            
            // Get interpretation text
            $interpretationGuide = json_decode($test['interpretation_guide'], true);
            $interpretation = $this->getInterpretationText($severityLevel, $test['type']);
            error_log("Interpretation: " . $interpretation);

            // Save result
            $resultData = [
                'user_id' => $user['id'], // Fixed: use 'id' instead of 'user_id'
                'test_id' => $input['test_id'],
                'test_type' => $input['test_type'] ?? $test['type'], // Add test_type
                'score' => $totalScore, // Changed from 'total_score' to 'score'
                'responses' => $input['answers'], // Changed from 'answers' to 'responses'
                'severity_level' => $severityLevel,
                'interpretation' => $interpretation
            ];

            error_log("Result data: " . json_encode($resultData));

            $resultId = $this->resultModel->saveResult($resultData);
            error_log("Result saved with ID: " . $resultId);
            
            echo json_encode([
                'success' => true,
                'message' => 'Test submitted successfully',
                'result' => [
                    'result_id' => $resultId,
                    'total_score' => $totalScore,
                    'severity_level' => $severityLevel,
                    'interpretation' => $interpretation,
                    'test_name' => $test['name']
                ]
            ]);
            
        } catch (Exception $e) {
            error_log("Test submission error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            http_response_code(500);
            echo json_encode(['error' => 'Test submission failed: ' . $e->getMessage()]);
        }
    }

    public function getUserResults() {
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

            $limit = intval($_GET['limit'] ?? 10);
            $offset = intval($_GET['offset'] ?? 0);
            $testType = $_GET['test_type'] ?? null;

            if ($testType) {
                $results = $this->resultModel->getUserResultsByTestType($user['id'], $testType, $limit);
            } else {
                $results = $this->resultModel->getUserResults($user['id'], $limit, $offset);
            }

            // Parse JSON answers for each result
            foreach ($results as &$result) {
                $result['answers'] = json_decode($result['answers'], true);
            }
            
            echo json_encode([
                'success' => true,
                'results' => $results
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch results: ' . $e->getMessage()]);
        }
    }

    public function getProgressData() {
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

            $testType = $_GET['test_type'] ?? null;
            $months = intval($_GET['months'] ?? 6);

            if (!$testType) {
                http_response_code(400);
                echo json_encode(['error' => 'Test type is required']);
                return;
            }

            $progressData = $this->resultModel->getProgressData($user['user_id'], $testType, $months);
            
            echo json_encode([
                'success' => true,
                'progress_data' => $progressData,
                'test_type' => $testType,
                'months' => $months
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch progress data: ' . $e->getMessage()]);
        }
    }

    public function getUserStats() {
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

            $stats = $this->resultModel->getUserStats($user['user_id']);
            
            echo json_encode([
                'success' => true,
                'stats' => $stats
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch user stats: ' . $e->getMessage()]);
        }
    }

    private function getInterpretationText($severityLevel, $testType) {
        $interpretations = [
            'depression' => [
                'minimal' => 'Your responses suggest minimal or no depression symptoms. Continue maintaining good mental health practices.',
                'mild' => 'Your responses indicate mild depression symptoms. Consider speaking with a healthcare professional or counselor.',
                'moderate' => 'Your responses suggest moderate depression symptoms. We recommend consulting with a mental health professional.',
                'moderately_severe' => 'Your responses indicate moderately severe depression symptoms. Please consider seeking professional help soon.',
                'severe' => 'Your responses suggest severe depression symptoms. We strongly recommend seeking immediate professional help.'
            ],
            'anxiety' => [
                'minimal' => 'Your responses suggest minimal anxiety symptoms. Keep up the good work with stress management.',
                'mild' => 'Your responses indicate mild anxiety symptoms. Consider relaxation techniques or speaking with a counselor.',
                'moderate' => 'Your responses suggest moderate anxiety symptoms. Professional support may be beneficial.',
                'severe' => 'Your responses indicate severe anxiety symptoms. We recommend seeking professional help.'
            ],
            'stress' => [
                'low' => 'Your perceived stress level is low. You seem to be managing life\'s challenges well.',
                'moderate' => 'Your perceived stress level is moderate. Consider stress management techniques and self-care practices.',
                'high' => 'Your perceived stress level is high. Consider seeking support and implementing stress reduction strategies.'
            ]
        ];

        return $interpretations[$testType][$severityLevel] ?? 'Please consult with a mental health professional for proper interpretation.';
    }
}

// Handle the request
if (isset($_GET['action'])) {
    $controller = new TestController();
    $action = $_GET['action'];
    
    switch ($action) {
        case 'get-all':
            $controller->getAllTests();
            break;
        case 'get-test':
            $controller->getTest();
            break;
        case 'submit':
            $controller->submitTest();
            break;
        case 'user-results':
            $controller->getUserResults();
            break;
        case 'progress':
            $controller->getProgressData();
            break;
        case 'stats':
            $controller->getUserStats();
            break;
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Action not found']);
            break;
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'No action specified']);
}
?>
