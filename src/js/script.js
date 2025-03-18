// Function to check if user is logged in
function isLoggedIn() {
    const userStr = localStorage.getItem('user');
    return !!userStr;
}

// Function to update UI based on login state
function updateUIForLoginState(loggedIn) {
    // Update body class
    document.body.classList.toggle('logged-in', loggedIn);
    
    // Update protected features visibility
    const protectedFeatures = document.querySelectorAll('.protected-feature');
    const signedInOnly = document.querySelectorAll('.signed-in-only');
    const signedOutOnly = document.querySelectorAll('.signed-out-only');

    protectedFeatures.forEach(el => {
        el.style.display = loggedIn ? '' : 'none';
    });

    signedInOnly.forEach(el => {
        el.style.display = loggedIn ? '' : 'none';
    });

    signedOutOnly.forEach(el => {
        el.style.display = loggedIn ? 'none' : '';
    });

    // Check if we're on a protected page
    const protectedPages = ['/views/reading-list.html', '/views/profile.html'];
    const currentPath = window.location.pathname.toLowerCase();
    
    if (protectedPages.some(page => currentPath.endsWith(page.toLowerCase())) && !loggedIn) {
        window.location.href = 'sign-in.html?returnUrl=' + encodeURIComponent(window.location.pathname);
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', async () => {
    try {
        // Check authentication status first
        const response = await fetch('/bookhub-1/api/auth/auth_check.php', {
            credentials: 'include'
        });
        const text = await response.text();
        const [status, message, userData] = text.split('|');
        
        if (status === 'authenticated') {
            const [userId, username, email] = userData.split(',');
            localStorage.setItem('user', `${userId},${username},${email}`);
        } else {
            localStorage.removeItem('user');
        }

        // Now update UI based on login state
        updateUIForLoginState(isLoggedIn());

        // Load book data based on current path
        loadBooks();
    } catch (error) {
        console.error('Error checking authentication:', error);
        localStorage.removeItem('user');
        updateUIForLoginState(false);
    }
});

// Book display and modal functionality
let currentBookId = null;

// Function to create book card HTML
function createBookCard(book) {
    const defaultCover = 'data:image/svg+xml;base64,' + btoa(`
        <svg width="200" height="300" xmlns="http://www.w3.org/2000/svg">
            <rect width="100%" height="100%" fill="#e1e1e1"/>
            <text x="50%" y="50%" font-family="Arial" font-size="20" fill="#2c3e50" text-anchor="middle" dy=".3em">No Cover</text>
        </svg>
    `);

    const coverPath = book.cover_image ? 
        '../' + book.cover_image.replace(/^\/+/, '') : 
        defaultCover;

    return `
        <div class="book-card" onclick="showBookModal(${book.book_id})">
            <div class="book-cover">
                <img src="${coverPath}" 
                     alt="${book.title}"
                     onerror="this.src='${defaultCover}'">
            </div>
        </div>
    `;
}

// Function to load books
async function loadBooks() {
    try {
        const response = await fetch('../api/books/list_books.php');
        if (!response.ok) throw new Error('Failed to fetch books');
        
        const text = await response.text();
        if (text.startsWith('ERROR')) throw new Error(text.substring(6));
        
        const books = text.split('\n')
            .filter(line => line.trim() && !line.startsWith('SUCCESS'))
            .map(line => {
                const [id, title, author, description, genre, year, cover, file_path] = line.split('|');
                return { book_id: parseInt(id), title, author, description, genre, publication_year: year, cover_image: cover, file_path };
            });

        // Update book grids
        const grids = {
            'trending-grid': books.slice(0, 6),
            'new-books-grid': books.slice(6, 12),
            'movie-adaptations-grid': books.slice(12, 18)
        };

        Object.entries(grids).forEach(([gridId, gridBooks]) => {
            const grid = document.getElementById(gridId);
            if (grid) {
                grid.innerHTML = gridBooks.map(book => createBookCard(book)).join('');
            }
        });

        // For search page, show all books
        const searchGrid = document.getElementById('search-results-grid');
        if (searchGrid) {
            searchGrid.innerHTML = books.map(book => createBookCard(book)).join('');
        }
    } catch (error) {
        console.error('Error loading books:', error);
        showMessage('Failed to load books');
    }
}

// Function to start reading a book
function startReading(bookId) {
    if (!isLoggedIn()) {
        showLoginPrompt();
        return;
    }
    window.location.href = `reader.html?book_id=${bookId}`;
}

// Function to add book to reading list
async function addToReadingList(bookId) {
    if (!isLoggedIn()) {
        showLoginPrompt();
        return;
    }

    try {
        const response = await fetch('/bookhub-1/api/reading-list/add.php', {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'text/plain'
            },
            body: `book_id:${bookId}|list_type:want-to-read`
        });

        const text = await response.text();
        if (text.startsWith('ERROR')) {
            if (text.includes('already in')) {
                showMessage('This book is already in your reading list!');
            } else {
                showMessage('Failed to add book to reading list. Please try again.');
            }
            return;
        }

        showMessage('Book added to your reading list!');
        document.getElementById('bookModal').style.display = 'none';
    } catch (error) {
        console.error('Error adding book to reading list:', error);
        showMessage('Failed to add book to reading list. Please try again.');
    }
}

// Function to show login prompt
function showLoginPrompt() {
    const shouldLogin = confirm('You need to be logged in to use this feature. Would you like to log in now?');
    if (shouldLogin) {
        window.location.href = 'sign-in.html';
    }
}

// Function to show message
function showMessage(message) {
    const messageDiv = document.createElement('div');
    messageDiv.className = 'message';
    messageDiv.textContent = message;
    messageDiv.style.position = 'fixed';
    messageDiv.style.top = '20px';
    messageDiv.style.left = '50%';
    messageDiv.style.transform = 'translateX(-50%)';
    messageDiv.style.padding = '10px 20px';
    messageDiv.style.backgroundColor = 'var(--primary-color)';
    messageDiv.style.color = 'white';
    messageDiv.style.borderRadius = '5px';
    messageDiv.style.zIndex = '1000';
    
    document.body.appendChild(messageDiv);
    setTimeout(() => messageDiv.remove(), 3000);
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    // Load books
    loadBooks();

    // Set up modal close handlers
    const modal = document.getElementById('bookModal');
    const closeBtn = document.querySelector('.close');
    
    if (closeBtn) {
        closeBtn.onclick = () => modal.style.display = 'none';
    }
    
    window.onclick = (event) => {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    };

    // Set up search functionality
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(performSearch, 300));
    }
});

// Debounce function for search
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

// Search functionality
async function performSearch() {
    const searchInput = document.getElementById('searchInput');
    const searchGrid = document.getElementById('search-results-grid');
    
    if (!searchInput || !searchGrid) return;

    const query = searchInput.value.trim();
    if (!query) {
        await loadBooks(); // Reset to show all books
        return;
    }

    try {
        const response = await fetch(`../api/books/list_books.php?search=${encodeURIComponent(query)}`);
        if (!response.ok) throw new Error('Search failed');

        const text = await response.text();
        if (text.startsWith('ERROR')) throw new Error(text.substring(6));

        const books = text.split('\n')
            .filter(line => line.trim() && !line.startsWith('SUCCESS'))
            .map(line => {
                const [id, title, author, description, genre, year, cover, file_path] = line.split('|');
                return { book_id: parseInt(id), title, author, description, genre, publication_year: year, cover_image: cover, file_path };
            });

        searchGrid.innerHTML = books.map(book => createBookCard(book)).join('');
    } catch (error) {
        console.error('Search error:', error);
        showMessage('Search failed. Please try again.');
    }
}
