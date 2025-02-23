// Function to toggle theme
function toggleTheme() {
    document.body.classList.toggle('dark-mode');
    const isDarkMode = document.body.classList.contains('dark-mode');
    localStorage.setItem('theme', isDarkMode ? 'dark' : 'light');
}

// Function to initialize theme
function initTheme() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    if (savedTheme === 'dark') {
        document.body.classList.add('dark-mode');
    }
}

// Initialize theme when DOM loads
document.addEventListener('DOMContentLoaded', () => {
    initTheme();

    // Get theme toggle element
    const themeToggle = document.getElementById('themeToggle');

    // Add click event to theme toggle button
    if (themeToggle) {
        themeToggle.addEventListener('click', toggleTheme);
    }
});
