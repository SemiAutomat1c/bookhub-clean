<?php
require_once 'config/database.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get database connection
$conn = getConnection();
if (!$conn) {
    die("Database connection failed");
}

// Query all books
$query = "SELECT * FROM books ORDER BY book_id ASC";
$result = $conn->query($query);

if (!$result) {
    die("Query failed: " . $conn->error);
}

echo "=== All Books in Database ===\n\n";
while ($row = $result->fetch_assoc()) {
    echo "Book ID: " . $row['book_id'] . "\n";
    echo "Title: " . $row['title'] . "\n";
    echo "Author: " . $row['author'] . "\n";
    echo "Genre: " . ($row['genre'] ?? 'Not set') . "\n";
    echo "Description: " . ($row['description'] ?? 'Not set') . "\n";
    echo "Publication Year: " . ($row['publication_year'] ?? 'Not set') . "\n";
    echo "Cover Image: " . ($row['cover_image'] ?? 'Not set') . "\n";
    echo "File Path: " . ($row['file_path'] ?? 'Not set') . "\n";
    echo str_repeat("-", 50) . "\n\n";
}

$conn->close();
?> 