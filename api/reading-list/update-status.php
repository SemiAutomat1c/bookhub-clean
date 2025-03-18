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
    $raw_data = file_get_contents('php://input');
    $data = array();
    foreach (explode('|', $raw_data) as $pair) {
        $parts = explode(':', $pair);
        if (count($parts) === 2) {
            $data[trim($parts[0])] = trim($parts[1]);
        }
    }
    
    if (!isset($data['book_id']) || !isset($data['list_type'])) {
        echo "ERROR|Missing required fields";
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $book_id = $data['book_id'];
    $list_type = $data['list_type'];
    $progress = isset($data['progress']) ? $data['progress'] : null;

    // Validate list type
    $valid_list_types = ['want-to-read', 'currently-reading', 'completed'];
    if (!in_array($list_type, $valid_list_types)) {
        echo "ERROR|Invalid list type";
        exit;
    }

    // Get database connection
    $conn = getConnection();
    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    // Check if entry exists
    $check_sql = "SELECT list_id FROM reading_lists WHERE user_id = ? AND book_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $user_id, $book_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        // Update existing entry
        $sql = "UPDATE reading_lists SET list_type = ?, progress = ? WHERE user_id = ? AND book_id = ?";
        $stmt = $conn->prepare($sql);
        $progress = $progress !== null ? $progress : 0;
        $stmt->bind_param("siii", $list_type, $progress, $user_id, $book_id);
    } else {
        // Insert new entry
        $sql = "INSERT INTO reading_lists (user_id, book_id, list_type, progress) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $progress = $progress !== null ? $progress : 0;
        $stmt->bind_param("iisi", $user_id, $book_id, $list_type, $progress);
    }

    if (!$stmt->execute()) {
        throw new Exception("Failed to update reading status: " . $stmt->error);
    }

    echo "SUCCESS|Reading status updated";

} catch (Exception $e) {
    error_log("Update status error: " . $e->getMessage());
    echo "ERROR|" . $e->getMessage();
} finally {
    if (isset($check_stmt)) {
        $check_stmt->close();
    }
    if (isset($stmt)) {
        $stmt->close();
    }
}
?> 