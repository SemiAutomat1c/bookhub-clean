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
    $username = isset($data['username']) ? trim($data['username']) : '';
    $email = isset($data['email']) ? trim($data['email']) : '';
    $role = isset($data['role']) ? $data['role'] : '';
    $password = isset($data['password']) ? trim($data['password']) : '';

    // Validate required fields
    if (empty($userId) || empty($username) || empty($email) || empty($role)) {
        throw new Exception("Missing required fields");
    }

    // Prevent modifying own account
    if ($userId === $_SESSION['user_id']) {
        throw new Exception("Cannot modify own account");
    }

    // Check if username or email already exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE (username = ? OR email = ?) AND user_id != ?");
    $stmt->bind_param("ssi", $username, $email, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        throw new Exception("Username or email already exists");
    }
    $stmt->close();

    // Update user
    if (!empty($password)) {
        // Update with password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, password = ?, is_admin = ? WHERE user_id = ?");
        $isAdmin = ($role === 'admin') ? 1 : 0;
        $stmt->bind_param("sssii", $username, $email, $hashedPassword, $isAdmin, $userId);
    } else {
        // Update without password
        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, is_admin = ? WHERE user_id = ?");
        $isAdmin = ($role === 'admin') ? 1 : 0;
        $stmt->bind_param("ssii", $username, $email, $isAdmin, $userId);
    }

    if (!$stmt->execute()) {
        throw new Exception("Failed to update user: " . $stmt->error);
    }

    if ($stmt->affected_rows === 0) {
        throw new Exception("No changes made to user");
    }

    echo "SUCCESS|User updated successfully";

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