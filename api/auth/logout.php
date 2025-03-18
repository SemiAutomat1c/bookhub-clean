<?php
session_start();

// Set headers
header('Content-Type: text/plain');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Debug log before logout
    error_log("Logging out user. Current session: " . print_r($_SESSION, true));

    // Clear all session variables
    $_SESSION = array();

    // Destroy the session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }

    // Destroy the session
    session_destroy();

    // Debug log after logout
    error_log("Session destroyed successfully");

    // Return success message
    echo "success|Logged out successfully";

} catch (Exception $e) {
    error_log("Logout error: " . $e->getMessage());
    http_response_code(500);
    echo "error|" . $e->getMessage();
}
?>