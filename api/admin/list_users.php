<?php
session_start();

// Set headers
header('Content-Type: text/plain');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once '../../config/database.php';

try {
    // Verify admin access
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
        throw new Exception("Unauthorized access");
    }

    // Get database connection
    $conn = getConnection();
    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    // Get search parameters
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $role = isset($_GET['role']) ? $_GET['role'] : '';
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'username';

    // Build base query
    $query = "SELECT 
        u.user_id,
        u.username,
        u.email,
        u.is_admin,
        u.created_at,
        u.last_login,
        COUNT(DISTINCT rl.book_id) as books_count
    FROM users u
    LEFT JOIN reading_lists rl ON u.user_id = rl.user_id
    ";

    $whereConditions = [];
    $params = [];
    $types = "";

    // Add search conditions if provided
    if (!empty($search)) {
        $whereConditions[] = "(u.username LIKE ? OR u.email LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= "ss";
    }

    // Add role filter if provided
    if (!empty($role)) {
        $whereConditions[] = "u.is_admin = ?";
        $params[] = ($role === 'admin' ? 1 : 0);
        $types .= "i";
    }

    // Combine WHERE conditions
    if (!empty($whereConditions)) {
        $query .= " WHERE " . implode(" AND ", $whereConditions);
    }

    // Add GROUP BY
    $query .= " GROUP BY u.user_id";

    // Add sorting
    $query .= " ORDER BY ";
    switch ($sort) {
        case 'email':
            $query .= "u.email ASC";
            break;
        case 'date':
            $query .= "u.created_at DESC";
            break;
        default:
            $query .= "u.username ASC";
    }

    // Prepare and execute statement
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        throw new Exception("Failed to execute query: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $users = [];

    while ($row = $result->fetch_assoc()) {
        // Include all users, including the current admin
        $users[] = implode("|", [
            $row['user_id'],
            $row['username'],
            $row['email'],
            $row['is_admin'] ? 'admin' : 'user',
            $row['created_at'],
            $row['last_login'] ?? 'Never',
            $row['books_count']
        ]);
    }

    if (count($users) > 0) {
        echo "SUCCESS|" . implode("\n", $users);
    } else {
        echo "SUCCESS|NO_USERS";
    }

} catch (Exception $e) {
    echo "ERROR|" . $e->getMessage();
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
?> 