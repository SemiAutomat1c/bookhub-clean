<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$conn = new mysqli('localhost', 'root', '', 'bookhub');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

try {
    echo "<h2>Books Table Status:</h2>";
    
    // Check books data
    $result = $conn->query("SELECT book_id, title, author, genre, publication_year FROM books ORDER BY book_id");
    if ($result->num_rows > 0) {
        echo "<h3>Books Data:</h3>";
        while ($row = $result->fetch_assoc()) {
            echo "ID: {$row['book_id']}, ";
            echo "Title: {$row['title']}, ";
            echo "Author: {$row['author']}, ";
            echo "Genre: {$row['genre']}, ";
            echo "Year: " . ($row['publication_year'] ? $row['publication_year'] : 'Not set');
            echo "<br>";
        }
    } else {
        echo "No books found in database.<br>";
    }

    // Check ratings data
    $result = $conn->query("SELECT COUNT(*) as count FROM ratings");
    $row = $result->fetch_assoc();
    echo "<h3>Ratings Status:</h3>";
    echo "Total ratings in database: {$row['count']}<br>";

    // Check for any NULL publication years
    $result = $conn->query("SELECT COUNT(*) as count FROM books WHERE publication_year IS NULL");
    $row = $result->fetch_assoc();
    echo "<h3>Data Validation:</h3>";
    echo "Books without publication year: {$row['count']}<br>";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
} finally {
    $conn->close();
}
?> 