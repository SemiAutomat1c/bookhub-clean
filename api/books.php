<?php
header('Content-Type: application/json');

// Include database connection
require_once '../config/database.php';

// Get book ID from query parameter
$bookId = isset($_GET['id']) ? $_GET['id'] : null;

if (!$bookId) {
    http_response_code(400);
    echo json_encode(['error' => 'Book ID is required']);
    exit;
}

try {
    // Prepare and execute query
    $stmt = $conn->prepare("SELECT * FROM books WHERE id = ?");
    $stmt->bind_param("i", $bookId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Check if book exists
    if ($result->num_rows === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Book not found']);
        exit;
    }
    
    // Get book data
    $book = $result->fetch_assoc();
    
    // Add PDF URL
    $book['pdfUrl'] = '../assets/books/' . $book['pdf_file'];
    
    // Return book data
    echo json_encode($book);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

$stmt->close();
$conn->close();
?>
