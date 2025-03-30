<?php
session_start();

// Set headers for CORS and content type
header('Content-Type: text/plain');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
error_log("=== START list_books.php ===");
error_log("Session data: " . print_r($_SESSION, true));
error_log("Request headers: " . print_r(getallheaders(), true));
error_log("Origin: " . ($origin ?? 'none'));

// Include database configuration
require_once '../../config/database.php';

try {
    // Check if user is admin for certain operations
    $is_admin = isset($_SESSION['user_id']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
    error_log("Is admin: " . ($is_admin ? 'true' : 'false'));

    // Get database connection
    $conn = getConnection();
    if (!$conn) {
        throw new Exception("Database connection failed");
    }
    error_log("Database connection successful");

    // Get search parameters
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $genre = isset($_GET['genre']) ? $_GET['genre'] : '';
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'title';
    error_log("Search params - search: '$search', genre: '$genre', sort: '$sort'");

    // Build base query
    $query = "SELECT * FROM books";
    $params = [];
    $types = "";

    // Add search conditions if provided
    if (!empty($search)) {
        $query .= " WHERE (title LIKE ? OR author LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= "ss";
    }

    // Add genre filter if provided
    if (!empty($genre)) {
        $query .= empty($search) ? " WHERE" : " AND";
        $query .= " genre = ?";
        $params[] = $genre;
        $types .= "s";
    }

    // Add sorting
    $query .= " ORDER BY ";
    switch ($sort) {
        case 'author':
            $query .= "author ASC";
            break;
        case 'date':
            $query .= "created_at DESC";
            break;
        default:
            $query .= "title ASC";
    }

    error_log("Final query: " . $query);
    error_log("Query params: " . print_r($params, true));

    // Prepare and execute statement
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        throw new Exception("Failed to execute query: " . $stmt->error);
    }

    $result = $stmt->get_result();
    error_log("Query executed successfully. Found " . $result->num_rows . " books");
    
    $books = [];

    while ($row = $result->fetch_assoc()) {
        error_log("Processing book: ID=" . $row['book_id'] . ", Title=" . $row['title']);
        $books[] = implode("|", [
            $row['book_id'],
            $row['title'],
            $row['author'],
            $row['description'] ?? '',
            $row['genre'] ?? '',
            $row['publication_year'] ?? '',
            $row['cover_image'] ?? '',
            $row['file_path'] ?? ''
        ]);
    }

    if (count($books) > 0) {
        error_log("Returning " . count($books) . " books");
        echo "SUCCESS|" . implode("\n", $books);
    } else {
        error_log("No books found");
        echo "SUCCESS|NO_BOOKS";
    }

} catch (Exception $e) {
    error_log("Error in list_books.php: " . $e->getMessage());
    echo "ERROR|" . $e->getMessage();
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
    error_log("=== END list_books.php ===");
}
?> 