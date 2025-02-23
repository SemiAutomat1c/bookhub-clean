-- Drop existing tables
DROP TABLE IF EXISTS reading_list;
DROP TABLE IF EXISTS books;
DROP TABLE IF EXISTS users;

-- Create users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create books table
CREATE TABLE books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    description TEXT,
    cover_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create reading_list table
CREATE TABLE reading_list (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_book (user_id, book_id)
);

-- Insert sample books
INSERT INTO books (title, author, description, cover_image) VALUES
('The Great Gatsby', 'F. Scott Fitzgerald', 'A story of decadence and excess.', 'images/gatsby.jpg'),
('To Kill a Mockingbird', 'Harper Lee', 'A classic of modern American literature.', 'images/mockingbird.jpg'),
('1984', 'George Orwell', 'A dystopian social science fiction novel.', 'images/1984.jpg'),
('Pride and Prejudice', 'Jane Austen', 'A romantic novel of manners.', 'images/pride.jpg'),
('The Hobbit', 'J.R.R. Tolkien', 'A fantasy novel and children''s book.', 'images/hobbit.jpg');
