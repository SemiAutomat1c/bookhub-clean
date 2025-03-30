<?php
require_once 'config/database.php';

define('CHECK_TIME', date('Y-m-d H:i:s'));

function runCheck() {
    global $conn;
    $results = [];
    
    // 1. Database Connection
    try {
        if ($conn && $conn instanceof mysqli) {
            $results['database'] = "✅ Connected";
        } else {
            throw new Exception("Connection not established");
        }
    } catch(Exception $e) {
        $results['database'] = "❌ Failed: " . $e->getMessage();
        return $results;
    }

    // 2. Core Tables
    $required_tables = [
        'users' => ['user_id', 'username', 'password_hash', 'email', 'full_name'],
        'books' => ['id', 'title', 'author', 'description'],
        'reviews' => ['id', 'user_id', 'book_id', 'content'],
        'login_attempts' => ['id', 'email', 'attempt_time'],
        'user_activity_log' => ['id', 'user_id', 'activity_type'],
        'password_reset_tokens' => ['id', 'user_id', 'token']
    ];

    foreach($required_tables as $table => $required_columns) {
        // Check if table exists
        $table_check = $conn->query("SHOW TABLES LIKE '$table'");
        if ($table_check->num_rows > 0) {
            // Get record count
            $count_query = $conn->query("SELECT COUNT(*) as count FROM $table");
            $count = $count_query->fetch_assoc()['count'];
            
            // Check columns
            $columns_query = $conn->query("SHOW COLUMNS FROM $table");
            $existing_columns = [];
            while($col = $columns_query->fetch_assoc()) {
                $existing_columns[] = $col['Field'];
            }
            
            // Verify required columns exist
            $missing_columns = array_diff($required_columns, $existing_columns);
            
            if (empty($missing_columns)) {
                $results[$table] = "✅ ($count records)";
            } else {
                $results[$table] = "⚠️ Missing columns: " . implode(', ', $missing_columns);
            }
        } else {
            $results[$table] = "❌ Table missing";
        }
    }

    // 3. Authentication Files
    $auth_files = ['login.php', 'register.php', 'auth_check.php', 'logout.php'];
    foreach($auth_files as $file) {
        $file_path = 'api/auth/' . $file;
        $results['auth_' . $file] = file_exists($file_path) ? "✅ Present" : "❌ Missing";
    }

    // 4. Core Pages
    $core_pages = [
        'views/index.html',
        'views/sign-in.html',
        'views/profile.html',
        'views/reading-list.html'
    ];
    foreach($core_pages as $page) {
        $results[basename($page)] = file_exists($page) ? "✅ Found" : "❌ Missing";
    }

    // 5. Configuration Files
    $config_files = [
        'config/database.php',
        'src/js/auth.js',
        'assets/css/styles.css'
    ];
    foreach($config_files as $file) {
        $results['config_' . basename($file)] = file_exists($file) ? "✅ Found" : "❌ Missing";
    }

    // 6. Database Relationships
    $relationships = [
        'reviews_users' => "SELECT COUNT(*) as count FROM information_schema.KEY_COLUMN_USAGE 
                           WHERE TABLE_NAME = 'reviews' 
                           AND REFERENCED_TABLE_NAME = 'users'",
        'reviews_books' => "SELECT COUNT(*) as count FROM information_schema.KEY_COLUMN_USAGE 
                          WHERE TABLE_NAME = 'reviews' 
                          AND REFERENCED_TABLE_NAME = 'books'"
    ];

    foreach($relationships as $name => $query) {
        $result = $conn->query($query);
        $count = $result->fetch_assoc()['count'];
        $results[$name] = $count > 0 ? "✅ Valid" : "❌ Missing";
    }

    return $results;
}

// Run and Display
echo "=== BookHub Pre-Defense Check ===\n";
echo "Time: " . CHECK_TIME . "\n";
echo "PHP Version: " . phpversion() . "\n";
echo "MySQL Version: " . $conn->get_server_info() . "\n\n";

$results = runCheck();

// Display Results by Category
$categories = [
    'Database' => ['database'],
    'Core Tables' => ['users', 'books', 'reviews', 'login_attempts', 'user_activity_log', 'password_reset_tokens'],
    'Authentication Files' => ['auth_login.php', 'auth_register.php', 'auth_auth_check.php', 'auth_logout.php'],
    'Core Pages' => ['index.html', 'sign-in.html', 'profile.html', 'reading-list.html'],
    'Configuration' => ['config_database.php', 'config_auth.js', 'config_styles.css'],
    'Relationships' => ['reviews_users', 'reviews_books']
];

foreach($categories as $category => $items) {
    echo "\n=== $category ===\n";
    foreach($items as $item) {
        if (isset($results[$item])) {
            echo str_pad($item, 25) . ": " . $results[$item] . "\n";
        }
    }
}

// Check for Critical Issues
$critical_issues = array_filter($results, function($result) {
    return strpos($result, '❌') !== false;
});

if (!empty($critical_issues)) {
    echo "\n=== CRITICAL ISSUES ===\n";
    foreach($critical_issues as $item => $result) {
        echo "• $item: $result\n";
    }
}

$conn->close();
?>
