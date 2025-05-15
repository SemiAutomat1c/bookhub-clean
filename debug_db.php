<?php
require_once './config/database.php';

try {
    // Get database connection
    $conn = getConnection();
    if (!$conn) {
        die("Database connection failed\n");
    }
    
    echo "Database connection successful\n\n";
    
    // List all tables
    $result = $conn->query("SHOW TABLES");
    if (!$result) {
        die("Error listing tables: " . $conn->error . "\n");
    }
    
    echo "TABLES IN DATABASE:\n";
    $tables = [];
    while ($row = $result->fetch_row()) {
        $tables[] = $row[0];
        echo "- " . $row[0] . "\n";
    }
    
    echo "\n";
    
    // Check if reading_list or reading_lists exists
    if (in_array('reading_list', $tables)) {
        echo "READING_LIST TABLE FOUND\n";
        
        // Show reading_list structure
        $result = $conn->query("DESCRIBE reading_list");
        
        echo "READING_LIST STRUCTURE:\n";
        while ($row = $result->fetch_assoc()) {
            echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
        }
        
        // Count entries in reading_list
        $result = $conn->query("SELECT COUNT(*) as count FROM reading_list");
        $count = $result->fetch_assoc()['count'];
        echo "\nTOTAL ENTRIES IN READING_LIST: " . $count . "\n";
        
        // Get sample entries from reading_list
        $result = $conn->query("SELECT * FROM reading_list LIMIT 3");
        
        echo "\nSAMPLE ENTRIES:\n";
        while ($row = $result->fetch_assoc()) {
            echo json_encode($row) . "\n";
        }
    }
    
    if (in_array('reading_lists', $tables)) {
        echo "\nREADING_LISTS TABLE FOUND\n";
        
        // Show reading_lists structure
        $result = $conn->query("DESCRIBE reading_lists");
        
        echo "READING_LISTS STRUCTURE:\n";
        while ($row = $result->fetch_assoc()) {
            echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
        }
        
        // Count entries in reading_lists
        $result = $conn->query("SELECT COUNT(*) as count FROM reading_lists");
        $count = $result->fetch_assoc()['count'];
        echo "\nTOTAL ENTRIES IN READING_LISTS: " . $count . "\n";
        
        // Get sample entries from reading_lists
        $result = $conn->query("SELECT * FROM reading_lists LIMIT 3");
        
        echo "\nSAMPLE ENTRIES:\n";
        while ($row = $result->fetch_assoc()) {
            echo json_encode($row) . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
} finally {
    if (isset($conn) && !($conn instanceof mysqli_stmt)) {
        $conn->close();
    }
}
?> 