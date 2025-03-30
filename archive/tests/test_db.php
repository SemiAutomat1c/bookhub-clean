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

echo "Testing Database Setup:\n\n";

// Test 1: Check books in vw_books_details
echo "1. Testing vw_books_details view:\n";
$result = $conn->query("SELECT * FROM vw_books_details LIMIT 3");
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "Book: {$row['title']} by {$row['author']}\n";
        echo "Rating: {$row['average_rating']} ({$row['total_ratings']} ratings)\n";
        echo "-------------------\n";
    }
} else {
    echo "No books found in view\n";
}

// Test 2: Test author book count function
echo "\n2. Testing fn_get_author_book_count function:\n";
$result = $conn->query("SELECT DISTINCT author FROM books LIMIT 2");
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $author = $row['author'];
        $count = $conn->query("SELECT fn_get_author_book_count('$author') as count")->fetch_assoc()['count'];
        echo "Author: $author has $count book(s)\n";
    }
}

// Test 3: Check popular books view
echo "\n3. Testing vw_popular_books view:\n";
$result = $conn->query("SELECT * FROM vw_popular_books LIMIT 2");
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "Popular Book: {$row['title']}\n";
        echo "Rating: {$row['average_rating']} ({$row['total_ratings']} ratings)\n";
        echo "-------------------\n";
    }
} else {
    echo "No books found in popular books view\n";
}

$conn->close();
?>
