<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

try {
    $conn = getConnection();

    $books = [
        'The Great Gatsby' => 'gatsby.jpg',
        '1984' => '1984.jpg',
        'Pride and Prejudice' => 'pride.jpg',
        'The Hobbit' => 'hobbit.jpg',
        'Dune' => 'dune.jpg',
        'The Catcher in the Rye' => 'catcher.jpg',
        'The Alchemist' => 'alchemist.jpg',
        'The Lord of the Rings' => 'lotr.jpg',
        'Brave New World' => 'brave.jpg'
    ];

    foreach ($books as $title => $image) {
        $title = $conn->real_escape_string($title);
        $path = 'assets/images/covers/' . $image;
        
        $sql = "UPDATE books SET cover_image = '$path' WHERE title = '$title'";
        if ($conn->query($sql)) {
            echo "Updated cover path for: $title<br>";
        } else {
            echo "Error updating cover path for $title: " . $conn->error . "<br>";
        }
    }

    $conn->close();
    echo "Done updating cover paths!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?> 