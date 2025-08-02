<?php
require_once __DIR__ . '/../config/Database.php';

class TestResult {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function saveResult($resultData) {
        $sql = "INSERT INTO test_results (user_id, test_id, responses, raw_score, severity_level, interpretation) 
                VALUES (:user_id, :test_id, :responses, :raw_score, :severity_level, :interpretation)";
        
        $params = [
            ':user_id' => $resultData['user_id'],
            ':test_id' => $resultData['test_id'] ?? null,
            ':responses' => json_encode($resultData['responses']),
            ':raw_score' => $resultData['score'],
            ':severity_level' => $resultData['severity_level'] ?? 'minimal',
            ':interpretation' => $resultData['interpretation'] ?? null
        ];

        try {
            $this->db->query($sql, $params);
            return $this->db->getConnection()->lastInsertId();
        } catch (Exception $e) {
            throw new Exception("Result save failed: " . $e->getMessage());
        }
    }

    public function getUserResults($userId, $limit = 10, $offset = 0) {
        $sql = "SELECT tr.*, mht.name as test_name, mht.type as test_type 
                FROM test_results tr
                LEFT JOIN mental_health_tests mht ON tr.test_id = mht.id
                WHERE tr.user_id = :user_id
                ORDER BY tr.completed_at DESC
                LIMIT :limit OFFSET :offset";
        
        return $this->db->fetchAll($sql, [
            ':user_id' => $userId,
            ':limit' => $limit,
            ':offset' => $offset
        ]);
    }

    public function getUserResultsByTestType($userId, $testType, $limit = 5) {
        $sql = "SELECT tr.*, mht.name as test_name 
                FROM test_results tr
                LEFT JOIN mental_health_tests mht ON tr.test_id = mht.id
                WHERE tr.user_id = :user_id AND mht.type = :test_type
                ORDER BY tr.completed_at DESC
                LIMIT :limit";
        
        return $this->db->fetchAll($sql, [
            ':user_id' => $userId,
            ':test_type' => $testType,
            ':limit' => $limit
        ]);
    }

    public function getResultById($resultId) {
        $sql = "SELECT tr.*, mht.name as test_name, mht.type as test_type
                FROM test_results tr
                LEFT JOIN mental_health_tests mht ON tr.test_id = mht.id
                WHERE tr.id = :result_id";
        
        return $this->db->fetch($sql, [':result_id' => $resultId]);
    }

    public function getProgressData($userId, $testType, $months = 6) {
        $sql = "SELECT tr.raw_score, tr.severity_level, tr.completed_at
                FROM test_results tr
                JOIN mental_health_tests mht ON tr.test_id = mht.id
                WHERE tr.user_id = :user_id 
                AND mht.type = :test_type 
                AND tr.completed_at >= DATE_SUB(NOW(), INTERVAL :months MONTH)
                ORDER BY tr.completed_at ASC";
        
        return $this->db->fetchAll($sql, [
            ':user_id' => $userId,
            ':test_type' => $testType,
            ':months' => $months
        ]);
    }

    public function getLatestResult($userId, $testType) {
        $sql = "SELECT tr.*, mht.name as test_name
                FROM test_results tr
                JOIN mental_health_tests mht ON tr.test_id = mht.id
                WHERE tr.user_id = :user_id AND mht.type = :test_type
                ORDER BY tr.completed_at DESC
                LIMIT 1";
        
        return $this->db->fetch($sql, [
            ':user_id' => $userId,
            ':test_type' => $testType
        ]);
    }

    public function calculateScore($answers, $testType) {
        $totalScore = 0;
        
        switch ($testType) {
            case 'depression':
            case 'anxiety':
                // PHQ-9 and GAD-7 use simple sum
                $totalScore = array_sum($answers);
                break;
                
            case 'stress':
                // PSS has reverse scoring for items 4, 5, 7, 8 (0-indexed: 3, 4, 6, 7)
                $reverseItems = [3, 4, 6, 7];
                foreach ($answers as $index => $answer) {
                    if (in_array($index, $reverseItems)) {
                        $totalScore += (4 - $answer); // Reverse score
                    } else {
                        $totalScore += $answer;
                    }
                }
                break;
                
            default:
                $totalScore = array_sum($answers);
        }
        
        return $totalScore;
    }

    public function interpretScore($score, $testType) {
        $interpretations = [
            'depression' => [
                'minimal' => [0, 4],
                'mild' => [5, 9],
                'moderate' => [10, 14],
                'moderately_severe' => [15, 19],
                'severe' => [20, 27]
            ],
            'anxiety' => [
                'minimal' => [0, 4],
                'mild' => [5, 9],
                'moderate' => [10, 14],
                'severe' => [15, 21]
            ],
            'stress' => [
                'low' => [0, 13],
                'moderate' => [14, 26],
                'high' => [27, 40]
            ]
        ];

        if (!isset($interpretations[$testType])) {
            return 'unknown';
        }

        foreach ($interpretations[$testType] as $level => $range) {
            if ($score >= $range[0] && $score <= $range[1]) {
                return $level;
            }
        }

        return 'unknown';
    }

    public function deleteResult($resultId, $userId) {
        $sql = "DELETE FROM test_results WHERE id = :result_id AND user_id = :user_id";
        try {
            $this->db->query($sql, [
                ':result_id' => $resultId,
                ':user_id' => $userId
            ]);
            return true;
        } catch (Exception $e) {
            throw new Exception("Result deletion failed: " . $e->getMessage());
        }
    }

    public function getUserStats($userId) {
        $sql = "SELECT 
                    COUNT(*) as total_tests,
                    COUNT(DISTINCT mht.type) as test_types_taken,
                    AVG(tr.raw_score) as average_score,
                    MAX(tr.completed_at) as last_test_date
                FROM test_results tr
                JOIN mental_health_tests mht ON tr.test_id = mht.id
                WHERE tr.user_id = :user_id";
        
        return $this->db->fetch($sql, [':user_id' => $userId]);
    }
}
?>
