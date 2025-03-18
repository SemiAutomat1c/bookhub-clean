// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    // Check authentication and update UI
    checkLoginState();

    // Load book data based on current path
    loadBooks();
});

function isUserLoggedIn() {
    const authToken = localStorage.getItem('authToken');
    return !!authToken;
}

function checkLoginState() {
    const isLoggedIn = isUserLoggedIn();
    
    // Update body class
    document.body.classList.toggle('logged-in', isLoggedIn);
    
    // Update protected features visibility
    const protectedFeatures = document.querySelectorAll('.protected-feature');
    const signedInOnly = document.querySelectorAll('.signed-in-only');
    const signedOutOnly = document.querySelectorAll('.signed-out-only');

    protectedFeatures.forEach(el => {
        el.style.display = isLoggedIn ? '' : 'none';
    });

    signedInOnly.forEach(el => {
        el.style.display = isLoggedIn ? '' : 'none';
    });

    signedOutOnly.forEach(el => {
        el.style.display = isLoggedIn ? 'none' : '';
    });

    // Check if we're on a protected page
    const protectedPages = ['/views/reading-list.html', '/views/profile.html'];
    const currentPath = window.location.pathname.toLowerCase();
    
    if (protectedPages.some(page => currentPath.endsWith(page.toLowerCase())) && !isLoggedIn) {
        window.location.href = 'sign-in.html?returnUrl=' + encodeURIComponent(window.location.pathname);
    }
}

async function loadBooks() {
    try {
        const response = await fetch('../api/books.php');
        if (!response.ok) throw new Error('Failed to load books');
        
        const text = await response.text();
        if (text.startsWith('ERROR:')) {
            throw new Error(text.substring(6));
        }
        
        if (text === 'NO_RESULTS') {
            displayNoBooks();
            return;
        }

        const books = text.split('\n').map(line => {
            const [
                id, title, author, cover_image, description, genre,
                publication_year, file_path, file_type, average_rating,
                total_ratings
            ] = line.split('|');

            return {
                id, title, author, cover_image, description, genre,
                publication_year, file_path, file_type,
                average_rating: parseFloat(average_rating),
                total_ratings: parseInt(total_ratings),
                popularity: parseFloat(average_rating) * parseInt(total_ratings)
            };
        });

        // Display books in different sections
        const trendingBooks = books
            .sort((a, b) => (b.popularity || 0) - (a.popularity || 0))
            .slice(0, 8);

        const currentYear = new Date().getFullYear();
        const newBooks = books
            .filter(book => {
                const publishYear = parseInt(book.publication_year || currentYear);
                return publishYear >= currentYear - 2;
            })
            .sort((a, b) => parseInt(b.publication_year || '0') - parseInt(a.publication_year || '0'))
            .slice(0, 8);

        // Display books in their respective sections
        displayBooksInSection('#trending-grid', trendingBooks, 'trending');
        displayBooksInSection('#new-books-grid', newBooks, 'new');
        
        // If we're on the search page, display all books
        const searchResults = document.querySelector('#search-results-grid');
        if (searchResults) {
            displayBooksInSection('#search-results-grid', books, 'search');
        }

    } catch (error) {
        console.error('Error loading books:', error);
        displayError(error.message);
    }
}

function displayBooksInSection(selector, books, displayType) {
    const container = document.querySelector(selector);
    if (!container) return;

    if (!books || books.length === 0) {
        container.innerHTML = `
            <div class="no-books-message">
                <i class="fas fa-books"></i>
                <p>No books available in this section.</p>
            </div>
        `;
        return;
    }

    container.innerHTML = books.map(book => BookComponent.createBookCard(book, displayType)).join('');
}

function displayNoBooks() {
    const containers = ['#trending-grid', '#new-books-grid', '#search-results-grid'];
    containers.forEach(selector => {
        const container = document.querySelector(selector);
        if (container) {
            container.innerHTML = `
                <div class="no-books-message">
                    <i class="fas fa-books"></i>
                    <p>No books available at the moment.</p>
                </div>
            `;
        }
    });
}

function displayError(message) {
    const containers = ['#trending-grid', '#new-books-grid', '#search-results-grid'];
    containers.forEach(selector => {
        const container = document.querySelector(selector);
        if (container) {
            container.innerHTML = `
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <p>${message}</p>
                </div>
            `;
        }
    });
}

// Search functionality
function setupSearch() {
    const searchInput = document.querySelector('#searchInput');
    const searchButton = document.querySelector('.search-button');
    
    if (searchInput && searchButton) {
        searchButton.addEventListener('click', () => performSearch());
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') performSearch();
        });
    }
}

async function performSearch() {
    const searchInput = document.querySelector('#searchInput');
    if (!searchInput) return;

    const query = searchInput.value.trim();
    if (!query) return;

    try {
        const response = await fetch(`../api/books.php?search=${encodeURIComponent(query)}`);
        if (!response.ok) throw new Error('Search failed');
        
        const text = await response.text();
        if (text.startsWith('ERROR:')) {
            throw new Error(text.substring(6));
        }
        
        if (text === 'NO_RESULTS') {
            displayNoSearchResults(query);
            return;
        }

        const books = text.split('\n').map(line => {
            const [
                id, title, author, cover_image, description, genre,
                publication_year, file_path, file_type, average_rating,
                total_ratings
            ] = line.split('|');

            return {
                id, title, author, cover_image, description, genre,
                publication_year, file_path, file_type,
                average_rating: parseFloat(average_rating),
                total_ratings: parseInt(total_ratings)
            };
        });

        displaySearchResults(books, query);

    } catch (error) {
        console.error('Search error:', error);
        displayError('Search failed. Please try again later.');
    }
}

function displaySearchResults(books, query) {
    const container = document.querySelector('#search-results-grid');
    if (!container) return;

    const resultsHeader = document.querySelector('.search-header h2');
    if (resultsHeader) {
        resultsHeader.textContent = `Search Results for "${query}"`;
    }

    container.innerHTML = books.map(book => BookComponent.createBookCard(book, 'search')).join('');
}

function displayNoSearchResults(query) {
    const container = document.querySelector('#search-results-grid');
    if (!container) return;

    const resultsHeader = document.querySelector('.search-header h2');
    if (resultsHeader) {
        resultsHeader.textContent = `No Results for "${query}"`;
    }

    container.innerHTML = `
        <div class="no-results-message">
            <i class="fas fa-search"></i>
            <p>No books found matching your search.</p>
            <p>Try different keywords or browse our categories.</p>
        </div>
    `;
}

// Initialize search when DOM is loaded
document.addEventListener('DOMContentLoaded', setupSearch);
