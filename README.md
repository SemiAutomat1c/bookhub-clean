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
   git clone https://github.com/yourusername/bookhub.git
   cd bookhub
   ```

2. **Database Setup**
   - Configure database credentials in `config/database.php`
   - Run the setup script:
     ```bash
     php setup/database/setup.php
     ```

3. **File Permissions**
   Ensure these directories are writable:
   ```
   assets/images/covers/
   assets/books/pdfs/
   ```

4. **Web Server Configuration**
   - Configure your web server to point to the project directory
   - Ensure `.htaccess` is properly configured for URL rewriting

5. **Default Admin Account**
   After installation, you can log in with:
   - Username: admin
   - Password: admin123
   **Important:** Change the admin password after first login!

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

## Acknowledgments

- All the contributors who participated in this project
- Open source libraries used in development
- The reading community for inspiration and feedback 