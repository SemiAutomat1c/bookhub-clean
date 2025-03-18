<?php
// Basic session security
session_start();
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_only_cookies', 1);
session_regenerate_id(true);

// Simple CSRF protection
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Basic security functions
class Security {
    // Constants for password validation
    const MIN_PASSWORD_LENGTH = 8;
    const PASSWORD_REGEX = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/';
    
    // Verify CSRF token with timing attack protection
    public static function verifyCSRFToken($token) {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    // Enhanced input sanitization
    public static function sanitize($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitize'], $input);
        }
        $input = trim($input);
        $input = strip_tags($input);
        return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    // Enhanced email validation
    public static function isValidEmail($email) {
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    // Enhanced password validation
    public static function isValidPassword($password) {
        if (strlen($password) < self::MIN_PASSWORD_LENGTH) {
            return false;
        }
        
        // Check for password complexity
        return preg_match(self::PASSWORD_REGEX, $password);
    }
    
    // Get password requirements message
    public static function getPasswordRequirements() {
        return "Password must be at least 8 characters long and contain: 
                1 uppercase letter, 
                1 lowercase letter, 
                1 number, and 
                1 special character (@$!%*?&)";
    }
    
    // Set comprehensive security headers
    public static function setHeaders() {
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline';");
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
    
    // Generate secure random token
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
}

// Set security headers by default
Security::setHeaders(); 