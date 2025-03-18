<?php
require_once '../config/database.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Create connection without database
    $tempConn = new mysqli($host, $username, $password);
    
    // Check connection
    if ($tempConn->connect_error) {
        die("ERROR: Connection failed: " . $tempConn->connect_error);
    }

    // Create database
    $sql = "CREATE DATABASE IF NOT EXISTS $database";
    if ($tempConn->query($sql)) {
        echo "Database created successfully<br>";
    } else {
        die("Error creating database: " . $tempConn->error);
    }

    // Close temporary connection
    $tempConn->close();

    // Get connection to the new database
    $conn = getDBConnection();

    // Create users table
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        is_admin TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    if ($conn->query($sql)) {
        echo "Users table created successfully<br>";
    } else {
        die("Error creating users table: " . $conn->error);
    }

    // Create books table
    $sql = "CREATE TABLE IF NOT EXISTS books (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        author VARCHAR(100) NOT NULL,
        description TEXT,
        cover_image VARCHAR(255),
        genre VARCHAR(50),
        publication_year INT,
        rating DECIMAL(3,2) DEFAULT 0,
        total_ratings INT DEFAULT 0,
        file_path VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    if ($conn->query($sql)) {
        echo "Books table created successfully<br>";
    } else {
        die("Error creating books table: " . $conn->error);
    }

    // Create reading_list table
    $sql = "CREATE TABLE IF NOT EXISTS reading_list (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        book_id INT NOT NULL,
        added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user_book (user_id, book_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    if ($conn->query($sql)) {
        echo "Reading list table created successfully<br>";
    } else {
        die("Error creating reading_list table: " . $conn->error);
    }

    // Create reviews table
    $sql = "CREATE TABLE IF NOT EXISTS reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        book_id INT NOT NULL,
        rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
        review_text TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user_book_review (user_id, book_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    if ($conn->query($sql)) {
        echo "Reviews table created successfully<br>";
    } else {
        die("Error creating reviews table: " . $conn->error);
    }

    // Create default admin user if not exists
    $adminEmail = 'admin@bookhub.com';
    $adminPassword = 'admin123';
    $adminFullName = 'Admin User';
    $adminUsername = 'admin';

    // Check if admin exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $adminEmail);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        // Create admin user
        $passwordHash = password_hash($adminPassword, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, full_name, is_admin) VALUES (?, ?, ?, ?, 1)");
        $stmt->bind_param("ssss", $adminUsername, $adminEmail, $passwordHash, $adminFullName);
        
        if ($stmt->execute()) {
            echo "Admin user created successfully<br>";
            echo "Email: admin@bookhub.com<br>";
            echo "Password: admin123<br>";
        } else {
            echo "Error creating admin user: " . $stmt->error . "<br>";
        }
    } else {
        echo "Admin user already exists<br>";
    }

    echo "<br>Database setup completed successfully!";

} catch (Exception $e) {
    die("ERROR: " . $e->getMessage());
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?> 