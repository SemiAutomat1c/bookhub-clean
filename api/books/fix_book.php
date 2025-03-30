<?php
session_start();
require_once '../../config/database.php';

// Set headers
header('Content-Type: text/plain');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Verify admin access
    if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
        throw new Exception("Unauthorized access");
    }

    // Get database connection
    $conn = getConnection();
    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    // First, verify the book exists and get current data
    $check = $conn->prepare("SELECT * FROM books WHERE book_id = 4");
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Book with ID 4 not found");
    }
    
    $currentData = $result->fetch_assoc();
    echo "Current book data:\n";
    print_r($currentData);
    
    // Update the book data using direct query first to ensure it works
    $updateQuery = "UPDATE books SET 
        title = '1984',
        author = 'George Orwell',
        description = 'A dystopian social science fiction novel that explores themes of totalitarianism and surveillance.',
        genre = 'Fiction',
        publication_year = '1949'
        WHERE book_id = 4";
        
    if (!$conn->query($updateQuery)) {
        throw new Exception("Failed to update book: " . $conn->error);
    }
    
    echo "\nRows affected: " . $conn->affected_rows . "\n";
    
    // Verify the update
    $verify = $conn->prepare("SELECT * FROM books WHERE book_id = 4");
    $verify->execute();
    $updated = $verify->get_result()->fetch_assoc();
    
    echo "\nUpdated book data:\n";
    print_r($updated);
    
    if ($conn->affected_rows > 0) {
        echo "\nSUCCESS|Book updated successfully";
    } else {
        // Check if any values actually changed
        $changes = array();
        if ($currentData['title'] !== '1984') $changes[] = 'title';
        if ($currentData['author'] !== 'George Orwell') $changes[] = 'author';
        if ($currentData['description'] !== 'A dystopian social science fiction novel that explores themes of totalitarianism and surveillance.') $changes[] = 'description';
        if ($currentData['genre'] !== 'Fiction') $changes[] = 'genre';
        if ($currentData['publication_year'] !== '1949') $changes[] = 'publication_year';
        
        if (empty($changes)) {
            echo "\nNo changes needed - data already up to date";
        } else {
            echo "\nExpected changes in: " . implode(', ', $changes);
            throw new Exception("Update failed - no rows affected despite changes needed");
        }
    }

} catch (Exception $e) {
    echo "\nERROR|" . $e->getMessage();
} finally {
    if (isset($check)) $check->close();
    if (isset($verify)) $verify->close();
    if (isset($conn) && $conn instanceof mysqli) $conn->close();
}
?> 