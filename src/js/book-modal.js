// Book data cache
let bookData = {};

// Toast notification function
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    // Trigger reflow
    toast.offsetHeight;
    
    // Add show class
    toast.classList.add('show');
    
    // Remove after 3 seconds
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            document.body.removeChild(toast);
        }, 300);
    }, 3000);
}

// Helper function to parse book data from text format
function parseBookData(text) {
    if (!text || text.startsWith('ERROR')) {
        console.error('Error in book data:', text);
        return [];
    }

    return text.split('\n')
        .filter(line => line.trim() && !line.startsWith('SUCCESS'))
        .map(line => {
            const [
                book_id,
                title,
                author,
                cover_image,
                description,
                genre,
                publication_year,
                file_path,
                file_type
            ] = line.split('|');

            return {
                book_id: parseInt(book_id),
                title,
                author,
                cover: cover_image,
                description,
                genre,
                publication_year,
                file_path,
                file_type
            };
        });
}

// Function to check if a book is in the reading list
async function checkBookInReadingList(bookId) {
    try {
        console.log('Checking if book is in reading list:', bookId); // Debug log
        const response = await fetch('/bookhub-1/api/reading-list/get.php', {
            credentials: 'include'
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const text = await response.text();
        console.log('Reading list response:', text); // Debug log

        if (text.startsWith('ERROR')) {
            throw new Error(text.substring(6));
        }

        // Parse the response
        const lines = text.split('\n');
        if (lines[0] !== 'SUCCESS') {
            throw new Error('Invalid response format');
        }

        // Check each section for the book ID
        let currentSection = '';
        for (let i = 1; i < lines.length; i++) {
            const line = lines[i].trim();
            if (!line) continue;

            if (line === 'WANT_TO_READ' || line === 'CURRENTLY_READING' || line === 'COMPLETED') {
                currentSection = line;
                continue;
            }

            if (line === 'NO_BOOKS') {
                continue;
            }

            // Parse book entry
            const [id] = line.split('|');
            if (id === bookId.toString()) {
                console.log('Book found in section:', currentSection); // Debug log
                return currentSection;
            }
        }

        console.log('Book not found in reading list'); // Debug log
        return null;
    } catch (error) {
        console.error('Error checking reading list:', error);
        return null;
    }
}

// Fetch book data from API
async function loadBookData(bookId) {
    try {
        console.log('Loading book data...'); // Debug log
        const response = await fetch('/bookhub-1/api/books.php', {
            credentials: 'include'
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const text = await response.text();
        console.log('Book data response:', text); // Debug log
        
        const books = parseBookData(text);
        console.log('Parsed books:', books); // Debug log
        
        // Index books by id
        bookData = books.reduce((acc, book) => {
            acc[book.book_id] = book;
            return acc;
        }, {});
        
        console.log('Book data indexed:', bookData); // Debug log

        return bookData[bookId];
    } catch (error) {
        console.error('Error loading books:', error);
        bookData = {}; // Reset on error
        return null;
    }
}

// Function to show book details in modal
function showBookModal(bookId) {
    const modal = document.getElementById('bookModal');
    const modalContent = document.querySelector('.modal-content');
    
    // Load book data
    loadBookData(bookId).then(bookData => {
        if (!bookData) {
            showToast('Error loading book details', 'error');
            return;
        }

        const coverImage = bookData.cover ? '../' + bookData.cover.replace(/^\/+/, '') : '../assets/images/default-cover.jpg';
        
        modalContent.innerHTML = `
            <button class="close">&times;</button>
            <div class="book-details">
                <div class="book-cover">
                    <img src="${coverImage}" alt="${bookData.title} cover" onerror="this.src='../assets/images/default-cover.jpg'">
                </div>
                <div class="book-info">
                    <h2>${bookData.title}</h2>
                    <p class="author">by ${bookData.author}</p>
                    <span class="book-genre">${bookData.genre}</span>
                    <div class="book-description">
                        <h3>Description</h3>
                        <p>${bookData.description || 'No description available.'}</p>
                    </div>
                    <div class="modal-actions">
                        ${bookData.file_path ? `
                            <button class="primary-btn" onclick="startReading('${bookId}')">
                                <i class="fas fa-book-reader"></i> Read Now
                            </button>
                        ` : ''}
                        <div class="list-actions">
                            <select class="list-type-select" id="listType">
                                <option value="want-to-read">Want to Read</option>
                                <option value="currently-reading">Currently Reading</option>
                                <option value="completed">Completed</option>
                            </select>
                            <button class="primary-btn" onclick="addToReadingList('${bookId}', document.getElementById('listType').value)">
                                <i class="fas fa-plus"></i> Add to List
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Show modal
        modal.style.display = 'flex';

        // Close button functionality
        const closeBtn = modalContent.querySelector('.close');
        closeBtn.onclick = () => {
            modal.style.display = 'none';
        };

        // Close modal when clicking outside
        modal.onclick = (e) => {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        };

        // Close modal with Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.style.display === 'flex') {
                modal.style.display = 'none';
            }
        });

        // Check if book is already in reading list
        checkBookInReadingList(bookId).then(listType => {
            if (listType) {
                const listActions = modalContent.querySelector('.list-actions');
                listActions.innerHTML = `
                    <div class="already-added">
                        This book is already in your "${listType.replace(/-/g, ' ')}" list
                    </div>
                `;
            }
        });
    });
}

// Helper function to generate star rating HTML
function getStarRating(rating) {
    const fullStars = Math.floor(rating);
    const hasHalfStar = rating % 1 >= 0.5;
    const emptyStars = 5 - fullStars - (hasHalfStar ? 1 : 0);
    
    return `
        ${'<i class="fas fa-star"></i>'.repeat(fullStars)}
        ${hasHalfStar ? '<i class="fas fa-star-half-alt"></i>' : ''}
        ${'<i class="far fa-star"></i>'.repeat(emptyStars)}
    `;
}

// Function to start reading a book
function startReading(bookId) {
    if (!bookData[bookId]) {
        console.error('Book not found:', bookId);
        return;
    }
    
    // Redirect to reader page with book ID
    window.location.href = `/bookhub-1/views/reader.html?book_id=${bookId}`;
}

// Function to add a book to the reading list
async function addToReadingList(bookId, listType = 'want-to-read') {
    try {
        // Format the data as expected by the server
        const data = `book_id:${bookId}|list_type:${listType}`;
        
        const response = await fetch('/bookhub-1/api/reading-list/add.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'text/plain',
            },
            body: data,
            credentials: 'include'
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const text = await response.text();
        console.log('Reading list response:', text); // Debug log

        if (text.startsWith('ERROR')) {
            throw new Error(text.substring(6));
        }

        showToast('Book added to reading list', 'success');

        // Reload the book modal
        showBookModal(bookId);
    } catch (error) {
        console.error('Error adding book to reading list:', error);
        showToast('Error adding book to reading list', 'error');
    }
}