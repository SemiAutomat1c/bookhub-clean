// Wait for DOM to load
document.addEventListener('DOMContentLoaded', () => {
    // Check if user is logged in
    const authToken = localStorage.getItem('authToken');
    if (!authToken) {
        // Redirect to sign in page with return URL
        const currentPage = encodeURIComponent(window.location.pathname);
        window.location.href = `sign-in.html?returnUrl=${currentPage}`;
        return;
    }

    // Initialize the page
    initializePage();
});

function initializePage() {
    // Get tab elements
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');

    // Add click event to tab buttons
    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            // Remove active class from all buttons and contents
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));

            // Add active class to clicked button and corresponding content
            button.classList.add('active');
            const tabId = button.getAttribute('data-tab');
            document.getElementById(tabId).classList.add('active');

            // Load books for the selected tab
            loadBooks(tabId);
        });
    });

    // Load initial books for the currently-reading tab
    loadBooks('currently-reading');

    // Setup logout button
    const logoutButton = document.getElementById('logout-button');
    if (logoutButton) {
        logoutButton.addEventListener('click', () => {
            localStorage.removeItem('authToken');
            localStorage.removeItem('userData');
            window.location.href = '../index.html';
        });
    }
}

function loadBooks(listType) {
    // Get the book grid for the current tab
    const bookGrid = document.querySelector(`#${listType} .book-grid`);
    if (!bookGrid) return;

    // Get reading list from localStorage
    const readingList = JSON.parse(localStorage.getItem('readingList') || '{}');
    const books = readingList[listType] || [];

    if (books.length === 0) {
        bookGrid.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-book-open"></i>
                <h3>No books yet</h3>
                <p>Start building your reading list by adding books from our collection</p>
                <a href="../index.html" class="browse-books-btn">Browse Books</a>
            </div>
        `;
        return;
    }

    // Display books
    bookGrid.innerHTML = books.map(book => `
        <div class="book-card" data-book-id="${book.id}">
            <img src="${book.cover || 'https://cdn-icons-png.flaticon.com/512/3145/3145765.png'}" 
                 alt="${book.title}" 
                 class="book-cover"
                 onerror="this.src='https://cdn-icons-png.flaticon.com/512/3145/3145765.png'">
            <div class="book-info">
                <h3 class="book-title">${book.title}</h3>
                <p class="book-author">${book.author}</p>
                ${listType === 'currently-reading' ? `
                    <div class="book-progress">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: ${book.progress || 0}%"></div>
                        </div>
                        <p class="progress-text">${book.progress || 0}% Complete</p>
                    </div>
                ` : ''}
                <button class="remove-book-btn" onclick="removeBook('${book.id}', '${listType}')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    `).join('');

    // Add click listeners to book cards
    const bookCards = bookGrid.querySelectorAll('.book-card');
    bookCards.forEach(card => {
        card.addEventListener('click', (e) => {
            // Don't trigger if clicking the remove button
            if (e.target.closest('.remove-book-btn')) return;

            const bookId = card.dataset.bookId;
            const book = books.find(b => b.id === bookId);
            if (book) {
                showBookDetails(book);
            }
        });
    });
}

function removeBook(bookId, listType) {
    // Get current reading list
    const readingList = JSON.parse(localStorage.getItem('readingList') || '{}');
    
    // Remove book from the specified list
    if (readingList[listType]) {
        readingList[listType] = readingList[listType].filter(book => book.id !== bookId);
        localStorage.setItem('readingList', JSON.stringify(readingList));
    }

    // Reload the current tab
    loadBooks(listType);
}

function showBookDetails(book) {
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.innerHTML = `
        <div class="modal-content">
            <button class="close-modal">&times;</button>
            <div class="book-details">
                <img src="${book.cover || 'https://cdn-icons-png.flaticon.com/512/3145/3145765.png'}" 
                     alt="${book.title}" 
                     class="book-cover">
                <div class="book-info">
                    <h2>${book.title}</h2>
                    <p class="author">by ${book.author}</p>
                    <p class="description">${book.description || 'No description available.'}</p>
                    <div class="reading-progress">
                        <h3>Reading Progress</h3>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: ${book.progress || 0}%"></div>
                        </div>
                        <p>${book.progress || 0}% Complete</p>
                    </div>
                    <div class="progress-controls">
                        <button onclick="updateProgress('${book.id}', ${Math.max((book.progress || 0) - 10, 0)})">-10%</button>
                        <button onclick="updateProgress('${book.id}', ${Math.min((book.progress || 0) + 10, 100)})">+10%</button>
                    </div>
                </div>
            </div>
        </div>
    `;

    document.body.appendChild(modal);

    // Close modal functionality
    const closeBtn = modal.querySelector('.close-modal');
    closeBtn.onclick = () => modal.remove();
    modal.onclick = (e) => {
        if (e.target === modal) modal.remove();
    };
}

function updateProgress(bookId, newProgress) {
    const readingList = JSON.parse(localStorage.getItem('readingList') || '{}');
    
    // Update progress in currently-reading list
    if (readingList['currently-reading']) {
        readingList['currently-reading'] = readingList['currently-reading'].map(book => {
            if (book.id === bookId) {
                return { ...book, progress: newProgress };
            }
            return book;
        });
        
        localStorage.setItem('readingList', JSON.stringify(readingList));
        loadBooks('currently-reading');
        
        // If progress is 100%, ask if want to move to completed
        if (newProgress === 100) {
            if (confirm('Congratulations on finishing the book! Would you like to move it to your completed list?')) {
                moveBook(bookId, 'currently-reading', 'completed');
            }
        }
    }
}

function moveBook(bookId, fromList, toList) {
    const readingList = JSON.parse(localStorage.getItem('readingList') || '{}');
    
    // Find the book in the source list
    const book = readingList[fromList]?.find(b => b.id === bookId);
    if (!book) return;
    
    // Remove from source list
    readingList[fromList] = readingList[fromList].filter(b => b.id !== bookId);
    
    // Initialize target list if it doesn't exist
    if (!readingList[toList]) {
        readingList[toList] = [];
    }
    
    // Add to target list
    readingList[toList].push({
        ...book,
        dateCompleted: toList === 'completed' ? new Date().toISOString() : undefined
    });
    
    // Save changes
    localStorage.setItem('readingList', JSON.stringify(readingList));
    
    // Reload both lists
    loadBooks(fromList);
    loadBooks(toList);
}
