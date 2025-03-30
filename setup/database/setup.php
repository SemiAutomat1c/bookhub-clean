<?php
// Database configuration
$DB_HOST = 'localhost';
$DB_USERNAME = 'root';
$DB_PASSWORD = '';
$DB_NAME = 'bookhub';

// Function to create database connection
function createConnection($host, $username, $password, $dbname = null) {
    try {
        $dsn = "mysql:host=$host" . ($dbname ? ";dbname=$dbname" : "");
        $conn = new PDO($dsn, $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// Function to execute SQL file
function executeSQLFile($conn, $filename) {
    try {
        echo "Executing $filename...\n";
        $sql = file_get_contents($filename);
        
        // Split SQL file into individual statements
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach($statements as $statement) {
            if (!empty($statement)) {
                $conn->exec($statement);
            }
        }
        echo "Successfully executed $filename\n";
        return true;
    } catch(PDOException $e) {
        echo "Error executing $filename: " . $e->getMessage() . "\n";
        return false;
    }
}

// Function to create necessary directories
function createDirectories() {
    $directories = [
        '../assets/images/covers',
        '../assets/books/pdfs'
    ];
    
    foreach($directories as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
            echo "Created directory: $dir\n";
        }
    }
}

// Main setup process
try {
    echo "Starting database setup...\n";
    
    // Create initial connection without database
    $conn = createConnection($DB_HOST, $DB_USERNAME, $DB_PASSWORD);
    
    // Create database if not exists
    $conn->exec("CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database checked/created successfully\n";
    
    // Connect to the database
    $conn = createConnection($DB_HOST, $DB_USERNAME, $DB_PASSWORD, $DB_NAME);
    
    // Execute the initialization SQL file
    if (!executeSQLFile($conn, __DIR__ . '/init.sql')) {
        throw new Exception("Failed to initialize database structure");
    }
    
    // Create necessary directories
    createDirectories();
    
    echo "\nSetup completed successfully!\n";
    echo "Default admin credentials:\n";
    echo "Username: admin\n";
    echo "Password: admin123\n";
    
} catch(Exception $e) {
    die("Setup failed: " . $e->getMessage() . "\n");
} 