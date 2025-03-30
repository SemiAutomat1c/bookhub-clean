<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$conn = new mysqli('localhost', 'root', '', 'bookhub');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

try {
    // Check table structure
    echo "<h3>Table Structure:</h3>";
    $result = $conn->query("DESCRIBE books");
    while ($row = $result->fetch_assoc()) {
        echo "Field: {$row['Field']}, Type: {$row['Type']}, Null: {$row['Null']}, Key: {$row['Key']}, Default: {$row['Default']}<br>";
    }

    // Check table contents
    echo "<h3>Table Contents:</h3>";
    $result = $conn->query("SELECT * FROM books");
    while ($row = $result->fetch_assoc()) {
        echo "ID: {$row['book_id']}, Title: {$row['title']}, Author: {$row['author']}, Genre: {$row['genre']}, Year: {$row['publication_year']}<br>";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
} finally {
    $conn->close();
}
?> 