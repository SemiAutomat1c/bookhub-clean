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

// Get book_id from request
$book_id = isset($_GET['book_id']) ? (int)$_GET['book_id'] : 0;

if ($book_id > 0) {
    // Call the stored procedure
    $result = $conn->query("CALL sp_get_book_details($book_id)");
    
    if ($result && $result->num_rows > 0) {
        $book = $result->fetch_assoc();
        header('Content-Type: application/json');
        echo json_encode($book);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Book not found']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid book ID']);
}

$conn->close();
?>
