DELIMITER //

-- Function to calculate reading progress percentage
CREATE OR REPLACE FUNCTION fn_calculate_reading_progress(p_current_page INT, p_total_pages INT)
RETURNS DECIMAL(5,2)
DETERMINISTIC
BEGIN
    IF p_total_pages <= 0 THEN
        RETURN 0;
    END IF;
    RETURN (p_current_page * 100.0 / p_total_pages);
END //

-- Function to get user's total books being read
CREATE OR REPLACE FUNCTION fn_get_user_reading_count(p_user_id INT)
RETURNS INT
DETERMINISTIC
BEGIN
    DECLARE total INT;
    SELECT COUNT(*) INTO total
    FROM reading_progress
    WHERE user_id = p_user_id;
    RETURN total;
END //

-- Function to get average rating by genre
CREATE OR REPLACE FUNCTION fn_get_genre_rating(p_genre VARCHAR(100))
RETURNS DECIMAL(3,2)
DETERMINISTIC
BEGIN
    DECLARE avg_rating DECIMAL(3,2);
    SELECT COALESCE(AVG(r.rating), 0) INTO avg_rating
    FROM books b
    LEFT JOIN ratings r ON b.book_id = r.book_id
    WHERE b.genre = p_genre;
    RETURN avg_rating;
END //

-- Function to check if user has rated a book
CREATE OR REPLACE FUNCTION fn_has_user_rated_book(p_user_id INT, p_book_id INT)
RETURNS BOOLEAN
DETERMINISTIC
BEGIN
    DECLARE has_rated BOOLEAN;
    SELECT EXISTS(
        SELECT 1 
        FROM ratings 
        WHERE user_id = p_user_id 
        AND book_id = p_book_id
    ) INTO has_rated;
    RETURN has_rated;
END //

-- Function to get user's favorite genre (genre with most books read)
CREATE OR REPLACE FUNCTION fn_get_user_favorite_genre(p_user_id INT)
RETURNS VARCHAR(100)
DETERMINISTIC
BEGIN
    DECLARE fav_genre VARCHAR(100);
    SELECT b.genre INTO fav_genre
    FROM reading_progress rp
    JOIN books b ON rp.book_id = b.book_id
    WHERE rp.user_id = p_user_id
    GROUP BY b.genre
    ORDER BY COUNT(*) DESC
    LIMIT 1;
    RETURN COALESCE(fav_genre, 'None');
END //

-- Stored procedure to get detailed book information
CREATE OR REPLACE PROCEDURE sp_get_book_details(IN p_book_id INT)
BEGIN
    SELECT 
        b.book_id,
        b.title,
        b.author,
        b.cover_image,
        b.description,
        b.genre,
        COALESCE(AVG(r.rating), 0) as average_rating,
        COUNT(DISTINCT r.rating_id) as total_ratings,
        (SELECT COUNT(*) FROM books WHERE author = b.author) as author_books_count,
        fn_get_genre_rating(b.genre) as genre_average_rating
    FROM books b
    LEFT JOIN ratings r ON b.book_id = r.book_id
    WHERE b.book_id = p_book_id
    GROUP BY b.book_id;
END //

DELIMITER ;
