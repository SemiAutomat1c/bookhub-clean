-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 19, 2025 at 07:22 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bookhub`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_get_book_recommendations` (IN `p_user_id` INT, IN `p_limit` INT)   BEGIN
    -- Create temporary table for user's genre preferences
    CREATE TEMPORARY TABLE IF NOT EXISTS user_genres AS
    SELECT b.genre, COUNT(*) as genre_count
    FROM reading_lists rl
    JOIN books b ON rl.book_id = b.book_id
    WHERE rl.user_id = p_user_id
    GROUP BY b.genre;
    
    -- Select recommended books based on user's reading history and preferences
    SELECT DISTINCT 
        b.book_id,
        b.title,
        b.author,
        b.genre,
        b.publication_year,
        COALESCE(AVG(r.rating), 0) as avg_rating,
        COUNT(DISTINCT r.rating_id) as total_ratings
    FROM books b
    LEFT JOIN ratings r ON b.book_id = r.book_id
    LEFT JOIN user_genres ug ON b.genre = ug.genre
    WHERE b.book_id NOT IN (
        SELECT book_id 
        FROM reading_lists 
        WHERE user_id = p_user_id
    )
    GROUP BY b.book_id, b.title, b.author, b.genre, b.publication_year
    ORDER BY 
        ug.genre_count DESC,
        avg_rating DESC,
        total_ratings DESC
    LIMIT p_limit;
    
    -- Clean up
    DROP TEMPORARY TABLE IF EXISTS user_genres;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_update_reading_status` (IN `p_user_id` INT, IN `p_book_id` INT, IN `p_progress` INT, IN `p_list_type` VARCHAR(20))   BEGIN
    -- Update reading list status
    INSERT INTO reading_lists (user_id, book_id, list_type, progress)
    VALUES (p_user_id, p_book_id, p_list_type, p_progress)
    ON DUPLICATE KEY UPDATE
        list_type = p_list_type,
        progress = p_progress,
        last_updated = NOW();
END$$

--
-- Functions
--
CREATE DEFINER=`root`@`localhost` FUNCTION `fn_calculate_reading_progress` (`p_user_id` INT, `p_book_id` INT) RETURNS DECIMAL(5,2) DETERMINISTIC BEGIN
    DECLARE v_progress INT;
    DECLARE v_result DECIMAL(5,2);
    
    SELECT progress INTO v_progress
    FROM reading_lists
    WHERE user_id = p_user_id 
    AND book_id = p_book_id;
    
    IF v_progress IS NULL THEN
        RETURN 0;
    END IF;
    
    RETURN v_progress;
END$$

CREATE DEFINER=`root`@`localhost` FUNCTION `fn_get_reading_streak` (`p_user_id` INT) RETURNS INT(11) DETERMINISTIC BEGIN
    DECLARE v_streak INT;
    DECLARE v_last_read DATE;
    DECLARE v_current_date DATE;
    
    SELECT MAX(DATE(last_updated))
    INTO v_last_read
    FROM reading_lists
    WHERE user_id = p_user_id
    AND list_type = 'currently-reading';
    
    SET v_current_date = CURDATE();
    
    IF v_last_read IS NULL OR DATEDIFF(v_current_date, v_last_read) > 1 THEN
        RETURN 0;
    END IF;
    
    SELECT COUNT(DISTINCT DATE(last_updated))
    INTO v_streak
    FROM reading_lists
    WHERE user_id = p_user_id
    AND DATE(last_updated) <= v_last_read
    AND DATE(last_updated) >= DATE_SUB(v_last_read, INTERVAL 30 DAY)
    AND DATE(last_updated) IN (
        SELECT DATE_SUB(v_last_read, INTERVAL n DAY)
        FROM (
            SELECT 0 AS n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4
            UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9
        ) numbers
        WHERE DATE_SUB(v_last_read, INTERVAL n DAY) >= DATE_SUB(v_last_read, INTERVAL 30 DAY)
    );
    
    RETURN v_streak;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `books`
--

CREATE TABLE `books` (
  `book_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `genre` varchar(50) DEFAULT NULL,
  `publication_year` int(11) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `file_type` varchar(10) DEFAULT 'pdf',
  `total_pages` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `books`
--

INSERT INTO `books` (`book_id`, `title`, `author`, `description`, `cover_image`, `genre`, `publication_year`, `file_path`, `file_type`, `total_pages`, `created_at`, `updated_at`) VALUES
(8, '1984', 'George Orwell', 'A dystopian social science fiction novel that explores themes of totalitarianism and surveillance.', 'assets/images/covers/67db0669f073c.jpg', 'Fiction', 1949, 'assets/books/67db0669f11cc.pdf', 'pdf', 0, '2025-03-19 18:00:31', '2025-03-19 18:01:13'),
(9, 'Animal Farm', 'George Orwell', 'A satirical allegorical novella that reflects events leading up to the Russian Revolution and the Stalinist era of the Soviet Union.', 'assets/images/covers/67db06895bd55.jpg', 'Fiction', 1945, 'assets/books/67db06895c275.pdf', 'pdf', 0, '2025-03-19 18:00:31', '2025-03-19 18:01:45'),
(10, 'Brave New World', 'Aldous Huxley', 'A dystopian novel that explores a genetically engineered future society where comfort and happiness are prioritized over truth and freedom.', 'assets/images/covers/67db0695dbd45.jpg', 'Fiction', 1932, 'assets/books/67db0695dc16b.pdf', 'pdf', 0, '2025-03-19 18:00:31', '2025-03-19 18:01:57'),
(11, 'Catcher in the Rye', 'J.D. Salinger', 'A story of teenage alienation and loss of innocence in American society, following Holden Caulfield\'s experiences in New York City.', 'assets/images/covers/67db06a7c4fcf.jpg', 'Fiction', 1951, 'assets/books/67db06a7c53f3.pdf', 'pdf', 0, '2025-03-19 18:00:31', '2025-03-19 18:02:15'),
(12, 'Don Quixote', 'Miguel de Cervantes Saavedra', 'The story of a man who loses his sanity and becomes a knight errant, traveling across Spain with his squire Sancho Panza.', 'assets/images/covers/67db06ca309e5.jpg', 'Non-Fiction', 1811, 'assets/books/67db06ca30e08.pdf', 'pdf', 0, '2025-03-19 18:00:31', '2025-03-19 18:02:50'),
(13, 'Fahrenheit 451', 'Ray Bradbury', 'A dystopian novel about a future American society where books are outlawed and firemen burn any that are found.', 'assets/images/covers/67db06dc4fe51.jpg', 'Fiction', 1953, 'assets/books/67db06dc502a4.pdf', 'pdf', 0, '2025-03-19 18:00:31', '2025-03-19 18:03:08'),
(14, 'Lord of the Flies', 'William Golding', 'A novel about a group of British boys stranded on an uninhabited island and their disastrous attempt to govern themselves.', 'assets/images/covers/67db06eb149cd.jpg', 'Fiction', 1954, 'assets/books/67db06eb14da4.pdf', 'pdf', 0, '2025-03-19 18:00:31', '2025-03-19 18:03:23'),
(15, 'Moby Dick', 'Herman Melville', 'The story of the obsessive quest of Ahab, captain of the whaler Pequod, for revenge on Moby Dick, the giant white sperm whale.', 'assets/images/covers/67db0707bc6fb.jpg', 'Fiction', 1851, 'assets/books/67db0707bcb3e.pdf', 'pdf', 0, '2025-03-19 18:00:31', '2025-03-19 18:03:51'),
(16, 'One Hundred Years of Solitude', 'Gabriel García Márquez', 'A landmark of magical realism that tells the multi-generational story of the Buendía family in the fictional town of Macondo.', 'assets/images/covers/67db071623c82.jpg', 'Fiction', 1967, 'assets/books/67db0716240db.pdf', 'pdf', 0, '2025-03-19 18:00:31', '2025-03-19 18:04:06'),
(17, 'Pride and Prejudice', 'Jane Austen', 'A romantic novel following the emotional development of Elizabeth Bennet as she deals with issues of manners, upbringing, and marriage.', 'assets/images/covers/67db072bd00e6.jpg', 'Romance', 1813, 'assets/books/67db072bd06b8.pdf', 'pdf', 0, '2025-03-19 18:00:31', '2025-03-19 18:04:27'),
(18, 'The Divine Comedy', 'Dante Alighieri', 'An epic poem that describes Dante\'s journey through Hell, Purgatory, and Paradise, guided by Virgil and later by Beatrice.', 'assets/images/covers/67db075214bae.jpg', 'Thriller', 1888, 'assets/books/67db075214fed.pdf', 'pdf', 0, '2025-03-19 18:00:31', '2025-03-19 18:05:06'),
(19, 'The Great Gatsby', 'F. Scott Fitzgerald', 'A story of the mysteriously wealthy Jay Gatsby and his obsessive love for the beautiful Daisy Buchanan, set against the backdrop of the Roaring Twenties.', 'assets/images/covers/67db076babb22.jpg', 'Fiction', 1925, 'assets/books/67db076bac05c.pdf', 'pdf', 0, '2025-03-19 18:00:31', '2025-03-19 18:05:31'),
(20, 'The Hobbit', 'J.R.R. Tolkien', 'A fantasy novel about the adventures of Bilbo Baggins, who embarks on a quest to help a group of dwarves reclaim their mountain home from a dragon.', 'assets/images/covers/67db077fe46f2.jpg', 'Fantasy', 1937, 'assets/books/67db077fe5c7b.pdf', 'pdf', 0, '2025-03-19 18:00:31', '2025-03-19 18:05:51'),
(21, 'The Odyssey', 'Homer', 'An ancient Greek epic poem that follows Odysseus\'s journey home after the fall of Troy, and the adventures that ensue.', 'assets/images/covers/67db07986c241.jpg', 'Fantasy', 1998, 'assets/books/67db07986c67d.pdf', 'pdf', 0, '2025-03-19 18:00:31', '2025-03-19 18:06:16'),
(22, 'To Kill a Mockingbird', 'Harper Lee', 'A story about racial injustice and the loss of innocence in the American South, told through the eyes of young Scout Finch.', 'assets/images/covers/67db07a6903a9.jpg', 'Fiction', 1960, 'assets/books/67db07a6907cc.pdf', 'pdf', 0, '2025-03-19 18:00:31', '2025-03-19 18:06:30');

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `attempt_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NOT NULL DEFAULT (current_timestamp() + interval 1 hour),
  `used` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ratings`
--

CREATE TABLE `ratings` (
  `rating_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `rating` decimal(2,1) NOT NULL CHECK (`rating` >= 0 and `rating` <= 5),
  `review` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reading_lists`
--

CREATE TABLE `reading_lists` (
  `list_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `list_type` enum('want-to-read','currently-reading','completed') NOT NULL,
  `progress` int(11) DEFAULT 0,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reading_lists`
--

INSERT INTO `reading_lists` (`list_id`, `user_id`, `book_id`, `list_type`, `progress`, `added_at`, `created_at`, `updated_at`, `last_updated`) VALUES
(11, 1, 22, 'want-to-read', 0, '2025-03-19 18:07:00', '2025-03-19 18:07:00', '2025-03-19 18:07:00', '2025-03-19 18:07:00'),
(12, 1, 21, 'currently-reading', 2, '2025-03-19 18:07:03', '2025-03-19 18:07:03', '2025-03-19 18:07:16', '2025-03-19 18:07:16'),
(13, 1, 19, 'completed', 0, '2025-03-19 18:08:43', '2025-03-19 18:08:43', '2025-03-19 18:08:43', '2025-03-19 18:08:43');

-- --------------------------------------------------------

--
-- Table structure for table `reading_progress`
--

CREATE TABLE `reading_progress` (
  `progress_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `book_id` int(11) NOT NULL,
  `current_page` int(11) DEFAULT 0,
  `is_completed` tinyint(1) DEFAULT 0,
  `last_read_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `is_admin` tinyint(1) DEFAULT 0,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expires` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password_hash`, `full_name`, `created_at`, `updated_at`, `last_login`, `is_active`, `is_admin`, `reset_token`, `reset_token_expires`) VALUES
(1, 'admin', 'admin@bookhub.com', '$2y$10$aJYGGcfwj7v8A0VHd1heu.OFKDYaH1UlpOnRWHMPEhLjTwloES2S6', 'Admin User', '2025-03-19 15:02:58', '2025-03-19 17:45:37', '2025-03-19 17:45:37', 1, 1, NULL, NULL),
(2, 'user1', 'user1@bookhub.com', '$2y$10$vgpned7JZnyBaOBLCtqgYe.TsmfGL2Cm8BOL5O5k9odzr4dIlOu26', 'John Doe', '2025-03-19 15:02:58', '2025-03-19 17:44:14', '2025-03-19 17:44:14', 1, 0, NULL, NULL),
(3, 'user2', 'user2@bookhub.com', '$2y$10$D5XkqtXlng4J7jmcCu07UeMsvO4tR4pIqdxRc/s9uILgu5oqgdzOC', 'Jane Smith', '2025-03-19 15:02:58', '2025-03-19 15:02:58', NULL, 1, 0, NULL, NULL),
(4, 'user3', 'user3@gmail.com', '$2y$10$eiQyMqX6CnsWTTRsL4oLbuh0xjkQDENU.56z2Rr70.9Z91T5L4OHG', 'Ryan', '2025-03-19 18:16:59', '2025-03-19 18:16:59', NULL, 1, 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_activity_log`
--

CREATE TABLE `user_activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `activity_type` varchar(50) NOT NULL,
  `activity_details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_popular_books`
-- (See below for the actual view)
--
CREATE TABLE `vw_popular_books` (
`book_id` int(11)
,`title` varchar(255)
,`author` varchar(255)
,`genre` varchar(50)
,`total_readers` bigint(21)
,`completed_readers` bigint(21)
,`avg_rating` decimal(6,5)
,`total_ratings` bigint(21)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_user_reading_stats`
-- (See below for the actual view)
--
CREATE TABLE `vw_user_reading_stats` (
`user_id` int(11)
,`username` varchar(50)
,`full_name` varchar(100)
,`total_books` bigint(21)
,`books_completed` decimal(22,0)
,`books_reading` decimal(22,0)
,`books_wanted` decimal(22,0)
,`last_activity` timestamp
);

-- --------------------------------------------------------

--
-- Structure for view `vw_popular_books`
--
DROP TABLE IF EXISTS `vw_popular_books`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_popular_books`  AS SELECT `b`.`book_id` AS `book_id`, `b`.`title` AS `title`, `b`.`author` AS `author`, `b`.`genre` AS `genre`, count(distinct `rl`.`user_id`) AS `total_readers`, count(distinct case when `rl`.`list_type` = 'completed' then `rl`.`user_id` end) AS `completed_readers`, avg(case when `r`.`rating` is not null then `r`.`rating` else NULL end) AS `avg_rating`, count(distinct `r`.`rating_id`) AS `total_ratings` FROM ((`books` `b` left join `reading_lists` `rl` on(`b`.`book_id` = `rl`.`book_id`)) left join `ratings` `r` on(`b`.`book_id` = `r`.`book_id`)) GROUP BY `b`.`book_id`, `b`.`title`, `b`.`author`, `b`.`genre` ORDER BY count(distinct `rl`.`user_id`) DESC, avg(case when `r`.`rating` is not null then `r`.`rating` else NULL end) DESC ;

-- --------------------------------------------------------

--
-- Structure for view `vw_user_reading_stats`
--
DROP TABLE IF EXISTS `vw_user_reading_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_user_reading_stats`  AS SELECT `u`.`user_id` AS `user_id`, `u`.`username` AS `username`, `u`.`full_name` AS `full_name`, count(distinct `rl`.`book_id`) AS `total_books`, sum(case when `rl`.`list_type` = 'completed' then 1 else 0 end) AS `books_completed`, sum(case when `rl`.`list_type` = 'currently-reading' then 1 else 0 end) AS `books_reading`, sum(case when `rl`.`list_type` = 'want-to-read' then 1 else 0 end) AS `books_wanted`, max(`rl`.`last_updated`) AS `last_activity` FROM (`users` `u` left join `reading_lists` `rl` on(`u`.`user_id` = `rl`.`user_id`)) GROUP BY `u`.`user_id`, `u`.`username`, `u`.`full_name` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `books`
--
ALTER TABLE `books`
  ADD PRIMARY KEY (`book_id`),
  ADD KEY `idx_author` (`author`),
  ADD KEY `idx_genre` (`genre`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email_time` (`email`,`attempt_time`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_token` (`token`),
  ADD KEY `idx_user_token` (`user_id`,`token`,`expires_at`);

--
-- Indexes for table `ratings`
--
ALTER TABLE `ratings`
  ADD PRIMARY KEY (`rating_id`),
  ADD UNIQUE KEY `unique_user_book_rating` (`user_id`,`book_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indexes for table `reading_lists`
--
ALTER TABLE `reading_lists`
  ADD PRIMARY KEY (`list_id`),
  ADD UNIQUE KEY `unique_user_book_list` (`user_id`,`book_id`),
  ADD KEY `book_id` (`book_id`),
  ADD KEY `idx_user_status` (`user_id`,`list_type`),
  ADD KEY `idx_added_at` (`added_at`);

--
-- Indexes for table `reading_progress`
--
ALTER TABLE `reading_progress`
  ADD PRIMARY KEY (`progress_id`),
  ADD UNIQUE KEY `unique_user_book_progress` (`user_id`,`book_id`),
  ADD KEY `book_id` (`book_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_username` (`username`);

--
-- Indexes for table `user_activity_log`
--
ALTER TABLE `user_activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_activity` (`user_id`,`activity_type`,`created_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `books`
--
ALTER TABLE `books`
  MODIFY `book_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ratings`
--
ALTER TABLE `ratings`
  MODIFY `rating_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `reading_lists`
--
ALTER TABLE `reading_lists`
  MODIFY `list_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `reading_progress`
--
ALTER TABLE `reading_progress`
  MODIFY `progress_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `user_activity_log`
--
ALTER TABLE `user_activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD CONSTRAINT `password_reset_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `ratings`
--
ALTER TABLE `ratings`
  ADD CONSTRAINT `ratings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ratings_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`) ON DELETE CASCADE;

--
-- Constraints for table `reading_lists`
--
ALTER TABLE `reading_lists`
  ADD CONSTRAINT `reading_lists_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reading_lists_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`) ON DELETE CASCADE;

--
-- Constraints for table `reading_progress`
--
ALTER TABLE `reading_progress`
  ADD CONSTRAINT `reading_progress_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reading_progress_ibfk_2` FOREIGN KEY (`book_id`) REFERENCES `books` (`book_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_activity_log`
--
ALTER TABLE `user_activity_log`
  ADD CONSTRAINT `user_activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
