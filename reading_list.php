<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

// Get the request body
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    echo json_encode(['error' => 'Invalid request data']);
    exit;
}

// Database configuration
require_once 'config/database.php';
$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $data['action'] ?? '';

switch ($action) {
    case 'add':
        $book_id = $data['book_id'] ?? 0;
        $list_type = $data['list_type'] ?? '';
        
        if (!$book_id || !$list_type) {
            echo json_encode(['error' => 'Missing book_id or list_type']);
            exit;
        }
        
        // Check if book is already in any list
        $stmt = $conn->prepare("SELECT id FROM reading_lists WHERE user_id = ? AND book_id = ?");
        $stmt->bind_param("ii", $user_id, $book_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo json_encode(['error' => 'Book already in reading list']);
            exit;
        }
        
        // Add book to reading list
        $stmt = $conn->prepare("INSERT INTO reading_lists (user_id, book_id, list_type) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $user_id, $book_id, $list_type);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Failed to add book to list']);
        }
        break;
        
    case 'remove':
        $book_id = $data['book_id'] ?? 0;
        
        if (!$book_id) {
            echo json_encode(['error' => 'Missing book_id']);
            exit;
        }
        
        $stmt = $conn->prepare("DELETE FROM reading_lists WHERE user_id = ? AND book_id = ?");
        $stmt->bind_param("ii", $user_id, $book_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'Failed to remove book from list']);
        }
        break;
        
    case 'get':
        $stmt = $conn->prepare("
            SELECT rl.book_id, rl.list_type, b.title, b.author, b.cover, b.description, b.rating, b.genre
            FROM reading_lists rl
            JOIN books b ON rl.book_id = b.book_id
            WHERE rl.user_id = ?
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $reading_list = [
            'currently-reading' => [],
            'want-to-read' => [],
            'completed' => []
        ];
        
        while ($row = $result->fetch_assoc()) {
            $reading_list[$row['list_type']][] = [
                'id' => $row['book_id'],
                'title' => $row['title'],
                'author' => $row['author'],
                'cover' => $row['cover'],
                'description' => $row['description'],
                'rating' => $row['rating'],
                'genre' => $row['genre']
            ];
        }
        
        echo json_encode(['success' => true, 'data' => $reading_list]);
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
}

$conn->close();
?>
