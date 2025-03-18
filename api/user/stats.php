<?php
session_start();
require_once '../../config/database.php';

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
    header('Access-Control-Allow-Methods: GET');
    header('Access-Control-Allow-Headers: Content-Type');
}

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo "ERROR|User not authenticated";
        exit;
    }

    $user_id = $_SESSION['user_id'];
    
    // Get database connection
    $conn = getConnection();
    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    // Get reading statistics
    $stats = array();

    // Total books in reading list
    $sql = "SELECT COUNT(*) as total FROM reading_lists WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['total_books'] = $result->fetch_assoc()['total'];
    $stmt->close();

    // Books by list type
    $sql = "SELECT list_type, COUNT(*) as count FROM reading_lists WHERE user_id = ? GROUP BY list_type";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $stats[$row['list_type']] = $row['count'];
    }
    $stmt->close();

    // Average reading progress
    $sql = "SELECT AVG(progress) as avg_progress FROM reading_lists WHERE user_id = ? AND list_type = 'currently-reading'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['average_progress'] = round($result->fetch_assoc()['avg_progress'] ?? 0);
    $stmt->close();

    // Books completed this month
    $sql = "SELECT COUNT(*) as count FROM reading_lists 
            WHERE user_id = ? 
            AND list_type = 'completed' 
            AND YEAR(last_updated) = YEAR(CURRENT_DATE)
            AND MONTH(last_updated) = MONTH(CURRENT_DATE)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats['completed_this_month'] = $result->fetch_assoc()['count'];
    $stmt->close();

    // Format response as text
    $response = array("SUCCESS");
    $response[] = "total_books:" . $stats['total_books'];
    $response[] = "want-to-read:" . ($stats['want-to-read'] ?? 0);
    $response[] = "currently-reading:" . ($stats['currently-reading'] ?? 0);
    $response[] = "completed:" . ($stats['completed'] ?? 0);
    $response[] = "average_progress:" . $stats['average_progress'];
    $response[] = "completed_this_month:" . $stats['completed_this_month'];
    
    echo implode("\n", $response);

} catch (Exception $e) {
    error_log("Error getting user stats: " . $e->getMessage());
    echo "ERROR|" . $e->getMessage();
}
?> 