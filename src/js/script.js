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
function createBookCard(book, showProgress = false) {
    const defaultCover = '../assets/images/default-cover.jpg';
    const coverPath = book.cover_image ? 
        '../' + book.cover_image.replace(/^\/+/, '') : 
        defaultCover;

    let progressHtml = '';
    if (showProgress) {
        progressHtml = `
            <div class="progress-bar">
                <div class="progress" style="width: ${book.progress || 0}%"></div>
            </div>
            <p class="progress-text">${book.progress || 0}% completed</p>
        `;
    }

    return `
        <div class="book-card" onclick="showBookModal(${book.book_id})" style="cursor: pointer;">
            <div class="book-cover">
                <img src="${coverPath}" 
                     alt="${book.title} cover"
                     onerror="this.src='${defaultCover}'">
                ${showProgress ? progressHtml : ''}
            </div>
            <div class="book-info">
                <h3 class="book-title">${book.title}</h3>
                <p class="author">${book.author}</p>
                ${showProgress ? 
                    `<a href="reader.html?book_id=${book.book_id}" class="continue-reading-btn" onclick="event.stopPropagation()">
                        Continue Reading
                    </a>` : 
                    ''
                }
            </div>
        </div>
    `;
}

// Function to show book modal
async function showBookModal(bookId) {
    try {
        const response = await fetch(`../api/books.php?id=${bookId}`, {
            credentials: 'include'
        });
        
        if (!response.ok) throw new Error('Failed to fetch book details');
        
        const text = await response.text();
        if (text.startsWith('ERROR')) throw new Error(text.substring(6));
        
        const [_, ...bookData] = text.split('|');
        const [id, title, author, coverImage, description, genre, year] = bookData;
        
        const isInReadingList = await checkBookInReadingList(bookId);
        const coverPath = coverImage ? 
            '../' + coverImage.replace(/^\/+/, '') : 
            '../assets/images/default-cover.jpg';

        const modal = document.createElement('div');
        modal.id = 'bookModal';
        modal.className = 'modal';
        modal.innerHTML = `
            <div class="modal-content">
                <span class="close">&times;</span>
                <div class="book-details">
                    <div class="book-cover">
                        <img src="${coverPath}" 
                             alt="${title}"
                             onerror="this.src='../assets/images/default-cover.jpg'">
                    </div>
                    <div class="book-info">
                        <h2>${title}</h2>
                        <p class="author">By ${author}</p>
                        <p class="genre">${genre} · ${year}</p>
                        <div class="book-description">
                            <h3>Description</h3>
                            <p>${description || 'No description available.'}</p>
                        </div>
                        <div class="modal-actions">
                            ${isInReadingList ? 
                                `<p class="already-added">This book is already in your reading list!</p>` :
                                `<button class="primary-btn read-btn" onclick="startReading(${id})">
                                    <i class="fas fa-book-reader"></i> Read Now
                                </button>
                                <button class="secondary-btn add-to-list-btn" onclick="addToReadingList(${id})">
                                    <i class="fas fa-plus"></i> Add to List
                                </button>`
                            }
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Remove any existing modal
        const existingModal = document.getElementById('bookModal');
        if (existingModal) {
            existingModal.remove();
        }

        document.body.appendChild(modal);
        modal.style.display = 'block';

        // Set up close handlers
        const closeBtn = modal.querySelector('.close');
        closeBtn.onclick = () => {
            modal.style.display = 'none';
            modal.remove();
        };

        window.onclick = (event) => {
            if (event.target === modal) {
                modal.style.display = 'none';
                modal.remove();
            }
        };

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                modal.style.display = 'none';
                modal.remove();
            }
        });

    } catch (error) {
        console.error('Error showing book modal:', error);
        showMessage('Failed to load book details');
    }
}

// Function to load books
async function loadBooks() {
    try {
        // Add timestamp to prevent caching
        const timestamp = new Date().getTime();
        const response = await fetch(`../api/books/list_books.php?t=${timestamp}`, {
            cache: 'no-store',
            credentials: 'include'
        });
        if (!response.ok) throw new Error('Failed to fetch books');
        
        const text = await response.text();
        console.log('Raw API Response:', text); // Debug log for raw response
        
        if (text.startsWith('ERROR')) throw new Error(text.substring(6));
        
        const books = text.split('\n')
            .filter(line => line.trim())
            .map(line => {
                // Handle the case where SUCCESS is in the same line as the first book
                if (line.startsWith('SUCCESS|')) {
                    line = line.substring(8); // Remove 'SUCCESS|' prefix
                }
                const [id, title, author, description, genre, year, cover, file_path] = line.split('|');
                console.log('Processing book line:', line); // Debug each book line
                return { 
                    book_id: parseInt(id), 
                    title: title || 'Untitled', 
                    author: author || 'Unknown Author', 
                    description: description || 'No description available',
                    genre: genre || 'Uncategorized',
                    publication_year: year || 'Unknown',
                    cover_image: cover || '',
                    file_path: file_path || ''
                };
            });

        console.log('All processed books:', books); // Debug all processed books

        // Create a map for quick book lookups
        const booksMap = new Map(books.map(book => [book.book_id, book]));
        console.log('Books map:', Object.fromEntries(booksMap)); // Debug books map

        // Handle Continue Reading section
        const continueReadingGrid = document.getElementById('continue-reading-grid');
        if (continueReadingGrid) {
            const authenticated = await checkAuthStatus();
            if (!authenticated) {
                continueReadingGrid.innerHTML = `
                    <div class="sign-in-prompt">
                        <div class="prompt-icon">
                            <i class="fas fa-book"></i>
                        </div>
                        <h3>Track Your Reading Progress</h3>
                        <p>Sign in to keep track of books you're currently reading</p>
                        <a href="sign-in.html" class="sign-in-button">Sign In</a>
                    </div>
                `;
            } else {
                try {
                    const readingListResponse = await fetch('/bookhub-1/api/reading-list/get.php', {
                        credentials: 'include'
                    });
                    
                    if (!readingListResponse.ok) throw new Error('Failed to fetch reading list');
                    
                    const readingListText = await readingListResponse.text();
                    console.log('Reading list response:', readingListText);
                    
                    if (readingListText.startsWith('ERROR')) throw new Error(readingListText.substring(6));
                    
                    const lines = readingListText.split('\n');
                    let currentlyReading = [];
                    let inCurrentlyReadingSection = false;
                    
                    for (let i = 0; i < lines.length; i++) {
                        const line = lines[i].trim();
                        if (!line) continue;
                        
                        if (line === 'CURRENTLY_READING') {
                            inCurrentlyReadingSection = true;
                            continue;
                        } else if (line === 'WANT_TO_READ' || line === 'COMPLETED') {
                            inCurrentlyReadingSection = false;
                            continue;
                        } else if (line === 'NO_BOOKS' && inCurrentlyReadingSection) {
                            currentlyReading = [];
                            break;
                        } else if (inCurrentlyReadingSection && line !== 'SUCCESS') {
                            const [bookId, title, author, coverImage, progress] = line.split('|');
                            const bookDetails = booksMap.get(parseInt(bookId)) || {
                                book_id: parseInt(bookId),
                                title,
                                author,
                                cover_image: coverImage
                            };
                            currentlyReading.push({
                                ...bookDetails,
                                progress: parseInt(progress) || 0
                            });
                        }
                    }

                    if (currentlyReading.length === 0) {
                        continueReadingGrid.innerHTML = `
                            <div class="empty-state">
                                <p>No books in progress</p>
                            </div>
                        `;
                    } else {
                        continueReadingGrid.innerHTML = currentlyReading
                            .map(book => createBookCard(book, true))
                            .join('');
                    }
                } catch (error) {
                    console.error('Error fetching reading list:', error);
                    continueReadingGrid.innerHTML = `
                        <div class="error-state">
                            <p>Failed to load reading list. Please try again later.</p>
                        </div>
                    `;
                }
            }
        }

        // Update other book grids
        const trendingBooks = books
            .sort((a, b) => b.book_id - a.book_id) // Newest first
            .slice(0, 4);
        console.log('Trending books:', trendingBooks); // Debug trending books

        const grids = {
            'trending-grid': trendingBooks,
            'new-books-grid': books
                .filter(book => !trendingBooks.find(b => b.book_id === book.book_id))
                .slice(0, 4),
            'movie-adaptations-grid': books
                .filter(book => 
                    ['Classic', 'Fiction', 'Mystery', 'Fantasy'].includes(book.genre) &&
                    !trendingBooks.find(b => b.book_id === book.book_id)
                )
                .slice(0, 4)
        };

        console.log('Grid assignments:', grids); // Debug log

        Object.entries(grids).forEach(([gridId, gridBooks]) => {
            const grid = document.getElementById(gridId);
            if (grid) {
                console.log(`Updating ${gridId} with ${gridBooks.length} books`); // Debug log
                if (gridBooks.length > 0) {
                    const gridHtml = gridBooks.map(book => `
                        <div class="book-card" onclick="showBookModal(${book.book_id})">
                            <div class="book-cover">
                                <img src="${book.cover_image ? '../' + book.cover_image.replace(/^\/+/, '') : '../assets/images/default-cover.jpg'}" 
                                     alt="${book.title} cover"
                                     onerror="this.src='../assets/images/default-cover.jpg'">
                            </div>
                            <div class="book-info">
                                <h3 class="book-title">${book.title}</h3>
                                <p class="author">${book.author}</p>
                                <p class="genre">${book.genre}</p>
                            </div>
                        </div>
                    `).join('');
                    grid.innerHTML = gridHtml;
                } else {
                    grid.innerHTML = '<div class="empty-state"><p>No books available</p></div>';
                }
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
