<?php
require_once '../../utils/Security.php';

// Initialize security
Security::init();

// Set response headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Generate new CSRF token
    $csrf_token = Security::generateCSRFToken();

    // Return success response
    echo json_encode([
        'success' => true,
        'csrf_token' => $csrf_token
    ]);

} catch (Exception $e) {
    error_log("CSRF error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
} 