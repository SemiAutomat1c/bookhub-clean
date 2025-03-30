<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$conn = new mysqli('localhost', 'root', '', 'bookhub');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

try {
    // Check if publication_year column exists
    $result = $conn->query("SHOW COLUMNS FROM books LIKE 'publication_year'");
    
    if ($result->num_rows === 0) {
        // Add publication_year column if it doesn't exist
        $sql = "ALTER TABLE books ADD COLUMN publication_year INT AFTER genre";
        if (!$conn->query($sql)) {
            throw new Exception("Error adding publication_year column: " . $conn->error);
        }
        echo "Added publication_year column successfully.<br>";
    }

    // Update existing books with publication years
    $updates = [
        ['The Great Gatsby', 1925],
        ['1984', 1949],
        ['To Kill a Mockingbird', 1960],
        ['The Hobbit', 1937],
        ['Pride and Prejudice', 1813]
    ];

    $stmt = $conn->prepare("UPDATE books SET publication_year = ? WHERE title = ?");
    
    foreach ($updates as [$title, $year]) {
        $stmt->bind_param("is", $year, $title);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            echo "Updated publication year for '$title' to $year<br>";
        }
    }

    echo "Database update completed successfully!";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    $conn->close();
}
?> 