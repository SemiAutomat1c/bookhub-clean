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
error_log("Session data in add_book.php: " . print_r($_SESSION, true));
error_log("Request headers: " . print_r(getallheaders(), true));
error_log("Origin: " . ($origin ?? 'none'));

// Include database configuration
require_once '../../config/database.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    error_log("Unauthorized access attempt in add_book.php. Session data: " . print_r($_SESSION, true));
    http_response_code(401);
    echo "ERROR|Unauthorized access";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug logging
    error_log("Add book attempt - POST data: " . print_r($_POST, true));
    error_log("Files data: " . print_r($_FILES, true));

    $title = $_POST['title'] ?? '';
    $author = $_POST['author'] ?? '';
    $description = $_POST['description'] ?? '';
    $genre = $_POST['genre'] ?? '';

    // Validate required fields
    if (empty($title) || empty($author)) {
        echo "ERROR|Title and author are required";
        exit();
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

    // Handle file upload for cover image
    $cover_image = '';
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === 0) {
        $file_extension = strtolower(pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif');
        
        if (!in_array($file_extension, $allowed_extensions)) {
            echo "ERROR|Invalid file type for cover image. Only JPG, JPEG, PNG & GIF files are allowed.";
            exit();
        }
        
        $file_name = uniqid() . '.' . $file_extension;
        $target_path = $covers_dir . '/' . $file_name;
        
        if (!move_uploaded_file($_FILES['cover_image']['tmp_name'], $target_path)) {
            $error = error_get_last();
            error_log("Failed to move cover image. Error: " . ($error ? $error['message'] : 'Unknown error'));
            echo "ERROR|Failed to upload cover image";
            exit();
        }
        
        $cover_image = 'assets/images/covers/' . $file_name;
        error_log("Successfully uploaded cover image to: " . $target_path);
    }

    // Handle book file upload
    $file_path = '';
    $file_type = '';
    if (isset($_FILES['book_file']) && $_FILES['book_file']['error'] === 0) {
        $file_extension = strtolower(pathinfo($_FILES['book_file']['name'], PATHINFO_EXTENSION));
        if ($file_extension !== 'pdf') {
            echo "ERROR|Only PDF files are allowed for books.";
            exit();
        }
        
        $file_name = uniqid() . '.pdf';
        $target_path = $books_dir . '/' . $file_name;
        
        if (!move_uploaded_file($_FILES['book_file']['tmp_name'], $target_path)) {
            $error = error_get_last();
            error_log("Failed to move book file. Error: " . ($error ? $error['message'] : 'Unknown error'));
            echo "ERROR|Failed to upload book file";
            exit();
        }
        
        $file_path = 'assets/books/' . $file_name;
        $file_type = 'pdf';
        error_log("Successfully uploaded book file to: " . $target_path);
    }

    try {
        // Get database connection
        $conn = getConnection();
        if (!$conn) {
            throw new Exception("Database connection failed");
        }

        // Prepare and execute query
        $stmt = $conn->prepare("INSERT INTO books (title, author, description, genre, cover_image, file_path, file_type) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . $conn->error);
        }

        $stmt->bind_param("sssssss", $title, $author, $description, $genre, $cover_image, $file_path, $file_type);

        if (!$stmt->execute()) {
            throw new Exception("Failed to add book: " . $stmt->error);
        }

        echo "SUCCESS|Book added successfully|" . $stmt->insert_id;
    } catch (Exception $e) {
        error_log("Error adding book: " . $e->getMessage());
        echo "ERROR|" . $e->getMessage();
    } finally {
        if (isset($stmt)) $stmt->close();
        if (isset($conn)) $conn->close();
    }
} else {
    echo "ERROR|Invalid request method";
}
?> 