<?php
require_once '../../utils/Security.php';
require_once '../../config/database.php';

// Initialize security
Security::init();

// Set response headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Check if user is authenticated
    if (!Security::isAuthenticated()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Not authenticated'
        ]);
        exit;
    }

    // Get user ID from session
    $user_id = Security::getCurrentUserId();

    // Get database connection
    $conn = getConnection();

    // Get user information
    $stmt = $conn->prepare("
        SELECT 
            user_id,
            username,
            email,
            full_name,
            created_at,
            last_login,
            (
                SELECT COUNT(*)
                FROM reading_list
                WHERE user_id = users.user_id
            ) as reading_list_count,
            (
                SELECT COUNT(*)
                FROM reading_progress
                WHERE user_id = users.user_id
                AND is_completed = TRUE
            ) as completed_books_count,
            (
                SELECT COUNT(*)
                FROM ratings
                WHERE user_id = users.user_id
            ) as ratings_count
        FROM users
        WHERE user_id = ?
    ");

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("User not found");
    }

    $user = $result->fetch_assoc();

    // Generate new CSRF token
    $csrf_token = Security::generateCSRFToken();

    // Return success response
    echo json_encode([
        'success' => true,
        'csrf_token' => $csrf_token,
        'user' => [
            'id' => $user['user_id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'full_name' => $user['full_name'],
            'memberSince' => $user['created_at'],
            'lastLogin' => $user['last_login'],
            'stats' => [
                'readingListCount' => (int)$user['reading_list_count'],
                'completedBooksCount' => (int)$user['completed_books_count'],
                'ratingsCount' => (int)$user['ratings_count']
            ]
        ]
    ]);

} catch (Exception $e) {
    error_log("User info error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
} 