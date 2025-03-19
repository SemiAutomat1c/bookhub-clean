# BookHub - Digital Book Management System

BookHub is a web-based digital book management system that allows users to organize, read, and track their digital book collection.

## Features

- 📚 Digital book library management
- 📖 Built-in PDF reader
- 📋 Reading progress tracking
- 📑 Reading lists (Want to read, Currently reading, Completed)
- 🔍 Advanced search functionality
- 👤 User profiles and authentication
- 🌓 Dark/Light mode support
- 📱 Responsive design

## Prerequisites

1. PHP 7.4 or higher
2. MySQL 5.7 or higher
3. Apache/Nginx web server
4. PDO PHP extension enabled
5. GD PHP extension (for image handling)

## Project Structure

```
bookhub/
├── api/                  # API endpoints
├── assets/              # Static assets (CSS, images)
│   ├── css/            # Stylesheets
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
   - Navigate to the setup directory
   - Configure database credentials in `database/setup.php`
   - Run the setup script:
     ```bash
     php database/setup.php
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

## Default Admin Account

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