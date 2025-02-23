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

// Create database if not exists (silently)
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
$conn->query($sql);

// Select the database
$conn->select_db(DB_NAME);

// Create users table if not exists (silently)
$sql = "CREATE TABLE IF NOT EXISTS users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($sql);

// Create books table if not exists (silently)
$sql = "CREATE TABLE IF NOT EXISTS books (
    book_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    description TEXT,
    genre VARCHAR(50),
    cover_image VARCHAR(255),
    file_path VARCHAR(255),
    file_type VARCHAR(10) DEFAULT 'pdf',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($sql);

// Create ratings table if not exists (silently)
$sql = "CREATE TABLE IF NOT EXISTS ratings (
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

// Close connection
$conn->close();

?>
