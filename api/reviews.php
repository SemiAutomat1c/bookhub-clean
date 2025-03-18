<?php
require_once '../config/database.php';
require_once '../auth_check.php';

// Set headers
header('Content-Type: text/plain');

// Get database connection
$conn = getConnection();

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handleGetReviews($conn);
        break;
    case 'POST':
        handlePostReview($conn);
        break;
    case 'DELETE':
        handleDeleteReview($conn);
        break;
    default:
        echo "ERROR|Invalid request method";
}

function handleGetReviews($conn) {
    $bookId = $_GET['book_id'] ?? null;
    
    if (!$bookId) {
        echo "ERROR|Book ID is required";
        return;
    }

    $stmt = $conn->prepare("
        SELECT r.rating_id, r.user_id, r.rating, r.review, r.created_at,
               u.username, u.full_name
        FROM ratings r
        JOIN users u ON r.user_id = u.user_id
        WHERE r.book_id = ?
        ORDER BY r.created_at DESC
    ");
    
    $stmt->bind_param("i", $bookId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo "DATA|no_reviews";
        return;
    }

    $output = "DATA";
    while ($row = $result->fetch_assoc()) {
        $output .= sprintf("|review|%d|%d|%s|%s|%s|%s|%s",
            $row['rating_id'],
            $row['user_id'],
            $row['rating'],
            $row['review'],
            $row['created_at'],
            $row['username'],
            $row['full_name']
        );
    }
    
    echo $output;
}

function handlePostReview($conn) {
    // Parse input data
    $raw_data = file_get_contents('php://input');
    $data = array();
    foreach (explode('|', $raw_data) as $pair) {
        $parts = explode(':', $pair);
        if (count($parts) === 2) {
            $data[trim($parts[0])] = trim($parts[1]);
        }
    }

    // Validate required fields
    if (!isset($data['book_id']) || !isset($data['rating'])) {
        echo "ERROR|Missing required fields";
        return;
    }

    $bookId = (int)$data['book_id'];
    $rating = (float)$data['rating'];
    $review = $data['review'] ?? '';
    $userId = $_SESSION['user_id'];

    // Validate rating
    if ($rating < 0 || $rating > 5) {
        echo "ERROR|Rating must be between 0 and 5";
        return;
    }

    try {
        // Check if user already reviewed this book
        $stmt = $conn->prepare("SELECT rating_id FROM ratings WHERE user_id = ? AND book_id = ?");
        $stmt->bind_param("ii", $userId, $bookId);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            // Update existing review
            $stmt = $conn->prepare("
                UPDATE ratings 
                SET rating = ?, review = ?, updated_at = CURRENT_TIMESTAMP
                WHERE user_id = ? AND book_id = ?
            ");
            $stmt->bind_param("dsii", $rating, $review, $userId, $bookId);
        } else {
            // Create new review
            $stmt = $conn->prepare("
                INSERT INTO ratings (user_id, book_id, rating, review)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->bind_param("iids", $userId, $bookId, $rating, $review);
        }

        if ($stmt->execute()) {
            echo "SUCCESS|Review saved successfully";
        } else {
            throw new Exception("Failed to save review");
        }
    } catch (Exception $e) {
        echo "ERROR|" . $e->getMessage();
    }
}

function handleDeleteReview($conn) {
    $raw_data = file_get_contents('php://input');
    $data = array();
    foreach (explode('|', $raw_data) as $pair) {
        $parts = explode(':', $pair);
        if (count($parts) === 2) {
            $data[trim($parts[0])] = trim($parts[1]);
        }
    }

    if (!isset($data['review_id'])) {
        echo "ERROR|Review ID is required";
        return;
    }

    $reviewId = (int)$data['review_id'];
    $userId = $_SESSION['user_id'];

    try {
        // Check if review belongs to user
        $stmt = $conn->prepare("SELECT user_id FROM ratings WHERE rating_id = ?");
        $stmt->bind_param("i", $reviewId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            echo "ERROR|Review not found";
            return;
        }

        $review = $result->fetch_assoc();
        if ($review['user_id'] !== $userId) {
            echo "ERROR|Unauthorized to delete this review";
            return;
        }

        // Delete review
        $stmt = $conn->prepare("DELETE FROM ratings WHERE rating_id = ?");
        $stmt->bind_param("i", $reviewId);

        if ($stmt->execute()) {
            echo "SUCCESS|Review deleted successfully";
        } else {
            throw new Exception("Failed to delete review");
        }
    } catch (Exception $e) {
        echo "ERROR|" . $e->getMessage();
    }
}

$conn->close();
?>
