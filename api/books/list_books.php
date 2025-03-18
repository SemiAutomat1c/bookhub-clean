<?php
session_start();

// Set headers for CORS and content type
header('Content-Type: text/plain');

// Get the origin
$allowed_origins = array(
    'http://localhost',
    'http://127.0.0.1',
    'http://DESKTOP-24M6GLF'
);

$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
}

header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Debug logging for session and request
error_log("Session data in list_books.php: " . print_r($_SESSION, true));
error_log("Request headers: " . print_r(getallheaders(), true));
error_log("Origin: " . ($origin ?? 'none'));

// Include database configuration
require_once '../../config/database.php';

try {
    // Check if user is admin for certain operations
    $is_admin = isset($_SESSION['user_id']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;

    // Get database connection
    $conn = getConnection();
    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    // Get search parameters
    $search = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : '%';
    $genre = isset($_GET['genre']) ? $_GET['genre'] : '';
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'title';

    // Build the query
    $query = "SELECT book_id, title, author, description, genre, publication_year, cover_image, file_path, file_type, created_at FROM books WHERE (title LIKE ? OR author LIKE ?)";
    if (!empty($genre)) {
        $query .= " AND genre = ?";
    }

    // Add sorting
    switch ($sort) {
        case 'author':
            $query .= " ORDER BY author ASC";
            break;
        case 'date':
            $query .= " ORDER BY created_at DESC";
            break;
        default:
            $query .= " ORDER BY title ASC";
    }

    // Prepare and execute query
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }

    if (!empty($genre)) {
        $stmt->bind_param("sss", $search, $search, $genre);
    } else {
        $stmt->bind_param("ss", $search, $search);
    }

    if (!$stmt->execute()) {
        throw new Exception("Failed to execute query: " . $stmt->error);
    }

    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $output = "";
        while ($row = $result->fetch_assoc()) {
            // Format each book's data
            $output .= implode("|", [
                $row['book_id'],
                $row['title'],
                $row['author'],
                $row['description'] ?? '',
                $row['genre'] ?? '',
                $row['publication_year'] ?? '',
                $row['cover_image'] ?? '',
                $row['file_path'] ?? '',
                $row['file_type'] ?? '',
                $row['created_at'] ?? ''
            ]) . "\n";
        }
        echo "SUCCESS|" . trim($output);
    } else {
        echo "SUCCESS|NO_BOOKS";
    }

} catch (Exception $e) {
    error_log("Error in list_books.php: " . $e->getMessage());
    echo "ERROR|" . $e->getMessage();
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
}
?> 