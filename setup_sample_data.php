<?php
require_once 'config.php';

class DatabaseSeeder {
    private $conn;
    private $sampleBooks = [
        [
            'title' => 'The Great Gatsby',
            'author' => 'F. Scott Fitzgerald',
            'cover' => 'covers/gatsby.jpg',
            'description' => 'A story of decadence and excess, Gatsby explores the darker aspects of the American Dream.',
            'genre' => 'Classic',
            'year' => '1925',
            'file_path' => 'books/gatsby.pdf',
            'file_type' => 'PDF'
        ],
        [
            'title' => '1984',
            'author' => 'George Orwell',
            'cover' => 'covers/1984.jpg',
            'description' => 'A dystopian social science fiction novel focusing on the consequences of totalitarianism.',
            'genre' => 'Science Fiction',
            'year' => '1949',
            'file_path' => 'books/1984.epub',
            'file_type' => 'EPUB'
        ],
        [
            'title' => 'To Kill a Mockingbird',
            'author' => 'Harper Lee',
            'cover' => 'covers/mockingbird.jpg',
            'description' => 'A story of racial injustice and the loss of innocence in the American South.',
            'genre' => 'Classic',
            'year' => '1960',
            'file_path' => 'books/mockingbird.pdf',
            'file_type' => 'PDF'
        ],
        [
            'title' => 'The Hobbit',
            'author' => 'J.R.R. Tolkien',
            'cover' => 'covers/hobbit.jpg',
            'description' => 'A fantasy novel about the adventures of Bilbo Baggins.',
            'genre' => 'Fantasy',
            'year' => '1937',
            'file_path' => 'books/hobbit.epub',
            'file_type' => 'EPUB'
        ],
        [
            'title' => 'Pride and Prejudice',
            'author' => 'Jane Austen',
            'cover' => 'covers/pride.jpg',
            'description' => 'A romantic novel following the emotional development of Elizabeth Bennet.',
            'genre' => 'Romance',
            'year' => '1813',
            'file_path' => 'books/pride.pdf',
            'file_type' => 'PDF'
        ]
    ];

    public function __construct() {
        $this->conn = new mysqli($GLOBALS['host'], $GLOBALS['username'], $GLOBALS['password'], $GLOBALS['database']);
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
    }

    public function createTables() {
        // Create books table if not exists
        $sql = "CREATE TABLE IF NOT EXISTS books (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            author VARCHAR(255) NOT NULL,
            cover VARCHAR(255),
            description TEXT,
            genre VARCHAR(50),
            year VARCHAR(4),
            file_path VARCHAR(255),
            file_type VARCHAR(10),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        if ($this->conn->query($sql) === TRUE) {
            echo "Books table created successfully\n";
        } else {
            echo "Error creating books table: " . $this->conn->error . "\n";
        }

        // Create users table if not exists
        $sql = "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        if ($this->conn->query($sql) === TRUE) {
            echo "Users table created successfully\n";
        } else {
            echo "Error creating users table: " . $this->conn->error . "\n";
        }
    }

    public function seedBooks() {
        // Clear existing books
        $this->conn->query("TRUNCATE TABLE books");
        
        $stmt = $this->conn->prepare("INSERT INTO books (title, author, cover, description, genre, year, file_path, file_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($this->sampleBooks as $book) {
            $stmt->bind_param("ssssssss", 
                $book['title'],
                $book['author'],
                $book['cover'],
                $book['description'],
                $book['genre'],
                $book['year'],
                $book['file_path'],
                $book['file_type']
            );
            
            if ($stmt->execute()) {
                echo "Added book: {$book['title']}\n";
            } else {
                echo "Error adding book {$book['title']}: " . $stmt->error . "\n";
            }
        }
        
        $stmt->close();
    }

    public function createSampleUser() {
        // Create a sample user for testing
        $name = "Demo User";
        $email = "demo@bookhub.com";
        $password = password_hash("demo123", PASSWORD_DEFAULT);
        
        $stmt = $this->conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $password);
        
        if ($stmt->execute()) {
            echo "Created sample user: $email (password: demo123)\n";
        } else {
            echo "Error creating sample user: " . $stmt->error . "\n";
        }
        
        $stmt->close();
    }

    public function __destruct() {
        $this->conn->close();
    }
}

// Run the seeder
echo "=== Setting up BookHub Sample Data ===\n";
$seeder = new DatabaseSeeder();
$seeder->createTables();
$seeder->seedBooks();
$seeder->createSampleUser();
echo "=== Setup Complete ===\n"; 