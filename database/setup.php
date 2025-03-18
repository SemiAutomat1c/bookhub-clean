<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'bookhub';

try {
    // Create connection
    $conn = new mysqli($host, $username, $password);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    echo "Connected successfully\n";

    // Create database if it doesn't exist
    $sql = "CREATE DATABASE IF NOT EXISTS $database";
    if ($conn->query($sql)) {
        echo "Database checked/created successfully\n";
    } else {
        throw new Exception("Error creating database: " . $conn->error);
    }

    // Select the database
    $conn->select_db($database);

    // Set the correct charset
    if (!$conn->set_charset("utf8mb4")) {
        throw new Exception("Error setting charset: " . $conn->error);
    }

    // Read and execute the SQL file
    $sql_file = __DIR__ . '/setup.sql';
    $sql_content = file_get_contents($sql_file);

    if ($sql_content === false) {
        throw new Exception("Error reading SQL file");
    }

    // Split the SQL file into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql_content)));

    // Execute each statement
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            if ($conn->query($statement)) {
                echo "Executed successfully: " . substr($statement, 0, 50) . "...\n";
            } else {
                throw new Exception("Error executing statement: " . $conn->error . "\nStatement: " . $statement);
            }
        }
    }

    echo "Database setup completed successfully!";

} catch (Exception $e) {
    die("Setup failed: " . $e->getMessage() . "\n");
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?> 