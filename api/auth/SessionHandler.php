<?php
class SessionManager {
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            // Set session cookie parameters
            session_set_cookie_params([
                'lifetime' => 86400,
                'path' => '/',
                'domain' => 'localhost',
                'secure' => false,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);

            session_start();
            error_log("Session started with ID: " . session_id());
            error_log("Session cookie params: " . print_r(session_get_cookie_params(), true));
        }
    }

    public static function regenerate() {
        $old_session_id = session_id();
        session_regenerate_id(true);
        error_log("Session ID regenerated from {$old_session_id} to " . session_id());
    }

    public static function destroy() {
        error_log("Destroying session: " . session_id());
        error_log("Session data before destroy: " . print_r($_SESSION, true));
        
        $_SESSION = array();

        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', [
                'expires' => time() - 3600,
                'path' => '/',
                'domain' => 'localhost',
                'secure' => false,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }

        session_destroy();
    }

    public static function set($key, $value) {
        $_SESSION[$key] = $value;
        error_log("Session set {$key}: " . print_r($value, true));
    }

    public static function get($key) {
        $value = $_SESSION[$key] ?? null;
        error_log("Session get {$key}: " . print_r($value, true));
        return $value;
    }

    public static function isLoggedIn() {
        $is_logged_in = isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
        error_log("Session check - isLoggedIn: " . ($is_logged_in ? 'true' : 'false'));
        error_log("Session data during check: " . print_r($_SESSION, true));
        return $is_logged_in;
    }
}
?>
