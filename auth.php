<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'bookhub');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Get the POST data
$data = json_decode(file_get_contents('php://input'), true);
$action = isset($data['action']) ? $data['action'] : '';

// Initialize response array
$response = array(
    'success' => false,
    'message' => '',
    'token' => null,
    'data' => null
);

// Create database connection
$conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($conn->connect_error) {
    $response['message'] = "Connection failed: " . $conn->connect_error;
    echo json_encode($response);
    exit;
}

// Function to generate a random token
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

// Handle different actions
switch ($action) {
    case 'login':
        $email = $data['email'];
        $password = $data['password'];
        
        // Prepare SQL statement
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                // Generate token
                $token = generateToken();
                
                // Store token in session
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['token'] = $token;
                
                $response['success'] = true;
                $response['message'] = "Login successful";
                $response['token'] = $token;
                $response['data'] = array(
                    'user_id' => $user['user_id'],
                    'username' => $user['username'],
                    'email' => $user['email']
                );
            } else {
                $response['message'] = "Invalid password";
            }
        } else {
            $response['message'] = "User not found";
        }
        break;
        
    case 'signup':
        $username = $data['username'];
        $email = $data['email'];
        $password = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Check if email already exists
        $stmt = $conn->prepare("SELECT email FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $response['message'] = "Email already exists";
            break;
        }
        
        // Insert new user
        $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $email, $password);
        
        if ($stmt->execute()) {
            // Generate token
            $token = generateToken();
            
            // Store token in session
            $_SESSION['user_id'] = $conn->insert_id;
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $email;
            $_SESSION['token'] = $token;
            
            $response['success'] = true;
            $response['message'] = "Registration successful";
            $response['token'] = $token;
            $response['data'] = array(
                'user_id' => $_SESSION['user_id'],
                'username' => $username,
                'email' => $email
            );
        } else {
            $response['message'] = "Registration failed";
        }
        break;
        
    case 'logout':
        session_destroy();
        $response['success'] = true;
        $response['message'] = "Logout successful";
        break;
        
    default:
        $response['message'] = "Invalid action";
}

// Close connection
$conn->close();

// Send response
header('Content-Type: application/json');
echo json_encode($response);
