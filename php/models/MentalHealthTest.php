<?php
require_once __DIR__ . '/../config/Database.php';

class MentalHealthTest {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function getAllTests() {
        $sql = "SELECT * FROM mental_health_tests ORDER BY created_at DESC";
        return $this->db->fetchAll($sql);
    }

    public function getTestById($testId) {
        $sql = "SELECT * FROM mental_health_tests WHERE id = :test_id";
        return $this->db->fetch($sql, [':test_id' => $testId]);
    }

    public function getTestByType($testType) {
        $sql = "SELECT * FROM mental_health_tests WHERE type = :test_type";
        return $this->db->fetch($sql, [':test_type' => $testType]);
    }

    public function createTest($testData) {
        $sql = "INSERT INTO mental_health_tests (name, type, description, max_score) 
                VALUES (:name, :type, :description, :max_score)";
        
        $params = [
            ':name' => $testData['name'],
            ':type' => $testData['type'],
            ':description' => $testData['description'],
            ':max_score' => $testData['max_score'] ?? 27
        ];

        try {
            $this->db->query($sql, $params);
            return $this->db->getConnection()->lastInsertId();
        } catch (Exception $e) {
            throw new Exception("Test creation failed: " . $e->getMessage());
        }
    }

    public function updateTest($testId, $testData) {
        $sql = "UPDATE mental_health_tests SET 
                name = :name,
                type = :type,
                description = :description,
                max_score = :max_score,
                updated_at = CURRENT_TIMESTAMP
                WHERE id = :test_id";

        $params = [
            ':name' => $testData['name'],
            ':type' => $testData['type'],
            ':description' => $testData['description'],
            ':max_score' => $testData['max_score'] ?? 27,
            ':test_id' => $testId
        ];

        try {
            $this->db->query($sql, $params);
            return true;
        } catch (Exception $e) {
            throw new Exception("Test update failed: " . $e->getMessage());
        }
    }

    public function deleteTest($testId) {
        $sql = "DELETE FROM mental_health_tests WHERE id = :test_id";
        try {
            $this->db->query($sql, [':test_id' => $testId]);
            return true;
        } catch (Exception $e) {
            throw new Exception("Test deletion failed: " . $e->getMessage());
        }
    }

    // Get default tests data for seeding
    public function getDefaultTests() {
        return [
            [
                'name' => 'PHQ-9 Depression Assessment',
                'type' => 'depression',
                'description' => 'Patient Health Questionnaire-9 for depression screening',
                'questions' => [
                    'Little interest or pleasure in doing things',
                    'Feeling down, depressed, or hopeless',
                    'Trouble falling or staying asleep, or sleeping too much',
                    'Feeling tired or having little energy',
                    'Poor appetite or overeating',
                    'Feeling bad about yourself',
                    'Trouble concentrating on things',
                    'Moving or speaking slowly or being restless',
                    'Thoughts that you would be better off dead'
                ],
                'scoring_method' => '0-3 scale per question, total 0-27',
                'interpretation_guide' => [
                    '0-4' => 'Minimal depression',
                    '5-9' => 'Mild depression',
                    '10-14' => 'Moderate depression',
                    '15-19' => 'Moderately severe depression',
                    '20-27' => 'Severe depression'
                ]
            ],
            [
                'name' => 'GAD-7 Anxiety Assessment',
                'type' => 'anxiety',
                'description' => 'Generalized Anxiety Disorder-7 screening tool',
                'questions' => [
                    'Feeling nervous, anxious, or on edge',
                    'Not being able to stop or control worrying',
                    'Worrying too much about different things',
                    'Trouble relaxing',
                    'Being so restless that it is hard to sit still',
                    'Becoming easily annoyed or irritable',
                    'Feeling afraid, as if something awful might happen'
                ],
                'scoring_method' => '0-3 scale per question, total 0-21',
                'interpretation_guide' => [
                    '0-4' => 'Minimal anxiety',
                    '5-9' => 'Mild anxiety',
                    '10-14' => 'Moderate anxiety',
                    '15-21' => 'Severe anxiety'
                ]
            ],
            [
                'name' => 'K10 Psychological Distress Scale',
                'type' => 'k10',
                'description' => 'Kessler 10 psychological distress assessment',
                'questions' => [
                    'In the past 30 days, how often did you feel tired for no good reason?',
                    'In the past 30 days, how often did you feel nervous?',
                    'In the past 30 days, how often did you feel so nervous that nothing could calm you down?',
                    'In the past 30 days, how often did you feel hopeless?',
                    'In the past 30 days, how often did you feel restless or fidgety?',
                    'In the past 30 days, how often were you so restless you could not sit still?',
                    'In the past 30 days, how often did you feel depressed?',
                    'In the past 30 days, how often did you feel that everything was an effort?',
                    'In the past 30 days, how often did you feel so sad that nothing could cheer you up?',
                    'In the past 30 days, how often did you feel worthless?'
                ],
                'scoring_method' => '1-5 scale per question, total 10-50',
                'interpretation_guide' => [
                    '10-15' => 'Low psychological distress',
                    '16-21' => 'Moderate psychological distress',
                    '22-29' => 'High psychological distress',
                    '30-50' => 'Very high psychological distress'
                ]
            ],
            [
                'name' => 'DASS-21 Assessment',
                'type' => 'dass21',
                'description' => 'Depression, Anxiety and Stress Scale - 21 items',
                'questions' => [
                    'I found it hard to wind down',
                    'I was aware of dryness of my mouth',
                    'I could not seem to experience any positive feeling at all',
                    'I experienced breathing difficulty',
                    'I found it difficult to work up the initiative to do things',
                    'I tended to over-react to situations',
                    'I experienced trembling (eg, in the hands)'
                ],
                'scoring_method' => '0-3 scale per question',
                'interpretation_guide' => [
                    '0-9' => 'Normal',
                    '10-13' => 'Mild',
                    '14-20' => 'Moderate',
                    '21-27' => 'Severe',
                    '28+' => 'Extremely severe'
                ]
            ],
            [
                'name' => 'Insomnia Severity Index',
                'type' => 'insomnia',
                'description' => 'Assessment of insomnia symptoms and sleep quality',
                'questions' => [
                    'Difficulty falling asleep',
                    'Difficulty staying asleep',
                    'Problems waking up too early',
                    'How satisfied/dissatisfied are you with your current sleep pattern?',
                    'How noticeable to others do you think your sleep problem is?'
                ],
                'scoring_method' => '0-4 scale per question, total 0-28',
                'interpretation_guide' => [
                    '0-7' => 'No clinically significant insomnia',
                    '8-14' => 'Subthreshold insomnia',
                    '15-21' => 'Clinical insomnia (moderate severity)',
                    '22-28' => 'Clinical insomnia (severe)'
                ]
            ],
            [
                'name' => 'Eating Disorder Examination',
                'type' => 'eating',
                'description' => 'Brief assessment of eating disorder symptoms',
                'questions' => [
                    'Have you been deliberately trying to limit the amount of food you eat?',
                    'Have you had episodes of binge eating?',
                    'Have you felt guilty after eating?',
                    'How concerned have you been about your weight?',
                    'How often do you think about food?'
                ],
                'scoring_method' => '0-4 scale per question',
                'interpretation_guide' => [
                    '0-5' => 'Low risk',
                    '6-10' => 'Moderate risk',
                    '11-15' => 'High risk',
                    '16-20' => 'Very high risk'
                ]
            ]
        ];
    }
}
?>
