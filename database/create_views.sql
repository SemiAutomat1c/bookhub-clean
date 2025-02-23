-- Create a view for books with complete details
CREATE OR REPLACE VIEW vw_books_details AS
SELECT 
    b.book_id,
    b.title,
    b.author,
    b.cover_image,
    b.description,
    b.genre,
    COALESCE(AVG(r.rating), 0) as average_rating,
    COUNT(r.rating_id) as total_ratings
FROM books b
LEFT JOIN ratings r ON b.book_id = r.book_id
GROUP BY b.book_id;

-- Create a view for popular books (most rated)
CREATE OR REPLACE VIEW vw_popular_books AS
SELECT 
    book_id,
    title,
    author,
    cover_image,
    average_rating,
    total_ratings
FROM vw_books_details
WHERE total_ratings > 0
ORDER BY total_ratings DESC, average_rating DESC;

-- Create a view for user reading progress
CREATE OR REPLACE VIEW vw_user_reading_progress AS
SELECT 
    u.user_id,
    u.username,
    b.book_id,
    b.title,
    rp.current_page,
    rp.last_read_date
FROM users u
JOIN reading_progress rp ON u.user_id = rp.user_id
JOIN books b ON rp.book_id = b.book_id;
