<?php
require_once '../config/Database.php';

try {
    $db = new Database();
    
    // First, clear existing test data
    $db->query("DELETE FROM tests");
    
    // Insert basic mental health tests
    $tests = [
        [
            'name' => 'GAD-7 Anxiety Test',
            'type' => 'anxiety',
            'description' => 'Generalized Anxiety Disorder 7-item scale for anxiety assessment',
            'max_score' => 21
        ],
        [
            'name' => 'PHQ-9 Depression Test', 
            'type' => 'depression',
            'description' => 'Patient Health Questionnaire-9 for depression screening',
            'max_score' => 27
        ],
        [
            'name' => 'K10 Psychological Distress Test',
            'type' => 'stress',
            'description' => 'Kessler Psychological Distress Scale for stress and anxiety',
            'max_score' => 50
        ],
        [
            'name' => 'DASS-21 Stress Assessment',
            'type' => 'stress', 
            'description' => 'Depression Anxiety Stress Scales - 21 item version',
            'max_score' => 21
        ],
        [
            'name' => 'Insomnia Severity Index',
            'type' => 'insomnia',
            'description' => 'Assessment of sleep difficulties and insomnia symptoms',
            'max_score' => 28
        ],
        [
            'name' => 'Eating Disorder Assessment',
            'type' => 'eating',
            'description' => 'Screening tool for eating disorder symptoms and behaviors',
            'max_score' => 25
        ]
    ];
    
    foreach ($tests as $test) {
        $sql = "INSERT INTO tests (name, type, description, max_score) VALUES (:name, :type, :description, :max_score)";
        $db->query($sql, [
            ':name' => $test['name'],
            ':type' => $test['type'],
            ':description' => $test['description'],
            ':max_score' => $test['max_score']
        ]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Test data seeded successfully',
        'tests_created' => count($tests)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Seeding failed: ' . $e->getMessage()
    ]);
}
?>
