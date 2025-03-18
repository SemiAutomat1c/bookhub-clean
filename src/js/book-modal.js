// Book data cache
let bookData = {};

// Helper function to parse book data from text format
function parseBookData(text) {
    return text.split('\n').map(line => {
        const [
            book_id,
            title,
            author,
            cover_image,
            description,
            genre,
            file_path,
            file_type,
            average_rating,
            total_ratings
        ] = line.split('|');

        return {
            id: book_id,
            title,
            author,
            cover: cover_image,
            description,
            genre,
            file_path,
            file_type,
            rating: parseFloat(average_rating),
            ratingCount: parseInt(total_ratings),
            popularity: parseFloat(average_rating) * parseInt(total_ratings)
        };
    });
}

// Fetch book data from API
async function loadBookData() {
    try {
        const response = await fetch('get_books.php');
        const text = await response.text();
        const books = parseBookData(text);
        
        // Index books by id
        bookData = books.reduce((acc, book) => {
            acc[book.id] = book;
            return acc;
        }, {});
    } catch (error) {
        console.error('Error loading books:', error);
    }
}

// Function to show book details in modal
function showBookModal(bookId) {
    const book = bookData[bookId];
    if (!book) {
        console.error('Book not found:', bookId);
        return;
    }

    const modal = document.createElement('div');
    modal.className = 'book-modal';
    modal.innerHTML = `
        <div class="modal-content">
            <span class="close">&times;</span>
            <div class="book-details">
                <img src="${book.cover || 'assets/images/default-cover.jpg'}" 
                     alt="${book.title}" 
                     class="book-cover">
                <div class="book-info">
                    <h2>${book.title}</h2>
                    <p class="author">By ${book.author}</p>
                    <div class="rating">
                        ${getStarRating(book.rating)}
                        <span class="rating-count">(${book.ratingCount} ratings)</span>
                    </div>
                    <p class="genre">${book.genre}</p>
                    <p class="description">${book.description}</p>
                    <div class="actions">
                        <button class="read-button" onclick="startReading('${book.id}')">
                            Start Reading
                        </button>
                        <button class="add-list-button" onclick="addToList('${book.id}')">
                            Add to List
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    // Close modal functionality
    const closeBtn = modal.querySelector('.close');
    closeBtn.onclick = () => modal.remove();
    modal.onclick = (e) => {
        if (e.target === modal) modal.remove();
    };
}

// Helper function to generate star rating HTML
function getStarRating(rating) {
    const fullStars = Math.floor(rating);
    const hasHalfStar = rating % 1 >= 0.5;
    const emptyStars = 5 - fullStars - (hasHalfStar ? 1 : 0);
    
    return `
        ${'<i class="fas fa-star"></i>'.repeat(fullStars)}
        ${hasHalfStar ? '<i class="fas fa-star-half-alt"></i>' : ''}
        ${'<i class="far fa-star"></i>'.repeat(emptyStars)}
    `;
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', loadBookData);
