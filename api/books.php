<?php
session_start();
require_once '../config/database.php';

// Set headers
header('Content-Type: text/plain');
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
$allowed_origins = array(
    'http://localhost',
    'http://127.0.0.1',
    'http://localhost:80',
    'http://localhost:8080'
);
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST');
    header('Access-Control-Allow-Headers: Content-Type');
}

// Check if user is admin for write operations
function isAdmin() {
    return isset($_SESSION['user_id']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

// Handle GET requests
function handleGetRequest($conn) {
    // Get specific book
    if (isset($_GET['id'])) {
        $bookId = $_GET['id'];
        $stmt = $conn->prepare("SELECT * FROM books WHERE book_id = ?");
        $stmt->bind_param("i", $bookId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo "ERROR|Book not found";
            return;
        }

        $book = $result->fetch_assoc();
        echo "SUCCESS|" . implode("|", [
            $book['book_id'],
            $book['title'],
            $book['author'],
            $book['cover_image'] ?? '',
            $book['description'] ?? '',
            $book['genre'] ?? '',
            $book['publication_year'] ?? '',
            $book['file_path'] ?? '',
            $book['file_type'] ?? ''
        ]);
        return;
    }

    // Search books
    if (isset($_GET['search'])) {
        $search = '%' . $_GET['search'] . '%';
        $stmt = $conn->prepare("
            SELECT * FROM books 
            WHERE title LIKE ? OR author LIKE ? OR genre LIKE ?
            ORDER BY title ASC
        ");
        $stmt->bind_param("sss", $search, $search, $search);
    } else {
        // Get all books
        $stmt = $conn->prepare("SELECT * FROM books ORDER BY title ASC");
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo "SUCCESS|NO_BOOKS";
        return;
    }

    $output = [];
    while ($book = $result->fetch_assoc()) {
        $output[] = implode("|", [
            $book['book_id'],
            $book['title'],
            $book['author'],
            $book['cover_image'] ?? '',
            $book['description'] ?? '',
            $book['genre'] ?? '',
            $book['publication_year'] ?? '',
            $book['file_path'] ?? '',
            $book['file_type'] ?? ''
        ]);
    }

    echo "SUCCESS|" . implode("\n", $output);
}

try {
    // Get database connection
    $conn = getConnection();
    if (!$conn) {
        error_log("Failed to get database connection in books.php");
        throw new Exception("Database connection failed");
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        handleGetRequest($conn);
    } else {
        echo "ERROR|Invalid request method";
    }
} catch (Exception $e) {
    error_log("Error in books.php: " . $e->getMessage());
    echo "ERROR|" . $e->getMessage();
} finally {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
?>
