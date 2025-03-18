<?php
session_start();
require_once '../../config/database.php';

// Set headers
header('Content-Type: text/plain');
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
$allowed_origins = array(
    'http://localhost',
    'http://127.0.0.1',
    'http://localhost:80',
    'http://localhost:8080'
);
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: POST');
    header('Access-Control-Allow-Headers: Content-Type');
}

try {
    // Check if user is logged in
    error_log("Session data in add.php: " . print_r($_SESSION, true));
    
    if (!isset($_SESSION['user_id'])) {
        error_log("User not authenticated in add.php");
        echo "ERROR|User not authenticated";
        exit;
    }

    // Get POST data
    $input = file_get_contents('php://input');
    error_log("Received input in add.php: " . $input);
    
    $data = array();
    
    // Parse input string with format "key1:value1|key2:value2"
    $pairs = explode('|', $input);
    foreach ($pairs as $pair) {
        $parts = explode(':', $pair);
        if (count($parts) == 2) {
            $data[trim($parts[0])] = trim($parts[1]);
        }
    }
    error_log("Parsed data in add.php: " . print_r($data, true));
    
    if (!isset($data['book_id']) || !isset($data['list_type'])) {
        error_log("Missing required fields in add.php. Data received: " . print_r($data, true));
        echo "ERROR|Missing required fields";
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $book_id = $data['book_id'];
    $list_type = $data['list_type'];
    
    error_log("Processing add request - User ID: $user_id, Book ID: $book_id, List Type: $list_type");
    
    // Validate list type
    $valid_list_types = ['want-to-read', 'currently-reading', 'completed'];
    if (!in_array($list_type, $valid_list_types)) {
        error_log("Invalid list type: $list_type");
        echo "ERROR|Invalid list type";
        exit;
    }

    // Get database connection
    $conn = getConnection();
    if (!$conn) {
        error_log("Database connection failed in add.php");
        throw new Exception("Database connection failed");
    }

    // Debug the tables in the database
    $tables_result = $conn->query("SHOW TABLES");
    error_log("Tables in database:");
    while ($table = $tables_result->fetch_array()) {
        error_log("- " . $table[0]);
    }

    // Check if book exists
    $check_book_sql = "SELECT book_id FROM books WHERE book_id = ?";
    $check_book_stmt = $conn->prepare($check_book_sql);
    $check_book_stmt->bind_param("i", $book_id);
    $check_book_stmt->execute();
    $book_result = $check_book_stmt->get_result();
    error_log("Book check result - Found rows: " . $book_result->num_rows);
    
    if ($book_result->num_rows === 0) {
        error_log("Book not found: $book_id");
        throw new Exception("Book not found");
    }

    // Check if entry already exists
    $check_sql = "SELECT list_id FROM reading_lists WHERE user_id = ? AND book_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $user_id, $book_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    error_log("Existing entry check - Found rows: " . $result->num_rows);
    
    if ($result->num_rows > 0) {
        // Update existing entry
        $sql = "UPDATE reading_lists SET list_type = ?, last_updated = CURRENT_TIMESTAMP WHERE user_id = ? AND book_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $list_type, $user_id, $book_id);
        error_log("Updating existing entry with SQL: " . $sql);
    } else {
        // Insert new entry
        $sql = "INSERT INTO reading_lists (user_id, book_id, list_type, progress) VALUES (?, ?, ?, 0)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iis", $user_id, $book_id, $list_type);
        error_log("Inserting new entry with SQL: " . $sql);
    }

    if (!$stmt->execute()) {
        error_log("Database error: " . $stmt->error);
        throw new Exception("Failed to add book to reading list: " . $stmt->error);
    }
    error_log("Database operation successful - Affected rows: " . $stmt->affected_rows);

    // Verify the entry was added/updated
    $verify_sql = "SELECT * FROM reading_lists WHERE user_id = ? AND book_id = ?";
    $verify_stmt = $conn->prepare($verify_sql);
    $verify_stmt->bind_param("ii", $user_id, $book_id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();
    error_log("Verification check - Found rows: " . $verify_result->num_rows);
    if ($verify_row = $verify_result->fetch_assoc()) {
        error_log("Verified entry: " . print_r($verify_row, true));
    }

    echo "SUCCESS|Book added to reading list";

} catch (Exception $e) {
    error_log("Add to reading list error: " . $e->getMessage());
    echo "ERROR|" . $e->getMessage();
} finally {
    if (isset($check_book_stmt)) {
        $check_book_stmt->close();
    }
    if (isset($check_stmt)) {
        $check_stmt->close();
    }
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($verify_stmt)) {
        $verify_stmt->close();
    }
}
?> 