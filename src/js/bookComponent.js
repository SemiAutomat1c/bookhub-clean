// Book component class for reusable book display
class BookComponent {
    static createBookCard(book, displayType = 'default') {
        return `
            <div class="book-card" data-book-id="${book.id}">
                <img src="${book.cover_image || '../assets/images/default-cover.jpg'}" 
                     alt="${book.title}" 
                     class="book-cover"
                     onerror="this.src='../assets/images/default-cover.jpg'">
                <div class="book-info">
                    <h3 class="book-title">${book.title}</h3>
                    <p class="book-author">by ${book.author}</p>
                    ${this.getRatingDisplay(book)}
                    ${this.getTypeSpecificDisplay(book, displayType)}
                    <button class="view-details-btn" onclick="BookComponent.showBookDetails('${book.id}')">
                        View Details
                    </button>
                </div>
            </div>
        `;
    }

    static getRatingDisplay(book) {
        if (!book.average_rating) return '';
        return `
            <div class="book-rating">
                ${this.getStarRating(parseFloat(book.average_rating))}
                <span class="rating-count">(${book.total_ratings || 0})</span>
            </div>
        `;
    }

    static getStarRating(rating) {
        const fullStars = Math.floor(rating);
        const hasHalfStar = rating % 1 >= 0.5;
        const emptyStars = 5 - fullStars - (hasHalfStar ? 1 : 0);
        
        return `
            ${'<i class="fas fa-star"></i>'.repeat(fullStars)}
            ${hasHalfStar ? '<i class="fas fa-star-half-alt"></i>' : ''}
            ${'<i class="far fa-star"></i>'.repeat(emptyStars)}
        `;
    }

    static getTypeSpecificDisplay(book, displayType) {
        switch (displayType) {
            case 'new':
                return `<p class="book-published">Published: ${book.publication_year || 'N/A'}</p>`;
            case 'trending':
                return this.getRatingDisplay(book);
            default:
                return '';
        }
    }

    static async showBookDetails(bookId) {
        try {
            const response = await fetch(`../api/books.php?id=${bookId}`);
            if (!response.ok) throw new Error('Failed to fetch book details');
            
            const text = await response.text();
            if (text.startsWith('ERROR:')) {
                throw new Error(text.substring(6));
            }
            
            const bookData = this.parseBookData(text);
            this.displayBookModal(bookData);
        } catch (error) {
            console.error('Error fetching book details:', error);
            alert('Failed to load book details. Please try again later.');
        }
    }

    static parseBookData(text) {
        const [
            id, title, author, cover_image, description, genre,
            publication_year, file_path, file_type, average_rating,
            total_ratings
        ] = text.split('|');

        return {
            id, title, author, cover_image, description, genre,
            publication_year, file_path, file_type,
            average_rating: parseFloat(average_rating),
            total_ratings: parseInt(total_ratings)
        };
    }

    static displayBookModal(book) {
        // Remove any existing modal
        const existingModal = document.querySelector('.book-modal');
        if (existingModal) existingModal.remove();

        const modal = document.createElement('div');
        modal.className = 'book-modal modal';
        modal.innerHTML = `
            <div class="modal-content">
                <span class="close">&times;</span>
                <div class="book-details">
                    <div class="book-cover">
                        <img src="${book.cover_image || '../assets/images/default-cover.jpg'}" 
                             alt="${book.title}"
                             onerror="this.src='../assets/images/default-cover.jpg'">
                    </div>
                    <div class="book-info">
                        <h2>${book.title}</h2>
                        <p class="author">By ${book.author}</p>
                        
                        <div class="book-metadata">
                            <div class="metadata-row">
                                <div class="metadata-item">
                                    <i class="fas fa-star"></i>
                                    <strong>Rating:</strong>
                                    <span>${book.average_rating}/5</span>
                                    (${book.total_ratings} ratings)
                                </div>
                                <div class="metadata-item">
                                    <i class="fas fa-book"></i>
                                    <strong>Genre:</strong>
                                    <span>${book.genre}</span>
                                </div>
                            </div>
                            <div class="metadata-row">
                                <div class="metadata-item">
                                    <i class="fas fa-calendar"></i>
                                    <strong>Published:</strong>
                                    <span>${book.publication_year}</span>
                                </div>
                                <div class="metadata-item">
                                    <i class="fas fa-file-alt"></i>
                                    <strong>Format:</strong>
                                    <span>${book.file_type.toUpperCase()}</span>
                                </div>
                            </div>
                        </div>

                        <div class="book-description">
                            <h3>Description</h3>
                            <p>${book.description || 'No description available.'}</p>
                        </div>

                        <div class="modal-actions">
                            ${this.getBookActions(book)}
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        // Add event listeners
        const closeBtn = modal.querySelector('.close');
        closeBtn.onclick = () => modal.remove();

        window.onclick = (event) => {
            if (event.target === modal) {
                modal.remove();
            }
        };
    }

    static getBookActions(book) {
        const isLoggedIn = !!localStorage.getItem('authToken');
        if (!isLoggedIn) {
            return `
                <p class="login-prompt">
                    <a href="sign-in.html">Sign in</a> to add this book to your reading list
                </p>
            `;
        }

        return `
            <button class="primary-btn read-btn" onclick="BookComponent.startReading('${book.id}')">
                <i class="fas fa-book-reader"></i>
                Read Now
            </button>
            <button class="secondary-btn add-to-list-btn" onclick="BookComponent.addToReadingList('${book.id}')">
                <i class="fas fa-plus"></i>
                Add to List
            </button>
        `;
    }

    static async startReading(bookId) {
        // Implement reading functionality
        window.location.href = `reader.html?id=${bookId}`;
    }

    static async addToReadingList(bookId) {
        try {
            const response = await fetch('../api/reading-list.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=add&book_id=${bookId}`
            });

            if (!response.ok) throw new Error('Failed to add book to reading list');
            
            const text = await response.text();
            if (text.startsWith('ERROR:')) {
                throw new Error(text.substring(6));
            }

            alert('Book added to your reading list!');
        } catch (error) {
            console.error('Error adding book to reading list:', error);
            alert('Failed to add book to reading list. Please try again later.');
        }
    }
} 