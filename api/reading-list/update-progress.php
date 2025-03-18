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
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['book_id']) || !isset($data['progress'])) {
        echo "ERROR|Missing required fields";
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $book_id = $data['book_id'];
    $progress = $data['progress'];

    // Validate progress
    if (!is_numeric($progress) || $progress < 0 || $progress > 100) {
        echo "ERROR|Invalid progress value";
        exit;
    }

    // Get database connection
    $conn = getConnection();
    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    // Update progress
    $sql = "UPDATE reading_lists SET progress = ? WHERE user_id = ? AND book_id = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }

    $stmt->bind_param("iii", $progress, $user_id, $book_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to update progress: " . $stmt->error);
    }

    if ($stmt->affected_rows === 0) {
        echo "ERROR|Book not found in reading list";
        exit;
    }

    echo "SUCCESS|Progress updated";

} catch (Exception $e) {
    error_log("Update progress error: " . $e->getMessage());
    echo "ERROR|" . $e->getMessage();
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
}
?> 