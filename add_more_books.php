<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$conn = new mysqli('localhost', 'root', '', 'bookhub');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Array of books to add
$books = [
    [
        'title' => 'Lord of the Flies',
        'author' => 'William Golding',
        'description' => 'A group of British boys stranded on an uninhabited island and their disastrous attempt to govern themselves.',
        'genre' => 'Fiction',
        'year' => 1954
    ],
    [
        'title' => 'Brave New World',
        'author' => 'Aldous Huxley',
        'description' => 'A dystopian novel set in a futuristic World State, inhabited by genetically modified citizens and an intelligence-based social hierarchy.',
        'genre' => 'Science Fiction',
        'year' => 1932
    ],
    [
        'title' => 'The Catcher in the Rye',
        'author' => 'J.D. Salinger',
        'description' => 'The story of teenage protagonist Holden Caulfield and his experiences in New York City.',
        'genre' => 'Fiction',
        'year' => 1951
    ],
    [
        'title' => 'Animal Farm',
        'author' => 'George Orwell',
        'description' => 'An allegorical novella reflecting events leading up to the Russian Revolution of 1917.',
        'genre' => 'Fiction',
        'year' => 1945
    ],
    [
        'title' => 'Fahrenheit 451',
        'author' => 'Ray Bradbury',
        'description' => 'A dystopian novel about a future American society where books are outlawed.',
        'genre' => 'Science Fiction',
        'year' => 1953
    ],
    [
        'title' => 'One Hundred Years of Solitude',
        'author' => 'Gabriel García Márquez',
        'description' => 'The multi-generational story of the Buendía family in the fictional town of Macondo.',
        'genre' => 'Fiction',
        'year' => 1967
    ],
    [
        'title' => 'The Odyssey',
        'author' => 'Homer',
        'description' => 'Ancient Greek epic poem following Odysseus\'s journey home after the Trojan War.',
        'genre' => 'Classic',
        'year' => -800
    ],
    [
        'title' => 'Don Quixote',
        'author' => 'Miguel de Cervantes',
        'description' => 'The story of a man who loses his sanity reading too many chivalric romances and decides to become a knight.',
        'genre' => 'Classic',
        'year' => 1605
    ],
    [
        'title' => 'Moby-Dick',
        'author' => 'Herman Melville',
        'description' => 'The story of the obsessive quest of Ahab, captain of the whaler Pequod, for revenge on Moby Dick.',
        'genre' => 'Fiction',
        'year' => 1851
    ],
    [
        'title' => 'The Divine Comedy',
        'author' => 'Dante Alighieri',
        'description' => 'An epic poem describing Dante\'s journey through Hell, Purgatory, and Paradise.',
        'genre' => 'Classic',
        'year' => 1320
    ]
];

try {
    // Prepare the insert statement
    $stmt = $conn->prepare("INSERT INTO books (title, author, description, genre, publication_year) VALUES (?, ?, ?, ?, ?)");
    
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }

    // Counter for successful insertions
    $successful = 0;

    // Add each book
    foreach ($books as $book) {
        $stmt->bind_param("ssssi", 
            $book['title'],
            $book['author'],
            $book['description'],
            $book['genre'],
            $book['year']
        );

        if ($stmt->execute()) {
            $successful++;
            echo "Added: {$book['title']} by {$book['author']}<br>";
        } else {
            echo "Error adding {$book['title']}: " . $stmt->error . "<br>";
        }
    }

    echo "<br>Successfully added $successful out of " . count($books) . " books.";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    $conn->close();
}
?> 