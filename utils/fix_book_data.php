<?php
require_once 'config/database.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get database connection
$conn = getConnection();
if (!$conn) {
    die("Database connection failed");
}

try {
    // First, verify the book exists
    $check = $conn->prepare("SELECT * FROM books WHERE book_id = 4");
    $check->execute();
    $result = $check->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Book with ID 4 not found");
    }
    
    echo "Current book data:\n";
    print_r($result->fetch_assoc());
    
    // Update the book data
    $stmt = $conn->prepare("UPDATE books SET 
        title = ?,
        author = ?,
        description = ?,
        genre = ?,
        publication_year = ?
        WHERE book_id = 4");
        
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    
    $title = '1984';
    $author = 'George Orwell';
    $description = 'A dystopian social science fiction novel that explores themes of totalitarianism and surveillance.';
    $genre = 'Fiction';
    $year = '1949';
    
    $stmt->bind_param("sssss", $title, $author, $description, $genre, $year);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to update book: " . $stmt->error);
    }
    
    if ($stmt->affected_rows === 0) {
        throw new Exception("No rows were updated");
    }
    
    // Verify the update
    $verify = $conn->prepare("SELECT * FROM books WHERE book_id = 4");
    $verify->execute();
    $updated = $verify->get_result()->fetch_assoc();
    
    echo "\nBook updated successfully!\n";
    echo "New book data:\n";
    print_r($updated);

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($check)) $check->close();
    if (isset($verify)) $verify->close();
    $conn->close();
}
?> 