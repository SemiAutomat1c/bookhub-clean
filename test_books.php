<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

// Get all books
$result = $conn->query("SELECT * FROM books");

echo "<h2>Books in Database:</h2>";
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "<pre>";
        print_r($row);
        echo "</pre>";
    }
} else {
    echo "No books found in database";
}

$conn->close();
?>
