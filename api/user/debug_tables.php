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
    // Get database connection
    $conn = getConnection();
    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    echo "Database Connection Successful\n\n";
    
    // Check reading_list table structure
    $sql = "DESCRIBE reading_list";
    $result = $conn->query($sql);
    
    if (!$result) {
        echo "Error describing reading_list: " . $conn->error . "\n";
        
        // Try with reading_lists (plural)
        $sql = "DESCRIBE reading_lists";
        $result = $conn->query($sql);
        
        if (!$result) {
            echo "Error describing reading_lists: " . $conn->error . "\n";
        } else {
            echo "READING_LISTS TABLE STRUCTURE:\n";
            while ($row = $result->fetch_assoc()) {
                echo $row['Field'] . " - " . $row['Type'] . " - " . $row['Key'] . "\n";
            }
        }
    } else {
        echo "READING_LIST TABLE STRUCTURE:\n";
        while ($row = $result->fetch_assoc()) {
            echo $row['Field'] . " - " . $row['Type'] . " - " . $row['Key'] . "\n";
        }
    }
    
    echo "\n";
    
    // Check books table structure
    $sql = "DESCRIBE books";
    $result = $conn->query($sql);
    
    if (!$result) {
        echo "Error describing books: " . $conn->error . "\n";
    } else {
        echo "BOOKS TABLE STRUCTURE:\n";
        while ($row = $result->fetch_assoc()) {
            echo $row['Field'] . " - " . $row['Type'] . " - " . $row['Key'] . "\n";
        }
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
} finally {
    if (isset($conn) && !($conn instanceof mysqli_stmt)) {
        $conn->close();
    }
}
?> 