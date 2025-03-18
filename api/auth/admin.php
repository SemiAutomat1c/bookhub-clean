<?php
session_start();

// Set headers for CORS and content type
header('Content-Type: text/plain');

// Get the origin
$allowed_origins = array(
    'http://localhost',
    'http://127.0.0.1',
    'http://DESKTOP-24M6GLF'
);

$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
}

header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Debug logging for session and request
error_log("Session data in admin.php: " . print_r($_SESSION, true));
error_log("Request headers: " . print_r(getallheaders(), true));
error_log("Origin: " . ($origin ?? 'none'));
error_log("Request method: " . $_SERVER['REQUEST_METHOD']);

try {
    // Connect to database to verify admin status
    $conn = new mysqli('localhost', 'root', '', 'bookhub');
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        http_response_code(500);
        echo "ERROR|Database connection failed";
        exit();
    }

    // Check if user is admin
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
        error_log("Session check failed: user_id=" . ($_SESSION['user_id'] ?? 'not set') . 
                ", is_admin=" . ($_SESSION['is_admin'] ?? 'not set'));
        
        // Double check admin status from database
        if (isset($_SESSION['user_id'])) {
            $stmt = $conn->prepare("SELECT is_admin FROM users WHERE user_id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $_SESSION['user_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    if ($row['is_admin'] == 1) {
                        // Update session
                        $_SESSION['is_admin'] = true;
                        echo "SUCCESS|Admin access granted";
                        $stmt->close();
                        $conn->close();
                        exit();
                    }
                }
                $stmt->close();
            }
        }
        
        http_response_code(401);
        echo "ERROR|Unauthorized access";
        $conn->close();
        exit();
    }

    echo "SUCCESS|Admin access verified";
} catch (Exception $e) {
    error_log("Admin check error: " . $e->getMessage());
    http_response_code(500);
    echo "ERROR|" . $e->getMessage();
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?> 