-- Drop existing tables if they exist
DROP TABLE IF EXISTS reading_progress;
DROP TABLE IF EXISTS ratings;
DROP TABLE IF EXISTS reading_list;
DROP TABLE IF EXISTS books;
DROP TABLE IF EXISTS users;

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
    reset_token VARCHAR(255) NULL,
    reset_token_expires TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create reading_list table
CREATE TABLE reading_list (
    list_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_book_list (user_id, book_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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