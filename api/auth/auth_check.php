<?php
session_start();

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
    header('Access-Control-Allow-Methods: GET, POST');
    header('Access-Control-Allow-Headers: Content-Type');
}

// Debug logging
error_log("Session data: " . print_r($_SESSION, true));

// Session timeout (30 minutes)
define('SESSION_TIMEOUT', 1800);

try {
    if (!isset($_SESSION['user_id'])) {
        error_log("No user_id in session");
        echo "not_authenticated|User not logged in";
        exit;
    }

    // Check session timeout
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        // Session has expired
        error_log("Session expired");
        session_destroy();
        echo "not_authenticated|Session expired";
        exit;
    }

    // Update last activity time
    $_SESSION['last_activity'] = time();

    // Create database connection
    $conn = new mysqli('localhost', 'root', '', 'bookhub');
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("SELECT user_id, username, email FROM users WHERE user_id = ?");
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }

    $stmt->bind_param("i", $_SESSION['user_id']);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute query: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        error_log("User found in database: " . print_r($row, true));
        echo "authenticated|User is logged in|{$row['user_id']},{$row['username']},{$row['email']}";
    } else {
        // User ID in session but not found in database
        error_log("User not found in database for ID: " . $_SESSION['user_id']);
        session_destroy();
        echo "not_authenticated|Invalid user session";
    }

} catch (Exception $e) {
    error_log("Auth check error: " . $e->getMessage());
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
