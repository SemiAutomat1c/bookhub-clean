<?php
session_start();
header('Content-Type: text/plain');

if (!isset($_SESSION['user_id'])) {
    echo "ERROR|Not authenticated";
    exit;
}

require_once '../config/database.php';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    echo "ERROR|Database connection failed";
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'add':
        $book_id = $_POST['book_id'] ?? 0;
        $list_type = $_POST['list_type'] ?? 'want-to-read';
        
        $stmt = $conn->prepare("INSERT INTO reading_list (user_id, book_id, list_type) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $user_id, $book_id, $list_type);
        
        if ($stmt->execute()) {
            echo "SUCCESS|Book added to reading list";
        } else {
            echo "ERROR|Failed to add book";
        }
        $stmt->close();
        break;
        
    case 'remove':
        $book_id = $_POST['book_id'] ?? 0;
        
        $stmt = $conn->prepare("DELETE FROM reading_list WHERE user_id = ? AND book_id = ?");
        $stmt->bind_param("ii", $user_id, $book_id);
        
        if ($stmt->execute()) {
            echo "SUCCESS|Book removed from reading list";
        } else {
            echo "ERROR|Failed to remove book";
        }
        $stmt->close();
        break;
        
    case 'list':
        $query = "SELECT b.*, rl.list_type, rl.created_at as added_date 
                 FROM reading_list rl 
                 JOIN books b ON rl.book_id = b.book_id 
                 WHERE rl.user_id = ?";
                 
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $books = [];
        while ($row = $result->fetch_assoc()) {
            $books[] = implode("|", [
                $row['book_id'],
                $row['title'],
                $row['author'],
                $row['list_type'],
                $row['added_date']
            ]);
        }
        
        if (count($books) > 0) {
            echo "SUCCESS|" . implode("\n", $books);
        } else {
            echo "SUCCESS|No books in reading list";
        }
        $stmt->close();
        break;
        
    default:
        echo "ERROR|Invalid action";
}

$conn->close();
?>
