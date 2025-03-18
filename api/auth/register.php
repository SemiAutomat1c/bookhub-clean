<?php
session_start();
require_once '../../config/database.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Set headers
header('Content-Type: text/plain');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Debug: Log the incoming request
error_log("Registration attempt - POST data: " . print_r($_POST, true));

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "error|Method not allowed";
    exit;
}

try {
    // Get POST data
    $data = $_POST;

    // Debug: Log the received data
    error_log("Received registration data: " . print_r($data, true));

    // Validate required fields
    $required_fields = ['full_name', 'email', 'password'];
    $missing = array_filter($required_fields, function($field) use ($data) {
        return !isset($data[$field]) || trim($data[$field]) === '';
    });

    if (!empty($missing)) {
        http_response_code(400);
        echo "error|Missing required fields: " . implode(', ', $missing);
        exit;
    }

    // Get database connection
    $conn = getConnection();
    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    // Sanitize and validate input
    $fullname = $conn->real_escape_string(trim($data['full_name']));
    $email = $conn->real_escape_string(trim($data['email']));
    $password = $data['password'];

    // Generate base username from full name
    $base_username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', str_replace(' ', '', $fullname)));
    $username = $base_username;
    $counter = 1;

    // Check if username exists and generate a unique one
    do {
        $check_username = $conn->query("SELECT username FROM users WHERE username = '" . $conn->real_escape_string($username) . "'");
        if ($check_username->num_rows === 0) {
            break;
        }
        $username = $base_username . $counter;
        $counter++;
    } while (true);

    // Debug: Log sanitized data
    error_log("Sanitized data - Username: $username, Email: $email");

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo "error|Invalid email format";
        exit;
    }

    // Validate password strength
    if (strlen($password) < 8) {
        http_response_code(400);
        echo "error|Password must be at least 8 characters long";
        exit;
    }

    // Check if email already exists
    $check_email = $conn->query("SELECT id FROM users WHERE email = '" . $conn->real_escape_string($email) . "'");
    if ($check_email->num_rows > 0) {
        http_response_code(409);
        echo "error|Email already exists";
        exit;
    }

    // Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user
    $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $username, $email, $password_hash);
    
    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;
        
        // Set session variables
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $username;
        $_SESSION['email'] = $email;
        
        http_response_code(201);
        echo "success|User registered successfully|$user_id,$username,$email";
    } else {
        throw new Exception("Failed to create user: " . $stmt->error);
    }

} catch (Exception $e) {
    error_log("Registration error: " . $e->getMessage());
    http_response_code(500);
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