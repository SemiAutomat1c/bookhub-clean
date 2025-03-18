<?php
class Security {
    // Session configuration
    private static $session_name = 'bookhub_session';
    private static $session_lifetime = 7200; // 2 hours
    private static $session_path = '/';
    private static $session_domain = '';
    private static $session_secure = true;
    private static $session_httponly = true;
    
    // Initialize security settings
    public static function init() {
        // Set secure session parameters
        ini_set('session.use_strict_mode', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.use_trans_sid', 0);
        ini_set('session.cookie_httponly', 1);
        
        if (self::$session_secure) {
            ini_set('session.cookie_secure', 1);
        }
        
        // Set session cookie parameters
        session_set_cookie_params(
            self::$session_lifetime,
            self::$session_path,
            self::$session_domain,
            self::$session_secure,
            self::$session_httponly
        );
        
        // Set session name
        session_name(self::$session_name);
        
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Regenerate session ID periodically
        if (!isset($_SESSION['last_regeneration'])) {
            self::regenerateSession();
        } elseif (time() - $_SESSION['last_regeneration'] > 1800) { // 30 minutes
            self::regenerateSession();
        }
    }
    
    // Regenerate session ID
    public static function regenerateSession() {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
    
    // Generate CSRF token
    public static function generateCSRFToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    // Verify CSRF token
    public static function verifyCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    // Sanitize input
    public static function sanitize($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitize'], $input);
        }
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }
    
    // Validate email
    public static function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    // Validate password strength
    public static function isValidPassword($password) {
        // At least 8 characters long
        // Contains at least one uppercase letter
        // Contains at least one lowercase letter
        // Contains at least one number
        // Contains at least one special character
        return strlen($password) >= 8 &&
               preg_match('/[A-Z]/', $password) &&
               preg_match('/[a-z]/', $password) &&
               preg_match('/[0-9]/', $password) &&
               preg_match('/[^A-Za-z0-9]/', $password);
    }
    
    // Generate password hash
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);
    }
    
    // Verify password
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    // Generate secure random token
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    // Check if user is authenticated
    public static function isAuthenticated() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    // Get current user ID
    public static function getCurrentUserId() {
        return self::isAuthenticated() ? $_SESSION['user_id'] : null;
    }
    
    // Require authentication
    public static function requireAuth() {
        if (!self::isAuthenticated()) {
            header('HTTP/1.1 401 Unauthorized');
            echo json_encode(['error' => 'Authentication required']);
            exit;
        }
    }
    
    // Clean session data
    public static function clearSession() {
        $_SESSION = array();
        
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        
        session_destroy();
    }
} 