<?php
require_once 'auth_check.php';
require_once 'database.php';

// Set content type to text/plain
header('Content-Type: text/plain');

// Get POST data as raw input and parse manually
$raw_data = file_get_contents('php://input');
$data = array();
foreach (explode('|', $raw_data) as $pair) {
    $parts = explode(':', $pair);
    if (count($parts) === 2) {
        $data[trim($parts[0])] = trim($parts[1]);
    }
}

if (isset($data['bookId']) && isset($data['page'])) {
    $book_id = (int)$data['bookId'];
    $page = (int)$data['page'];
    $user_id = $_SESSION['user_id'];
    
    try {
        // Update or insert reading progress
        $stmt = $conn->prepare("
            INSERT INTO reading_progress (user_id, book_id, current_page, last_read)
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                current_page = VALUES(current_page),
                last_read = VALUES(last_read)
        ");
        
        $stmt->bind_param("iii", $user_id, $book_id, $page);
        
        if ($stmt->execute()) {
            echo "SUCCESS|Progress saved successfully";
        } else {
            throw new Exception('Failed to save progress');
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo "ERROR|" . $e->getMessage();
    }
    
} else {
    http_response_code(400);
    echo "ERROR|Invalid request data";
}

$conn->close();
?>
