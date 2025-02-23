-- Update existing book if it exists
UPDATE books 
SET 
    file_path = '../books/samples/sample_chapter.html',
    file_type = 'html'
WHERE title = 'The Great Gatsby';

-- Insert if it doesn't exist
INSERT INTO books (title, author, description, genre, cover_image, file_path, file_type)
SELECT 
    'The Great Gatsby',
    'F. Scott Fitzgerald',
    'The Great Gatsby is a 1925 novel by American writer F. Scott Fitzgerald. Set in the Jazz Age on Long Island, near New York City, the novel depicts first-person narrator Nick Carraway\'s interactions with mysterious millionaire Jay Gatsby and Gatsby\'s obsession to reunite with his former lover, Daisy Buchanan.',
    'Classic Literature',
    '../assets/images/books/gatsby.jpg',
    '../books/samples/sample_chapter.html',
    'html'
WHERE NOT EXISTS (
    SELECT 1 FROM books WHERE title = 'The Great Gatsby'
);
