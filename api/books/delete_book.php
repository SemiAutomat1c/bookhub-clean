<?php
session_start();

// Include database configuration
require_once '../../config/database.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: /bookhub-1/views/index.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $book_id = $_POST['book_id'] ?? '';

    if (empty($book_id)) {
        echo "ERROR|Book ID is required";
        exit();
    }

    // Get database connection
    $conn = getConnection();
    if (!$conn) {
        echo "ERROR|Database connection failed";
        exit();
    }

    // Get book details before deletion
    $stmt = $conn->prepare("SELECT cover_image, file_path FROM books WHERE book_id = ?");
    if (!$stmt) {
        echo "ERROR|Failed to prepare statement: " . $conn->error;
        exit();
    }

    $stmt->bind_param("i", $book_id);
    if (!$stmt->execute()) {
        echo "ERROR|Failed to execute query: " . $stmt->error;
        $stmt->close();
        $conn->close();
        exit();
    }

    $result = $stmt->get_result();
    $book = $result->fetch_assoc();
    $stmt->close();

    // Delete the book from database
    $stmt = $conn->prepare("DELETE FROM books WHERE book_id = ?");
    if (!$stmt) {
        echo "ERROR|Failed to prepare delete statement: " . $conn->error;
        $conn->close();
        exit();
    }

    $stmt->bind_param("i", $book_id);
    if ($stmt->execute()) {
        // Delete associated files if they exist
        if ($book) {
            if (!empty($book['cover_image'])) {
                $cover_path = $_SERVER['DOCUMENT_ROOT'] . '/bookhub-1/' . $book['cover_image'];
                if (file_exists($cover_path)) {
                    unlink($cover_path);
                }
            }
            if (!empty($book['file_path'])) {
                $file_path = $_SERVER['DOCUMENT_ROOT'] . '/bookhub-1/' . $book['file_path'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }
        }
        echo "SUCCESS|Book deleted successfully";
    } else {
        echo "ERROR|Failed to delete book: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
} else {
    echo "ERROR|Invalid request method";
}
?> 