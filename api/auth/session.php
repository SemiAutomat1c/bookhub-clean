<?php
require_once '../../config/database.php';

// Session timeout in seconds (30 minutes)
define('SESSION_TIMEOUT', 1800);

// Function to initialize session
function initSession() {
    if (session_status() === PHP_SESSION_NONE) {
        setSecureSessionParams();
        session_start();
    }
}

// Function to validate session
function validateSession() {
    if (!isset($_SESSION['last_activity'])) {
        return false;
    }
    
    if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
        destroySession();
        return false;
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
    return true;
}

// Function to create user session
function createUserSession($userData) {
    // Regenerate session ID to prevent session fixation
    session_regenerate_id(true);
    
    $_SESSION['user_id'] = $userData['user_id'];
    $_SESSION['username'] = $userData['username'];
    $_SESSION['email'] = $userData['email'];
    $_SESSION['full_name'] = $userData['full_name'];
    $_SESSION['last_activity'] = time();
}

// Function to destroy session
function destroySession() {
    $_SESSION = array();
    
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    session_destroy();
}

// Function to get current user data
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return array(
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'email' => $_SESSION['email'],
        'full_name' => $_SESSION['full_name']
    );
}

// Initialize session
initSession();

// Validate session if user is logged in
if (isLoggedIn() && !validateSession()) {
    destroySession();
    header("Location: /bookhub-1/views/sign-in.html");
    exit();
}
?> 