<?php
session_start();

// Set headers
header('Content-Type: text/plain');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once '../../config/database.php';

try {
    // Verify admin access
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
        throw new Exception("Unauthorized access");
    }

    // Check if it's a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method");
    }

    // Get database connection
    $conn = getConnection();
    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    // Get POST data
    $input = file_get_contents('php://input');
    $data = [];
    parse_str($input, $data);

    $userId = isset($data['user_id']) ? intval($data['user_id']) : 0;

    // Validate user ID
    if (empty($userId)) {
        throw new Exception("Invalid user ID");
    }

    // Prevent deleting own account
    if ($userId === $_SESSION['user_id']) {
        throw new Exception("Cannot delete own account");
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Delete user's reading list entries
        $stmt = $conn->prepare("DELETE FROM reading_lists WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();

        // Delete user's reading progress
        $stmt = $conn->prepare("DELETE FROM reading_progress WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stmt->close();

        // Finally, delete the user
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();

        if ($stmt->affected_rows === 0) {
            throw new Exception("User not found");
        }

        // Commit transaction
        $conn->commit();
        echo "SUCCESS|User deleted successfully";

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    echo "ERROR|" . $e->getMessage();
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
?> 