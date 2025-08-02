<?php
require_once __DIR__ . '/../config/Database.php';

class Comment {
    private $db;

    public function __construct() {
        $this->db = new Database();
    }

    public function create($commentData) {
        $sql = "INSERT INTO comments (post_id, user_id, content, is_anonymous) 
                VALUES (:post_id, :user_id, :content, :is_anonymous)";
        
        $params = [
            ':post_id' => $commentData['post_id'],
            ':user_id' => $commentData['user_id'],
            ':content' => $commentData['content'],
            ':is_anonymous' => $commentData['is_anonymous'] ?? false
        ];

        try {
            $this->db->query($sql, $params);
            
            // Update comments count in posts table
            $updateSql = "UPDATE posts SET comments_count = comments_count + 1 WHERE id = :post_id";
            $this->db->query($updateSql, [':post_id' => $commentData['post_id']]);
            
            return $this->db->getConnection()->lastInsertId();
        } catch (Exception $e) {
            throw new Exception("Comment creation failed: " . $e->getMessage());
        }
    }

    public function getByPostId($postId, $limit = 50, $offset = 0) {
        $sql = "SELECT c.*, u.name as author_name 
                FROM comments c 
                LEFT JOIN users u ON c.user_id = u.id 
                WHERE c.post_id = :post_id 
                ORDER BY c.created_at ASC 
                LIMIT :limit OFFSET :offset";
        
        return $this->db->fetchAll($sql, [
            ':post_id' => $postId,
            ':limit' => $limit,
            ':offset' => $offset
        ]);
    }

    public function delete($commentId, $userId) {
        $sql = "DELETE FROM comments WHERE id = :comment_id AND user_id = :user_id";
        
        try {
            $this->db->query($sql, [
                ':comment_id' => $commentId,
                ':user_id' => $userId
            ]);
            return true;
        } catch (Exception $e) {
            throw new Exception("Comment deletion failed: " . $e->getMessage());
        }
    }

    public function getCommentCount($postId) {
        $sql = "SELECT COUNT(*) as count FROM comments WHERE post_id = :post_id";
        $result = $this->db->fetch($sql, [':post_id' => $postId]);
        return $result['count'] ?? 0;
    }
}
?>
