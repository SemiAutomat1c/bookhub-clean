<?php
// Database Configuration
$env = getenv('ENVIRONMENT') ?: 'development';

$config = [
    'development' => [
        'host' => 'localhost',
        'dbname' => 'bookhub',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4'
    ],
    'production' => [
        'host' => getenv('DB_HOST'),
        'dbname' => getenv('DB_NAME'),
        'username' => getenv('DB_USER'),
        'password' => getenv('DB_PASS'),
        'charset' => 'utf8mb4'
    ]
];

$dbConfig = $config[$env];

try {
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}",
        $dbConfig['username'],
        $dbConfig['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch(PDOException $e) {
    error_log("Connection failed: " . $e->getMessage());
    die("Connection failed. Please try again later.");
}

// Enable error reporting for development
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_error.log');
error_reporting(E_ALL);

// Create logs directory if it doesn't exist
if (!is_dir(__DIR__ . '/../logs')) {
    mkdir(__DIR__ . '/../logs', 0755, true);
}

// Function to get database connection
function getConnection() {
    static $conn = null;
    
    if ($conn === null || !($conn instanceof mysqli) || $conn->connect_error) {
        try {
            $conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
            
            if ($conn->connect_error) {
                error_log("Database connection failed: " . $conn->connect_error);
                throw new Exception("Database connection failed");
            }
            
            // Set charset and collation
            if (!$conn->set_charset("utf8mb4")) {
                error_log("Error setting charset: " . $conn->error);
                throw new Exception("Database configuration error");
            }
            
            // Set SQL mode for stricter queries
            $conn->query("SET SESSION sql_mode = 'STRICT_ALL_TABLES'");
            
        } catch (Exception $e) {
            error_log("Database error: " . $e->getMessage());
            return null;
        }
    }
    
    return $conn;
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Function to validate login status
function checkLoginStatus() {
    if (!isLoggedIn()) {
        header("Location: /bookhub-1/views/sign-in.html");
        exit();
    }
}

// Function to set secure session parameters
function setSecureSessionParams() {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Lax');
    
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => false,  // Set to true in production
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

// Initialize session with secure parameters
if (session_status() === PHP_SESSION_NONE) {
    setSecureSessionParams();
    session_start();
}

// Set CORS headers for all requests
setCORSHeaders();

// Get initial database connection
$conn = getConnection();
if (!$conn) {
    error_log("Failed to establish initial database connection");
    die("ERROR|Database connection failed");
}

// Function to set CORS headers
function setCORSHeaders() {
    if (!isset($_SERVER['REQUEST_METHOD'])) {
        return; // Skip CORS headers for CLI
    }

    $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
    
    $allowed_origins = array(
        'http://localhost',
        'http://127.0.0.1',
        'http://localhost:80',
        'http://localhost:8080'
    );
    
    if (in_array($origin, $allowed_origins)) {
        header("Access-Control-Allow-Origin: $origin");
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    }
    
    // Add security headers
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';");
    
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }
}

?>
