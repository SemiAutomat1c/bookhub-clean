<?php
require_once 'SessionHandler.php';
require_once '../../config/database.php';

SessionManager::start();
setCORSHeaders();

echo "Session Info:\n";
echo "Session ID: " . session_id() . "\n";
echo "Session Name: " . session_name() . "\n";
echo "Session Cookie Params:\n";
print_r(session_get_cookie_params());
echo "\n\nSession Data:\n";
print_r($_SESSION);
echo "\n\nCookie Data:\n";
print_r($_COOKIE);
echo "\n\nServer Info:\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Session Save Handler: " . ini_get('session.save_handler') . "\n";
echo "Session Save Path: " . ini_get('session.save_path') . "\n";
echo "Session Cookie Domain: " . ini_get('session.cookie_domain') . "\n";
echo "Session Cookie Path: " . ini_get('session.cookie_path') . "\n";
echo "Session Cookie Secure: " . ini_get('session.cookie_secure') . "\n";
echo "Session Cookie HTTPOnly: " . ini_get('session.cookie_httponly') . "\n";
echo "Session Cookie SameSite: " . ini_get('session.cookie_samesite') . "\n";
?>
