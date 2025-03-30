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

// Function to get all cover images
function getCovers($path) {
    $covers = [];
    if ($handle = opendir($path)) {
        while (false !== ($entry = readdir($handle))) {
            if ($entry != "." && $entry != ".." && is_file($path . '/' . $entry)) {
                if (preg_match('/\.(jpg|jpeg|png|gif)$/i', $entry)) {
                    $covers[] = $entry;
                }
            }
        }
        closedir($handle);
    }
    return $covers;
}

try {
    echo "<h2>Book Recovery Tool</h2>";
    
    // Get all cover images
    $covers_path = 'assets/images/covers';
    $covers = getCovers($covers_path);
    
    echo "<p>Found " . count($covers) . " cover images.</p>";
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['book'])) {
            foreach ($_POST['book'] as $index => $data) {
                $title = $conn->real_escape_string($data['title']);
                $author = $conn->real_escape_string($data['author']);
                $genre = $conn->real_escape_string($data['genre']);
                $cover_image = $conn->real_escape_string($data['cover_image']);
                $description = $conn->real_escape_string($data['description'] ?? '');
                $publication_year = !empty($data['publication_year']) ? intval($data['publication_year']) : null;
                
                $sql = "INSERT INTO books (title, author, genre, description, publication_year, cover_image) 
                        VALUES (?, ?, ?, ?, ?, ?)";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssss", $title, $author, $genre, $description, $publication_year, $cover_image);
                
                if ($stmt->execute()) {
                    echo "<p style='color: green;'>Successfully recovered: $title by $author</p>";
                } else {
                    echo "<p style='color: red;'>Error recovering $title: " . $stmt->error . "</p>";
                }
                $stmt->close();
            }
            echo "<p><a href='/bookhub-1/views/index.html'>Return to homepage</a></p>";
        }
    } else {
        // Display form
        echo "<form method='post' style='max-width: 800px; margin: 20px auto;'>";
        echo "<style>
            .book-entry { border: 1px solid #ccc; padding: 15px; margin: 10px 0; border-radius: 5px; }
            .form-group { margin: 10px 0; }
            label { display: block; margin-bottom: 5px; font-weight: bold; }
            input[type='text'], input[type='number'] { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
            select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
            textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; min-height: 100px; }
            .submit-btn { padding: 12px 24px; background: #007bff; color: white; border: none; cursor: pointer; border-radius: 4px; font-size: 16px; }
            .submit-btn:hover { background: #0056b3; }
            .cover-preview { max-width: 150px; margin: 10px 0; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
            </style>";
        
        // For each cover image, create a form section
        foreach ($covers as $index => $cover) {
            echo "<div class='book-entry'>";
            echo "<h3>Book #" . ($index + 1) . "</h3>";
            
            // Hidden field for cover image path
            echo "<input type='hidden' name='book[$index][cover_image]' value='assets/images/covers/$cover'>";
            
            // Show cover preview
            echo "<div class='form-group'>";
            echo "<strong>Cover Image:</strong><br>";
            echo "<img src='/$covers_path/$cover' class='cover-preview'><br>";
            echo "<em>File: $cover</em>";
            echo "</div>";
            
            // Title field
            echo "<div class='form-group'>";
            echo "<label>Title:</label>";
            echo "<input type='text' name='book[$index][title]' required>";
            echo "</div>";
            
            // Author field
            echo "<div class='form-group'>";
            echo "<label>Author:</label>";
            echo "<input type='text' name='book[$index][author]' required>";
            echo "</div>";
            
            // Genre field
            echo "<div class='form-group'>";
            echo "<label>Genre:</label>";
            echo "<select name='book[$index][genre]' required>
                <option value=''>Select Genre</option>
                <option value='Fiction'>Fiction</option>
                <option value='Non-Fiction'>Non-Fiction</option>
                <option value='Mystery'>Mystery</option>
                <option value='Science Fiction'>Science Fiction</option>
                <option value='Fantasy'>Fantasy</option>
                <option value='Romance'>Romance</option>
                <option value='Thriller'>Thriller</option>
                <option value='Horror'>Horror</option>
                <option value='Biography'>Biography</option>
                <option value='History'>History</option>
                <option value='Poetry'>Poetry</option>
                <option value='Classic'>Classic</option>
                <option value='Contemporary'>Contemporary</option>
                </select>";
            echo "</div>";
            
            // Publication Year field
            echo "<div class='form-group'>";
            echo "<label>Publication Year:</label>";
            echo "<input type='number' name='book[$index][publication_year]' min='1800' max='2024'>";
            echo "</div>";
            
            // Description field
            echo "<div class='form-group'>";
            echo "<label>Description:</label>";
            echo "<textarea name='book[$index][description]'></textarea>";
            echo "</div>";
            
            echo "</div>"; // End book-entry
        }
        
        echo "<button type='submit' class='submit-btn'>Recover Books</button>";
        echo "</form>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
} finally {
    $conn->close();
}
?> 