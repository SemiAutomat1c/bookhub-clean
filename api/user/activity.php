<?php
session_start();
require_once '../../config/database.php';

// Set headers
header('Content-Type: text/plain');
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
$allowed_origins = array(
    'http://localhost',
    'http://127.0.0.1',
    'http://localhost:80'
);
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET');
    header('Access-Control-Allow-Headers: Content-Type');
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "ERROR|User not logged in";
    exit;
}

try {
    // Get database connection
    $conn = getConnection();
    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    $userId = $_SESSION['user_id'];
    
    // Initialize activities array
    $activities = [];
    
    // Check if reading_list table exists
    $tables_result = $conn->query("SHOW TABLES LIKE 'reading_list'");
    $reading_list_exists = ($tables_result->num_rows > 0);
    
    // Check if reading_lists table exists
    $tables_result = $conn->query("SHOW TABLES LIKE 'reading_lists'");
    $reading_lists_exists = ($tables_result->num_rows > 0);
    
    // Function to parse results into activities
    function parseActivities($result) {
        $activities = [];
        while ($row = $result->fetch_assoc()) {
            // Generate activity message based on list_type
            $activityType = 'added';
            
            if ($row['list_type'] === 'completed') {
                $message = "Completed \"" . $row['title'] . "\"";
                $activityType = 'completed';
            } else {
                $message = "Added \"" . $row['title'] . "\" to " . $row['list_type'];
            }
            
            $activities[] = [
                'book_id' => $row['book_id'],
                'title' => $row['title'],
                'author' => $row['author'],
                'cover_image' => $row['cover_image'],
                'list_type' => $row['list_type'],
                'progress' => $row['progress'] ?? 0,
                'activity_type' => $activityType,
                'message' => $message,
                'created_at' => $row['created_at'] ?? $row['timestamp'] ?? date('Y-m-d H:i:s'),
                'timestamp' => strtotime($row['created_at'] ?? $row['timestamp'] ?? 'now')
            ];
        }
        return $activities;
    }
    
    // Query reading_list if it exists
    if ($reading_list_exists) {
        $sql = "SELECT 
                    r.book_id,
                    b.title,
                    b.author,
                    b.cover_image,
                    r.list_type,
                    r.progress,
                    r.created_at
                FROM 
                    reading_list r
                JOIN 
                    books b ON r.book_id = b.book_id
                WHERE 
                    r.user_id = ?
                ORDER BY 
                    r.created_at DESC
                LIMIT 10";
        
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $userId);
            
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                $activities = array_merge($activities, parseActivities($result));
            }
            
            $stmt->close();
        }
    }
    
    // Query reading_lists if it exists
    if ($reading_lists_exists) {
        $sql = "SELECT 
                    r.book_id,
                    b.title,
                    b.author,
                    b.cover_image,
                    r.list_type,
                    r.progress,
                    r.added_at as created_at
                FROM 
                    reading_lists r
                JOIN 
                    books b ON r.book_id = b.book_id
                WHERE 
                    r.user_id = ?
                ORDER BY 
                    r.added_at DESC
                LIMIT 10";
        
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $userId);
            
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                $activities = array_merge($activities, parseActivities($result));
            }
            
            $stmt->close();
        }
    }
    
    // Sort activities by timestamp in descending order
    usort($activities, function($a, $b) {
        return $b['timestamp'] - $a['timestamp'];
    });
    
    // Remove duplicates (same book and list_type)
    $unique_activities = [];
    $seen_keys = [];
    
    foreach ($activities as $activity) {
        $key = $activity['book_id'] . '-' . $activity['list_type'];
        if (!isset($seen_keys[$key])) {
            $unique_activities[] = $activity;
            $seen_keys[$key] = true;
        }
    }
    
    // Take only the first 10 activities
    $unique_activities = array_slice($unique_activities, 0, 10);
    
    // Format response
    echo "SUCCESS\n";
    
    if (empty($unique_activities)) {
        echo "NO_ACTIVITIES";
    } else {
        foreach ($unique_activities as $activity) {
            echo implode('|', [
                $activity['book_id'],
                $activity['title'],
                $activity['author'],
                $activity['cover_image'],
                $activity['list_type'],
                $activity['progress'],
                $activity['activity_type'],
                $activity['message'],
                $activity['created_at'],
                $activity['created_at']
            ]) . "\n";
        }
    }

} catch (Exception $e) {
    error_log("Activity error: " . $e->getMessage());
    echo "ERROR|" . $e->getMessage();
} finally {
    if (isset($conn) && !($conn instanceof mysqli_stmt)) {
        $conn->close();
    }
}
?> 