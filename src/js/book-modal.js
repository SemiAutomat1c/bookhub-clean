// Book data cache
let bookData = {};

// Fetch book data from API
async function loadBookData() {
    try {
        const response = await fetch('get_books.php');
        const result = await response.json();
        
        if (result.success) {
            // Combine all book sections and index by id
            const allBooks = [
                ...(result.data.trending || []),
                ...(result.data.newReleases || []),
                ...(result.data.adaptations || [])
            ];
            
            bookData = allBooks.reduce((acc, book) => {
                acc[book.id] = book;
                return acc;
            }, {});
        }
    } catch (error) {
        console.error('Error loading books:', error);
    }
}

// Initialize book modal functionality
function initializeBookModal() {
    // Make all book cards clickable
    document.querySelectorAll('.book-card').forEach(card => {
        card.style.cursor = 'pointer';
        card.addEventListener('click', () => {
            const bookId = card.dataset.bookId;
            const book = bookData[bookId];
            
            if (!book) {
                console.error('Book not found:', bookId);
                return;
            }

            // Populate modal
            const modal = document.getElementById('bookModal');
            modal.querySelector('.modal-cover').src = book.coverImage || 'images/default-cover.jpg';
            modal.querySelector('h2').textContent = book.title;
            modal.querySelector('.author').textContent = book.author;
            modal.querySelector('.description').textContent = book.description;
            
            // Set details
            const detailValues = modal.querySelectorAll('.detail-value');
            detailValues[0].textContent = book.genre;
            detailValues[1].textContent = book.rating ? book.rating.toFixed(1) + '/5' : 'Not rated';
            detailValues[2].textContent = book.publishedYear;
            detailValues[3].textContent = book.pages || 'Unknown';

            // Show modal
            modal.style.display = 'flex';
        });
    });

    // Close modal when clicking the close button or outside
    const modal = document.getElementById('bookModal');
    const closeBtn = modal.querySelector('.modal-close');
    const overlay = modal.querySelector('.modal-overlay');

    closeBtn.addEventListener('click', () => {
        modal.style.display = 'none';
    });

    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) {
            modal.style.display = 'none';
        }
    });
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    loadBookData().then(() => {
        initializeBookModal();
    });
});
