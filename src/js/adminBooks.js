// Admin Books Management

document.addEventListener('DOMContentLoaded', () => {
    // Check if user is admin
    checkAdminAccess();
});

async function checkAdminAccess() {
    try {
        const response = await fetch('../api/auth/admin.php', {
            credentials: 'include'
        });

        if (!response.ok) {
            throw new Error('Failed to check admin access');
        }
        
        const text = await response.text();
        console.log('Admin check response:', text); // Debug log

        if (text.startsWith('ERROR')) {
            window.location.href = 'index.html';
            return;
        }
        
        // Initialize admin interface if access is granted
        initializeAdminInterface();
        
        // Load books
        loadAdminBooks();
        
    } catch (error) {
        console.error('Error checking admin access:', error);
        window.location.href = 'index.html';
    }
}

function initializeAdminInterface() {
    // Setup navigation
    const navItems = document.querySelectorAll('.admin-nav-item');
    navItems.forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            const section = item.getAttribute('data-section');
            switchSection(section);
        });
    });

    // Setup search
    const searchInput = document.getElementById('bookSearch');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(handleSearch, 300));
    }

    // Setup filters
    const genreFilter = document.getElementById('genreFilter');
    const sortBy = document.getElementById('sortBy');
    if (genreFilter) genreFilter.addEventListener('change', handleFilters);
    if (sortBy) sortBy.addEventListener('change', handleFilters);

    // Setup book form
    const bookForm = document.getElementById('bookForm');
    if (bookForm) {
        bookForm.addEventListener('submit', handleBookSubmit);
    }
}

function switchSection(sectionId) {
    // Update navigation
    document.querySelectorAll('.admin-nav-item').forEach(item => {
        item.classList.toggle('active', item.getAttribute('data-section') === sectionId);
    });

    // Update sections
    document.querySelectorAll('.admin-section').forEach(section => {
        section.classList.toggle('active', section.id === `${sectionId}-section`);
    });
}

async function loadAdminBooks() {
    try {
        const searchInput = document.getElementById('bookSearch').value;
        const genreFilter = document.getElementById('genreFilter').value;
        const sortBy = document.getElementById('sortBy').value;

        const response = await fetch(`../api/books/list_books.php?search=${encodeURIComponent(searchInput)}&genre=${encodeURIComponent(genreFilter)}&sort=${encodeURIComponent(sortBy)}`, {
            credentials: 'include'
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const text = await response.text();
        console.log('Load books response:', text); // Debug log
        
        if (text.startsWith('ERROR')) {
            throw new Error(text.substring(6));
        }

        const booksGrid = document.getElementById('adminBooksGrid');
        booksGrid.innerHTML = '';

        if (text === 'SUCCESS|NO_BOOKS') {
            displayNoBooks();
            return;
        }

        // Remove 'SUCCESS|' prefix and split into books
        const booksData = text.substring(8).split('\n').filter(line => line.trim());
        
        if (booksData.length === 0) {
            displayNoBooks();
            return;
        }

        booksData.forEach(bookData => {
            const [id, title, author, description, genre, publication_year, cover, file_path, file_type] = bookData.split('|');
            if (id && title) {
                const bookCard = createBookCard(id, title, author, description, genre, publication_year, cover, file_path);
                booksGrid.appendChild(bookCard);
            }
        });
    } catch (error) {
        console.error('Error loading books:', error);
        displayError('Failed to load books. Please try again.');
    }
}

function createBookCard(id, title, author, description, genre, year, cover, file_path) {
    // Escape special characters in strings for onclick handlers
    const escapedTitle = title.replace(/'/g, "\\'");
    const escapedAuthor = author.replace(/'/g, "\\'");
    const escapedDescription = (description || '').replace(/'/g, "\\'");

    // Default image if no cover is provided (light gray background with "No Cover" text)
    const defaultCover = 'data:image/svg+xml;base64,' + btoa(`
        <svg width="200" height="300" xmlns="http://www.w3.org/2000/svg">
            <rect width="100%" height="100%" fill="#e1e1e1"/>
            <text x="50%" y="50%" font-family="Arial" font-size="20" fill="#2c3e50" text-anchor="middle">No Cover</text>
        </svg>
    `);
    
    // Construct the full image path
    let coverPath = cover ? '../' + cover.replace(/^\/+/, '') : defaultCover;

    const card = document.createElement('div');
    card.className = 'admin-book-card';
    card.style.textAlign = 'center';
    card.innerHTML = `
        <div class="book-cover" style="width: 200px; height: 300px; margin: 0 auto 15px auto; background-color: #f5f5f5;">
            <img src="${coverPath}" 
                 alt="${escapedTitle}"
                 onerror="this.onerror=null; this.src='${defaultCover}';"
                 style="width: 100%; height: 100%; object-fit: cover;">
        </div>
        <div class="book-info" style="text-align: left;">
            <h3 style="text-align: center; margin-bottom: 15px;">${escapedTitle}</h3>
            <p><strong>Author:</strong> ${escapedAuthor}</p>
            <p><strong>Genre:</strong> ${genre || 'N/A'}</p>
            <p><strong>Year:</strong> ${year || 'N/A'}</p>
            <p class="description"><strong>Description:</strong> ${escapedDescription || 'No description available.'}</p>
            <div class="book-actions" style="text-align: center; margin-top: 15px;">
                <button class="edit-btn" onclick="editBook(${id}, '${escapedTitle}', '${escapedAuthor}', '${escapedDescription}', '${genre || ''}', '${year || ''}', '${file_path || ''}')">
                    <i class="fas fa-edit"></i> Edit
                </button>
                <button class="delete-btn" onclick="deleteBook(${id})">
                    <i class="fas fa-trash"></i> Delete
                </button>
            </div>
            ${file_path ? `
            <div class="book-download" style="text-align: center; margin-top: 10px;">
                <a href="../${file_path}" target="_blank" class="download-btn">
                    <i class="fas fa-download"></i> Download PDF
                </a>
            </div>
            ` : ''}
        </div>
    `;
    return card;
}

function handleSearch() {
    const searchTerm = document.getElementById('bookSearch').value.toLowerCase();
    const books = Array.from(document.querySelectorAll('.admin-book-card'));

    books.forEach(book => {
        const title = book.querySelector('h3').textContent.toLowerCase();
        const author = book.querySelector('p').textContent.toLowerCase();
        const matches = title.includes(searchTerm) || author.includes(searchTerm);
        book.style.display = matches ? '' : 'none';
    });
}

function handleFilters() {
    const genre = document.getElementById('genreFilter').value;
    const sortBy = document.getElementById('sortBy').value;
    const books = Array.from(document.querySelectorAll('.admin-book-card'));

    // Filter by genre
    books.forEach(book => {
        const bookGenre = book.querySelector('p:nth-child(3)').textContent.replace('Genre: ', '');
        book.style.display = !genre || bookGenre === genre ? '' : 'none';
    });

    // Sort books
    const sortedBooks = books.sort((a, b) => {
        const aValue = getSortValue(a, sortBy);
        const bValue = getSortValue(b, sortBy);
        return aValue.localeCompare(bValue);
    });

    const grid = document.getElementById('adminBooksGrid');
    sortedBooks.forEach(book => grid.appendChild(book));
}

function getSortValue(bookCard, sortBy) {
    switch (sortBy) {
        case 'title':
            return bookCard.querySelector('h3').textContent;
        case 'author':
            return bookCard.querySelector('p').textContent;
        case 'date':
            return bookCard.querySelector('p:nth-child(4)').textContent;
        default:
            return '';
    }
}

function showAddBookModal() {
    const modal = document.getElementById('bookFormModal');
    const form = document.getElementById('bookForm');
    const modalTitle = document.getElementById('modalTitle');

    // Reset form and title
    form.reset();
    form.removeAttribute('data-book-id');
    modalTitle.textContent = 'Add New Book';

    // Show modal
    modal.style.display = 'block';
}

function closeBookModal() {
    const modal = document.getElementById('bookFormModal');
    modal.style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('bookFormModal');
    if (event.target === modal) {
        closeBookModal();
    }
}

// Close modal when clicking the X button
document.addEventListener('DOMContentLoaded', () => {
    const closeBtn = document.querySelector('.close');
    if (closeBtn) {
        closeBtn.onclick = closeBookModal;
    }

    // Add form submit handler
    const bookForm = document.getElementById('bookForm');
    if (bookForm) {
        bookForm.addEventListener('submit', handleBookSubmit);
    }
});

async function handleBookSubmit(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    const bookId = form.getAttribute('data-book-id');
    
    try {
        const url = bookId ? 
            '../api/books/update_book.php' : 
            '../api/books/add_book.php';
            
        if (bookId) {
            formData.append('book_id', bookId);
        }

        // Validate publication year
        const year = formData.get('publication_year');
        if (year) {
            const yearNum = parseInt(year);
            if (yearNum < 1800 || yearNum > new Date().getFullYear() + 1) {
                throw new Error('Publication year must be between 1800 and ' + (new Date().getFullYear() + 1));
            }
        }

        const response = await fetch(url, {
            method: 'POST',
            body: formData,
            credentials: 'include'
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const text = await response.text();
        console.log('Save book response:', text); // Debug log

        const [status, message] = text.split('|');
        if (status === 'ERROR') {
            throw new Error(message);
        }

        closeBookModal();
        await loadAdminBooks();
        showSuccess(message || 'Book saved successfully');
    } catch (error) {
        console.error('Error saving book:', error);
        showError(error.message || 'Failed to save book. Please try again.');
    }
}

async function deleteBook(bookId) {
    if (!confirm('Are you sure you want to delete this book?')) {
        return;
    }

    try {
        const formData = new FormData();
        formData.append('book_id', bookId);

        const response = await fetch('../api/books/delete_book.php', {
            method: 'POST',
            body: formData,
            credentials: 'include'
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const text = await response.text();
        console.log('Delete response:', text); // Debug log

        if (text.startsWith('ERROR')) {
            throw new Error(text.substring(6));
        }

        showSuccess('Book deleted successfully');
        await loadAdminBooks(); // Reload the books list
    } catch (error) {
        console.error('Error deleting book:', error);
        showError(error.message || 'Failed to delete book');
    }
}

async function editBook(id, title, author, description, genre, year, file_path) {
    try {
        const form = document.getElementById('bookForm');
        form.setAttribute('data-book-id', id);
        
        document.getElementById('modalTitle').textContent = 'Edit Book';
        document.getElementById('title').value = title;
        document.getElementById('author').value = author;
        document.getElementById('description').value = description || '';
        document.getElementById('genre').value = genre || '';
        document.getElementById('publication_year').value = year || '';
        
        // Show the current file name if exists
        const bookFileInput = document.getElementById('book_file');
        bookFileInput.required = false; // Make file not required for edit
        if (file_path) {
            const fileName = file_path.split('/').pop();
            bookFileInput.setAttribute('data-current-file', fileName);
        }

        const modal = document.getElementById('bookFormModal');
        modal.style.display = 'block';
    } catch (error) {
        console.error('Error loading book details:', error);
        showError(error.message || 'Failed to load book details. Please try again.');
    }
}

function showError(message) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.textContent = message;
    document.querySelector('.admin-content').prepend(errorDiv);
    setTimeout(() => errorDiv.remove(), 5000);
}

function showSuccess(message) {
    const successDiv = document.createElement('div');
    successDiv.className = 'success-message';
    successDiv.textContent = message;
    document.querySelector('.admin-content').prepend(successDiv);
    setTimeout(() => successDiv.remove(), 5000);
}

function displayNoBooks() {
    const grid = document.getElementById('adminBooksGrid');
    if (!grid) return;

    grid.innerHTML = `
        <div class="no-books-message">
            <i class="fas fa-books"></i>
            <p>No books available. Add some books to get started!</p>
        </div>
    `;
}

function displayError(message) {
    const grid = document.getElementById('adminBooksGrid');
    if (!grid) return;

    grid.innerHTML = `
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i>
            <p>${message}</p>
        </div>
    `;
}

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