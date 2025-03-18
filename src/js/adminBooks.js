// Admin Books Management

document.addEventListener('DOMContentLoaded', () => {
    // Check if user is admin
    checkAdminAccess();
});

async function checkAdminAccess() {
    try {
        const response = await fetch('../api/auth/admin.php');
        if (!response.ok) {
            throw new Error('Failed to check admin access');
        }
        
        const text = await response.text();
        if (text.startsWith('ERROR:')) {
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
        const response = await fetch('../api/books.php');
        if (!response.ok) throw new Error('Failed to load books');
        
        const text = await response.text();
        if (text.startsWith('ERROR:')) {
            throw new Error(text.substring(6));
        }
        
        if (text === 'NO_RESULTS') {
            displayNoBooks();
            return;
        }

        const books = text.split('\n').map(line => {
            const [
                id, title, author, cover_image, description, genre,
                publication_year, file_path, file_type, average_rating,
                total_ratings
            ] = line.split('|');

            return {
                id, title, author, cover_image, description, genre,
                publication_year, file_path, file_type,
                average_rating: parseFloat(average_rating),
                total_ratings: parseInt(total_ratings)
            };
        });

        displayAdminBooks(books);
        updateGenreFilter(books);

    } catch (error) {
        console.error('Error loading books:', error);
        displayError(error.message);
    }
}

function displayAdminBooks(books) {
    const grid = document.getElementById('adminBooksGrid');
    if (!grid) return;

    grid.innerHTML = books.map(book => createAdminBookCard(book)).join('');
}

function createAdminBookCard(book) {
    return `
        <div class="admin-book-card" data-book-id="${book.id}">
            <div class="book-cover">
                <img src="${book.cover_image || '../assets/images/default-cover.jpg'}" 
                     alt="${book.title}"
                     onerror="this.src='../assets/images/default-cover.jpg'">
            </div>
            <div class="book-info">
                <h3>${book.title}</h3>
                <p>by ${book.author}</p>
                <p>Genre: ${book.genre}</p>
                <p>Published: ${book.publication_year}</p>
                <div class="book-actions">
                    <button class="edit-btn" onclick="editBook('${book.id}')">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button class="delete-btn" onclick="deleteBook('${book.id}')">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
            </div>
        </div>
    `;
}

function updateGenreFilter(books) {
    const genreFilter = document.getElementById('genreFilter');
    if (!genreFilter) return;

    const genres = [...new Set(books.map(book => book.genre))];
    const genreOptions = genres.map(genre => 
        `<option value="${genre}">${genre}</option>`
    ).join('');

    genreFilter.innerHTML = `
        <option value="">All Genres</option>
        ${genreOptions}
    `;
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

    modalTitle.textContent = 'Add New Book';
    form.reset();
    form.removeAttribute('data-book-id');
    modal.style.display = 'block';
}

async function editBook(bookId) {
    try {
        const response = await fetch(`../api/books.php?id=${bookId}`);
        if (!response.ok) throw new Error('Failed to fetch book details');

        const text = await response.text();
        if (text.startsWith('ERROR:')) {
            throw new Error(text.substring(6));
        }

        const [
            id, title, author, cover_image, description, genre,
            publication_year, file_path, file_type
        ] = text.split('|');

        const modal = document.getElementById('bookFormModal');
        const form = document.getElementById('bookForm');
        const modalTitle = document.getElementById('modalTitle');

        modalTitle.textContent = 'Edit Book';
        form.setAttribute('data-book-id', id);

        // Fill form fields
        document.getElementById('title').value = title;
        document.getElementById('author').value = author;
        document.getElementById('genre').value = genre;
        document.getElementById('publicationYear').value = publication_year;
        document.getElementById('description').value = description;

        modal.style.display = 'block';

    } catch (error) {
        console.error('Error fetching book details:', error);
        alert('Failed to load book details. Please try again.');
    }
}

async function handleBookSubmit(e) {
    e.preventDefault();

    const form = e.target;
    const bookId = form.getAttribute('data-book-id');
    const isEdit = !!bookId;

    const formData = new FormData(form);
    formData.append('action', isEdit ? 'update' : 'add');
    if (isEdit) {
        formData.append('id', bookId);
    }

    try {
        const response = await fetch('../api/books.php', {
            method: 'POST',
            body: formData
        });

        if (!response.ok) throw new Error('Failed to save book');
        
        const text = await response.text();
        if (text.startsWith('ERROR:')) {
            throw new Error(text.substring(6));
        }

        closeBookModal();
        loadAdminBooks();
        showMessage(isEdit ? 'Book updated successfully!' : 'Book added successfully!');

    } catch (error) {
        console.error('Error saving book:', error);
        alert('Failed to save book. Please try again.');
    }
}

async function deleteBook(bookId) {
    if (!confirm('Are you sure you want to delete this book?')) return;

    try {
        const response = await fetch('../api/books.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=delete&id=${bookId}`
        });

        if (!response.ok) throw new Error('Failed to delete book');
        
        const text = await response.text();
        if (text.startsWith('ERROR:')) {
            throw new Error(text.substring(6));
        }

        loadAdminBooks();
        showMessage('Book deleted successfully!');

    } catch (error) {
        console.error('Error deleting book:', error);
        alert('Failed to delete book. Please try again.');
    }
}

function closeBookModal() {
    const modal = document.getElementById('bookFormModal');
    modal.style.display = 'none';
}

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
    
    setTimeout(() => {
        messageDiv.remove();
    }, 3000);
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