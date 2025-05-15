# BookHub - Digital Reading Companion

BookHub is a comprehensive web-based digital book management system that allows users to organize, read, and track their digital book collection while engaging with a community of readers.

## Features

### Core Features
- 📚 Digital book library management
- 📖 Built-in PDF reader
- 📋 Reading progress tracking
- 📑 Reading lists (Want to read, Currently reading, Completed)
- 🔍 Advanced search functionality
- 👤 User profiles and authentication
- 🌓 Dark/Light mode support
- 📱 Responsive design

### User Management
- User registration and authentication
- Profile management
- Role-based access control (Admin/User)
- Secure password reset functionality
- Login attempt monitoring

### Book Management
- Extensive book catalog with detailed information
- Book search and filtering by genre
- Cover image display
- PDF file integration for reading
- Book metadata including title, author, genre, and publication year

### Reading Experience
- Online PDF reader integration
- Progress synchronization
- Page tracking
- Reading session logging
- Customizable reading interface
- Reading streak monitoring
- Last read position memory

### Community Features
- Book ratings and reviews
- Average rating display
- Review management
- Popular books showcase
- Reading statistics

### Admin Features
- User management
- Book catalog management
- Content moderation
- Activity monitoring
- System statistics

## Technical Requirements

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- PDO PHP extension
- GD PHP extension (for image handling)

### Database Structure
- Users and authentication
- Books and metadata
- Reading lists and progress
- Ratings and reviews
- Activity logging
- Security monitoring

## Project Structure

```
bookhub/
├── api/                  # API endpoints
├── assets/              # Static assets (CSS, images)
│   ├── css/            # Stylesheets
│   ├── js/             # JavaScript files
│   ├── images/         # Images and icons
│   └── books/          # Book files storage
├── config/             # Configuration files
├── middleware/         # Authentication middleware
├── setup/              # Setup and installation files
├── src/               # Source code
│   ├── js/           # JavaScript files
│   └── routes/       # Route definitions
└── views/            # HTML views
```

## Installation

1. **Clone the Repository**
   ```bash
   git clone https://github.com/SemiAutomat1c/bookhub-clean.git
   cd bookhub-clean
   ```

2. **Configure Environment**
   * Copy `.env.example` to `.env` and update the database credentials
   * Make sure PHP and MySQL are installed and running

3. **Database Setup**
   - Configure database credentials in `config/database.php`
   - Run the setup script:
     ```bash
     php setup/database/setup.php
     ```

4. **File Permissions**
   Ensure these directories are writable:
   ```
   assets/images/covers/
   assets/books/pdfs/
   ```

5. **Web Server Configuration**
   - Configure your web server to point to the project directory
   - Ensure `.htaccess` is properly configured for URL rewriting
   - Set the base path in `index.php` to match your server configuration

6. **Default Admin Account**
   After installation, you can log in with:
   - Username: admin
   - Password: admin123
   **Important:** Change the admin password after first login!

## Fixed Issues

The following issues have been fixed in this version:

1. Duplicate PHP code blocks in multiple files (config.php, database.php, index.php, routes/web.php)
2. Inconsistent database connection methods (standardized using both PDO and mysqli)
3. Missing constant definitions (DB_HOST, DB_USERNAME, etc.)
4. Incorrect path references in error handlers
5. Created missing files (404.html, unauthorized.html, AuthMiddleware.php)
6. Updated route paths to use the correct project name
7. Fixed profile page reading statistics display issues
8. Resolved compatibility issues between reading_list and reading_lists database tables
9. Enhanced error handling in the activity and stats API endpoints
10. Implemented cross-table compatibility for reading lists to ensure consistent user experience
11. Added debugging tools for database inspection (debug_db.php, api/debug.php)
12. Improved data synchronization between different views (profile page and reading list)

## Database Structure Notes

### Reading List Tables

BookHub maintains compatibility with two table structures for reading lists:

1. **reading_list** (singular) - The primary table with structure:
   ```
   - list_id (INT, Primary Key)
   - user_id (INT, Foreign Key)
   - book_id (INT, Foreign Key)
   - list_type (ENUM: 'want-to-read', 'currently-reading', 'completed')
   - progress (INT)
   - created_at (TIMESTAMP)
   - updated_at (TIMESTAMP)
   ```

2. **reading_lists** (plural) - The legacy table with structure:
   ```
   - list_id (INT, Primary Key)
   - user_id (INT, Foreign Key)
   - book_id (INT, Foreign Key)
   - list_type (ENUM: 'want-to-read', 'currently-reading', 'completed')
   - progress (INT)
   - added_at (TIMESTAMP)
   - last_updated (TIMESTAMP)
   ```

The application now handles data operations across both tables for complete compatibility, ensuring a smooth user experience regardless of which table structure is active in your installation.

## Security Features

- Secure password hashing
- Session management
- CORS protection
- XSS prevention
- SQL injection protection
- Input validation
- Rate limiting for login attempts
- CSRF protection

## Development

The project uses:
- PHP for backend
- MySQL for database
- Vanilla JavaScript for frontend
- CSS3 with custom properties for theming
- PDF.js for PDF rendering

## Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For issues or questions:
1. Check the documentation
2. Review error logs
3. Open an issue on GitHub

## Recent Updates

### May 2024
- **Profile Page Improvements**: Fixed user reading statistics display and activity tracking
- **Reading List Compatibility**: Implemented cross-table compatibility between reading_list and reading_lists
- **API Enhancements**: Improved error handling and data validation in activity.php and stats.php
- **Debugging Tools**: Added database inspection tools for troubleshooting
- **Data Synchronization**: Enhanced data flow between different views of the application

## Acknowledgments

- All the contributors who participated in this project
- Open source libraries used in development
- The reading community for inspiration and feedback 