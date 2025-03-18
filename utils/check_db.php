<?php
require_once '../config/database.php';

// Check if users table exists
$tableCheck = $conn->query("SHOW TABLES LIKE 'users'");
if ($tableCheck->num_rows == 0) {
    // Create users table
    $sql = "CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_email (email)
    )";
    
    if ($conn->query($sql)) {
        echo "Users table created successfully<br>";
    } else {
        echo "Error creating users table: " . $conn->error . "<br>";
    }
} else {
    echo "Users table exists<br>";
}

// Show table structure
$result = $conn->query("DESCRIBE users");
echo "<br>Table structure:<br>";
while ($row = $result->fetch_assoc()) {
    echo "{$row['Field']} - {$row['Type']} - {$row['Null']} - {$row['Key']}<br>";
}

// Test database connection
echo "<br>Database connection status: ";
if ($conn->ping()) {
    echo "OK";
} else {
    echo "Failed: " . $conn->error;
}
?> 