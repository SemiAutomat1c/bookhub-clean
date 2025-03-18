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

    // Get recent activity (last 30 days)
    $sql = "SELECT 
                rl.list_type,
                rl.progress,
                rl.added_at,
                rl.last_updated,
                b.book_id,
                b.title,
                b.author,
                b.cover_image
            FROM reading_lists rl
            JOIN books b ON rl.book_id = b.book_id
            WHERE rl.user_id = ?
            AND (
                rl.added_at >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
                OR rl.last_updated >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
            )
            ORDER BY 
                GREATEST(rl.added_at, rl.last_updated) DESC
            LIMIT 10";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }

    $stmt->bind_param("i", $user_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute query: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    // Start response
    $response = array("SUCCESS");
    
    if ($result->num_rows === 0) {
        $response[] = "NO_ACTIVITIES";
    } else {
        while ($row = $result->fetch_assoc()) {
            // Determine activity type and message
            if ($row['added_at'] == $row['last_updated']) {
                $type = 'added';
                $message = "Added " . $row['title'] . " to " . str_replace('-', ' ', $row['list_type']) . " list";
            } else {
                if ($row['list_type'] == 'currently-reading') {
                    $type = 'progress';
                    $message = "Updated progress on " . $row['title'] . " to " . $row['progress'] . "%";
                } else if ($row['list_type'] == 'completed') {
                    $type = 'completed';
                    $message = "Completed reading " . $row['title'];
                } else {
                    $type = 'updated';
                    $message = "Updated status of " . $row['title'] . " to " . str_replace('-', ' ', $row['list_type']);
                }
            }

            // Format each activity as a line
            $response[] = implode('|', [
                $row['book_id'],
                $row['title'],
                $row['author'],
                $row['cover_image'] ?? '',
                $row['list_type'],
                $row['progress'] ?? '0',
                $type,
                $message,
                $row['added_at'],
                $row['last_updated']
            ]);
        }
    }

    echo implode("\n", $response);

} catch (Exception $e) {
    error_log("Error getting user activity: " . $e->getMessage());
    echo "ERROR|" . $e->getMessage();
}
?> 