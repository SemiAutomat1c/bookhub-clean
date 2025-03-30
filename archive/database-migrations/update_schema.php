<?php
// Database configuration
$conn = new mysqli('localhost', 'root', '', 'bookhub');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Add publication_year column if it doesn't exist
$sql = "SHOW COLUMNS FROM books LIKE 'publication_year'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    $sql = "ALTER TABLE books ADD COLUMN publication_year INT AFTER genre";
    if ($conn->query($sql) === TRUE) {
        echo "Column publication_year added successfully";
    } else {
        echo "Error adding column: " . $conn->error;
    }
} else {
    echo "Column publication_year already exists";
}

$conn->close();
?> 