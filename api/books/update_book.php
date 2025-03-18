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

header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Debug logging for session and request
error_log("Session data in update_book.php: " . print_r($_SESSION, true));
error_log("Request headers: " . print_r(getallheaders(), true));
error_log("Origin: " . ($origin ?? 'none'));

// Include database configuration
require_once '../../config/database.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    error_log("Unauthorized access attempt in update_book.php. Session data: " . print_r($_SESSION, true));
    http_response_code(401);
    echo "ERROR|Unauthorized access";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get database connection
        $conn = getConnection();
        if (!$conn) {
            throw new Exception("Database connection failed");
        }

        // Debug logging
        error_log("Update book attempt - POST data: " . print_r($_POST, true));
        error_log("Files data: " . print_r($_FILES, true));

        $book_id = $_POST['book_id'] ?? '';
        $title = $_POST['title'] ?? '';
        $author = $_POST['author'] ?? '';
        $description = $_POST['description'] ?? '';
        $genre = $_POST['genre'] ?? '';
        $publication_year = $_POST['publication_year'] ?? null;

        // Validate required fields
        if (empty($book_id) || empty($title) || empty($author)) {
            echo "ERROR|Book ID, title and author are required";
            exit();
        }

        // Validate publication year
        if ($publication_year !== null) {
            $publication_year = intval($publication_year);
            if ($publication_year < 1800 || $publication_year > date('Y') + 1) {
                echo "ERROR|Publication year must be between 1800 and " . (date('Y') + 1);
                exit();
            }
        }

        // Create upload directories if they don't exist
        $covers_dir = $_SERVER['DOCUMENT_ROOT'] . '/bookhub-1/assets/images/covers';
        $books_dir = $_SERVER['DOCUMENT_ROOT'] . '/bookhub-1/assets/books';
        
        foreach ([$covers_dir, $books_dir] as $dir) {
            if (!file_exists($dir)) {
                if (!mkdir($dir, 0777, true)) {
                    error_log("Failed to create directory: $dir");
                    echo "ERROR|Failed to create upload directory: $dir";
                    exit();
                }
            }
            // Ensure directory is writable
            if (!is_writable($dir)) {
                error_log("Directory not writable: $dir");
                echo "ERROR|Upload directory not writable: $dir";
                exit();
            }
        }

        // Handle cover image update if new file is uploaded
        $cover_image_sql = '';
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === 0) {
            $file_extension = strtolower(pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif');
            
            if (!in_array($file_extension, $allowed_extensions)) {
                echo "ERROR|Invalid file type for cover image. Only JPG, JPEG, PNG & GIF files are allowed.";
                exit();
            }
            
            $file_name = uniqid() . '.' . $file_extension;
            $target_path = $covers_dir . '/' . $file_name;
            
            // Delete old cover image if it exists
            $stmt = $conn->prepare("SELECT cover_image FROM books WHERE book_id = ?");
            if (!$stmt) {
                echo "ERROR|Failed to prepare statement: " . $conn->error;
                exit();
            }
            
            $stmt->bind_param("i", $book_id);
            if (!$stmt->execute()) {
                echo "ERROR|Failed to execute query: " . $stmt->error;
                exit();
            }
            
            $result = $stmt->get_result();
            if ($old_cover = $result->fetch_assoc()) {
                if (!empty($old_cover['cover_image'])) {
                    $old_path = $_SERVER['DOCUMENT_ROOT'] . '/bookhub-1/' . $old_cover['cover_image'];
                    if (file_exists($old_path)) {
                        unlink($old_path);
                    }
                }
            }
            $stmt->close();
            
            if (!move_uploaded_file($_FILES['cover_image']['tmp_name'], $target_path)) {
                echo "ERROR|Failed to upload cover image";
                exit();
            }
            
            $cover_image = 'assets/images/covers/' . $file_name;
            $cover_image_sql = ", cover_image = ?";
        }

        // Handle book file update if new file is uploaded
        $file_sql = '';
        if (isset($_FILES['book_file']) && $_FILES['book_file']['error'] === 0) {
            $file_extension = strtolower(pathinfo($_FILES['book_file']['name'], PATHINFO_EXTENSION));
            if ($file_extension !== 'pdf') {
                echo "ERROR|Only PDF files are allowed for books.";
                exit();
            }
            
            $file_name = uniqid() . '.pdf';
            $target_path = $books_dir . '/' . $file_name;
            
            // Delete old book file if it exists
            $stmt = $conn->prepare("SELECT file_path FROM books WHERE book_id = ?");
            if (!$stmt) {
                echo "ERROR|Failed to prepare statement: " . $conn->error;
                exit();
            }
            
            $stmt->bind_param("i", $book_id);
            if (!$stmt->execute()) {
                echo "ERROR|Failed to execute query: " . $stmt->error;
                exit();
            }
            
            $result = $stmt->get_result();
            if ($old_file = $result->fetch_assoc()) {
                if (!empty($old_file['file_path'])) {
                    $old_path = $_SERVER['DOCUMENT_ROOT'] . '/bookhub-1/' . $old_file['file_path'];
                    if (file_exists($old_path)) {
                        unlink($old_path);
                    }
                }
            }
            $stmt->close();
            
            if (!move_uploaded_file($_FILES['book_file']['tmp_name'], $target_path)) {
                echo "ERROR|Failed to upload book file";
                exit();
            }
            
            $file_path = 'assets/books/' . $file_name;
            $file_type = 'pdf';
            $file_sql = ", file_path = ?, file_type = ?";
        }

        // Build update query
        $query = "UPDATE books SET title = ?, author = ?, description = ?, genre = ?, publication_year = ?" . $cover_image_sql . $file_sql . " WHERE book_id = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Failed to prepare update statement: " . $conn->error);
        }

        // Build parameter array and types string
        $params = [$title, $author, $description, $genre, $publication_year];
        $types = "ssssi";  // Note the 'i' for publication_year

        if (!empty($cover_image_sql)) {
            $params[] = $cover_image;
            $types .= "s";
        }

        if (!empty($file_sql)) {
            $params[] = $file_path;
            $params[] = $file_type;
            $types .= "ss";
        }

        $params[] = $book_id;
        $types .= "i";

        $stmt->bind_param($types, ...$params);

        if (!$stmt->execute()) {
            throw new Exception("Failed to update book: " . $stmt->error);
        }

        echo "SUCCESS|Book updated successfully";
    } catch (Exception $e) {
        error_log("Error updating book: " . $e->getMessage());
        echo "ERROR|" . $e->getMessage();
    } finally {
        if (isset($stmt)) $stmt->close();
        if (isset($conn)) $conn->close();
    }
} else {
    echo "ERROR|Invalid request method";
}
?> 