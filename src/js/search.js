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

// Store current books data
let currentBooks = [];

// Function to perform search
async function performSearch(query = null) {
    // Get query from parameter or input
    const searchQuery = query || searchInput.value.trim();
    searchInput.value = searchQuery; // Update input if query came from parameter
    
    try {
        const response = await fetch('../get_books.php');
        const text = await response.text();
        const books = parseBookData(text);
        currentBooks = books; // Store books for filtering
        
        // Get current filter values
        const filters = {
            genre: document.getElementById('genre').value,
            rating: parseFloat(document.getElementById('rating').value),
            availability: document.getElementById('availability').value
        };
        
        // Apply filters and search
        const filteredBooks = filterBooks(books, searchQuery, filters);
        
        // Sort results
        const sortBy = document.getElementById('sort').value;
        const sortedBooks = sortBooks(filteredBooks, sortBy);
        
        // Display results
        displayResults(sortedBooks);

        // Update URL without reloading
        const newUrl = new URL(window.location.href);
        newUrl.searchParams.set('q', searchQuery);
        window.history.pushState({}, '', newUrl);
    } catch (error) {
        console.error('Error fetching books:', error);
        document.getElementById('results-grid').innerHTML = `
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <p>Error loading books. Please try again.</p>
            </div>
        `;
    }
}

// Function to filter books based on search query and filters
function filterBooks(books, query, filters) {
    return books.filter(book => {
        // Search query filter
        const searchMatch = !query || [
            book.title,
            book.author,
            book.genre,
            book.description
        ].some(field => 
            field && field.toLowerCase().includes(query.toLowerCase())
        );

        // Genre filter
        const genreMatch = !filters.genre || book.genre === filters.genre;

        // Rating filter
        const ratingMatch = !filters.rating || parseFloat(book.rating) >= filters.rating;

        // Availability filter
        const availabilityMatch = !filters.availability || 
            (filters.availability === 'available' && book.file_path) ||
            (filters.availability === 'unavailable' && !book.file_path);

        return searchMatch && genreMatch && ratingMatch && availabilityMatch;
    });
}

// Function to sort books
function sortBooks(books, sortBy) {
    const sortFunctions = {
        'relevance': (a, b) => b.popularity - a.popularity,
        'title': (a, b) => a.title.localeCompare(b.title),
        'author': (a, b) => a.author.localeCompare(b.author),
        'rating': (a, b) => b.rating - a.rating
    };

    return [...books].sort(sortFunctions[sortBy] || sortFunctions['relevance']);
}

// Function to display search results
function displayResults(books) {
    const resultsGrid = document.getElementById('results-grid');
    
    if (books.length === 0) {
        resultsGrid.innerHTML = `
            <div class="no-results">
                <i class="fas fa-search"></i>
                <p>No books found matching your criteria.</p>
            </div>
        `;
        return;
    }

    resultsGrid.innerHTML = books.map(book => `
        <div class="book-card" data-book-id="${book.id}">
            <img src="${book.cover || '../assets/images/default-cover.jpg'}" 
                 alt="${book.title}" 
                 class="book-cover"
                 onerror="this.src='../assets/images/default-cover.jpg'">
            <div class="book-info">
                <h3 class="book-title">${book.title}</h3>
                <p class="book-author">${book.author}</p>
                <div class="book-rating">
                    ${getStarRating(book.rating)}
                    <span class="rating-count">(${book.ratingCount})</span>
                </div>
                <p class="book-genre">${book.genre}</p>
                <button class="view-details" onclick="showBookDetails('${book.id}')">
                    View Details
                </button>
            </div>
        </div>
    `).join('');
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

// Initialize search functionality
document.addEventListener('DOMContentLoaded', () => {
    // Get search query from URL if present
    const urlParams = new URLSearchParams(window.location.search);
    const query = urlParams.get('q');
    
    if (query) {
        document.getElementById('search-input').value = query;
        performSearch(query);
    }

    // Set up event listeners
    document.getElementById('search-input').addEventListener('input', debounce(performSearch, 300));
    document.getElementById('genre').addEventListener('change', () => performSearch());
    document.getElementById('rating').addEventListener('change', () => performSearch());
    document.getElementById('availability').addEventListener('change', () => performSearch());
    document.getElementById('sort').addEventListener('change', () => performSearch());
});

// Debounce helper function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
};
