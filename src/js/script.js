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

function loadBooks() {
    const booksPath = window.location.pathname.includes('/views/') ? '../books.json' : 'books.json';
    
    fetch(booksPath)
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to load books data');
            }
            return response.json();
        })
        .then(data => {
            if (!data || !data.books) {
                throw new Error('Invalid books data format');
            }

            // Display books in different sections
            const trendingBooks = data.books
                .sort((a, b) => (b.popularity || 0) - (a.popularity || 0))
                .slice(0, 8);

            const newBooks = data.books
                .filter(book => {
                    const publishYear = parseInt(book.published);
                    return publishYear >= 2020;
                })
                .sort((a, b) => parseInt(b.published) - parseInt(a.published))
                .slice(0, 8);

            let movieBooks = data.books
                .filter(book => book.hasMovie)
                .slice(0, 8);
                
            if (movieBooks.length < 8) {
                const remainingBooks = data.books
                    .filter(book => !book.hasMovie)
                    .sort(() => Math.random() - 0.5)
                    .slice(0, 8 - movieBooks.length);
                movieBooks = [...movieBooks, ...remainingBooks];
            }

            // Only try to display books if the elements exist
            const trendingGrid = document.querySelector('#trending-grid');
            const newBooksGrid = document.querySelector('#new-books-grid');
            const movieGrid = document.querySelector('#movie-adaptations-grid');

            if (trendingGrid) displayBooks('#trending-grid', trendingBooks, 'trending');
            if (newBooksGrid) displayBooks('#new-books-grid', newBooks, 'new');
            if (movieGrid) displayBooks('#movie-adaptations-grid', movieBooks, 'movie');
        })
        .catch(error => {
            console.error('Error loading books:', error);
            const grids = ['#trending-grid', '#new-books-grid', '#movie-adaptations-grid'];
            grids.forEach(grid => {
                const element = document.querySelector(grid);
                if (element) {
                    element.innerHTML = `
                        <div class="error-message">
                            <i class="fas fa-exclamation-circle"></i>
                            <p>Sorry, we couldn't load the books right now. Please try again later.</p>
                        </div>
                    `;
                }
            });
        });
}

function handleLogin(token, userData) {
    localStorage.setItem('authToken', token);
    localStorage.setItem('userData', JSON.stringify(userData));
    checkLoginState();
    
    // Redirect to return URL if present
    const urlParams = new URLSearchParams(window.location.search);
    const returnUrl = urlParams.get('returnUrl');
    if (returnUrl) {
        window.location.href = returnUrl;
    } else {
        window.location.href = '../index.html';
    }
}

function handleLogout() {
    localStorage.removeItem('authToken');
    localStorage.removeItem('userData');
    localStorage.removeItem('readingList');
    window.location.href = '../index.html';
}

function displayBooks(selector, books, displayType) {
    const container = document.querySelector(selector);
    if (!container) return;

    container.innerHTML = books.map(book => `
        <div class="book-card" data-book-id="${book.id}">
            <img src="${book.cover || 'https://cdn-icons-png.flaticon.com/512/3145/3145765.png'}" 
                 alt="${book.title}" 
                 class="book-cover"
                 onerror="this.src='https://cdn-icons-png.flaticon.com/512/3145/3145765.png'">
            <div class="book-info">
                <h3 class="book-title">${book.title}</h3>
                <p class="book-author">${book.author}</p>
                ${displayType === 'trending' ? `
                    <div class="book-rating">
                        ${getStarRating(book.rating)}
                        <span class="rating-count">(${book.ratingCount || 0})</span>
                    </div>
                ` : ''}
                ${displayType === 'new' ? `
                    <p class="book-published">Published: ${book.published}</p>
                ` : ''}
                ${displayType === 'movie' && book.hasMovie ? `
                    <span class="movie-badge">
                        <i class="fas fa-film"></i> Movie Available
                    </span>
                ` : ''}
                <button class="view-details" onclick="showBookDetails(${JSON.stringify(book).replace(/"/g, '&quot;')})">
                    View Details
                </button>
            </div>
        </div>
    `).join('');
}

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

function showBookDetails(book) {
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.innerHTML = `
        <div class="modal-content">
            <span class="close">&times;</span>
            <div class="book-details">
                <img src="${book.cover || 'https://cdn-icons-png.flaticon.com/512/3145/3145765.png'}" 
                     alt="${book.title}" 
                     class="book-cover"
                     onerror="this.src='https://cdn-icons-png.flaticon.com/512/3145/3145765.png'">
                <div class="book-info">
                    <h2>${book.title}</h2>
                    <p class="author">By ${book.author}</p>
                    <div class="rating">
                        ${getStarRating(book.rating)}
                        <span class="rating-count">(${book.ratingCount || 0} ratings)</span>
                    </div>
                    <p class="published">Published: ${book.published}</p>
                    <p class="description">${book.description || 'No description available.'}</p>
                    ${book.hasMovie ? '<p class="movie-note"><i class="fas fa-film"></i> A movie adaptation is available!</p>' : ''}
                    ${isUserLoggedIn() ? `
                        <div class="reading-list-actions">
                            <button onclick="addToReadingList(${JSON.stringify(book).replace(/"/g, '&quot;')}, 'want-to-read')">
                                <i class="fas fa-bookmark"></i> Add to Want to Read
                            </button>
                            <button onclick="addToReadingList(${JSON.stringify(book).replace(/"/g, '&quot;')}, 'currently-reading')">
                                <i class="fas fa-book-reader"></i> Start Reading
                            </button>
                        </div>
                    ` : `
                        <p class="login-prompt">
                            <a href="views/sign-in.html">Sign in</a> to add this book to your reading list
                        </p>
                    `}
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(modal);
    
    const closeBtn = modal.querySelector('.close');
    closeBtn.onclick = () => modal.remove();
    
    modal.onclick = (e) => {
        if (e.target === modal) modal.remove();
    };
}

function addToReadingList(book, listType = 'want-to-read') {
    if (!isUserLoggedIn()) {
        window.location.href = 'views/sign-in.html';
        return;
    }

    // Get current reading list
    let readingList = JSON.parse(localStorage.getItem('readingList') || '{}');
    
    // Initialize list type if it doesn't exist
    if (!readingList[listType]) {
        readingList[listType] = [];
    }
    
    // Check if book is already in any list
    const lists = ['currently-reading', 'want-to-read', 'completed'];
    const existingList = lists.find(list => 
        readingList[list] && readingList[list].some(b => b.id === book.id)
    );
    
    if (existingList) {
        if (existingList === listType) {
            alert('This book is already in your ' + listType.replace('-', ' ') + ' list.');
            return;
        }
        
        // Remove from existing list
        readingList[existingList] = readingList[existingList].filter(b => b.id !== book.id);
    }
    
    // Add to new list
    readingList[listType].push({
        ...book,
        addedAt: new Date().toISOString(),
        progress: 0
    });
    
    // Save updated list
    localStorage.setItem('readingList', JSON.stringify(readingList));
    
    alert('Book added to your ' + listType.replace('-', ' ') + ' list!');
}

function setupSearch() {
    const searchInput = document.querySelector('.search-input');
    if (!searchInput) return;

    searchInput.addEventListener('input', (e) => {
        const query = e.target.value.toLowerCase();
        // Implement search logic here
    });
}

// Initialize search
setupSearch();

// Default cover image URL
const DEFAULT_COVER = 'https://cdn-icons-png.flaticon.com/512/3145/3145765.png';
