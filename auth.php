<?php
require_once 'security.php';
require_once 'config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Rate limiting check
function checkRateLimit($email) {
    $attempts = isset($_SESSION['login_attempts'][$email]) ? $_SESSION['login_attempts'][$email] : ['count' => 0, 'first_attempt' => time()];
    
    // Reset attempts if more than 15 minutes have passed
    if (time() - $attempts['first_attempt'] > 900) {
        $attempts = ['count' => 0, 'first_attempt' => time()];
    }
    
    // Check if too many attempts
    if ($attempts['count'] >= 5) {
        return ['allowed' => false, 'wait_time' => ceil((900 - (time() - $attempts['first_attempt'])) / 60)];
    }
    
    // Increment attempts
    $attempts['count']++;
    $_SESSION['login_attempts'][$email] = $attempts;
    return ['allowed' => true];
}

// Get database connection
try {
    $conn = new mysqli($host, $username, $password, $database);
    if ($conn->connect_error) {
        throw new Exception("Database connection failed");
    }
} catch (Exception $e) {
    die(json_encode(['success' => false, 'message' => 'Service temporarily unavailable']));
}

$action = isset($_POST['action']) ? Security::sanitize($_POST['action']) : '';
$response = ['success' => false, 'message' => '', 'errors' => []];

switch($action) {
    case 'login':
        try {
            // Basic CSRF check for login
            if (!Security::verifyCSRFToken($_POST['csrf_token'])) {
                throw new Exception('Invalid request token');
            }

            $email = Security::sanitize($_POST['email']);
            $password = $_POST['password'];

            // Validate required fields
            if (empty($email) || empty($password)) {
                throw new Exception('Email and password are required');
            }

            // Check rate limiting
            $rateLimit = checkRateLimit($email);
            if (!$rateLimit['allowed']) {
                throw new Exception("Too many login attempts. Please wait {$rateLimit['wait_time']} minutes.");
            }

            if (!Security::isValidEmail($email)) {
                throw new Exception('Invalid email format');
            }

            $stmt = $conn->prepare("SELECT id, password, name, status FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                if ($row['status'] === 'inactive') {
                    throw new Exception('Account is inactive. Please verify your email.');
                }

                if (password_verify($password, $row['password'])) {
                    // Clear login attempts on successful login
                    unset($_SESSION['login_attempts'][$email]);
                    
                    // Regenerate session ID to prevent session fixation
                    session_regenerate_id(true);
                    
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['user_name'] = $row['name'];
                    $_SESSION['last_activity'] = time();
                    
                    $response['success'] = true;
                    $response['message'] = 'Login successful';
                    $response['redirect'] = '/dashboard.php';
                } else {
                    throw new Exception('Invalid email or password');
                }
            } else {
                throw new Exception('Invalid email or password');
            }
        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }
        break;

    case 'register':
        try {
            // Basic CSRF check for registration
            if (!Security::verifyCSRFToken($_POST['csrf_token'])) {
                throw new Exception('Invalid request token');
            }

            $name = Security::sanitize($_POST['name']);
            $email = Security::sanitize($_POST['email']);
            $password = $_POST['password'];
            $confirmPassword = $_POST['confirm_password'];

            // Validate required fields
            $errors = [];
            if (empty($name)) $errors['name'] = 'Name is required';
            if (empty($email)) $errors['email'] = 'Email is required';
            if (empty($password)) $errors['password'] = 'Password is required';
            if (empty($confirmPassword)) $errors['confirm_password'] = 'Password confirmation is required';
            
            if (!empty($errors)) {
                $response['errors'] = $errors;
                throw new Exception('Please fill in all required fields');
            }

            // Validate password match
            if ($password !== $confirmPassword) {
                throw new Exception('Passwords do not match');
            }

            if (!Security::isValidEmail($email)) {
                throw new Exception('Invalid email format');
            }

            if (!Security::isValidPassword($password)) {
                throw new Exception(Security::getPasswordRequirements());
            }

            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                throw new Exception('Email already registered');
            }

            // Generate verification token
            $verificationToken = Security::generateToken();
            
            // Create new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, verification_token, status) VALUES (?, ?, ?, ?, 'inactive')");
            $stmt->bind_param("ssss", $name, $email, $hashed_password, $verificationToken);
            
            if ($stmt->execute()) {
                // Send verification email (implement this part based on your email service)
                // sendVerificationEmail($email, $verificationToken);
                
                $response['success'] = true;
                $response['message'] = 'Registration successful! Please check your email to verify your account.';
            } else {
                throw new Exception('Registration failed');
            }
        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }
        break;

    case 'logout':
        // Clear all session data
        $_SESSION = array();
        
        // Destroy the session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        // Destroy the session
        session_destroy();
        
        $response['success'] = true;
        $response['message'] = 'Logout successful';
        $response['redirect'] = '/login.php';
        break;

    default:
        $response['message'] = 'Invalid action';
}

$conn->close();
header('Content-Type: application/json');
echo json_encode($response);
