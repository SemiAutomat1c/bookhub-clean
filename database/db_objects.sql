-- MySQL Views, Functions, and Stored Procedures for BookHub

-- Views
CREATE OR REPLACE VIEW vw_book_ratings AS
SELECT 
    b.book_id,
    b.title,
    b.author,
    COUNT(r.rating_id) as total_ratings,
    ROUND(AVG(r.rating), 2) as average_rating
FROM books b
LEFT JOIN ratings r ON b.book_id = r.book_id
GROUP BY b.book_id, b.title, b.author;

CREATE OR REPLACE VIEW vw_user_reading_stats AS
SELECT 
    u.user_id,
    u.username,
    COUNT(DISTINCT r.book_id) as books_rated,
    ROUND(AVG(r.rating), 2) as average_rating_given
FROM users u
LEFT JOIN ratings r ON u.user_id = r.user_id
GROUP BY u.user_id, u.username;

CREATE OR REPLACE VIEW vw_popular_books AS
SELECT 
    b.book_id,
    b.title,
    b.author,
    b.genre,
    COUNT(r.rating_id) as rating_count,
    ROUND(AVG(r.rating), 2) as average_rating
FROM books b
LEFT JOIN ratings r ON b.book_id = r.book_id
GROUP BY b.book_id, b.title, b.author, b.genre
HAVING rating_count > 0
ORDER BY average_rating DESC, rating_count DESC;

-- Functions
DELIMITER //

CREATE FUNCTION fn_calculate_user_reading_time(p_user_id INT) 
RETURNS INT
DETERMINISTIC
BEGIN
    DECLARE total_books INT;
    -- Assuming average reading time of 3 hours per book
    SELECT COUNT(*) * 3 INTO total_books
    FROM ratings
    WHERE user_id = p_user_id;
    RETURN total_books;
END //

CREATE FUNCTION fn_get_book_rating(p_book_id INT)
RETURNS DECIMAL(3,2)
DETERMINISTIC
BEGIN
    DECLARE avg_rating DECIMAL(3,2);
    SELECT ROUND(AVG(rating), 2) INTO avg_rating
    FROM ratings
    WHERE book_id = p_book_id;
    RETURN COALESCE(avg_rating, 0.00);
END //

-- Stored Procedures
CREATE PROCEDURE sp_add_book_rating(
    IN p_user_id INT,
    IN p_book_id INT,
    IN p_rating INT,
    IN p_review TEXT
)
BEGIN
    DECLARE existing_rating_id INT;
    
    -- Check if rating already exists
    SELECT rating_id INTO existing_rating_id
    FROM ratings
    WHERE user_id = p_user_id AND book_id = p_book_id;
    
    IF existing_rating_id IS NOT NULL THEN
        -- Update existing rating
        UPDATE ratings
        SET rating = p_rating,
            review = p_review
        WHERE rating_id = existing_rating_id;
    ELSE
        -- Insert new rating
        INSERT INTO ratings (user_id, book_id, rating, review)
        VALUES (p_user_id, p_book_id, p_rating, p_review);
    END IF;
END //

CREATE PROCEDURE sp_update_book_details(
    IN p_book_id INT,
    IN p_title VARCHAR(255),
    IN p_author VARCHAR(255),
    IN p_description TEXT,
    IN p_genre VARCHAR(50)
)
BEGIN
    UPDATE books
    SET title = COALESCE(p_title, title),
        author = COALESCE(p_author, author),
        description = COALESCE(p_description, description),
        genre = COALESCE(p_genre, genre)
    WHERE book_id = p_book_id;
END //

CREATE PROCEDURE sp_get_recommended_books(
    IN p_user_id INT,
    IN p_limit INT
)
BEGIN
    -- Get recommendations based on user's preferred genres
    SELECT DISTINCT b.*
    FROM books b
    INNER JOIN ratings r1 ON b.book_id = r1.book_id
    WHERE b.genre IN (
        SELECT DISTINCT b2.genre
        FROM books b2
        INNER JOIN ratings r2 ON b2.book_id = r2.book_id
        WHERE r2.user_id = p_user_id
        AND r2.rating >= 4
    )
    AND b.book_id NOT IN (
        SELECT book_id
        FROM ratings
        WHERE user_id = p_user_id
    )
    ORDER BY (
        SELECT AVG(r3.rating)
        FROM ratings r3
        WHERE r3.book_id = b.book_id
    ) DESC
    LIMIT p_limit;
END //

DELIMITER ; 