// Function to check if user is logged in
async function checkLoginStatus() {
    try {
        const response = await fetch('/bookhub-1/api/auth/auth_check.php', {
            credentials: 'include'
        });
        
        const text = await response.text();
        debugLog('Auth Check Response', text);
        
        const [status, message, userData] = text.split('|');
        
        if (status === 'authenticated') {
            const [userId, username, email] = userData.split(',');
            localStorage.setItem('user', `${userId},${username},${email}`);
            updateHeader(true, { id: userId, username, email });
            return true;
        } else {
            localStorage.removeItem('user');
            updateHeader(false);
            return false;
        }
    } catch (error) {
        debugLog('Auth Check Error', error);
        localStorage.removeItem('user');
        updateHeader(false);
        return false;
    }
}

// Function to get current user
function getCurrentUser() {
    const userStr = localStorage.getItem('user');
    if (userStr) {
        const [userId, username, email] = userStr.split(',');
        return { id: userId, username, email };
    }
    return null;
}

// Function to handle search
function performSearch() {
    const searchInput = document.getElementById('searchInput');
    const query = searchInput.value.trim();
    
    if (query) {
        // If we're not already on the search page, navigate to it
        if (!window.location.pathname.includes('search.html')) {
            window.location.href = 'search.html?q=' + encodeURIComponent(query);
        } else {
            // If we're on the search page, just trigger the search
            // The search page's own search function will handle this
            const searchEvent = new CustomEvent('performSearch', { detail: query });
            document.dispatchEvent(searchEvent);
        }
    }
}

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
            ratingCount: parseInt(total_ratings)
        };
    });
}

// Function to show search suggestions
async function showSearchSuggestions(query) {
    if (!query) {
        document.querySelector('.search-suggestions').innerHTML = '';
        return;
    }

    try {
        const response = await fetch('/bookhub-1/api/books/list_books.php');
        const text = await response.text();
        const books = parseBookData(text);
        
        // Filter books based on query
        const suggestions = books.filter(book => {
            const title = (book.title || '').toLowerCase();
            const author = (book.author || '').toLowerCase();
            const genre = (book.genre || '').toLowerCase();
            const searchQuery = query.toLowerCase();
            
            return title.includes(searchQuery) || 
                   author.includes(searchQuery) || 
                   genre.includes(searchQuery);
        }).slice(0, 5); // Show top 5 suggestions

        // Create suggestions HTML
        const suggestionsHtml = suggestions.map(book => `
            <div class="suggestion-item" onclick="window.location.href='search.html?q=${encodeURIComponent(book.title)}'">
                <img src="${book.cover || '../assets/images/default-cover.jpg'}" alt="${book.title}" class="suggestion-cover">
                <div class="suggestion-info">
                    <div class="suggestion-title">${book.title}</div>
                    <div class="suggestion-author">by ${book.author}</div>
                    <div class="suggestion-genre">${book.genre}</div>
                </div>
            </div>
        `).join('');

        // Show suggestions
        const suggestionsContainer = document.querySelector('.search-suggestions');
        suggestionsContainer.innerHTML = suggestionsHtml;
        suggestionsContainer.style.display = suggestions.length ? 'block' : 'none';
    } catch (error) {
        console.error('Error fetching suggestions:', error);
    }
}

// Function to handle logout
async function logout() {
    debugLog('Logout', 'Attempting logout...');
    try {
        const response = await fetch('/bookhub-1/api/auth/logout.php', {
            method: 'POST',
            credentials: 'include'
        });

        const text = await response.text();
        debugLog('Logout Response', text);

        // Clear user data regardless of response
        localStorage.removeItem('user');
        currentUser = null;

        // Update UI
        updateHeader(false);

        // Redirect to sign-in page
        window.location.href = '/bookhub-1/views/sign-in.html';
    } catch (error) {
        debugLog('Logout Error', error);
        // Still clear data and redirect even if logout fails
        localStorage.removeItem('user');
        currentUser = null;
        updateHeader(false);
        window.location.href = '/bookhub-1/views/sign-in.html';
    }
}

// Function to update header state
function updateHeader(isAuthenticated, userData = null) {
    debugLog('Header Update', 'Updating header state', { isAuthenticated, userData });
    
    // Add or remove logged-in class from body
    if (isAuthenticated) {
        document.body.classList.add('logged-in');
    } else {
        document.body.classList.remove('logged-in');
    }

    const userInfoElements = document.querySelectorAll('.user-info');

    // Update user info elements
    if (isAuthenticated && userData) {
        userInfoElements.forEach(element => {
            element.textContent = userData.username;
        });
    }

    debugLog('Header Update', 'Header update complete');
}

// Initialize header on page load
document.addEventListener('DOMContentLoaded', async () => {
    debugLog('Header Init', 'Initializing header');
    
    // Check authentication status
    const isAuthenticated = await checkLoginStatus();
    debugLog('Initial Auth Status', { isAuthenticated });

    // Set active nav button based on current page
    const currentPage = window.location.pathname;
    document.querySelectorAll('.nav-button').forEach(button => {
        const href = button.getAttribute('href');
        if (href && currentPage.includes(href)) {
            button.classList.add('active');
            debugLog('Active Button Set', { href, currentPage });
        }
    });

    // Handle search input
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        // Handle Enter key
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                performSearch();
                document.querySelector('.search-suggestions').style.display = 'none';
            }
        });

        // Handle input for suggestions
        let debounceTimer;
        searchInput.addEventListener('input', (e) => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                showSearchSuggestions(e.target.value.trim());
            }, 300);
        });

        // Hide suggestions when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.search-input-wrapper')) {
                document.querySelector('.search-suggestions').style.display = 'none';
            }
        });

        // Check for search query in URL
        const urlParams = new URLSearchParams(window.location.search);
        const queryParam = urlParams.get('q');
        if (queryParam && window.location.pathname.includes('search.html')) {
            searchInput.value = queryParam;
            // Trigger search if we're on the search page
            const searchEvent = new CustomEvent('performSearch', { detail: queryParam });
            document.dispatchEvent(searchEvent);
        }
    }
});

// Make functions globally available
window.logout = logout;
window.performSearch = performSearch;
window.updateHeader = updateHeader;
