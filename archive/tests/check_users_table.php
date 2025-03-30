<?php
require_once 'config/database.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get database connection
$conn = getConnection();
if (!$conn) {
    die("Database connection failed");
}

// Get table structure
$result = $conn->query("DESCRIBE users");

if (!$result) {
    die("Query failed: " . $conn->error);
}

echo "=== Users Table Structure ===\n\n";
while ($row = $result->fetch_assoc()) {
    echo "Field: " . $row['Field'] . "\n";
    echo "Type: " . $row['Type'] . "\n";
    echo "Null: " . $row['Null'] . "\n";
    echo "Default: " . ($row['Default'] ?? 'NULL') . "\n";
    echo str_repeat("-", 50) . "\n\n";
}

$conn->close();
?> 