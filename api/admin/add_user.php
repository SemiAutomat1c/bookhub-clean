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

    $username = isset($data['username']) ? trim($data['username']) : '';
    $email = isset($data['email']) ? trim($data['email']) : '';
    $role = isset($data['role']) ? $data['role'] : '';
    $password = isset($data['password']) ? trim($data['password']) : '';
    $full_name = isset($data['full_name']) ? trim($data['full_name']) : '';

    // Validate required fields
    if (empty($username) || empty($email) || empty($role) || empty($password) || empty($full_name)) {
        throw new Exception("Missing required fields");
    }

    // Check if username or email already exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        throw new Exception("Username or email already exists");
    }
    $stmt->close();

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user
    $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, full_name, is_admin) VALUES (?, ?, ?, ?, ?)");
    $isAdmin = ($role === 'admin') ? 1 : 0;
    $stmt->bind_param("ssssi", $username, $email, $hashedPassword, $full_name, $isAdmin);

    if (!$stmt->execute()) {
        throw new Exception("Failed to create user: " . $stmt->error);
    }

    $userId = $stmt->insert_id;
    echo "SUCCESS|User created successfully|$userId";

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