<?php
// Read the JSON file
$jsonData = file_get_contents('books.json');
$data = json_decode($jsonData, true);

if (!$data || !isset($data['books'])) {
    die("Error: Invalid JSON data\n");
}

// Open output file
$outputFile = fopen('books.txt', 'w');

// Convert each book to text format
foreach ($data['books'] as $book) {
    $fields = [
        $book['title'] ?? '',
        $book['author'] ?? '',
        $book['cover'] ?? '',
        $book['description'] ?? '',
        $book['genre'] ?? '',
        $book['published'] ?? '',
        isset($book['file_path']) ? $book['file_path'] : '',
        isset($book['file_type']) ? $book['file_type'] : 'pdf'
    ];

    // Escape pipe characters in fields
    $fields = array_map(function($field) {
        return str_replace('|', '\\|', $field);
    }, $fields);

    // Write line to file
    fwrite($outputFile, implode('|', $fields) . "\n");
}

fclose($outputFile);
echo "Conversion completed. Data written to books.txt\n"; 