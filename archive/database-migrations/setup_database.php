<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'bookhub');

// Create connection
$conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Drop database if exists
$sql = "DROP DATABASE IF EXISTS " . DB_NAME;
$conn->query($sql);

// Create database
$sql = "CREATE DATABASE " . DB_NAME;
$conn->query($sql);

// Select the database
$conn->select_db(DB_NAME);

// Create users table
$sql = "CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    is_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($sql);

// Create books table
$sql = "CREATE TABLE books (
    book_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    description TEXT,
    genre VARCHAR(50),
    publication_year INT,
    cover_image VARCHAR(255),
    file_path VARCHAR(255),
    file_type VARCHAR(10) DEFAULT 'pdf',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($sql);

// Create ratings table
$sql = "CREATE TABLE ratings (
    rating_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    book_id INT,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    review TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_book_rating (user_id, book_id)
)";
$conn->query($sql);

// Create reading_lists table
$sql = "CREATE TABLE reading_lists (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    list_type ENUM('want-to-read', 'currently-reading', 'completed') NOT NULL,
    progress INT DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_book (user_id, book_id)
)";
$conn->query($sql);

// Insert sample users
$admin_password = password_hash('admin123', PASSWORD_DEFAULT);
$sql = "INSERT INTO users (username, email, password, is_admin) VALUES 
('admin', 'admin@bookhub.com', '$admin_password', TRUE),
('user1', 'user1@bookhub.com', '$admin_password', FALSE),
('user2', 'user2@bookhub.com', '$admin_password', FALSE)";
$conn->query($sql);

// Insert sample books
$sql = "INSERT INTO books (title, author, description, genre, publication_year) VALUES 
('The Great Gatsby', 'F. Scott Fitzgerald', 'A story of decadence and excess.', 'Classic', 1925),
('1984', 'George Orwell', 'A dystopian social science fiction novel.', 'Science Fiction', 1949),
('To Kill a Mockingbird', 'Harper Lee', 'A story of racial injustice.', 'Classic', 1960),
('The Hobbit', 'J.R.R. Tolkien', 'A fantasy novel about Bilbo Baggins.', 'Fantasy', 1937),
('Pride and Prejudice', 'Jane Austen', 'A romantic novel of manners.', 'Romance', 1813)";
$conn->query($sql);

// Insert sample ratings
$sql = "INSERT INTO ratings (user_id, book_id, rating, review) VALUES 
(1, 1, 5, 'A masterpiece!'),
(1, 2, 4, 'Very thought-provoking'),
(2, 1, 4, 'Great read'),
(2, 3, 5, 'A classic for a reason'),
(3, 4, 5, 'Fantastic fantasy novel')";
$conn->query($sql);

// Insert sample reading list entries
$sql = "INSERT INTO reading_lists (user_id, book_id, list_type, progress) VALUES
(1, 1, 'completed', 100),
(1, 2, 'currently-reading', 45),
(1, 3, 'want-to-read', 0),
(2, 4, 'currently-reading', 75),
(2, 5, 'want-to-read', 0)";
$conn->query($sql);

// Create views
$sql = "CREATE OR REPLACE VIEW vw_book_ratings AS
SELECT 
    b.book_id,
    b.title,
    b.author,
    COUNT(r.rating_id) as total_ratings,
    ROUND(AVG(r.rating), 2) as average_rating
FROM books b
LEFT JOIN ratings r ON b.book_id = r.book_id
GROUP BY b.book_id, b.title, b.author";
$conn->query($sql);

$sql = "CREATE OR REPLACE VIEW vw_user_reading_stats AS
SELECT 
    u.user_id,
    u.username,
    COUNT(DISTINCT r.book_id) as books_rated,
    ROUND(AVG(r.rating), 2) as average_rating_given
FROM users u
LEFT JOIN ratings r ON u.user_id = r.user_id
GROUP BY u.user_id, u.username";
$conn->query($sql);

$sql = "CREATE OR REPLACE VIEW vw_popular_books AS
SELECT 
    b.book_id,
    b.title,
    b.author,
    b.genre,
    COUNT(r.rating_id) as rating_count,
    ROUND(AVG(r.rating), 2) as average_rating
FROM books b
LEFT JOIN ratings r ON b.book_id = r.book_id
GROUP BY b.book_id, b.title, b.author, b.genre
HAVING rating_count > 0
ORDER BY average_rating DESC, rating_count DESC";
$conn->query($sql);

// Create functions
$sql = "CREATE FUNCTION fn_calculate_user_reading_time(p_user_id INT) 
RETURNS INT
DETERMINISTIC
BEGIN
    DECLARE total_books INT;
    SELECT COUNT(*) * 3 INTO total_books
    FROM ratings
    WHERE user_id = p_user_id;
    RETURN total_books;
END";
$conn->query($sql);

$sql = "CREATE FUNCTION fn_get_book_rating(p_book_id INT)
RETURNS DECIMAL(3,2)
DETERMINISTIC
BEGIN
    DECLARE avg_rating DECIMAL(3,2);
    SELECT ROUND(AVG(rating), 2) INTO avg_rating
    FROM ratings
    WHERE book_id = p_book_id;
    RETURN COALESCE(avg_rating, 0.00);
END";
$conn->query($sql);

// Create stored procedures
$sql = "CREATE PROCEDURE sp_add_book_rating(
    IN p_user_id INT,
    IN p_book_id INT,
    IN p_rating INT,
    IN p_review TEXT
)
BEGIN
    DECLARE existing_rating_id INT;
    SELECT rating_id INTO existing_rating_id
    FROM ratings
    WHERE user_id = p_user_id AND book_id = p_book_id;
    
    IF existing_rating_id IS NOT NULL THEN
        UPDATE ratings
        SET rating = p_rating,
            review = p_review
        WHERE rating_id = existing_rating_id;
    ELSE
        INSERT INTO ratings (user_id, book_id, rating, review)
        VALUES (p_user_id, p_book_id, p_rating, p_review);
    END IF;
END";
$conn->query($sql);

$sql = "CREATE PROCEDURE sp_update_book_details(
    IN p_book_id INT,
    IN p_title VARCHAR(255),
    IN p_author VARCHAR(255),
    IN p_description TEXT,
    IN p_genre VARCHAR(50)
)
BEGIN
    UPDATE books
    SET title = COALESCE(p_title, title),
        author = COALESCE(p_author, author),
        description = COALESCE(p_description, description),
        genre = COALESCE(p_genre, genre)
    WHERE book_id = p_book_id;
END";
$conn->query($sql);

$sql = "CREATE PROCEDURE sp_get_recommended_books(
    IN p_user_id INT,
    IN p_limit INT
)
BEGIN
    SELECT DISTINCT b.*
    FROM books b
    INNER JOIN ratings r1 ON b.book_id = r1.book_id
    WHERE b.genre IN (
        SELECT DISTINCT b2.genre
        FROM books b2
        INNER JOIN ratings r2 ON b2.book_id = r2.book_id
        WHERE r2.user_id = p_user_id
        AND r2.rating >= 4
    )
    AND b.book_id NOT IN (
        SELECT book_id
        FROM ratings
        WHERE user_id = p_user_id
    )
    ORDER BY (
        SELECT AVG(r3.rating)
        FROM ratings r3
        WHERE r3.book_id = b.book_id
    ) DESC
    LIMIT p_limit;
END";
$conn->query($sql);

echo "Database setup completed successfully!<br>";
echo "Admin credentials:<br>";
echo "Username: admin<br>";
echo "Password: admin123<br>";
echo "Email: admin@bookhub.com<br>";
echo "<br>Sample users:<br>";
echo "Username: user1, Password: admin123<br>";
echo "Username: user2, Password: admin123<br>";

$conn->close();
?> 