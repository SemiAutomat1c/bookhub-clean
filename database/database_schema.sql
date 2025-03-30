-- BookHub Database Schema

-- Create the database if it doesn't exist
CREATE DATABASE IF NOT EXISTS bookhub;
USE bookhub;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    is_admin BOOLEAN DEFAULT FALSE,
    last_login TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT chk_email CHECK (email REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$')
);

-- Books table
CREATE TABLE IF NOT EXISTS books (
    book_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(100) NOT NULL,
    description TEXT,
    genre VARCHAR(50),
    publication_year INT,
    cover_image VARCHAR(255),
    file_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_title (title),
    INDEX idx_author (author),
    INDEX idx_genre (genre)
);

-- Reading Lists table (User's book collections)
CREATE TABLE IF NOT EXISTS reading_lists (
    reading_list_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    status ENUM('want-to-read', 'currently-reading', 'completed') NOT NULL,
    progress INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_book (user_id, book_id),
    CONSTRAINT chk_progress CHECK (progress >= 0 AND progress <= 100)
);

-- Reading Progress table (Detailed reading progress)
CREATE TABLE IF NOT EXISTS reading_progress (
    progress_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    current_page INT NOT NULL,
    total_pages INT NOT NULL,
    last_read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE,
    CONSTRAINT chk_pages CHECK (current_page <= total_pages AND current_page >= 0)
);

-- Reviews table
CREATE TABLE IF NOT EXISTS reviews (
    review_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    rating INT NOT NULL,
    review_text TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_book_review (user_id, book_id),
    CONSTRAINT chk_rating CHECK (rating >= 1 AND rating <= 5)
);

-- User Preferences table
CREATE TABLE IF NOT EXISTS user_preferences (
    preference_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    theme_preference ENUM('light', 'dark') DEFAULT 'light',
    font_size VARCHAR(20) DEFAULT 'medium',
    notification_enabled BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_preferences (user_id)
);

-- Reading Sessions table (Track reading time)
CREATE TABLE IF NOT EXISTS reading_sessions (
    session_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    start_time TIMESTAMP NOT NULL,
    end_time TIMESTAMP,
    duration_minutes INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE,
    CONSTRAINT chk_session_time CHECK (end_time IS NULL OR end_time >= start_time)
);

-- Create views for common queries
CREATE OR REPLACE VIEW vw_user_reading_stats AS
SELECT 
    u.user_id,
    u.username,
    COUNT(DISTINCT rl.book_id) as total_books,
    SUM(CASE WHEN rl.status = 'completed' THEN 1 ELSE 0 END) as books_completed,
    SUM(CASE WHEN rl.status = 'currently-reading' THEN 1 ELSE 0 END) as books_reading,
    SUM(CASE WHEN rl.status = 'want-to-read' THEN 1 ELSE 0 END) as books_wanted
FROM users u
LEFT JOIN reading_lists rl ON u.user_id = rl.user_id
GROUP BY u.user_id, u.username;

CREATE OR REPLACE VIEW vw_popular_books AS
SELECT 
    b.book_id,
    b.title,
    b.author,
    COUNT(DISTINCT rl.user_id) as total_readers,
    COUNT(DISTINCT CASE WHEN rl.status = 'completed' THEN rl.user_id END) as completed_readers,
    AVG(r.rating) as average_rating
FROM books b
LEFT JOIN reading_lists rl ON b.book_id = rl.book_id
LEFT JOIN reviews r ON b.book_id = r.book_id
GROUP BY b.book_id, b.title, b.author;

-- Create functions for common calculations
DELIMITER //

CREATE FUNCTION IF NOT EXISTS fn_calculate_reading_progress(
    p_user_id INT,
    p_book_id INT
) RETURNS INT
DETERMINISTIC
BEGIN
    DECLARE v_progress INT;
    
    SELECT progress INTO v_progress
    FROM reading_lists
    WHERE user_id = p_user_id AND book_id = p_book_id;
    
    RETURN COALESCE(v_progress, 0);
END //

CREATE FUNCTION IF NOT EXISTS fn_get_reading_streak(
    p_user_id INT
) RETURNS INT
DETERMINISTIC
BEGIN
    DECLARE v_streak INT;
    
    SELECT COUNT(DISTINCT DATE(last_read_at)) INTO v_streak
    FROM reading_progress
    WHERE user_id = p_user_id
    AND last_read_at >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY);
    
    RETURN v_streak;
END //

DELIMITER ;

-- Create stored procedures for common operations
DELIMITER //

CREATE PROCEDURE IF NOT EXISTS sp_update_reading_status(
    IN p_user_id INT,
    IN p_book_id INT,
    IN p_progress INT,
    IN p_status VARCHAR(20)
)
BEGIN
    INSERT INTO reading_lists (user_id, book_id, progress, status)
    VALUES (p_user_id, p_book_id, p_progress, p_status)
    ON DUPLICATE KEY UPDATE
    progress = p_progress,
    status = p_status,
    updated_at = CURRENT_TIMESTAMP;
END //

CREATE PROCEDURE IF NOT EXISTS sp_get_book_recommendations(
    IN p_user_id INT,
    IN p_limit INT
)
BEGIN
    SELECT DISTINCT b.*
    FROM books b
    INNER JOIN reading_lists rl ON b.book_id = rl.book_id
    WHERE b.genre IN (
        SELECT DISTINCT b2.genre
        FROM books b2
        INNER JOIN reading_lists rl2 ON b2.book_id = rl2.book_id
        WHERE rl2.user_id = p_user_id
        AND rl2.status = 'completed'
    )
    AND b.book_id NOT IN (
        SELECT book_id
        FROM reading_lists
        WHERE user_id = p_user_id
    )
    ORDER BY RAND()
    LIMIT p_limit;
END //

DELIMITER ; 