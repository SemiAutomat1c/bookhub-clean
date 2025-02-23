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
            window.location.href = '/bookhub/views/search.html?q=' + encodeURIComponent(query);
        } else {
            // If we're on the search page, just trigger the search
            // The search page's own search function will handle this
            const searchEvent = new Event('search');
            searchInput.dispatchEvent(searchEvent);
        }
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

    // Handle search on enter key
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                performSearch();
            }
        });
    }

    // If we're on the search page and there's a query parameter, set it in the search input
    if (currentPage.includes('search.html')) {
        const urlParams = new URLSearchParams(window.location.search);
        const query = urlParams.get('q');
        if (query && searchInput) {
            searchInput.value = decodeURIComponent(query);
            // Trigger search if the page has its own search function
            const searchEvent = new Event('search');
            searchInput.dispatchEvent(searchEvent);
        }
    }
});

// Make functions globally available
window.logout = logout;
window.performSearch = performSearch;
