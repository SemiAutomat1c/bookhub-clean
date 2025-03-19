<?php

class AuthMiddleware {
    /**
     * Check if user has access to the requested route
     */
    public static function checkAccess($route) {
        // If route doesn't require auth, allow access
        if (!isset($route['auth']) || !$route['auth']) {
            return true;
        }

        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: /bookhub-1/sign-in');
            exit;
        }

        // For admin routes, check admin status
        if (isset($route['admin']) && $route['admin']) {
            if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
                header('Location: /bookhub-1/');
                exit;
            }
        }

        return true;
    }
} 