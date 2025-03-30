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

try {
    // Add last_login column if it doesn't exist
    $sql = "ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login TIMESTAMP NULL DEFAULT NULL";
    if ($conn->query($sql)) {
        echo "Successfully added last_login column\n";
    } else {
        throw new Exception("Failed to add last_login column: " . $conn->error);
    }

    // Verify the column was added
    $result = $conn->query("DESCRIBE users");
    if (!$result) {
        throw new Exception("Failed to get table structure: " . $conn->error);
    }

    echo "\n=== Updated Users Table Structure ===\n\n";
    while ($row = $result->fetch_assoc()) {
        echo "Field: " . $row['Field'] . "\n";
        echo "Type: " . $row['Type'] . "\n";
        echo "Null: " . $row['Null'] . "\n";
        echo "Default: " . ($row['Default'] ?? 'NULL') . "\n";
        echo str_repeat("-", 50) . "\n\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} finally {
    $conn->close();
}
?> 