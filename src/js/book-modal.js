// Book data cache
let bookData = {};

// Helper function to parse book data from text format
function parseBookData(text) {
    if (!text || text.startsWith('ERROR')) {
        console.error('Error in book data:', text);
        return [];
    }

    return text.split('\n')
        .filter(line => line.trim() && !line.startsWith('SUCCESS'))
        .map(line => {
            const [
                book_id,
                title,
                author,
                cover_image,
                description,
                genre,
                publication_year,
                file_path,
                file_type
            ] = line.split('|');

            return {
                book_id: parseInt(book_id),
                title,
                author,
                cover: cover_image,
                description,
                genre,
                publication_year,
                file_path,
                file_type
            };
        });
}

// Function to check if a book is in the reading list
async function checkBookInReadingList(bookId) {
    try {
        console.log('Checking if book is in reading list:', bookId); // Debug log
        const response = await fetch('/bookhub-1/api/reading-list/get.php', {
            credentials: 'include'
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const text = await response.text();
        console.log('Reading list response:', text); // Debug log

        if (text.startsWith('ERROR')) {
            throw new Error(text.substring(6));
        }

        // Parse the response
        const lines = text.split('\n');
        if (lines[0] !== 'SUCCESS') {
            throw new Error('Invalid response format');
        }

        // Check each section for the book ID
        let currentSection = '';
        for (let i = 1; i < lines.length; i++) {
            const line = lines[i].trim();
            if (!line) continue;

            if (line === 'WANT_TO_READ' || line === 'CURRENTLY_READING' || line === 'COMPLETED') {
                currentSection = line;
                continue;
            }

            if (line === 'NO_BOOKS') {
                continue;
            }

            // Parse book entry
            const [id] = line.split('|');
            if (id === bookId.toString()) {
                console.log('Book found in section:', currentSection); // Debug log
                return true;
            }
        }

        console.log('Book not found in reading list'); // Debug log
        return false;
    } catch (error) {
        console.error('Error checking reading list:', error);
        return false;
    }
}

// Fetch book data from API
async function loadBookData() {
    try {
        console.log('Loading book data...'); // Debug log
        const response = await fetch('/bookhub-1/api/books.php', {
            credentials: 'include'
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const text = await response.text();
        console.log('Book data response:', text); // Debug log
        
        const books = parseBookData(text);
        console.log('Parsed books:', books); // Debug log
        
        // Index books by id
        bookData = books.reduce((acc, book) => {
            acc[book.book_id] = book;
            return acc;
        }, {});
        
        console.log('Book data indexed:', bookData); // Debug log
    } catch (error) {
        console.error('Error loading books:', error);
        bookData = {}; // Reset on error
    }
}

// Function to show book details in modal
async function showBookModal(bookId) {
    console.log('Opening modal for book ID:', bookId);
    
    if (Object.keys(bookData).length === 0) {
        await loadBookData();
    }
    
    const book = bookData[bookId];
    if (!book) {
        console.error('Book not found:', bookId);
        return;
    }

    const isInReadingList = await checkBookInReadingList(bookId);
    const coverPath = book.cover ? 
        book.cover.replace(/^\/+/, '') : 
        'assets/images/default-cover.jpg';

    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.style.display = 'block';
    modal.innerHTML = `
        <div class="modal-content">
            <span class="close">&times;</span>
            <div class="book-details">
                <div class="book-cover">
                    <img src="../${coverPath}" 
                         alt="${book.title}"
                         onerror="this.src='../assets/images/default-cover.jpg'">
                </div>
                <div class="book-info">
                    <h2>${book.title}</h2>
                    <p class="author">By ${book.author}</p>
                    <div class="book-description">
                        <h3>Description</h3>
                        <p>${book.description || 'No description available.'}</p>
                    </div>
                    <div class="modal-actions">
                        ${isInReadingList ? `
                            <p class="already-added">This book is already in your reading list!</p>
                        ` : `
                            <button class="primary-btn read-btn" onclick="startReading(${bookId})">
                                <i class="fas fa-book-reader"></i>
                                Read Now
                            </button>
                            <button class="secondary-btn add-to-list-btn" onclick="addToReadingList(${bookId})">
                                <i class="fas fa-plus"></i>
                                Add to List
                            </button>
                        `}
                    </div>
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.remove();
        }
    });

    const closeBtn = modal.querySelector('.close');
    closeBtn.addEventListener('click', () => {
        modal.remove();
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            modal.remove();
        }
    });
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

// Function to start reading a book
function startReading(bookId) {
    if (!bookData[bookId]) {
        console.error('Book not found:', bookId);
        return;
    }
    
    // Redirect to reader page with book ID
    window.location.href = `/bookhub-1/views/reader.html?book_id=${bookId}`;
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', loadBookData);

// Make functions globally available
window.showBookModal = showBookModal;
window.startReading = startReading;
window.addToReadingList = addToReadingList;
