<?php
require_once 'auth_check.php';
require_once 'database.php';

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

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
            echo json_encode(array('success' => true));
        } else {
            throw new Exception('Failed to save progress');
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(array('error' => $e->getMessage()));
    }
    
} else {
    http_response_code(400);
    echo json_encode(array('error' => 'Invalid request data'));
}

$conn->close();
?>
