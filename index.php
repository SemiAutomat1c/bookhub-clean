<?php
session_start();

// Load dependencies
require_once __DIR__ . '/middleware/AuthMiddleware.php';

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

// Load routes configuration
$routes = require __DIR__ . '/routes/web.php';

// Check if route exists
if (isset($routes[$path])) {
    $route = $routes[$path];
    
    // Check authentication and permissions
    if (!AuthMiddleware::checkAccess($route)) {
        exit;
    }
    
    $file = $route['path'];
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
            case 'css':
                header('Content-Type: text/css');
                break;
            case 'js':
                header('Content-Type: application/javascript');
                break;
            default:
                header('Content-Type: text/plain');
        }
        readfile($file);
        exit;
    }
}

// If no route matches or file doesn't exist, return 404
header("HTTP/1.0 404 Not Found");
include __DIR__ . '/../views/404.html'; 