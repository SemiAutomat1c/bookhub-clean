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

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo "ERROR|User not logged in";
        exit;
    }

    $userId = $_SESSION['user_id'];
    
    // Get database connection
    $conn = getConnection();
    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    // Initialize counts
    $stats = [
        'currently-reading' => 0,
        'want-to-read' => 0,
        'completed' => 0
    ];
    
    // Check if reading_list table exists
    $tables_result = $conn->query("SHOW TABLES LIKE 'reading_list'");
    $reading_list_exists = ($tables_result->num_rows > 0);
    
    // Check if reading_lists table exists
    $tables_result = $conn->query("SHOW TABLES LIKE 'reading_lists'");
    $reading_lists_exists = ($tables_result->num_rows > 0);
    
    // Query reading_list if it exists
    if ($reading_list_exists) {
        $sql = "SELECT 
                    list_type, 
                    COUNT(*) as count 
                FROM 
                    reading_list 
                WHERE 
                    user_id = ? 
                GROUP BY 
                    list_type";
        
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $userId);
            
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                
                // Populate counts from query results
                while ($row = $result->fetch_assoc()) {
                    if (isset($stats[$row['list_type']])) {
                        $stats[$row['list_type']] = $row['count'];
                    }
                }
            }
            
            $stmt->close();
        }
    }
    
    // Query reading_lists if it exists and combine the results
    if ($reading_lists_exists) {
        $sql = "SELECT 
                    list_type, 
                    COUNT(*) as count 
                FROM 
                    reading_lists 
                WHERE 
                    user_id = ? 
                GROUP BY 
                    list_type";
        
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $userId);
            
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                
                // Combine with previous stats - we'll use the highest count
                while ($row = $result->fetch_assoc()) {
                    if (isset($stats[$row['list_type']])) {
                        $stats[$row['list_type']] = max($stats[$row['list_type']], $row['count']);
                    }
                }
            }
            
            $stmt->close();
        }
    }
    
    // Return success with combined stats
    echo "SUCCESS\n";
    foreach ($stats as $type => $count) {
        echo "$type:$count\n";
    }

} catch (Exception $e) {
    error_log("Stats error: " . $e->getMessage());
    echo "ERROR|" . $e->getMessage();
} finally {
    if (isset($conn) && !($conn instanceof mysqli_stmt)) {
        $conn->close();
    }
}
?> 