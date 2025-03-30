<?php
session_start();
require_once '../../config/database.php';

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
    header('Access-Control-Allow-Methods: POST');
    header('Access-Control-Allow-Headers: Content-Type');
}

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
    
    if (!isset($data['book_id']) || !isset($data['progress'])) {
        echo "ERROR|Missing required fields";
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $book_id = $data['book_id'];
    $progress = intval($data['progress']);
    
    // Validate progress value
    if ($progress < 0 || $progress > 100) {
        echo "ERROR|Invalid progress value";
        exit;
    }

    // Get database connection
    $conn = getConnection();
    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    // Check if book exists in reading list
    $check_sql = "SELECT list_type FROM reading_lists WHERE user_id = ? AND book_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $user_id, $book_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows === 0) {
        // Add book to currently-reading list if not in any list
        $sql = "INSERT INTO reading_lists (user_id, book_id, list_type, progress) VALUES (?, ?, 'currently-reading', ?)";
    } else {
        $row = $result->fetch_assoc();
        if ($progress == 100 && $row['list_type'] != 'completed') {
            // Move to completed list if progress is 100%
            $sql = "UPDATE reading_lists SET list_type = 'completed', progress = ? WHERE user_id = ? AND book_id = ?";
        } else if ($progress < 100 && $row['list_type'] != 'currently-reading') {
            // Move to currently-reading list if progress is less than 100%
            $sql = "UPDATE reading_lists SET list_type = 'currently-reading', progress = ? WHERE user_id = ? AND book_id = ?";
        } else {
            // Just update progress
            $sql = "UPDATE reading_lists SET progress = ? WHERE user_id = ? AND book_id = ?";
        }
    }

    $stmt = $conn->prepare($sql);
    if ($result->num_rows === 0) {
        $stmt->bind_param("iii", $user_id, $book_id, $progress);
    } else {
        $stmt->bind_param("iii", $progress, $user_id, $book_id);
    }
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to update reading progress: " . $stmt->error);
    }

    echo "SUCCESS|Reading progress updated";

} catch (Exception $e) {
    error_log("Reading list error: " . $e->getMessage());
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