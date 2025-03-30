-- Disable foreign key checks and set character set
SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- Drop existing tables if they exist
DROP TABLE IF EXISTS reading_progress;
DROP TABLE IF EXISTS ratings;
DROP TABLE IF EXISTS reading_list;
DROP TABLE IF EXISTS books;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS login_attempts;
DROP TABLE IF EXISTS user_activity_log;
DROP TABLE IF EXISTS password_reset_tokens;

-- Enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Create users table
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    is_admin BOOLEAN DEFAULT FALSE,
    reset_token VARCHAR(255) NULL,
    reset_token_expires TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create books table
CREATE TABLE books (
    book_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    description TEXT,
    cover_image VARCHAR(255),
    genre VARCHAR(50),
    publication_year INT,
    file_path VARCHAR(255),
    file_type VARCHAR(10) DEFAULT 'pdf',
    total_pages INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_author (author),
    INDEX idx_genre (genre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create reading_list table
CREATE TABLE reading_list (
    list_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    list_type ENUM('want-to-read','currently-reading','completed') NOT NULL,
    progress INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_book (user_id, book_id),
    INDEX idx_user_status (user_id, list_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create reading_progress table
CREATE TABLE reading_progress (
    progress_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    current_page INT DEFAULT 0,
    is_completed BOOLEAN DEFAULT FALSE,
    last_read_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_book_progress (user_id, book_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create ratings table
CREATE TABLE ratings (
    rating_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    rating DECIMAL(2,1) NOT NULL CHECK (rating >= 0 AND rating <= 5),
    review TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_book_rating (user_id, book_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create login_attempts table
CREATE TABLE login_attempts (
    attempt_id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL,
    attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    INDEX idx_email_time (email, attempt_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create user_activity_log table
CREATE TABLE user_activity_log (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    activity_type VARCHAR(50) NOT NULL,
    activity_details TEXT,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_activity (user_id, activity_type, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create views
CREATE OR REPLACE VIEW vw_books_details AS
SELECT 
    b.*,
    COALESCE(AVG(r.rating), 0) as average_rating,
    COUNT(DISTINCT r.rating_id) as total_ratings,
    COUNT(DISTINCT rl.list_id) as total_in_lists,
    COUNT(DISTINCT rp.progress_id) as total_readers
FROM books b
LEFT JOIN ratings r ON b.book_id = r.book_id
LEFT JOIN reading_list rl ON b.book_id = rl.book_id
LEFT JOIN reading_progress rp ON b.book_id = rp.book_id
GROUP BY b.book_id;

CREATE OR REPLACE VIEW vw_popular_books AS
SELECT 
    b.*,
    COALESCE(AVG(r.rating), 0) as average_rating,
    COUNT(DISTINCT r.rating_id) as total_ratings,
    COUNT(DISTINCT rl.list_id) as total_in_lists
FROM books b
LEFT JOIN ratings r ON b.book_id = r.book_id
LEFT JOIN reading_list rl ON b.book_id = rl.book_id
GROUP BY b.book_id
HAVING total_ratings > 0
ORDER BY average_rating DESC, total_ratings DESC;

-- Insert sample admin user (password: admin123)
INSERT INTO users (username, email, password_hash, full_name, is_admin) 
VALUES ('admin', 'admin@bookhub.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin User', TRUE);

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