document.addEventListener('DOMContentLoaded', () => {
    const filterButtons = document.querySelectorAll('.filter-btn');
    const emptyListMessage = document.querySelector('.empty-list-message');
    const bookList = document.querySelector('.book-list');

    // Check if user is logged in using localStorage
    const authToken = localStorage.getItem('authToken');
    if (!authToken) {
        window.location.href = 'sign-in.html?returnUrl=' + encodeURIComponent(window.location.pathname);
        return;
    }

    // Handle filter buttons
    filterButtons.forEach(button => {
        button.addEventListener('click', () => {
            // Remove active class from all buttons
            filterButtons.forEach(btn => btn.classList.remove('active'));
            // Add active class to clicked button
            button.classList.add('active');
            
            // Filter logic will be implemented here
            const filter = button.dataset.filter;
            filterBooks(filter);
        });
    });

    // Function to filter books (placeholder)
    function filterBooks(filter) {
        // This will be implemented when we add the database functionality
        console.log('Filtering by:', filter);
        
        // For now, just show the empty message
        emptyListMessage.style.display = 'flex';
        bookList.style.display = 'none';
    }
});
