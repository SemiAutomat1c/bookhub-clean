<?php
header('Content-Type: application/json');

// Test data
$data = [
    'test' => true,
    'message' => 'If you see this as JSON, the JSON output is working'
];

echo json_encode($data);
?>
