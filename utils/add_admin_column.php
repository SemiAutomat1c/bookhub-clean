<?php
require_once '../config/database.php';

// Add is_admin column if it doesn't exist
$columnCheck = $conn->query("SHOW COLUMNS FROM users LIKE 'is_admin'");
if ($columnCheck->num_rows == 0) {
    $sql = "ALTER TABLE users ADD COLUMN is_admin TINYINT(1) DEFAULT 0";
    if ($conn->query($sql)) {
        echo "Added is_admin column successfully\n";
    } else {
        echo "Error adding is_admin column: " . $conn->error . "\n";
    }
} else {
    echo "is_admin column already exists\n";
}

// Update your user to be an admin (replace YOUR_EMAIL with your actual email)
$email = isset($_GET['email']) ? $_GET['email'] : '';
if ($email) {
    $stmt = $conn->prepare("UPDATE users SET is_admin = 1 WHERE email = ?");
    $stmt->bind_param("s", $email);
    if ($stmt->execute()) {
        echo "Updated user as admin successfully\n";
    } else {
        echo "Error updating user: " . $stmt->error . "\n";
    }
}

$conn->close();
?> 