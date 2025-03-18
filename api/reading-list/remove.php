<?php
session_start();
require_once '../../config/database.php';

// Set headers
header('Content-Type: text/plain');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo "ERROR|User not authenticated";
        exit;
    }

    // Get POST data
    $input = file_get_contents('php://input');
    $data = array();
    
    // Parse input string with format "key1:value1|key2:value2"
    $pairs = explode('|', $input);
    foreach ($pairs as $pair) {
        $parts = explode(':', $pair);
        if (count($parts) == 2) {
            $data[trim($parts[0])] = trim($parts[1]);
        }
    }
    
    if (!isset($data['book_id'])) {
        echo "ERROR|Missing book_id";
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $book_id = $data['book_id'];
    
    // Get database connection
    $conn = getConnection();
    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    // Delete the book from reading list
    $sql = "DELETE FROM reading_lists WHERE user_id = ? AND book_id = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }

    $stmt->bind_param("ii", $user_id, $book_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to remove book: " . $stmt->error);
    }
    
    if ($stmt->affected_rows > 0) {
        echo "SUCCESS|Book removed from reading list";
    } else {
        echo "ERROR|Book not found in reading list";
    }

    // Clean up
    $stmt->close();

} catch (Exception $e) {
    error_log("Reading list error: " . $e->getMessage());
    echo "ERROR|" . $e->getMessage();
} finally {
    if (isset($stmt) && $stmt instanceof mysqli_stmt) {
        $stmt->close();
    }
}
?> 