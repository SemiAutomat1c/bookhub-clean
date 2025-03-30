<?php
require_once 'config/database.php';

// Get database connection
$conn = getConnection();
if (!$conn) {
    die("Database connection failed");
}

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Testing Database Connection ===\n";
echo "Connection successful\n\n";

echo "=== Testing Books Query ===\n";
// Query all books
$query = "SELECT * FROM books ORDER BY book_id ASC";
echo "Query: $query\n\n";

$result = $conn->query($query);

if (!$result) {
    echo "Query Error: " . $conn->error . "\n";
    exit;
}

echo "=== Books in Database ===\n";
if ($result->num_rows > 0) {
    echo "Found " . $result->num_rows . " books:\n\n";
    while($row = $result->fetch_assoc()) {
        echo "Book ID: " . $row['book_id'] . "\n";
        echo "Title: " . $row['title'] . "\n";
        echo "Author: " . $row['author'] . "\n";
        echo "Genre: " . ($row['genre'] ?? 'Not set') . "\n";
        echo "Description: " . ($row['description'] ?? 'Not set') . "\n";
        echo "Publication Year: " . ($row['publication_year'] ?? 'Not set') . "\n";
        echo "Cover Image: " . ($row['cover_image'] ?? 'Not set') . "\n";
        echo "File Path: " . ($row['file_path'] ?? 'Not set') . "\n";
        echo "Created At: " . ($row['created_at'] ?? 'Not set') . "\n";
        echo "Updated At: " . ($row['updated_at'] ?? 'Not set') . "\n";
        echo str_repeat("-", 50) . "\n";
    }
} else {
    echo "No books found in database\n";
}

// Test specific book query
echo "\n=== Testing Specific Book Query (1984) ===\n";
$stmt = $conn->prepare("SELECT * FROM books WHERE title = '1984' OR book_id = 4");
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $book = $result->fetch_assoc();
    echo "Found book:\n";
    print_r($book);
} else {
    echo "Book '1984' not found\n";
}

// Close connection
$stmt->close();
$conn->close();
?> 