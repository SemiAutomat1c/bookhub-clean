<?php
require_once __DIR__ . '/../database.php';

class DatabaseUtils {
    private $conn;

    public function __construct() {
        $this->conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
        if ($this->conn->connect_error) {
            throw new Exception("Connection failed: " . $this->conn->connect_error);
        }
    }

    // View: vw_book_ratings
    public function getBookRatings($bookId = null) {
        $sql = "SELECT * FROM vw_book_ratings";
        if ($bookId !== null) {
            $sql .= " WHERE book_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $bookId);
        } else {
            $stmt = $this->conn->prepare($sql);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // View: vw_user_reading_stats
    public function getUserReadingStats($userId = null) {
        $sql = "SELECT * FROM vw_user_reading_stats";
        if ($userId !== null) {
            $sql .= " WHERE user_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $userId);
        } else {
            $stmt = $this->conn->prepare($sql);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // View: vw_popular_books
    public function getPopularBooks($limit = 10) {
        $sql = "SELECT * FROM vw_popular_books LIMIT ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Function: fn_calculate_user_reading_time
    public function calculateUserReadingTime($userId) {
        $sql = "SELECT fn_calculate_user_reading_time(?) as reading_time";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['reading_time'];
    }

    // Function: fn_get_book_rating
    public function getBookRating($bookId) {
        $sql = "SELECT fn_get_book_rating(?) as rating";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $bookId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['rating'];
    }

    // Procedure: sp_add_book_rating
    public function addOrUpdateBookRating($userId, $bookId, $rating, $review = null) {
        $sql = "CALL sp_add_book_rating(?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iiis", $userId, $bookId, $rating, $review);
        return $stmt->execute();
    }

    // Procedure: sp_update_book_details
    public function updateBookDetails($bookId, $title = null, $author = null, $description = null, $genre = null) {
        $sql = "CALL sp_update_book_details(?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("issss", $bookId, $title, $author, $description, $genre);
        return $stmt->execute();
    }

    // Procedure: sp_get_recommended_books
    public function getRecommendedBooks($userId, $limit = 5) {
        $sql = "CALL sp_get_recommended_books(?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $userId, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    // Helper method to format reading time
    public function formatReadingTime($hours) {
        if ($hours < 1) {
            return "Less than an hour";
        } elseif ($hours == 1) {
            return "1 hour";
        } else {
            return "$hours hours";
        }
    }

    public function __destruct() {
        $this->conn->close();
    }
}

// Example usage:
/*
try {
    $db = new DatabaseUtils();
    
    // Get book ratings
    $bookRatings = $db->getBookRatings();
    
    // Get user stats
    $userStats = $db->getUserReadingStats(1);
    
    // Get popular books
    $popularBooks = $db->getPopularBooks(5);
    
    // Calculate reading time
    $readingTime = $db->calculateUserReadingTime(1);
    $formattedTime = $db->formatReadingTime($readingTime);
    
    // Get book rating
    $rating = $db->getBookRating(1);
    
    // Add/update rating
    $db->addOrUpdateBookRating(1, 1, 5, "Great book!");
    
    // Update book details
    $db->updateBookDetails(1, "New Title", "New Author");
    
    // Get recommendations
    $recommendations = $db->getRecommendedBooks(1, 5);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
*/
?> 