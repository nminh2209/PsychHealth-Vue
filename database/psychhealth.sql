-- PsychHealth Improved Database Schema
-- Drop and recreate database
DROP DATABASE IF EXISTS psychhealth;
CREATE DATABASE psychhealth CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE psychhealth;

-- Users table with enhanced fields
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    age INT NULL,
    gender ENUM('male', 'female', 'other', 'prefer_not_to_say') DEFAULT 'other',
    profile_image VARCHAR(255) NULL,
    bio TEXT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    email_verified BOOLEAN DEFAULT FALSE,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Mental health test categories
CREATE TABLE test_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    icon VARCHAR(50),
    color VARCHAR(20) DEFAULT '#007bff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Mental health tests
CREATE TABLE mental_health_tests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    name VARCHAR(150) NOT NULL,
    type VARCHAR(50) NOT NULL,
    description TEXT,
    instructions TEXT,
    duration_minutes INT DEFAULT 10,
    max_score INT DEFAULT 27,
    scoring_guide JSON,
    interpretation_guide JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES test_categories(id) ON DELETE SET NULL
);

-- Test questions
CREATE TABLE test_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    test_id INT NOT NULL,
    question_number INT NOT NULL,
    question_text TEXT NOT NULL,
    question_type ENUM('multiple_choice', 'scale', 'yes_no') DEFAULT 'scale',
    options JSON,
    reverse_scored BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (test_id) REFERENCES mental_health_tests(id) ON DELETE CASCADE
);

-- User test results
CREATE TABLE test_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    test_id INT NOT NULL,
    session_id VARCHAR(100),
    responses JSON NOT NULL,
    raw_score INT NOT NULL,
    percentage_score DECIMAL(5,2),
    severity_level ENUM('minimal', 'mild', 'moderate', 'moderately_severe', 'severe') DEFAULT 'minimal',
    interpretation TEXT,
    recommendations TEXT,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (test_id) REFERENCES mental_health_tests(id) ON DELETE CASCADE
);

-- Community post categories
CREATE TABLE post_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    slug VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    color VARCHAR(20) DEFAULT '#6c757d',
    icon VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Community posts
CREATE TABLE posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT,
    category VARCHAR(50), -- Frontend compatibility - stores category slug
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    excerpt VARCHAR(300),
    is_anonymous BOOLEAN DEFAULT FALSE,
    is_published BOOLEAN DEFAULT TRUE,
    views_count INT DEFAULT 0,
    likes_count INT DEFAULT 0,
    comments_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES post_categories(id) ON DELETE SET NULL
);

-- Post comments
CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    parent_comment_id INT NULL,
    content TEXT NOT NULL,
    is_anonymous BOOLEAN DEFAULT FALSE,
    likes_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_comment_id) REFERENCES comments(id) ON DELETE CASCADE
);

-- Post likes/reactions
CREATE TABLE post_likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    reaction_type ENUM('like', 'love', 'helpful', 'support') DEFAULT 'like',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_post_like (user_id, post_id),
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Comment likes
CREATE TABLE comment_likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    comment_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_comment_like (user_id, comment_id),
    FOREIGN KEY (comment_id) REFERENCES comments(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Mental health resources
CREATE TABLE resources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(50) NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    content TEXT,
    resource_type ENUM('article', 'video', 'audio', 'tool', 'contact', 'emergency') DEFAULT 'article',
    url VARCHAR(500),
    phone_number VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    is_emergency BOOLEAN DEFAULT FALSE,
    is_24_7 BOOLEAN DEFAULT FALSE,
    country VARCHAR(50) DEFAULT 'Australia',
    tags JSON,
    view_count INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- User sessions for tracking
CREATE TABLE user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_token VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert initial data

-- Test categories
INSERT INTO test_categories (name, description, icon, color) VALUES
('Lo Âu', 'Các bài kiểm tra về rối loạn lo âu và hoảng sợ', 'fas fa-brain', '#dc3545'),
('Trầm Cảm', 'Các bài kiểm tra về triệu chứng trầm cảm và rối loạn tâm trạng', 'fas fa-heart', '#6f42c1'),
('Căng Thẳng', 'Các bài kiểm tra mức độ căng thẳng và khả năng đối phó', 'fas fa-thermometer-half', '#fd7e14'),
('Giấc Ngủ', 'Các bài kiểm tra về rối loạn giấc ngủ và mất ngủ', 'fas fa-moon', '#20c997'),
('Ăn Uống', 'Các bài kiểm tra về rối loạn ăn uống và hình ảnh cơ thể', 'fas fa-utensils', '#e83e8c'),
('Tổng Quát', 'Các bài kiểm tra sức khỏe tinh thần và thể chất tổng quát', 'fas fa-chart-line', '#0dcaf0');

-- Post categories
INSERT INTO post_categories (name, slug, description, color, icon) VALUES
('Hỗ Trợ Lo Âu', 'anxiety', 'Hỗ trợ và thảo luận về các rối loạn lo âu', '#dc3545', 'fas fa-brain'),
('Hỗ Trợ Trầm Cảm', 'depression', 'Hỗ trợ cho những người đối phó với trầm cảm', '#6f42c1', 'fas fa-heart'),
('Quản Lý Căng Thẳng', 'stress', 'Mẹo và hỗ trợ để quản lý căng thẳng', '#fd7e14', 'fas fa-thermometer-half'),
('Hỗ Trợ Chung', 'support', 'Hỗ trợ và khuyến khích sức khỏe tinh thần chung', '#28a745', 'fas fa-hands-helping'),
('Câu Chuyện Thành Công', 'success', 'Chia sẻ câu chuyện hồi phục và thành công của bạn', '#ffc107', 'fas fa-star'),
('Tài Nguyên & Mẹo Hay', 'resources', 'Tài nguyên hữu ích và mẹo thực tế', '#17a2b8', 'fas fa-lightbulb');

-- Mental health tests
INSERT INTO mental_health_tests (category_id, name, type, description, instructions, max_score, scoring_guide, interpretation_guide) VALUES
(1, 'Bài Kiểm Tra Lo Âu GAD-7', 'anxiety', 'Thang đo 7 câu hỏi về Rối loạn Lo âu Tổng quát để đo lường các triệu chứng lo âu', 'Trong 2 tuần qua, bạn có thường xuyên bị làm phiền bởi các vấn đề sau không?', 21, 
 JSON_OBJECT('scale', '0-3 điểm mỗi câu', 'total', '0-21 điểm'),
 JSON_OBJECT('0-4', 'Lo âu tối thiểu', '5-9', 'Lo âu nhẹ', '10-14', 'Lo âu vừa phải', '15-21', 'Lo âu nặng')),

(2, 'Bài Kiểm Tra Trầm Cảm PHQ-9', 'depression', 'Bảng câu hỏi sức khỏe bệnh nhân-9 để sàng lọc trầm cảm', 'Trong 2 tuần qua, bạn có thường xuyên bị làm phiền bởi bất kỳ vấn đề nào sau đây không?', 27,
 JSON_OBJECT('scale', '0-3 điểm mỗi câu', 'total', '0-27 điểm'),
 JSON_OBJECT('0-4', 'Trầm cảm tối thiểu', '5-9', 'Trầm cảm nhẹ', '10-14', 'Trầm cảm vừa phải', '15-19', 'Trầm cảm khá nặng', '20-27', 'Trầm cảm nặng')),

(3, 'Thang Đo Căng Thẳng Tâm Lý K10', 'k10', 'Thang đo Căng thẳng Tâm lý Kessler đo lường các triệu chứng lo âu và trầm cảm', 'Trong 30 ngày qua, bạn có thường xuyên cảm thấy...', 50,
 JSON_OBJECT('scale', '1-5 điểm mỗi câu', 'total', '10-50 điểm'),
 JSON_OBJECT('10-19', 'Căng thẳng thấp', '20-24', 'Căng thẳng vừa phải', '25-29', 'Căng thẳng cao', '30-50', 'Căng thẳng rất cao')),

(3, 'Thang Đo DASS-21', 'dass21', 'Thang đo Trầm cảm, Lo âu và Căng thẳng - 21 câu hỏi', 'Vui lòng đọc từng câu và chọn mức độ áp dụng với bạn trong tuần qua', 63,
 JSON_OBJECT('scale', '0-3 điểm mỗi câu', 'total', '0-63 điểm'),
 JSON_OBJECT('0-20', 'Bình thường', '21-33', 'Nhẹ', '34-46', 'Vừa phải', '47-59', 'Nặng', '60-63', 'Cực kỳ nặng')),

(4, 'Chỉ Số Mức Độ Mất Ngủ', 'insomnia', 'Công cụ đánh giá các triệu chứng mất ngủ và chất lượng giấc ngủ', 'Vui lòng đánh giá mức độ nghiêm trọng hiện tại của các vấn đề mất ngủ của bạn', 28,
 JSON_OBJECT('scale', '0-4 điểm mỗi câu', 'total', '0-28 điểm'),
 JSON_OBJECT('0-7', 'Không mất ngủ', '8-14', 'Mất ngủ dưới ngưỡng', '15-21', 'Mất ngủ lâm sàng vừa phải', '22-28', 'Mất ngủ lâm sàng nặng')),

(5, 'Sàng Lọc Rối Loạn Ăn Uống', 'eating', 'Bảng câu hỏi sàng lọc các triệu chứng rối loạn ăn uống', 'Vui lòng trả lời các câu hỏi sau về thói quen ăn uống và cảm xúc của bạn về thức ăn', 25,
 JSON_OBJECT('scale', '0-5 điểm mỗi câu', 'total', '0-25 điểm'),
 JSON_OBJECT('0-8', 'Nguy cơ thấp', '9-16', 'Nguy cơ vừa phải', '17-25', 'Nguy cơ cao - nên tìm kiếm sự giúp đỡ chuyên nghiệp'));

-- Sample mental health resources
INSERT INTO resources (category, title, description, resource_type, phone_number, is_emergency, is_24_7, country) VALUES
('Khủng hoảng', 'Lifeline Úc', 'Hỗ trợ khủng hoảng và phòng chống tự tử 24/7', 'liên hệ', '13 11 14', TRUE, TRUE, 'Úc'),
('Khủng hoảng', 'Đường dây Khủng hoảng Sức khỏe Tâm thần', 'Đường dây khủng hoảng sức khỏe tâm thần', 'liên hệ', '1800 011 511', TRUE, TRUE, 'Úc'),
('Hỗ trợ', 'Beyond Blue', 'Hỗ trợ cho lo âu, trầm cảm và sức khỏe tâm thần', 'liên hệ', '1300 22 4636', FALSE, TRUE, 'Úc'),
('Thanh niên', 'Kids Helpline', 'Dịch vụ tư vấn 24/7 cho trẻ em và thanh thiếu niên', 'liên hệ', '1800 55 1800', FALSE, TRUE, 'Úc'),
('Chuyên nghiệp', 'Psychology Today', 'Tìm kiếm chuyên gia sức khỏe tâm thần gần bạn', 'bài viết', NULL, FALSE, FALSE, 'Úc'),
('Khủng hoảng', 'Đường dây nóng Tâm lý Việt Nam', 'Hỗ trợ khủng hoảng tâm lý 24/7', 'liên hệ', '1900 0113', TRUE, TRUE, 'Việt Nam'),
('Hỗ trợ', 'Trung tâm Tâm lý Việt Nam', 'Dịch vụ tư vấn tâm lý chuyên nghiệp', 'liên hệ', '028 3822 5678', FALSE, FALSE, 'Việt Nam'),
('Thanh niên', 'Tổng đài Hỗ trợ Thanh niên VN', 'Hỗ trợ tâm lý cho thanh thiếu niên', 'liên hệ', '1800 1567', FALSE, TRUE, 'Việt Nam');

-- Add some test questions for GAD-7
INSERT INTO test_questions (test_id, question_number, question_text, options) VALUES
(1, 1, 'Cảm thấy lo lắng, bồn chồn hoặc căng thẳng', JSON_ARRAY('Hoàn toàn không', 'Vài ngày', 'Hơn một nửa số ngày', 'Gần như mỗi ngày')),
(1, 2, 'Không thể ngừng hoặc kiểm soát việc lo lắng', JSON_ARRAY('Hoàn toàn không', 'Vài ngày', 'Hơn một nửa số ngày', 'Gần như mỗi ngày')),
(1, 3, 'Lo lắng quá mức về nhiều thứ khác nhau', JSON_ARRAY('Hoàn toàn không', 'Vài ngày', 'Hơn một nửa số ngày', 'Gần như mỗi ngày')),
(1, 4, 'Khó thư giãn', JSON_ARRAY('Hoàn toàn không', 'Vài ngày', 'Hơn một nửa số ngày', 'Gần như mỗi ngày')),
(1, 5, 'Bồn chồn đến mức khó ngồi yên', JSON_ARRAY('Hoàn toàn không', 'Vài ngày', 'Hơn một nửa số ngày', 'Gần như mỗi ngày')),
(1, 6, 'Dễ nổi nóng hoặc cáu kỉnh', JSON_ARRAY('Hoàn toàn không', 'Vài ngày', 'Hơn một nửa số ngày', 'Gần như mỗi ngày')),
(1, 7, 'Cảm thấy sợ hãi như thể điều gì đó tồi tệ có thể xảy ra', JSON_ARRAY('Hoàn toàn không', 'Vài ngày', 'Hơn một nửa số ngày', 'Gần như mỗi ngày'));

-- Add some test questions for PHQ-9
INSERT INTO test_questions (test_id, question_number, question_text, options) VALUES
(2, 1, 'Ít quan tâm hoặc vui thích khi làm việc', JSON_ARRAY('Hoàn toàn không', 'Vài ngày', 'Hơn một nửa số ngày', 'Gần như mỗi ngày')),
(2, 2, 'Cảm thấy chán nản, trầm cảm hoặc tuyệt vọng', JSON_ARRAY('Hoàn toàn không', 'Vài ngày', 'Hơn một nửa số ngày', 'Gần như mỗi ngày')),
(2, 3, 'Khó ngủ hoặc ngủ quá nhiều', JSON_ARRAY('Hoàn toàn không', 'Vài ngày', 'Hơn một nửa số ngày', 'Gần như mỗi ngày')),
(2, 4, 'Cảm thấy mệt mỏi hoặc thiếu năng lượng', JSON_ARRAY('Hoàn toàn không', 'Vài ngày', 'Hơn một nửa số ngày', 'Gần như mỗi ngày')),
(2, 5, 'Ăn ít hoặc ăn quá nhiều', JSON_ARRAY('Hoàn toàn không', 'Vài ngày', 'Hơn một nửa số ngày', 'Gần như mỗi ngày')),
(2, 6, 'Cảm thấy tồi tệ về bản thân hoặc cảm thấy mình thất bại', JSON_ARRAY('Hoàn toàn không', 'Vài ngày', 'Hơn một nửa số ngày', 'Gần như mỗi ngày')),
(2, 7, 'Khó tập trung vào việc như đọc báo hoặc xem TV', JSON_ARRAY('Hoàn toàn không', 'Vài ngày', 'Hơn một nửa số ngày', 'Gần như mỗi ngày')),
(2, 8, 'Di chuyển hoặc nói chậm, hoặc bồn chồn và không yên', JSON_ARRAY('Hoàn toàn không', 'Vài ngày', 'Hơn một nửa số ngày', 'Gần như mỗi ngày')),
(2, 9, 'Có ý nghĩ muốn chết hoặc tự làm hại bản thân', JSON_ARRAY('Hoàn toàn không', 'Vài ngày', 'Hơn một nửa số ngày', 'Gần như mỗi ngày'));

-- Create indexes for better performance
CREATE INDEX idx_posts_user_id ON posts(user_id);
CREATE INDEX idx_posts_category_id ON posts(category_id);
CREATE INDEX idx_posts_created_at ON posts(created_at);
CREATE INDEX idx_comments_post_id ON comments(post_id);
CREATE INDEX idx_comments_user_id ON comments(user_id);
CREATE INDEX idx_test_results_user_id ON test_results(user_id);
CREATE INDEX idx_test_results_test_id ON test_results(test_id);
CREATE INDEX idx_test_questions_test_id ON test_questions(test_id);