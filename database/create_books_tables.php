<?php
require_once __DIR__ . '/../config/database.php';

try {
    // Disable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");

    // Create books table
    $conn->query("CREATE TABLE IF NOT EXISTS books (
        id INT PRIMARY KEY AUTO_INCREMENT,
        title VARCHAR(255) NOT NULL,
        author VARCHAR(100) NOT NULL,
        description TEXT,
        cover_url VARCHAR(255),
        genre VARCHAR(50),
        publication_year INT,
        publisher VARCHAR(100),
        language VARCHAR(50) DEFAULT 'English',
        page_count INT,
        average_rating DECIMAL(3,2) DEFAULT 0.00,
        total_reviews INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_title (title),
        INDEX idx_author (author)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Create reviews table
    $conn->query("CREATE TABLE IF NOT EXISTS reviews (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        book_id INT NOT NULL,
        content TEXT NOT NULL,
        rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user_book_review (user_id, book_id),
        INDEX idx_book_rating (book_id, rating)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Create reading list table
    $conn->query("CREATE TABLE IF NOT EXISTS reading_list (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        book_id INT NOT NULL,
        status ENUM('want_to_read', 'currently_reading', 'finished') DEFAULT 'want_to_read',
        progress_percent INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
        UNIQUE KEY unique_user_book (user_id, book_id),
        INDEX idx_user_status (user_id, status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Insert sample books
    $sample_books = [
        [
            'title' => 'The Great Gatsby',
            'author' => 'F. Scott Fitzgerald',
            'description' => 'A story of the fabulously wealthy Jay Gatsby and his love for the beautiful Daisy Buchanan.',
            'genre' => 'Fiction',
            'publication_year' => 1925
        ],
        [
            'title' => '1984',
            'author' => 'George Orwell',
            'description' => 'A dystopian social science fiction novel that follows Winston Smith in a totalitarian future society.',
            'genre' => 'Science Fiction',
            'publication_year' => 1949
        ],
        [
            'title' => 'To Kill a Mockingbird',
            'author' => 'Harper Lee',
            'description' => 'The story of young Scout Finch and her father Atticus, a lawyer who defends a black man accused of a terrible crime.',
            'genre' => 'Fiction',
            'publication_year' => 1960
        ]
    ];

    $stmt = $conn->prepare("INSERT IGNORE INTO books (title, author, description, genre, publication_year) VALUES (?, ?, ?, ?, ?)");
    
    foreach ($sample_books as $book) {
        $stmt->bind_param("ssssi", 
            $book['title'],
            $book['author'],
            $book['description'],
            $book['genre'],
            $book['publication_year']
        );
        $stmt->execute();
    }

    // Re-enable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");

    echo "SUCCESS|Books tables and sample data created successfully";
} catch (Exception $e) {
    echo "ERROR|" . $e->getMessage();
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
}
?> 