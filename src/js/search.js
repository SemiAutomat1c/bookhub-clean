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
let isLoading = false;

// Function to perform search with debounce
const debounceSearch = debounce(async (query) => {
    const searchInput = document.getElementById('searchInput');
    const searchGrid = document.getElementById('search-results-grid');
    
    if (!searchInput || !searchGrid) return;

    // Show loading state
    showLoading(true);

    try {
        const response = await fetch(`../api/books/list_books.php?search=${encodeURIComponent(query)}`, {
            credentials: 'include'
        });

        if (!response.ok) throw new Error('Search failed');

        const text = await response.text();
        if (text.startsWith('ERROR')) throw new Error(text.substring(6));

        const books = text.split('\n')
            .filter(line => line.trim() && !line.startsWith('SUCCESS'))
            .map(line => {
                const [id, title, author, description, genre, year, cover, file_path, file_type] = line.split('|');
                return { 
                    book_id: parseInt(id), 
                    title, 
                    author, 
                    description, 
                    genre, 
                    publication_year: year, 
                    cover_image: cover, 
                    file_path,
                    file_type
                };
            });

        currentBooks = books;
        applyFilters(); // Apply filters to the new results
    } catch (error) {
        console.error('Search error:', error);
        showError('Failed to perform search. Please try again.');
    } finally {
        showLoading(false);
    }
}, 300);

// Function to display search results
function displaySearchResults(books) {
    const searchGrid = document.getElementById('search-results-grid');
    const resultsCount = document.getElementById('results-count');
    
    if (!searchGrid) return;

    // Update results count
    if (resultsCount) {
        resultsCount.textContent = books.length;
    }

    if (books.length === 0) {
        searchGrid.innerHTML = `
            <div class="no-results">
                <i class="fas fa-search"></i>
                <p>No books found matching your criteria.</p>
            </div>
        `;
        return;
    }

    searchGrid.innerHTML = books.map(book => `
        <div class="book-card" onclick="showBookModal(${book.book_id})">
            <div class="book-cover">
                <img src="${book.cover_image ? '../' + book.cover_image.replace(/^\/+/, '') : '../assets/images/default-cover.jpg'}" 
                     alt="${book.title}"
                     onerror="this.src='../assets/images/default-cover.jpg'">
            </div>
            <div class="book-info">
                <h3 class="book-title">${book.title}</h3>
                <p class="book-author">by ${book.author}</p>
                ${book.genre ? `<span class="book-genre">${book.genre}</span>` : ''}
                ${book.publication_year ? `<span class="book-year">${book.publication_year}</span>` : ''}
            </div>
        </div>
    `).join('');
}

// Function to show loading state
function showLoading(show) {
    const searchGrid = document.getElementById('search-results-grid');
    if (!searchGrid) return;

    if (show) {
        searchGrid.innerHTML = `
            <div class="loading-results">
                <div class="loading-spinner"></div>
                <p class="loading-text">Searching books...</p>
            </div>
        `;
    }
}

// Function to show error message
function showError(message) {
    const searchGrid = document.getElementById('search-results-grid');
    if (!searchGrid) return;

    searchGrid.innerHTML = `
        <div class="no-results">
            <i class="fas fa-exclamation-circle"></i>
            <p>${message}</p>
        </div>
    `;
}

// Function to apply filters
async function applyFilters() {
    const genre = document.getElementById('genre')?.value || 'all';
    const language = document.getElementById('language')?.value || 'all';
    const year = document.getElementById('year')?.value || 'all';
    const rating = document.getElementById('rating')?.value || '0';
    const availability = document.getElementById('availability')?.value || 'all';
    const sort = document.getElementById('sort-select')?.value || 'relevance';

    let filteredBooks = [...currentBooks];

    // Apply genre filter
    if (genre !== 'all') {
        filteredBooks = filteredBooks.filter(book => book.genre.toLowerCase() === genre.toLowerCase());
    }

    // Apply language filter
    if (language !== 'all') {
        filteredBooks = filteredBooks.filter(book => book.language?.toLowerCase() === language.toLowerCase());
    }

    // Apply year filter
    if (year !== 'all') {
        filteredBooks = filteredBooks.filter(book => book.publication_year === year);
    }

    // Apply rating filter
    if (rating !== '0') {
        filteredBooks = filteredBooks.filter(book => (book.rating || 0) >= parseInt(rating));
    }

    // Apply availability filter
    if (availability !== 'all') {
        filteredBooks = filteredBooks.filter(book => {
            if (availability === 'ebook') return book.file_type === 'pdf' || book.file_type === 'epub';
            if (availability === 'audiobook') return book.file_type === 'mp3';
            return true;
        });
    }

    // Apply sorting
    switch (sort) {
        case 'rating':
            filteredBooks.sort((a, b) => (b.rating || 0) - (a.rating || 0));
            break;
        case 'date':
            filteredBooks.sort((a, b) => parseInt(b.publication_year) - parseInt(a.publication_year));
            break;
        case 'title':
            filteredBooks.sort((a, b) => a.title.localeCompare(b.title));
            break;
    }

    displaySearchResults(filteredBooks);
}

// Function to reset filters
function resetFilters() {
    // Reset all filter selects to their default values
    document.getElementById('genre').value = 'all';
    document.getElementById('language').value = 'all';
    document.getElementById('year').value = 'all';
    document.getElementById('rating').value = '0';
    document.getElementById('availability').value = 'all';
    document.getElementById('sort-select').value = 'relevance';

    // Re-apply filters (which will now show all books)
    applyFilters();
}

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
}

// Initialize search functionality
document.addEventListener('DOMContentLoaded', () => {
    // Set up search input handler
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => debounceSearch(e.target.value.trim()));
    }

    // Set up filter button handlers
    const applyFiltersBtn = document.getElementById('apply-filters');
    if (applyFiltersBtn) {
        applyFiltersBtn.addEventListener('click', applyFilters);
    }

    const resetFiltersBtn = document.getElementById('reset-filters');
    if (resetFiltersBtn) {
        resetFiltersBtn.addEventListener('click', resetFilters);
    }

    // Set up filter change handlers
    ['genre', 'language', 'year', 'rating', 'availability', 'sort-select'].forEach(filterId => {
        const filterElement = document.getElementById(filterId);
        if (filterElement) {
            filterElement.addEventListener('change', applyFilters);
        }
    });

    // Load initial books
    debounceSearch('');

    // Check for search query in URL
    const urlParams = new URLSearchParams(window.location.search);
    const queryParam = urlParams.get('q');
    if (queryParam) {
        searchInput.value = queryParam;
        debounceSearch(queryParam);
    }
});
