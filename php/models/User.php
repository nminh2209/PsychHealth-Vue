<?php
require_once __DIR__ . '/../config/Database.php';

class User {
    public $db; // Changed from private to public

    public function __construct() {
        $this->db = new Database();
    }

    public function create($userData) {
        $sql = "INSERT INTO users (name, email, password, age, gender) 
                VALUES (:name, :email, :password, :age, :gender)";
        
        $params = [
            ':name' => $userData['name'],
            ':email' => $userData['email'],
            ':password' => password_hash($userData['password'], PASSWORD_DEFAULT),
            ':age' => $userData['age'] ?? null,
            ':gender' => $userData['gender'] ?? 'other'
        ];

        try {
            $this->db->query($sql, $params);
            return $this->db->getConnection()->lastInsertId();
        } catch (Exception $e) {
            throw new Exception("User creation failed: " . $e->getMessage());
        }
    }

    public function findByEmail($email) {
        $sql = "SELECT * FROM users WHERE email = :email";
        return $this->db->fetch($sql, [':email' => $email]);
    }

    public function findByUsername($username) {
        // Username doesn't exist in the new schema, so we'll search by name instead
        $sql = "SELECT * FROM users WHERE name = :name";
        return $this->db->fetch($sql, [':name' => $username]);
    }

    public function findById($id) {
        $sql = "SELECT * FROM users WHERE id = :id";
        return $this->db->fetch($sql, [':id' => $id]);
    }

    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }

    public function updateProfile($userId, $profileData) {
        $sql = "UPDATE users SET 
                name = :name,
                age = :age,
                gender = :gender,
                updated_at = CURRENT_TIMESTAMP
                WHERE id = :user_id";

        $params = [
            ':name' => $profileData['name'],
            ':age' => $profileData['age'],
            ':gender' => $profileData['gender'],
            ':user_id' => $userId
        ];

        try {
            $this->db->query($sql, $params);
            return true;
        } catch (Exception $e) {
            throw new Exception("Profile update failed: " . $e->getMessage());
        }
    }

    public function updateLastLogin($userId) {
        // Since last_login doesn't exist in the new schema, we'll update the updated_at instead
        $sql = "UPDATE users SET updated_at = CURRENT_TIMESTAMP WHERE id = :user_id";
        $this->db->query($sql, [':user_id' => $userId]);
    }

    public function changePassword($userId, $newPassword) {
        $sql = "UPDATE users SET password = :password WHERE id = :user_id";
        $params = [
            ':password' => password_hash($newPassword, PASSWORD_DEFAULT),
            ':user_id' => $userId
        ];

        try {
            $this->db->query($sql, $params);
            return true;
        } catch (Exception $e) {
            throw new Exception("Password change failed: " . $e->getMessage());
        }
    }

    public function getAllUsers($limit = 10, $offset = 0) {
        $sql = "SELECT id, name, email, age, gender, created_at 
                FROM users ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        
        return $this->db->fetchAll($sql, [
            ':limit' => $limit,
            ':offset' => $offset
        ]);
    }

    public function emailExists($email) {
        return $this->findByEmail($email) !== false;
    }

    public function usernameExists($username) {
        return $this->findByUsername($username) !== false;
    }
}
?>
