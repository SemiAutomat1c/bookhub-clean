<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'bookhub');

// Create connection
$conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

try {
    // Insert sample users
    $conn->query("INSERT INTO users (username, email, password_hash, full_name, is_admin) VALUES 
        ('admin', 'admin@bookhub.com', '" . password_hash('admin123', PASSWORD_DEFAULT) . "', 'Admin User', TRUE),
        ('user1', 'user1@bookhub.com', '" . password_hash('user123', PASSWORD_DEFAULT) . "', 'John Doe', FALSE),
        ('user2', 'user2@bookhub.com', '" . password_hash('user123', PASSWORD_DEFAULT) . "', 'Jane Smith', FALSE)");

    // Insert sample books
    $conn->query("INSERT INTO books (title, author, description, genre, publication_year) VALUES 
        ('The Great Gatsby', 'F. Scott Fitzgerald', 'A story of decadence and excess...', 'Classic', 1925),
        ('1984', 'George Orwell', 'A dystopian social science fiction novel...', 'Science Fiction', 1949),
        ('To Kill a Mockingbird', 'Harper Lee', 'A story of racial injustice...', 'Classic', 1960)");

    // Insert sample ratings
    $conn->query("INSERT INTO ratings (user_id, book_id, rating, review) VALUES 
        (1, 1, 4.5, 'A masterpiece of American literature'),
        (1, 2, 5.0, 'A prophetic and powerful novel'),
        (2, 1, 4.0, 'Beautifully written, captures the era perfectly')");

    // Insert sample reading lists
    $conn->query("INSERT INTO reading_lists (user_id, book_id, list_type, progress, added_at) VALUES 
        (1, 1, 'want-to-read', 0, NOW()),
        (1, 2, 'currently-reading', 45, NOW()),
        (1, 3, 'completed', 100, NOW()),
        (2, 1, 'currently-reading', 30, NOW())");

    // Insert sample reading progress
    $conn->query("INSERT INTO reading_progress (user_id, book_id, current_page, is_completed) VALUES 
        (1, 2, 45, FALSE),
        (1, 3, 100, TRUE),
        (2, 1, 30, FALSE)");

    echo "Sample data inserted successfully!";
} catch (Exception $e) {
    echo "Error inserting sample data: " . $e->getMessage();
    die();
} finally {
    // Close the connection
    $conn->close();
} 