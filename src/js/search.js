// Function to display books in the search results grid
function displayBooks(books) {
    const grid = document.getElementById('search-results-grid');
    if (!grid) return;

    // Clear existing content
    grid.innerHTML = '';

    if (!books || books.length === 0) {
        grid.innerHTML = `
            <div class="no-results">
                <i class="fas fa-book-open"></i>
                <p>No books found matching your criteria</p>
            </div>
        `;
        return;
    }

    // Display each book
    books.forEach(book => {
        const card = document.createElement('div');
        card.className = 'book-card';
        card.onclick = () => showBookDetails(book);

        card.innerHTML = `
            <img src="${book.cover}" alt="${book.title}" class="book-cover">
            <div class="book-info">
                <h3 class="book-title">${book.title}</h3>
                <p class="book-author">${book.author}</p>
                <div class="book-rating">
                    ${getStarRating(book.rating)}
                </div>
            </div>
        `;

        grid.appendChild(card);
    });
}

// Function to get star rating HTML
function getStarRating(rating) {
    const fullStars = Math.floor(rating);
    const hasHalfStar = rating % 1 >= 0.5;
    let stars = '';
    
    for (let i = 0; i < 5; i++) {
        if (i < fullStars) {
            stars += '<i class="fas fa-star"></i>';
        } else if (i === fullStars && hasHalfStar) {
            stars += '<i class="fas fa-star-half-alt"></i>';
        } else {
            stars += '<i class="far fa-star"></i>';
        }
    }
    
    return stars;
}

// Function to filter books
function filterBooks(books) {
    const genreFilter = document.getElementById('genre-filter').value;
    const yearFilter = document.getElementById('year-filter').value;
    const ratingFilter = document.getElementById('rating-filter').value;

    return books.filter(book => {
        const matchesGenre = !genreFilter || book.genre.toLowerCase() === genreFilter.toLowerCase();
        const matchesYear = !yearFilter || book.published === yearFilter;
        const matchesRating = !ratingFilter || book.rating >= parseInt(ratingFilter);
        return matchesGenre && matchesYear && matchesRating;
    });
}

// Function to handle search
function handleSearch(query) {
    fetch('../books.json')
        .then(response => response.json())
        .then(data => {
            let filteredBooks = data.books;
            
            // Filter by search query
            if (query) {
                query = query.toLowerCase();
                filteredBooks = filteredBooks.filter(book => 
                    book.title.toLowerCase().includes(query) ||
                    book.author.toLowerCase().includes(query) ||
                    book.description.toLowerCase().includes(query)
                );
            }

            // Apply filters
            filteredBooks = filterBooks(filteredBooks);
            
            // Display results
            displayBooks(filteredBooks);
        })
        .catch(error => {
            console.error('Error loading books:', error);
            const grid = document.getElementById('search-results-grid');
            if (grid) {
                grid.innerHTML = `
                    <div class="no-results">
                        <i class="fas fa-exclamation-circle"></i>
                        <p>Sorry, we couldn't load the books right now. Please try again later.</p>
                    </div>
                `;
            }
        });
}

// Initialize search functionality
document.addEventListener('DOMContentLoaded', () => {
    // Get search query from URL if present
    const urlParams = new URLSearchParams(window.location.search);
    const searchQuery = urlParams.get('q');
    
    if (searchQuery) {
        document.querySelector('.search-input').value = searchQuery;
        handleSearch(searchQuery);
    } else {
        handleSearch(''); // Show all books initially
    }

    // Add filter change listeners
    ['genre-filter', 'year-filter', 'rating-filter'].forEach(id => {
        const filter = document.getElementById(id);
        if (filter) {
            filter.addEventListener('change', () => {
                handleSearch(document.querySelector('.search-input').value);
            });
        }
    });

    // Add search input listener
    const searchInput = document.querySelector('.search-input');
    const searchButton = document.querySelector('.search-button');

    if (searchInput && searchButton) {
        searchButton.addEventListener('click', () => {
            handleSearch(searchInput.value);
        });

        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                handleSearch(searchInput.value);
            }
        });
    }
});
