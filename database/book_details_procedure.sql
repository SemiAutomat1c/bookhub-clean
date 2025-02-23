DELIMITER //

CREATE OR REPLACE PROCEDURE sp_get_book_details(IN p_book_id INT)
BEGIN
    -- Get main book details with ratings
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
        (SELECT fn_get_genre_rating(b.genre)) as genre_average_rating
    FROM books b
    LEFT JOIN ratings r ON b.book_id = r.book_id
    WHERE b.book_id = p_book_id
    GROUP BY b.book_id;
END //

DELIMITER ;
