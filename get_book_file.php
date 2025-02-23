<?php
header('Content-Type: application/json');

// Database configuration
$conn = new mysqli('localhost', 'root', '', 'bookhub');

// Check connection
if ($conn->connect_error) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Get book ID from request
$book_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($book_id <= 0) {
    echo json_encode(['error' => 'Invalid book ID']);
    exit;
}

// Get book details
$stmt = $conn->prepare("SELECT book_id, title, file_path, file_type FROM books WHERE book_id = ?");
$stmt->bind_param("i", $book_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $row = $result->fetch_assoc()) {
    $response = [
        'book_id' => $row['book_id'],
        'title' => $row['title'],
        'file_url' => $row['file_path'],
        'file_type' => $row['file_type']
    ];
    echo json_encode($response);
} else {
    echo json_encode(['error' => 'Book not found']);
}

$conn->close();
?>
