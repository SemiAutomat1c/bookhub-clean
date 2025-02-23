// Theme toggle functionality
document.addEventListener('DOMContentLoaded', () => {
    const themeToggle = document.getElementById('themeToggle');
    const searchInput = document.getElementById('searchInput');
    const searchSuggestions = document.querySelector('.search-suggestions');

    // Theme toggle
    function toggleTheme() {
        document.body.classList.toggle('dark-mode');
        const isDarkMode = document.body.classList.contains('dark-mode');
        localStorage.setItem('theme', isDarkMode ? 'dark' : 'light');
    }

    function initTheme() {
        const savedTheme = localStorage.getItem('theme') || 'light';
        if (savedTheme === 'dark') {
            document.body.classList.add('dark-mode');
        }
    }

    // Initialize theme
    initTheme();

    // Add click event to theme toggle button
    if (themeToggle) {
        themeToggle.addEventListener('click', toggleTheme);
    }

    // Search functionality
    function performSearch() {
        const query = searchInput.value.trim();
        if (query) {
            window.location.href = `search.html?q=${encodeURIComponent(query)}`;
        }
    }

    // Add search event listeners
    if (searchInput) {
        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                performSearch();
            }
        });

        // Live search suggestions
        searchInput.addEventListener('input', () => {
            const query = searchInput.value.trim().toLowerCase();
            if (query.length >= 2) {
                // Here you would typically fetch suggestions from your backend
                // For now, we'll show some dummy suggestions
                const suggestions = [
                    'Harry Potter',
                    'The Lord of the Rings',
                    'The Hobbit',
                    'Pride and Prejudice'
                ].filter(book => book.toLowerCase().includes(query));

                if (suggestions.length > 0) {
                    searchSuggestions.innerHTML = suggestions
                        .map(s => `<div class="suggestion">${s}</div>`)
                        .join('');
                    searchSuggestions.style.display = 'block';
                } else {
                    searchSuggestions.style.display = 'none';
                }
            } else {
                searchSuggestions.style.display = 'none';
            }
        });

        // Hide suggestions when clicking outside
        document.addEventListener('click', (e) => {
            if (!searchInput.contains(e.target)) {
                searchSuggestions.style.display = 'none';
            }
        });
    }

    // Authentication state
    function checkAuthState() {
        const isLoggedIn = !!localStorage.getItem('authToken');
        document.body.classList.toggle('logged-in', isLoggedIn);
    }

    // Logout functionality
    window.logout = function() {
        localStorage.removeItem('authToken');
        localStorage.removeItem('user');
        checkAuthState();
        window.location.href = 'index.html';
    };

    // Initialize auth state
    checkAuthState();
});
