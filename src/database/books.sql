CREATE TABLE IF NOT EXISTS books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    description TEXT,
    cover_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert some sample books
INSERT INTO books (title, author, description, cover_image) VALUES
('The Great Gatsby', 'F. Scott Fitzgerald', 'A story of decadence and excess.', 'images/gatsby.jpg'),
('To Kill a Mockingbird', 'Harper Lee', 'A classic of modern American literature.', 'images/mockingbird.jpg'),
('1984', 'George Orwell', 'A dystopian social science fiction novel.', 'images/1984.jpg'),
('Pride and Prejudice', 'Jane Austen', 'A romantic novel of manners.', 'images/pride.jpg'),
('The Hobbit', 'J.R.R. Tolkien', 'A fantasy novel and children''s book.', 'images/hobbit.jpg');
