<?php
session_start();

// Set headers
header('Content-Type: text/plain');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Debug logging
error_log("Login attempt started");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "error|Method not allowed";
    exit;
}

try {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        http_response_code(400);
        echo "error|Email and password are required";
        exit;
    }

    // Create database connection
    $conn = new mysqli('localhost', 'root', '', 'bookhub');
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    // Debug log
    error_log("Attempting login for email: " . $email);

    // Get user by email
    $stmt = $conn->prepare("SELECT user_id, username, email, password_hash, is_admin FROM users WHERE email = ?");
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }

    $stmt->bind_param("s", $email);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute query: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        error_log("User found, verifying password");
        error_log("Stored hash: " . $row['password_hash']);
        error_log("Input password: " . $password);
        
        if (password_verify($password, $row['password_hash'])) {
            error_log("Password verified successfully");
            
            // Update last_login timestamp
            $updateStmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
            $updateStmt->bind_param("i", $row['user_id']);
            $updateStmt->execute();
            $updateStmt->close();
            
            // Set session variables
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['email'] = $row['email'];
            $_SESSION['is_admin'] = $row['is_admin'];
            $_SESSION['last_activity'] = time();

            // Return success with user data
            echo "success|Login successful|{$row['user_id']},{$row['username']},{$row['email']}";
        } else {
            error_log("Password verification failed");
            http_response_code(401);
            echo "error|Invalid password";
        }
    } else {
        error_log("User not found for email: " . $email);
        http_response_code(401);
        echo "error|User not found";
    }

} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    http_response_code(500);
    echo "error|" . $e->getMessage();
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?>