<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'bookhub';

// Create connection
$conn = new mysqli($host, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Connected successfully\n";

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS $database";
if ($conn->query($sql) === TRUE) {
    echo "Database checked/created successfully\n";
} else {
    die("Error creating database: " . $conn->error);
}

// Select the database
$conn->select_db($database);

// Read and execute the SQL file
$sql_file = __DIR__ . '/fixed_setup.sql';
$sql_content = file_get_contents($sql_file);

if ($sql_content === false) {
    die("Error reading SQL file");
}

// Split the SQL file into individual statements
$statements = array_filter(array_map('trim', explode(';', $sql_content)));

// Execute each statement
foreach ($statements as $statement) {
    if (!empty($statement)) {
        if ($conn->query($statement) === TRUE) {
            echo "Executed successfully: " . substr($statement, 0, 50) . "...\n";
        } else {
            die("Error executing statement: " . $conn->error . "\nStatement: " . $statement);
        }
    }
}

echo "Database setup completed successfully!";

// Close connection
$conn->close();
?> 