<?php
require_once '../config/database.php';

try {
    $pdo = getConnection();
    
    // Drop tables in correct order
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("DROP TABLE IF EXISTS reading_progress");
    $pdo->exec("DROP TABLE IF EXISTS ratings");
    $pdo->exec("DROP TABLE IF EXISTS reading_list");
    $pdo->exec("DROP TABLE IF EXISTS books");
    $pdo->exec("DROP TABLE IF EXISTS users");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    // Create tables
    $pdo->exec("
        CREATE TABLE users (
            user_id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(255) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            last_login TIMESTAMP NULL,
            is_active BOOLEAN DEFAULT TRUE,
            reset_token VARCHAR(255) NULL,
            reset_token_expires TIMESTAMP NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    $pdo->exec("
        CREATE TABLE books (
            book_id INT PRIMARY KEY AUTO_INCREMENT,
            title VARCHAR(255) NOT NULL,
            author VARCHAR(255) NOT NULL,
            description TEXT,
            cover_image VARCHAR(255),
            genre VARCHAR(50),
            publication_year INT,
            file_path VARCHAR(255),
            file_type VARCHAR(10) DEFAULT 'pdf',
            total_pages INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_author (author),
            INDEX idx_genre (genre)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    $pdo->exec("
        CREATE TABLE ratings (
            rating_id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            book_id INT NOT NULL,
            rating DECIMAL(2,1) NOT NULL CHECK (rating >= 0 AND rating <= 5),
            review TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
            FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE,
            UNIQUE KEY unique_user_book_rating (user_id, book_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    $pdo->exec("
        CREATE TABLE reading_progress (
            progress_id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            book_id INT NOT NULL,
            current_page INT DEFAULT 0,
            is_completed BOOLEAN DEFAULT FALSE,
            last_read_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
            FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE,
            UNIQUE KEY unique_user_book_progress (user_id, book_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    $pdo->exec("
        CREATE TABLE reading_list (
            list_id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            book_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
            FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE,
            UNIQUE KEY unique_user_book_list (user_id, book_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    // Insert test user
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, full_name) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        'johndoe',
        'john@example.com',
        password_hash('Test123!', PASSWORD_DEFAULT),
        'John Doe'
    ]);
    
    // Insert sample book
    $stmt = $pdo->prepare("INSERT INTO books (title, author, description, genre, publication_year) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        'The Great Gatsby',
        'F. Scott Fitzgerald',
        'A story of decadence and excess, The Great Gatsby portrays the lives of wealthy New Yorkers during the Roaring Twenties.',
        'Fiction',
        1925
    ]);
    
    echo "Database setup completed successfully!\n\n";
    echo "Test user created:\n";
    echo "Email: john@example.com\n";
    echo "Password: Test123!\n\n";
    echo "Sample book added: The Great Gatsby\n";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
?>
