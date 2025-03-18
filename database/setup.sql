-- Disable foreign key checks
SET FOREIGN_KEY_CHECKS = 0;

-- Drop tables if they exist
DROP TABLE IF EXISTS book_reviews;
DROP TABLE IF EXISTS reading_progress;
DROP TABLE IF EXISTS reading_list;
DROP TABLE IF EXISTS books;
DROP TABLE IF EXISTS users;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Create users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_admin BOOLEAN DEFAULT FALSE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create books table
CREATE TABLE books (
    book_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    description TEXT,
    genre VARCHAR(50),
    publication_year INT,
    cover_image VARCHAR(255),
    file_path VARCHAR(255),
    file_type VARCHAR(10),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create reading_list table
CREATE TABLE reading_list (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    status ENUM('want_to_read', 'reading', 'completed') DEFAULT 'want_to_read',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create reading_progress table
CREATE TABLE reading_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    chapter INT DEFAULT 1,
    page INT DEFAULT 1,
    last_read TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create book_reviews table
CREATE TABLE book_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    review TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample admin user (password: 'password')
INSERT INTO users (username, email, password, is_admin) 
VALUES ('admin', 'admin@bookhub.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE);

-- Insert sample books
INSERT INTO books (title, author, description, genre, publication_year, cover_image) VALUES
('The Great Gatsby', 'F. Scott Fitzgerald', 'A masterpiece of the Jazz Age, this novel explores themes of decadence, idealism, and excess in 1920s America through the story of the mysterious Jay Gatsby.', 'Fiction', 1925, 'assets/images/covers/gatsby.jpg'),
('To Kill a Mockingbird', 'Harper Lee', 'A powerful exploration of racial injustice and the loss of innocence in the American South, told through the eyes of young Scout Finch.', 'Fiction', 1960, 'assets/images/covers/mockingbird.jpg'),
('1984', 'George Orwell', 'A dystopian masterpiece that explores surveillance, totalitarianism, and the manipulation of truth in a nightmarish future society.', 'Science Fiction', 1949, 'assets/images/covers/1984.jpg'),
('Pride and Prejudice', 'Jane Austen', 'A witty and romantic novel about the relationship between Elizabeth Bennet and Mr. Darcy in Georgian era England.', 'Romance', 1813, 'assets/images/covers/pride.jpg'),
('The Hobbit', 'J.R.R. Tolkien', 'The beloved fantasy adventure of Bilbo Baggins, who journeys with a group of dwarves to reclaim their mountain home from a fearsome dragon.', 'Fantasy', 1937, 'assets/images/covers/hobbit.jpg'),
('Dune', 'Frank Herbert', 'An epic science fiction tale of politics, religion, and ecology on the desert planet Arrakis, following young Paul Atreides.', 'Science Fiction', 1965, 'assets/images/covers/dune.jpg'),
('The Catcher in the Rye', 'J.D. Salinger', 'The story of teenage alienation and rebellion told through the eyes of Holden Caulfield during his three-day journey through New York City.', 'Fiction', 1951, 'assets/images/covers/catcher.jpg'),
('The Alchemist', 'Paulo Coelho', 'A philosophical novel about a young Andalusian shepherd who travels to Egypt in search of treasure, discovering the meaning of life along the way.', 'Fiction', 1988, 'assets/images/covers/alchemist.jpg'),
('The Lord of the Rings', 'J.R.R. Tolkien', 'An epic high-fantasy trilogy following the quest to destroy the One Ring and defeat the Dark Lord Sauron.', 'Fantasy', 1954, 'assets/images/covers/lotr.jpg'),
('Brave New World', 'Aldous Huxley', 'A dystopian novel exploring a genetically engineered future society where comfort and happiness are prioritized over truth and freedom.', 'Science Fiction', 1932, 'assets/images/covers/brave.jpg'); 