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

echo "Testing New Database Functions:\n\n";

// Test 1: Reading Progress Calculation
echo "1. Testing fn_calculate_reading_progress:\n";
$result = $conn->query("SELECT fn_calculate_reading_progress(50, 200) as progress");
$progress = $result->fetch_assoc()['progress'];
echo "Reading progress for 50/200 pages: $progress%\n";

// Test 2: Genre Rating
echo "\n2. Testing fn_get_genre_rating:\n";
$result = $conn->query("SELECT DISTINCT genre FROM books WHERE genre IS NOT NULL LIMIT 2");
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $genre = $row['genre'];
        $rating = $conn->query("SELECT fn_get_genre_rating('$genre') as rating")->fetch_assoc()['rating'];
        echo "Genre '$genre' average rating: $rating\n";
    }
} else {
    echo "No genres found in books table\n";
}

// Test 3: Add some test ratings
echo "\n3. Adding test ratings:\n";
// First ensure we have a test user
$conn->query("INSERT IGNORE INTO users (username, email) VALUES ('test_user', 'test@example.com')");
$user_id = $conn->insert_id ?: 1;

// Add some ratings
$conn->query("INSERT IGNORE INTO ratings (book_id, user_id, rating) VALUES (1, $user_id, 4.5), (2, $user_id, 4.0)");
echo "Added test ratings for books\n";

// Test 4: Check user rating function
$result = $conn->query("SELECT fn_has_user_rated_book($user_id, 1) as has_rated");
$has_rated = $result->fetch_assoc()['has_rated'];
echo "\n4. Testing if user rated book:\n";
echo "Has test user rated book 1? " . ($has_rated ? "Yes" : "No") . "\n";

// Test 5: Add reading progress
echo "\n5. Testing reading progress:\n";
$conn->query("INSERT IGNORE INTO reading_progress (user_id, book_id, current_page) VALUES ($user_id, 1, 50), ($user_id, 2, 30)");

// Get reading count
$result = $conn->query("SELECT fn_get_user_reading_count($user_id) as count");
$count = $result->fetch_assoc()['count'];
echo "Test user is reading $count book(s)\n";

// Test 6: Get user's favorite genre
$result = $conn->query("SELECT fn_get_user_favorite_genre($user_id) as genre");
$genre = $result->fetch_assoc()['genre'];
echo "\n6. Testing user's favorite genre:\n";
echo "Test user's favorite genre: $genre\n";

// Test 7: Check genre ratings after adding test data
echo "\n7. Testing genre ratings after adding data:\n";
$result = $conn->query("SELECT DISTINCT genre FROM books WHERE genre IS NOT NULL LIMIT 2");
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $genre = $row['genre'];
        $rating = $conn->query("SELECT fn_get_genre_rating('$genre') as rating")->fetch_assoc()['rating'];
        echo "Genre '$genre' updated average rating: $rating\n";
    }
}

$conn->close();
?>
