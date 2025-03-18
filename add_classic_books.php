<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'bookhub');

// Create connection
$conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Array of classic books
$books = [
    [
        'title' => '1984',
        'author' => 'George Orwell',
        'genre' => 'Science Fiction',
        'publication_year' => 1949,
        'description' => 'A dystopian social science fiction novel that follows Winston Smith, who rebels against the totalitarian government of Oceania. The story explores themes of surveillance, censorship, and the manipulation of truth.'
    ],
    [
        'title' => 'Animal Farm',
        'author' => 'George Orwell',
        'genre' => 'Fiction',
        'publication_year' => 1945,
        'description' => 'An allegorical novella that reflects events leading up to the Russian Revolution of 1917. The story follows a group of farm animals who rebel against their human farmer, hoping to create a society where the animals can be equal, free, and happy.'
    ],
    [
        'title' => 'Brave New World',
        'author' => 'Aldous Huxley',
        'genre' => 'Science Fiction',
        'publication_year' => 1932,
        'description' => 'Set in a futuristic World State, whose citizens are environmentally engineered into a intelligence-based social hierarchy. The novel anticipates huge scientific developments in reproductive technology, sleep-learning, psychological manipulation, and classical conditioning.'
    ],
    [
        'title' => 'Don Quixote',
        'author' => 'Miguel de Cervantes',
        'genre' => 'Classic',
        'publication_year' => 1605,
        'description' => 'The story follows a hidalgo named Alonso Quixano who reads so many chivalric romances that he loses his sanity and decides to become a knight-errant, reviving chivalry and serving his country, under the name Don Quixote.'
    ],
    [
        'title' => 'Fahrenheit 451',
        'author' => 'Ray Bradbury',
        'genre' => 'Science Fiction',
        'publication_year' => 1953,
        'description' => 'Set in a dystopian society that burns books in order to control dangerous ideas and unhappy concepts. The novel tells the story of Guy Montag, a fireman who questions his role in censoring literature and destroying knowledge.'
    ],
    [
        'title' => 'Lord of the Flies',
        'author' => 'William Golding',
        'genre' => 'Fiction',
        'publication_year' => 1954,
        'description' => 'A group of British boys stranded on an uninhabited island attempt to govern themselves, with disastrous results. The novel explores themes of civilization and savagery, innocence and loss, and human nature.'
    ],
    [
        'title' => 'Moby-Dick',
        'author' => 'Herman Melville',
        'genre' => 'Fiction',
        'publication_year' => 1851,
        'description' => 'The saga of Captain Ahab and his monomaniacal pursuit of Moby Dick, the great white whale who maimed him. The novel explores themes of obsession, nature, and the limits of human knowledge.'
    ],
    [
        'title' => 'One Hundred Years of Solitude',
        'author' => 'Gabriel García Márquez',
        'genre' => 'Fiction',
        'publication_year' => 1967,
        'description' => 'The multi-generational story of the Buendía family in the fictional town of Macondo. The novel incorporates magical realism and chronicles the family\'s history over seven generations.'
    ],
    [
        'title' => 'Pride and Prejudice',
        'author' => 'Jane Austen',
        'genre' => 'Romance',
        'publication_year' => 1813,
        'description' => 'The story follows Elizabeth Bennet as she deals with issues of manners, upbringing, morality, education, and marriage in the society of the landed gentry of early 19th-century England.'
    ],
    [
        'title' => 'The Catcher in the Rye',
        'author' => 'J.D. Salinger',
        'genre' => 'Fiction',
        'publication_year' => 1951,
        'description' => 'The story of teenage protagonist Holden Caulfield and his experiences in New York City over the course of three days after being expelled from his boarding school. The novel deals with complex issues of identity, belonging, and connection.'
    ],
    [
        'title' => 'The Divine Comedy',
        'author' => 'Dante Alighieri',
        'genre' => 'Classic',
        'publication_year' => 1320,
        'description' => 'An epic poem that describes Dante\'s journey through Hell (Inferno), Purgatory (Purgatorio), and Paradise (Paradiso). It is an allegory representing the soul\'s journey towards God.'
    ],
    [
        'title' => 'The Great Gatsby',
        'author' => 'F. Scott Fitzgerald',
        'genre' => 'Fiction',
        'publication_year' => 1925,
        'description' => 'Set in the Jazz Age on Long Island, the novel depicts narrator Nick Carraway\'s interactions with mysterious millionaire Jay Gatsby and Gatsby\'s obsession to reunite with his former lover, Daisy Buchanan.'
    ],
    [
        'title' => 'The Hobbit',
        'author' => 'J.R.R. Tolkien',
        'genre' => 'Fantasy',
        'publication_year' => 1937,
        'description' => 'The story follows home-loving hobbit Bilbo Baggins as he joins a group of dwarves and the wizard Gandalf on a quest to reclaim the Lonely Mountain and its treasure from the dragon Smaug.'
    ],
    [
        'title' => 'The Odyssey',
        'author' => 'Homer',
        'genre' => 'Classic',
        'publication_year' => -800,
        'description' => 'Ancient Greek epic poem that follows Odysseus, king of Ithaca, and his journey home after the Trojan War. The poem covers both the tale of Odysseus\'s wanderings and the situation at home with his wife Penelope and son Telemachus.'
    ],
    [
        'title' => 'To Kill a Mockingbird',
        'author' => 'Harper Lee',
        'genre' => 'Fiction',
        'publication_year' => 1960,
        'description' => 'The story of young Scout Finch and her father Atticus, a lawyer who defends a black man accused of rape in the Depression-era South. The novel addresses issues of racism and injustice while maintaining the perspective of childhood innocence.'
    ]
];

try {
    // Prepare the insert statement
    $sql = "INSERT INTO books (title, author, genre, description, publication_year) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }

    // Counter for successful insertions
    $successful = 0;

    // Add each book
    foreach ($books as $book) {
        if (!$stmt->bind_param("ssssi", 
            $book['title'],
            $book['author'],
            $book['genre'],
            $book['description'],
            $book['publication_year']
        )) {
            throw new Exception("Binding parameters failed: " . $stmt->error);
        }

        if ($stmt->execute()) {
            $successful++;
            echo "<p style='color: green;'>Added: {$book['title']} by {$book['author']}</p>";
        } else {
            echo "<p style='color: red;'>Error adding {$book['title']}: " . $stmt->error . "</p>";
        }
    }

    echo "<p style='margin-top: 20px;'><strong>Successfully added $successful out of " . count($books) . " books.</strong></p>";
    echo "<p>You can now add cover images for these books through the admin interface.</p>";
    echo "<p><a href='/bookhub-1/views/admin.html' style='color: blue; text-decoration: underline;'>Go to Admin Panel</a></p>";

} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    $conn->close();
}
?> 