<?php
session_start();
header('Content-Type: text/plain');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "ERROR|Authentication required";
    exit;
}

// Get the request body and parse manually
$raw_data = file_get_contents('php://input');
$data = array();
foreach (explode('|', $raw_data) as $pair) {
    $parts = explode(':', $pair);
    if (count($parts) === 2) {
        $data[trim($parts[0])] = trim($parts[1]);
    }
}

if (!$data) {
    echo "ERROR|Invalid request data";
    exit;
}

// Database configuration
require_once 'config/database.php';
$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    echo "ERROR|Database connection failed";
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $data['action'] ?? '';

switch ($action) {
    case 'add':
        $book_id = $data['book_id'] ?? 0;
        $list_type = $data['list_type'] ?? '';
        
        if (!$book_id || !$list_type) {
            echo "ERROR|Missing book_id or list_type";
            exit;
        }
        
        // Check if book is already in any list
        $stmt = $conn->prepare("SELECT id FROM reading_lists WHERE user_id = ? AND book_id = ?");
        $stmt->bind_param("ii", $user_id, $book_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo "ERROR|Book already in reading list";
            exit;
        }
        
        // Add book to reading list
        $stmt = $conn->prepare("INSERT INTO reading_lists (user_id, book_id, list_type) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $user_id, $book_id, $list_type);
        
        if ($stmt->execute()) {
            echo "SUCCESS|Book added to list";
        } else {
            echo "ERROR|Failed to add book to list";
        }
        break;
        
    case 'remove':
        $book_id = $data['book_id'] ?? 0;
        
        if (!$book_id) {
            echo "ERROR|Missing book_id";
            exit;
        }
        
        $stmt = $conn->prepare("DELETE FROM reading_lists WHERE user_id = ? AND book_id = ?");
        $stmt->bind_param("ii", $user_id, $book_id);
        
        if ($stmt->execute()) {
            echo "SUCCESS|Book removed from list";
        } else {
            echo "ERROR|Failed to remove book from list";
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
        
        $output = "DATA";
        
        while ($row = $result->fetch_assoc()) {
            $output .= sprintf("|book|%d|%s|%s|%s|%s|%s|%s|%s|%s",
                $row['book_id'],
                $row['list_type'],
                $row['title'],
                $row['author'],
                $row['cover'],
                $row['description'],
                $row['rating'],
                $row['genre']
            );
        }
        
        echo $output === "DATA" ? "DATA|no_books" : $output;
        break;
        
    default:
        echo "ERROR|Invalid action";
}

$conn->close();
?>
