<?php
require_once '../models/MentalHealthTest.php';
require_once '../config/Database.php';

class SeedDatabase {
    private $testModel;
    private $db;

    public function __construct() {
        $this->testModel = new MentalHealthTest();
        $this->db = new Database();
    }

    public function seedTests() {
        echo "Seeding mental health tests...\n";
        
        // Since your database already has the tests from the SQL file, we can skip this
        // or add additional tests if needed
        echo "Tests already exist in database from SQL file.\n";
    }

    public function seedSampleUsers() {
        echo "Seeding sample users...\n";
        
        $sampleUsers = [
            [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => 'TestPassword123',
                'age' => 34,
                'gender' => 'other'
            ],
            [
                'name' => 'Demo User',
                'email' => 'demo@psychhealth.com',
                'password' => 'DemoPassword456',
                'age' => 39,
                'gender' => 'other'
            ]
        ];

        require_once '../models/User.php';
        $userModel = new User();

        foreach ($sampleUsers as $userData) {
            try {
                if (!$userModel->emailExists($userData['email'])) {
                    $userId = $userModel->create($userData);
                    echo "Created user: {$userData['name']} (ID: {$userId})\n";
                } else {
                    echo "User already exists: {$userData['name']}\n";
                }
            } catch (Exception $e) {
                echo "Error creating user {$userData['name']}: " . $e->getMessage() . "\n";
            }
        }
    }

    public function seedSampleResources() {
        echo "Seeding sample resources...\n";
        
        $sampleResources = [
            [
                'title' => 'Understanding Depression',
                'category' => 'article',
                'content' => 'Depression is a common mental health condition that affects millions of people worldwide...',
                'url' => 'https://www.beyondblue.org.au/the-facts/depression',
                'tags' => json_encode(['depression', 'mental health', 'awareness']),
                'is_featured' => 1
            ],
            [
                'title' => 'Anxiety Management Techniques',
                'category' => 'article',
                'content' => 'Learn practical techniques to manage anxiety in your daily life...',
                'url' => null,
                'tags' => json_encode(['anxiety', 'coping strategies', 'self-help']),
                'is_featured' => 1
            ],
            [
                'title' => 'Mindfulness Meditation for Beginners',
                'category' => 'video',
                'content' => 'A beginner-friendly guide to mindfulness meditation...',
                'url' => 'https://www.youtube.com/watch?v=example',
                'tags' => json_encode(['mindfulness', 'meditation', 'relaxation']),
                'is_featured' => 0
            ],
            [
                'title' => 'Crisis Support Hotlines',
                'category' => 'hotline',
                'content' => 'Emergency mental health support contacts and resources...',
                'url' => null,
                'tags' => json_encode(['crisis', 'emergency', 'support']),
                'is_featured' => 1
            ]
        ];

        $sql = "INSERT INTO resources (title, category, content, url, tags, is_featured) 
                VALUES (:title, :category, :content, :url, :tags, :is_featured)";

        foreach ($sampleResources as $resource) {
            try {
                // Check if resource already exists
                $existing = $this->db->fetch(
                    "SELECT id FROM resources WHERE title = :title", 
                    [':title' => $resource['title']]
                );
                
                if (!$existing) {
                    $this->db->query($sql, [
                        ':title' => $resource['title'],
                        ':category' => $resource['category'],
                        ':content' => $resource['content'],
                        ':url' => $resource['url'],
                        ':tags' => $resource['tags'],
                        ':is_featured' => $resource['is_featured']
                    ]);
                    echo "Created resource: {$resource['title']}\n";
                } else {
                    echo "Resource already exists: {$resource['title']}\n";
                }
            } catch (Exception $e) {
                echo "Error creating resource {$resource['title']}: " . $e->getMessage() . "\n";
            }
        }
    }

    public function run() {
        echo "=== PsychHealth Database Seeder ===\n\n";
        
        try {
            $this->seedTests();
            echo "\n";
            
            $this->seedSampleUsers();
            echo "\n";
            
            $this->seedSampleResources();
            echo "\n";
            
            echo "=== Seeding completed successfully! ===\n";
            
        } catch (Exception $e) {
            echo "Seeding failed: " . $e->getMessage() . "\n";
        }
    }
}

// Run seeder if called directly
if (php_sapi_name() === 'cli' || (isset($_GET['run']) && $_GET['run'] === 'seed')) {
    $seeder = new SeedDatabase();
    $seeder->run();
} else {
    echo "Database seeder. Run from command line or add ?run=seed to URL.";
}
?>
