<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'bookhub');

// Create connection
$conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

try {
    // Check if reading_lists table exists
    $result = $conn->query("SHOW TABLES LIKE 'reading_lists'");
    if ($result->num_rows > 0) {
        echo "Reading lists table already exists. No changes made.<br>";
    } else {
        // Create reading_lists table
        $sql = "CREATE TABLE reading_lists (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            book_id INT NOT NULL,
            list_type ENUM('want-to-read', 'currently-reading', 'completed') NOT NULL,
            progress INT DEFAULT 0,
            last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
            FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE,
            UNIQUE KEY unique_user_book (user_id, book_id)
        )";

        if ($conn->query($sql)) {
            echo "Reading lists table created successfully!<br>";
            
            // Add some sample reading list entries for the admin user
            $sql = "INSERT INTO reading_lists (user_id, book_id, list_type, progress) 
                   SELECT 1, book_id, 'want-to-read', 0 
                   FROM books 
                   WHERE book_id IN (SELECT book_id FROM books LIMIT 2)";
            
            if ($conn->query($sql)) {
                echo "Added sample reading list entries for admin user.<br>";
            } else {
                echo "Note: Could not add sample entries. This is normal if you don't have any books yet.<br>";
            }
        } else {
            throw new Exception("Error creating table: " . $conn->error);
        }
    }

    echo "<br>You can now use the reading list features!";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
} finally {
    $conn->close();
}
?> 