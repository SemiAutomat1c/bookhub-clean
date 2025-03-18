<?php
require_once __DIR__ . '/../config/database.php';

try {
    // Disable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");

    // Drop existing users table if it exists
    $conn->query("DROP TABLE IF EXISTS users");

    // Create users table
    $sql = "CREATE TABLE users (
        user_id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) UNIQUE NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_username (username)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    if (!$conn->query($sql)) {
        throw new Exception("Error creating users table: " . $conn->error);
    }

    // Re-enable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");

    echo "SUCCESS|Database tables created successfully";
} catch (Exception $e) {
    echo "ERROR|" . $e->getMessage();
} finally {
    // Make sure foreign key checks are re-enabled
    if (isset($conn)) {
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");
        $conn->close();
    }
}
?> 