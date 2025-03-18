<?php
session_start();
require_once '../../config/database.php';

// Set headers
header('Content-Type: text/plain');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo "error|Not authenticated";
        exit;
    }

    // Create database connection
    $conn = getConnection();
    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    // Get user's admin status
    $stmt = $conn->prepare("SELECT is_admin FROM users WHERE id = ?");
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }

    $stmt->bind_param("i", $_SESSION['user_id']);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute query: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if ($row['is_admin']) {
            echo "success|User is admin";
        } else {
            echo "error|User is not admin";
        }
    } else {
        echo "error|User not found";
    }

} catch (Exception $e) {
    error_log("Admin check error: " . $e->getMessage());
    echo "error|" . $e->getMessage();
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?> 