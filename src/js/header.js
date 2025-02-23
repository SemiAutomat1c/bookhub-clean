// Function to check if user is logged in
function checkLoginStatus() {
    const isLoggedIn = localStorage.getItem('username') !== null && localStorage.getItem('authToken') !== null;
    document.body.classList.toggle('logged-in', isLoggedIn);
    return isLoggedIn;
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

// Function to show search suggestions
async function showSearchSuggestions(query) {
    if (!query) {
        document.querySelector('.search-suggestions').innerHTML = '';
        return;
    }

    try {
        const response = await fetch('../get_books.php');
        const books = await response.json();
        
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
                <img src="${book.cover_image || '../assets/images/default-cover.jpg'}" alt="${book.title}" class="suggestion-cover">
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
function logout() {
    localStorage.removeItem('authToken');
    localStorage.removeItem('username');
    localStorage.removeItem('user');
    checkLoginStatus();
    window.location.href = '/bookhub/views/index.html';
}

// Initialize header functionality
document.addEventListener('DOMContentLoaded', () => {
    checkLoginStatus();

    // Set active nav button based on current page
    const currentPage = window.location.pathname;
    document.querySelectorAll('.nav-button').forEach(button => {
        const href = button.getAttribute('href');
        if (href && currentPage.includes(href)) {
            button.classList.add('active');
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
