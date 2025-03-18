<?php
require_once '../../config/database.php';

// Set headers
header('Content-Type: text/plain');

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "ERROR: Not logged in";
    exit;
}

$conn = getConnection();

// Check if user is admin
$stmt = $conn->prepare("SELECT is_admin FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "ERROR: User not found";
    exit;
}

$user = $result->fetch_assoc();

if ($user['is_admin'] == 1) {
    $_SESSION['is_admin'] = true;
    echo "SUCCESS";
} else {
    echo "ERROR: Unauthorized";
}

$conn->close();
?> 