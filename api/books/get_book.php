<?php
session_start();
require_once '../../config/database.php';

// Set headers
header('Content-Type: text/plain');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo "ERROR|User not authenticated";
        exit;
    }

    // Get book ID from query parameters
    if (!isset($_GET['book_id'])) {
        echo "ERROR|Book ID is required";
        exit;
    }

    $book_id = $_GET['book_id'];
    
    // Get database connection
    $conn = getConnection();
    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    // Get book details
    $sql = "SELECT * FROM books WHERE book_id = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }

    $stmt->bind_param("i", $book_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to get book: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo "ERROR|Book not found";
        exit;
    }

    $book = $result->fetch_assoc();
    
    // Format response
    echo implode('|', [
        $book['book_id'],
        $book['title'],
        $book['author'],
        $book['description'] ?? '',
        $book['genre'] ?? '',
        $book['publication_year'] ?? '',
        $book['cover_image'] ?? '',
        $book['file_path'] ?? ''
    ]);

} catch (Exception $e) {
    error_log("Get book error: " . $e->getMessage());
    echo "ERROR|" . $e->getMessage();
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
}
?> 