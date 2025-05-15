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
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        error_log("User not authenticated in get.php");
        echo "ERROR|User not authenticated";
        exit;
    }

    $user_id = $_SESSION['user_id'];
    error_log("Getting reading list for user_id: " . $user_id);
    
    // Get database connection
    $conn = getConnection();
    if (!$conn) {
        error_log("Database connection failed in get.php");
        throw new Exception("Database connection failed");
    }

    // Debug the tables in the database
    $tables_result = $conn->query("SHOW TABLES");
    error_log("Tables in database:");
    while ($table = $tables_result->fetch_array()) {
        error_log("- " . $table[0]);
    }

    // Debug the reading_list table structure
    $structure_result = $conn->query("DESCRIBE reading_list");
    error_log("Reading list table structure:");
    while ($field = $structure_result->fetch_assoc()) {
        error_log("- " . $field['Field'] . " (" . $field['Type'] . ")");
    }

    // Get user's reading lists with book details from both tables
    $all_books = [];
    
    // Check if reading_list table exists
    $tables_result = $conn->query("SHOW TABLES LIKE 'reading_list'");
    $reading_list_exists = ($tables_result->num_rows > 0);
    
    // Check if reading_lists table exists
    $tables_result = $conn->query("SHOW TABLES LIKE 'reading_lists'");
    $reading_lists_exists = ($tables_result->num_rows > 0);
    
    // Function to process results into categorized book lists
    function processBooks($result, &$want_to_read, &$currently_reading, &$completed) {
        while ($row = $result->fetch_assoc()) {
            error_log("Processing row: " . print_r($row, true));
            
            // Format book data with necessary details
            $book_data = implode('|', [
                $row['book_id'],
                $row['title'],
                $row['author'],
                $row['cover_image'] ?? '',
                $row['progress'] ?? '0'
            ]);
            
            error_log("List type: " . $row['list_type']);
            switch ($row['list_type']) {
                case 'want-to-read':
                    $want_to_read[$row['book_id']] = $book_data;
                    error_log("Added to want-to-read list");
                    break;
                case 'currently-reading':
                    $currently_reading[$row['book_id']] = $book_data;
                    error_log("Added to currently-reading list");
                    break;
                case 'completed':
                    $completed[$row['book_id']] = $book_data;
                    error_log("Added to completed list");
                    break;
            }
        }
    }
    
    // Initialize lists with book_id as key to avoid duplicates
    $want_to_read = [];
    $currently_reading = [];
    $completed = [];
    
    // Query reading_list if it exists
    if ($reading_list_exists) {
        $sql = "SELECT 
                    rl.book_id,
                    rl.list_type,
                    rl.progress,
                    b.title,
                    b.author,
                    b.description,
                    b.genre,
                    b.cover_image
                FROM reading_list rl
                JOIN books b ON rl.book_id = b.book_id
                WHERE rl.user_id = ?";
        
        error_log("Executing SQL query for reading_list: " . $sql);
        error_log("With user_id: " . $user_id);
        
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                error_log("reading_list query result rows: " . $result->num_rows);
                processBooks($result, $want_to_read, $currently_reading, $completed);
            }
            
            $stmt->close();
        }
    }
    
    // Query reading_lists if it exists
    if ($reading_lists_exists) {
        $sql = "SELECT 
                    rl.book_id,
                    rl.list_type,
                    rl.progress,
                    b.title,
                    b.author,
                    b.description,
                    b.genre,
                    b.cover_image
                FROM reading_lists rl
                JOIN books b ON rl.book_id = b.book_id
                WHERE rl.user_id = ?";
        
        error_log("Executing SQL query for reading_lists: " . $sql);
        error_log("With user_id: " . $user_id);
        
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                error_log("reading_lists query result rows: " . $result->num_rows);
                processBooks($result, $want_to_read, $currently_reading, $completed);
            }
            
            $stmt->close();
        }
    }
    
    // Format response
    echo "SUCCESS\n";
    
    // Want to read list
    echo "WANT_TO_READ\n";
    if (empty($want_to_read)) {
        echo "NO_BOOKS\n";
        error_log("Want to read list is empty");
    } else {
        error_log("Want to read list has " . count($want_to_read) . " books");
        foreach ($want_to_read as $book) {
            echo $book . "\n";
        }
    }
    
    // Currently reading list
    echo "CURRENTLY_READING\n";
    if (empty($currently_reading)) {
        echo "NO_BOOKS\n";
        error_log("Currently reading list is empty");
    } else {
        error_log("Currently reading list has " . count($currently_reading) . " books");
        foreach ($currently_reading as $book) {
            echo $book . "\n";
        }
    }
    
    // Completed list
    echo "COMPLETED\n";
    if (empty($completed)) {
        echo "NO_BOOKS\n";
        error_log("Completed list is empty");
    } else {
        error_log("Completed list has " . count($completed) . " books");
        foreach ($completed as $book) {
            echo $book . "\n";
        }
    }

} catch (Exception $e) {
    error_log("Reading list error: " . $e->getMessage());
    echo "ERROR|" . $e->getMessage();
}
?>