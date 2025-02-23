DELIMITER //

-- Function to get book rating
CREATE OR REPLACE FUNCTION fn_get_book_rating(p_book_id INT)
RETURNS DECIMAL(3,2)
DETERMINISTIC
BEGIN
    DECLARE avg_rating DECIMAL(3,2);
    
    SELECT COALESCE(AVG(rating), 0)
    INTO avg_rating
    FROM ratings
    WHERE book_id = p_book_id;
    
    RETURN avg_rating;
END;

-- Function to get total books by author
CREATE OR REPLACE FUNCTION fn_get_author_book_count(p_author VARCHAR(255))
RETURNS INT
DETERMINISTIC
BEGIN
    DECLARE total INT;
    
    SELECT COUNT(*)
    INTO total
    FROM books
    WHERE author = p_author;
    
    RETURN total;
END;

-- Function to check if user has started reading a book
CREATE OR REPLACE FUNCTION fn_has_started_reading(p_user_id INT, p_book_id INT)
RETURNS BOOLEAN
DETERMINISTIC
BEGIN
    DECLARE has_started BOOLEAN;
    
    SELECT EXISTS(
        SELECT 1 
        FROM reading_progress 
        WHERE user_id = p_user_id 
        AND book_id = p_book_id
    ) INTO has_started;
    
    RETURN has_started;
END;

DELIMITER ;
