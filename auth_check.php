<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Initialize response array
$response = array(
    'success' => false,
    'message' => '',
    'data' => null
);

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    $response['success'] = true;
    $response['data'] = array(
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'email' => $_SESSION['email']
    );
} else {
    $response['message'] = "Not authenticated";
}

// Send response
header('Content-Type: application/json');
echo json_encode($response);
