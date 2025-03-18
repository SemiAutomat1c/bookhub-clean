<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$conn = new mysqli('localhost', 'root', '', 'bookhub');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

try {
    // Drop and recreate the books table
    $sql = "DROP TABLE IF EXISTS books";
    $conn->query($sql);

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

    // Insert sample books with publication years
    $sql = "INSERT INTO books (title, author, description, genre, publication_year) VALUES 
    ('The Great Gatsby', 'F. Scott Fitzgerald', 'A story of decadence and excess.', 'Classic', 1925),
    ('1984', 'George Orwell', 'A dystopian social science fiction novel.', 'Science Fiction', 1949),
    ('To Kill a Mockingbird', 'Harper Lee', 'A story of racial injustice.', 'Classic', 1960),
    ('The Hobbit', 'J.R.R. Tolkien', 'A fantasy novel about Bilbo Baggins.', 'Fantasy', 1937),
    ('Pride and Prejudice', 'Jane Austen', 'A romantic novel of manners.', 'Romance', 1813)";
    
    if ($conn->query($sql)) {
        echo "Database schema and sample data updated successfully!";
    } else {
        throw new Exception("Error inserting sample data: " . $conn->error);
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
} finally {
    $conn->close();
}
?> 