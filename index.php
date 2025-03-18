<?php
session_start();

// Get the requested path
$request_uri = $_SERVER['REQUEST_URI'];
$base_path = '/bookhub-1/';
$path = str_replace($base_path, '', $request_uri);

// Remove query string if present
if (($pos = strpos($path, '?')) !== false) {
    $path = substr($path, 0, $pos);
}

// Remove trailing slash
$path = rtrim($path, '/');

// Define routes
$routes = [
    '' => 'views/index.html',
    'index' => 'views/index.html',
    'reader' => 'views/reader.html',
    'admin' => 'views/admin.html',
    'sign-in' => 'views/sign-in.html',
    'profile' => 'views/profile.html',
    'search' => 'views/search.html',
    'reading-list' => 'views/reading-list.html'
];

// Check if route exists
if (isset($routes[$path])) {
    $file = $routes[$path];
    if (file_exists($file)) {
        // Set content type based on file extension
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        switch ($ext) {
            case 'html':
                header('Content-Type: text/html');
                break;
            case 'json':
                header('Content-Type: application/json');
                break;
            // Add more content types as needed
        }
        readfile($file);
        exit;
    }
}

// If no route matches or file doesn't exist, return 404
header("HTTP/1.0 404 Not Found");
echo "404 Not Found"; 