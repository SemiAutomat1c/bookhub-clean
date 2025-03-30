<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$conn = new mysqli('localhost', 'root', '', 'bookhub');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

try {
    // Backup existing data
    $books = [];
    $result = $conn->query("SELECT * FROM books");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $books[] = $row;
        }
    }

    // Backup ratings data
    $ratings = [];
    $result = $conn->query("SELECT * FROM ratings");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $ratings[] = $row;
        }
    }

    // Drop tables in correct order (ratings first due to foreign key)
    $conn->query("DROP TABLE IF EXISTS ratings");
    $conn->query("DROP TABLE IF EXISTS books");

    // Recreate books table
    $sql = "CREATE TABLE books (
        book_id INT PRIMARY KEY AUTO_INCREMENT,
        title VARCHAR(255) NOT NULL,
        author VARCHAR(255) NOT NULL,
        description TEXT,
        genre VARCHAR(50),
        publication_year INT,
        cover_image VARCHAR(255),
        file_path VARCHAR(255),
        file_type VARCHAR(10) DEFAULT 'pdf',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    if (!$conn->query($sql)) {
        throw new Exception("Error creating books table: " . $conn->error);
    }

    // Recreate ratings table
    $sql = "CREATE TABLE ratings (
        rating_id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT,
        book_id INT,
        rating INT CHECK (rating >= 1 AND rating <= 5),
        review TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE,
        UNIQUE KEY unique_user_book_rating (user_id, book_id)
    )";

    if (!$conn->query($sql)) {
        throw new Exception("Error creating ratings table: " . $conn->error);
    }

    // Restore books data
    if (!empty($books)) {
        foreach ($books as $book) {
            $stmt = $conn->prepare("INSERT INTO books (book_id, title, author, description, genre, publication_year, cover_image, file_path, file_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssisss", 
                $book['book_id'],
                $book['title'],
                $book['author'],
                $book['description'],
                $book['genre'],
                $book['publication_year'],
                $book['cover_image'],
                $book['file_path'],
                $book['file_type']
            );
            $stmt->execute();
            $stmt->close();
        }
    }

    // Restore ratings data
    if (!empty($ratings)) {
        foreach ($ratings as $rating) {
            $stmt = $conn->prepare("INSERT INTO ratings (rating_id, user_id, book_id, rating, review) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iiiis", 
                $rating['rating_id'],
                $rating['user_id'],
                $rating['book_id'],
                $rating['rating'],
                $rating['review']
            );
            $stmt->execute();
            $stmt->close();
        }
    }

    // Set default publication years for books without them
    $sql = "UPDATE books SET publication_year = YEAR(CURRENT_DATE()) WHERE publication_year IS NULL";
    $conn->query($sql);

    echo "Database tables recreated successfully with all data preserved!";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
} finally {
    $conn->close();
}
?> 