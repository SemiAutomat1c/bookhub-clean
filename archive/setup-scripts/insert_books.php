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
    // Books data array
    $books = [
        [
            'title' => '1984',
            'author' => 'George Orwell',
            'description' => 'A dystopian social science fiction novel that explores themes of totalitarianism and surveillance.',
            'genre' => 'Fiction',
            'publication_year' => 1949,
            'cover_image' => 'assets/images/covers/1984.jpg',
            'file_path' => 'books/1984.pdf'
        ],
        [
            'title' => 'Animal Farm',
            'author' => 'George Orwell',
            'description' => 'A satirical allegorical novella that reflects events leading up to the Russian Revolution and the Stalinist era of the Soviet Union.',
            'genre' => 'Fiction',
            'publication_year' => 1945,
            'cover_image' => 'assets/images/covers/animal-farm.jpg',
            'file_path' => 'books/animal-farm.pdf'
        ],
        [
            'title' => 'Brave New World',
            'author' => 'Aldous Huxley',
            'description' => 'A dystopian novel that explores a genetically engineered future society where comfort and happiness are prioritized over truth and freedom.',
            'genre' => 'Fiction',
            'publication_year' => 1932,
            'cover_image' => 'assets/images/covers/brave-new-world.jpg',
            'file_path' => 'books/brave-new-world.pdf'
        ],
        [
            'title' => 'Catcher in the Rye',
            'author' => 'J.D. Salinger',
            'description' => 'A story of teenage alienation and loss of innocence in American society, following Holden Caulfield\'s experiences in New York City.',
            'genre' => 'Fiction',
            'publication_year' => 1951,
            'cover_image' => 'assets/images/covers/catcher-in-the-rye.jpg',
            'file_path' => 'books/catcher-in-the-rye.pdf'
        ],
        [
            'title' => 'Don Quixote',
            'author' => 'Miguel de Cervantes Saavedra',
            'description' => 'The story of a man who loses his sanity and becomes a knight errant, traveling across Spain with his squire Sancho Panza.',
            'genre' => 'Classic',
            'publication_year' => 1605,
            'cover_image' => 'assets/images/covers/don-quixote.jpg',
            'file_path' => 'books/don-quixote.pdf'
        ],
        [
            'title' => 'Fahrenheit 451',
            'author' => 'Ray Bradbury',
            'description' => 'A dystopian novel about a future American society where books are outlawed and firemen burn any that are found.',
            'genre' => 'Fiction',
            'publication_year' => 1953,
            'cover_image' => 'assets/images/covers/fahrenheit-451.jpg',
            'file_path' => 'books/fahrenheit-451.pdf'
        ],
        [
            'title' => 'Lord of the Flies',
            'author' => 'William Golding',
            'description' => 'A novel about a group of British boys stranded on an uninhabited island and their disastrous attempt to govern themselves.',
            'genre' => 'Fiction',
            'publication_year' => 1954,
            'cover_image' => 'assets/images/covers/lord-of-the-flies.jpg',
            'file_path' => 'books/lord-of-the-flies.pdf'
        ],
        [
            'title' => 'Moby Dick',
            'author' => 'Herman Melville',
            'description' => 'The story of the obsessive quest of Ahab, captain of the whaler Pequod, for revenge on Moby Dick, the giant white sperm whale.',
            'genre' => 'Classic',
            'publication_year' => 1851,
            'cover_image' => 'assets/images/covers/moby-dick.jpg',
            'file_path' => 'books/moby-dick.pdf'
        ],
        [
            'title' => 'One Hundred Years of Solitude',
            'author' => 'Gabriel García Márquez',
            'description' => 'A landmark of magical realism that tells the multi-generational story of the Buendía family in the fictional town of Macondo.',
            'genre' => 'Fiction',
            'publication_year' => 1967,
            'cover_image' => 'assets/images/covers/one-hundred-years.jpg',
            'file_path' => 'books/one-hundred-years.pdf'
        ],
        [
            'title' => 'Pride and Prejudice',
            'author' => 'Jane Austen',
            'description' => 'A romantic novel following the emotional development of Elizabeth Bennet as she deals with issues of manners, upbringing, and marriage.',
            'genre' => 'Classic',
            'publication_year' => 1813,
            'cover_image' => 'assets/images/covers/pride-and-prejudice.jpg',
            'file_path' => 'books/pride-and-prejudice.pdf'
        ],
        [
            'title' => 'The Divine Comedy',
            'author' => 'Dante Alighieri',
            'description' => 'An epic poem that describes Dante\'s journey through Hell, Purgatory, and Paradise, guided by Virgil and later by Beatrice.',
            'genre' => 'Classic',
            'publication_year' => 1320,
            'cover_image' => 'assets/images/covers/divine-comedy.jpg',
            'file_path' => 'books/divine-comedy.pdf'
        ],
        [
            'title' => 'The Great Gatsby',
            'author' => 'F. Scott Fitzgerald',
            'description' => 'A story of the mysteriously wealthy Jay Gatsby and his obsessive love for the beautiful Daisy Buchanan, set against the backdrop of the Roaring Twenties.',
            'genre' => 'Fiction',
            'publication_year' => 1925,
            'cover_image' => 'assets/images/covers/great-gatsby.jpg',
            'file_path' => 'books/great-gatsby.pdf'
        ],
        [
            'title' => 'The Hobbit',
            'author' => 'J.R.R. Tolkien',
            'description' => 'A fantasy novel about the adventures of Bilbo Baggins, who embarks on a quest to help a group of dwarves reclaim their mountain home from a dragon.',
            'genre' => 'Fantasy',
            'publication_year' => 1937,
            'cover_image' => 'assets/images/covers/hobbit.jpg',
            'file_path' => 'books/hobbit.pdf'
        ],
        [
            'title' => 'The Odyssey',
            'author' => 'Homer',
            'description' => 'An ancient Greek epic poem that follows Odysseus\'s journey home after the fall of Troy, and the adventures that ensue.',
            'genre' => 'Classic',
            'publication_year' => -800,
            'cover_image' => 'assets/images/covers/odyssey.jpg',
            'file_path' => 'books/odyssey.pdf'
        ],
        [
            'title' => 'To Kill a Mockingbird',
            'author' => 'Harper Lee',
            'description' => 'A story about racial injustice and the loss of innocence in the American South, told through the eyes of young Scout Finch.',
            'genre' => 'Fiction',
            'publication_year' => 1960,
            'cover_image' => 'assets/images/covers/mockingbird.jpg',
            'file_path' => 'books/mockingbird.pdf'
        ]
    ];

    // Prepare the insert statement
    $stmt = $conn->prepare("INSERT INTO books (title, author, description, genre, publication_year, cover_image, file_path) VALUES (?, ?, ?, ?, ?, ?, ?)");

    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }

    // Insert each book
    foreach ($books as $book) {
        $stmt->bind_param("ssssis", 
            $book['title'],
            $book['author'],
            $book['description'],
            $book['genre'],
            $book['publication_year'],
            $book['cover_image'],
            $book['file_path']
        );

        if (!$stmt->execute()) {
            throw new Exception("Failed to insert book '{$book['title']}': " . $stmt->error);
        }

        echo "Successfully inserted book: {$book['title']}\n";
    }

    echo "\nAll books have been inserted successfully!";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?> 