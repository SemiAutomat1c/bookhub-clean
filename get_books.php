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

// Get books using the view
$sql = "SELECT 
    b.*,
    COALESCE(AVG(r.rating), 0) as average_rating,
    COUNT(DISTINCT r.rating_id) as total_ratings
FROM books b
LEFT JOIN ratings r ON b.book_id = r.book_id
GROUP BY b.book_id
ORDER BY b.book_id ASC";

$result = $conn->query($sql);

$books = array();
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $books[] = implode('|', array(
            $row['book_id'],
            $row['title'],
            $row['author'],
            $row['cover_image'],
            $row['description'],
            $row['genre'],
            $row['file_path'],
            $row['file_type'],
            round($row['average_rating'], 1),
            $row['total_ratings']
        ));
    }
}

// If this file was requested directly, return plain text
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    header('Content-Type: text/plain');
    echo implode("\n", $books);
}

$conn->close();
?>
