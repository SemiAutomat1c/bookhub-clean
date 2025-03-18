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

// Helper functions for text format handling
function parseTextResponse(text) {
    const [type, ...parts] = text.split('|');
    if (type === 'ERROR') {
        throw new Error(parts[0]);
    }
    return { type, parts };
}

function parseBookData(text) {
    const books = {
        'currently-reading': [],
        'want-to-read': [],
        'completed': []
    };
    
    const { type, parts } = parseTextResponse(text);
    if (type === 'DATA') {
        if (parts[0] === 'no_books') {
            return books;
        }
        
        for (let i = 0; i < parts.length; i += 9) {
            if (parts[i] === 'book') {
                const book = {
                    id: parts[i + 1],
                    list_type: parts[i + 2],
                    title: parts[i + 3],
                    author: parts[i + 4],
                    cover: parts[i + 5],
                    description: parts[i + 6],
                    rating: parts[i + 7],
                    genre: parts[i + 8]
                };
                books[book.list_type].push(book);
            }
        }
    }
    return books;
}

async function loadBooks(listType) {
    try {
        // Get the book grid for the current tab
        const bookGrid = document.querySelector(`#${listType} .book-grid`);
        if (!bookGrid) return;

        // Fetch reading list from server
        const response = await fetch('/reading_list.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'text/plain'
            },
            body: `action:get`
        });

        const text = await response.text();
        const books = parseBookData(text)[listType] || [];

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
    } catch (error) {
        console.error('Error loading books:', error);
        alert('Failed to load books. Please try again later.');
    }
}

async function removeBook(bookId, listType) {
    try {
        const response = await fetch('/reading_list.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'text/plain'
            },
            body: `action:remove|book_id:${bookId}`
        });

        const text = await response.text();
        const { type } = parseTextResponse(text);
        
        if (type === 'SUCCESS') {
            // Reload the current tab
            loadBooks(listType);
        }
    } catch (error) {
        console.error('Error removing book:', error);
        alert('Failed to remove book. Please try again later.');
    }
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

async function updateProgress(bookId, newProgress) {
    try {
        const response = await fetch('/save_progress.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'text/plain'
            },
            body: `bookId:${bookId}|page:${newProgress}`
        });

        const text = await response.text();
        const { type } = parseTextResponse(text);
        
        if (type === 'SUCCESS') {
            loadBooks('currently-reading');
            
            // If progress is 100%, ask if want to move to completed
            if (newProgress === 100) {
                if (confirm('Congratulations on finishing the book! Would you like to move it to your completed list?')) {
                    moveBook(bookId, 'currently-reading', 'completed');
                }
            }
        }
    } catch (error) {
        console.error('Error updating progress:', error);
        alert('Failed to update progress. Please try again later.');
    }
}

async function moveBook(bookId, fromList, toList) {
    try {
        // Remove from current list
        const removeResponse = await fetch('/reading_list.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'text/plain'
            },
            body: `action:remove|book_id:${bookId}`
        });

        const removeText = await removeResponse.text();
        const { type: removeType } = parseTextResponse(removeText);
        
        if (removeType === 'SUCCESS') {
            // Add to new list
            const addResponse = await fetch('/reading_list.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'text/plain'
                },
                body: `action:add|book_id:${bookId}|list_type:${toList}`
            });

            const addText = await addResponse.text();
            const { type: addType } = parseTextResponse(addText);
            
            if (addType === 'SUCCESS') {
                // Reload both lists
                loadBooks(fromList);
                loadBooks(toList);
            }
        }
    } catch (error) {
        console.error('Error moving book:', error);
        alert('Failed to move book. Please try again later.');
    }
}
