<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'bookhub');

// Create connection
$conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if ($conn->query($sql) === TRUE) {
    echo "Database checked/created successfully<br>";
} else {
    die("Error creating database: " . $conn->error);
}

// Select the database
$conn->select_db(DB_NAME);

// Function to execute SQL file
function executeSQLFile($conn, $filename) {
    echo "Executing $filename...<br>";
    $sql = file_get_contents($filename);
    
    // Split SQL file into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach($statements as $statement) {
        if (!empty($statement)) {
            if ($conn->query($statement) === FALSE) {
                echo "Error executing statement: " . $conn->error . "<br>";
                echo "Statement: " . $statement . "<br>";
                return false;
            }
        }
    }
    echo "Successfully executed $filename<br>";
    return true;
}

// Execute SQL files in order
$files = [
    __DIR__ . '/create_tables.sql',
    __DIR__ . '/create_views.sql',
    __DIR__ . '/create_functions.sql'
];

foreach ($files as $file) {
    if (!file_exists($file)) {
        die("Error: File $file does not exist<br>");
    }
    if (!executeSQLFile($conn, $file)) {
        die("Error executing $file<br>");
    }
}

// Import sample books from text file if books table is empty
$result = $conn->query("SELECT COUNT(*) as count FROM books");
$row = $result->fetch_assoc();

if ($row['count'] == 0) {
    echo "Importing sample books from books.txt...<br>";
    $booksData = file_get_contents(__DIR__ . '/../books.txt');
    $books = array_filter(explode("\n", $booksData));
    
    foreach ($books as $book) {
        list(
            $title,
            $author,
            $cover,
            $description,
            $genre,
            $year,
            $file_path,
            $file_type
        ) = array_pad(explode('|', $book), 8, null);
        
        $title = $conn->real_escape_string($title);
        $author = $conn->real_escape_string($author);
        $cover = $conn->real_escape_string($cover);
        $description = $conn->real_escape_string($description);
        $genre = $genre ? "'" . $conn->real_escape_string($genre) . "'" : 'NULL';
        $year = $year ? intval($year) : 'NULL';
        $file_path = $file_path ? "'" . $conn->real_escape_string($file_path) . "'" : 'NULL';
        $file_type = $file_type ? "'" . $conn->real_escape_string($file_type) . "'" : 'NULL';
        
        $sql = "INSERT INTO books (
                    title, 
                    author, 
                    cover_image, 
                    description, 
                    genre, 
                    publication_year,
                    file_path,
                    file_type
                ) VALUES (
                    '$title',
                    '$author',
                    '$cover',
                    '$description',
                    $genre,
                    $year,
                    $file_path,
                    $file_type
                )";
        
        if ($conn->query($sql) === TRUE) {
            echo "Imported book: $title<br>";
        } else {
            echo "Error importing book $title: " . $conn->error . "<br>";
        }
    }
}

echo "Database setup completed!";
$conn->close();
?>
