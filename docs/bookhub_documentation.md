# BookHub Database Documentation

## Entity-Relationship Diagram (ERD)

The BookHub database consists of nine main tables with their relationships:

### Core Tables
1. **Users** - User management and authentication
2. **Books** - Book information and metadata
3. **Reading Lists** - User's book collections
4. **Reading Progress** - Reading tracking
5. **Ratings** - Book ratings and reviews
6. **Login Attempts** - Security monitoring
7. **Password Reset Tokens** - Password recovery
8. **User Activity Log** - User action tracking
9. **Views** - Reading statistics and popular books

## Database Schema

### 1. Users Table
```sql
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active TINYINT(1) DEFAULT 1,
    is_admin TINYINT(1) DEFAULT 0,
    reset_token VARCHAR(255) DEFAULT NULL,
    reset_token_expires TIMESTAMP NULL
);
```

### 2. Books Table
```sql
CREATE TABLE books (
    book_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    description TEXT,
    cover_image VARCHAR(255),
    genre VARCHAR(50),
    publication_year INT,
    file_path VARCHAR(255),
    file_type VARCHAR(10) DEFAULT 'pdf',
    total_pages INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### 3. Reading Lists Table
```sql
CREATE TABLE reading_lists (
    list_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    list_type ENUM('want-to-read','currently-reading','completed') NOT NULL,
    progress INT DEFAULT 0,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_book_list (user_id, book_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE
);
```

### 4. Reading Progress Table
```sql
CREATE TABLE reading_progress (
    progress_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    current_page INT DEFAULT 0,
    is_completed TINYINT(1) DEFAULT 0,
    last_read_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_book_progress (user_id, book_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE
);
```

### 5. Ratings Table
```sql
CREATE TABLE ratings (
    rating_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    rating DECIMAL(2,1) NOT NULL CHECK (rating >= 0 AND rating <= 5),
    review TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_book_rating (user_id, book_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE
);
```

### 6. Login Attempts Table
```sql
CREATE TABLE login_attempts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(100) NOT NULL,
    attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email_time (email, attempt_time)
);
```

### 7. Password Reset Tokens Table
```sql
CREATE TABLE password_reset_tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP DEFAULT (CURRENT_TIMESTAMP + INTERVAL 1 HOUR),
    used TINYINT(1) DEFAULT 0,
    UNIQUE KEY idx_token (token),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);
```

### 8. User Activity Log Table
```sql
CREATE TABLE user_activity_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    activity_type VARCHAR(50) NOT NULL,
    activity_details TEXT,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_activity (user_id, activity_type, created_at),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);
```

## Database Views

### 1. Popular Books View (vw_popular_books)
```sql
CREATE VIEW vw_popular_books AS
SELECT 
    b.book_id,
    b.title,
    b.author,
    b.genre,
    COUNT(DISTINCT rl.user_id) as total_readers,
    COUNT(DISTINCT CASE WHEN rl.list_type = 'completed' THEN rl.user_id END) as completed_readers,
    AVG(CASE WHEN r.rating IS NOT NULL THEN r.rating ELSE NULL END) as avg_rating,
    COUNT(DISTINCT r.rating_id) as total_ratings
FROM books b
LEFT JOIN reading_lists rl ON b.book_id = rl.book_id
LEFT JOIN ratings r ON b.book_id = r.book_id
GROUP BY b.book_id, b.title, b.author, b.genre
ORDER BY total_readers DESC, avg_rating DESC;
```

### 2. User Reading Stats View (vw_user_reading_stats)
```sql
CREATE VIEW vw_user_reading_stats AS
SELECT 
    u.user_id,
    u.username,
    u.full_name,
    COUNT(DISTINCT rl.book_id) as total_books,
    SUM(CASE WHEN rl.list_type = 'completed' THEN 1 ELSE 0 END) as books_completed,
    SUM(CASE WHEN rl.list_type = 'currently-reading' THEN 1 ELSE 0 END) as books_reading,
    SUM(CASE WHEN rl.list_type = 'want-to-read' THEN 1 ELSE 0 END) as books_wanted,
    MAX(rl.last_updated) as last_activity
FROM users u
LEFT JOIN reading_lists rl ON u.user_id = rl.user_id
GROUP BY u.user_id, u.username, u.full_name;
```

## Stored Procedures

### 1. Get Book Recommendations
```sql
CREATE PROCEDURE sp_get_book_recommendations(IN p_user_id INT, IN p_limit INT)
BEGIN
    CREATE TEMPORARY TABLE IF NOT EXISTS user_genres AS
    SELECT b.genre, COUNT(*) as genre_count
    FROM reading_lists rl
    JOIN books b ON rl.book_id = b.book_id
    WHERE rl.user_id = p_user_id
    GROUP BY b.genre;
    
    SELECT DISTINCT 
        b.book_id, b.title, b.author, b.genre, b.publication_year,
        COALESCE(AVG(r.rating), 0) as avg_rating,
        COUNT(DISTINCT r.rating_id) as total_ratings
    FROM books b
    LEFT JOIN ratings r ON b.book_id = r.book_id
    LEFT JOIN user_genres ug ON b.genre = ug.genre
    WHERE b.book_id NOT IN (
        SELECT book_id FROM reading_lists WHERE user_id = p_user_id
    )
    GROUP BY b.book_id, b.title, b.author, b.genre, b.publication_year
    ORDER BY ug.genre_count DESC, avg_rating DESC, total_ratings DESC
    LIMIT p_limit;
    
    DROP TEMPORARY TABLE IF EXISTS user_genres;
END
```

### 2. Update Reading Status
```sql
CREATE PROCEDURE sp_update_reading_status(
    IN p_user_id INT,
    IN p_book_id INT,
    IN p_progress INT,
    IN p_list_type VARCHAR(20)
)
BEGIN
    INSERT INTO reading_lists (user_id, book_id, list_type, progress)
    VALUES (p_user_id, p_book_id, p_list_type, p_progress)
    ON DUPLICATE KEY UPDATE
        list_type = p_list_type,
        progress = p_progress,
        last_updated = NOW();
END
```

## Functions

### 1. Calculate Reading Progress
```sql
CREATE FUNCTION fn_calculate_reading_progress(p_user_id INT, p_book_id INT)
RETURNS DECIMAL(5,2)
DETERMINISTIC
BEGIN
    DECLARE v_progress INT;
    DECLARE v_result DECIMAL(5,2);
    
    SELECT progress INTO v_progress
    FROM reading_lists
    WHERE user_id = p_user_id AND book_id = p_book_id;
    
    RETURN COALESCE(v_progress, 0);
END
```

### 2. Get Reading Streak
```sql
CREATE FUNCTION fn_get_reading_streak(p_user_id INT)
RETURNS INT
DETERMINISTIC
BEGIN
    DECLARE v_streak INT;
    DECLARE v_last_read DATE;
    
    SELECT MAX(DATE(last_updated))
    INTO v_last_read
    FROM reading_lists
    WHERE user_id = p_user_id
    AND list_type = 'currently-reading';
    
    IF v_last_read IS NULL THEN
        RETURN 0;
    END IF;
    
    SELECT COUNT(DISTINCT DATE(last_updated))
    INTO v_streak
    FROM reading_lists
    WHERE user_id = p_user_id
    AND DATE(last_updated) <= v_last_read
    AND DATE(last_updated) >= DATE_SUB(v_last_read, INTERVAL 30 DAY);
    
    RETURN v_streak;
END
``` 