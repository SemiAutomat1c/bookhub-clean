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

// Read the JSON file
$jsonData = file_get_contents('books.json');
$data = json_decode($jsonData, true);

if ($data && isset($data['books'])) {
    foreach ($data['books'] as $book) {
        $id = $conn->real_escape_string($book['id']);
        $cover = $conn->real_escape_string($book['cover']);
        
        // Update the cover_image in the books table
        $sql = "UPDATE books SET cover_image = '$cover' WHERE book_id = '$id'";
        if ($conn->query($sql) === TRUE) {
            echo "Updated cover for book ID: $id<br>";
        } else {
            echo "Error updating cover for book ID: $id: " . $conn->error . "<br>";
        }
    }
}

$conn->close();
echo "Cover image update process completed!";
?>
