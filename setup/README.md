# BookHub Setup Guide

This directory contains all the necessary files and instructions to set up the BookHub application on a new system.

## Prerequisites

1. PHP 7.4 or higher
2. MySQL 5.7 or higher
3. Apache/Nginx web server
4. PDO PHP extension enabled
5. GD PHP extension (for image handling)

## Directory Structure

```
setup/
├── database/
│   ├── init.sql       # Database initialization script
│   └── setup.php      # Database setup script
├── sample_data/       # Sample books and cover images
└── README.md         # This file
```

## Setup Instructions

1. **Database Setup**

   a. Configure your database credentials in `database/setup.php`:
   ```php
   $DB_HOST = 'localhost';
   $DB_USERNAME = 'root';
   $DB_PASSWORD = '';
   $DB_NAME = 'bookhub';
   ```

   b. Run the setup script:
   ```bash
   php database/setup.php
   ```

   This will:
   - Create the database if it doesn't exist
   - Create all necessary tables
   - Insert sample data
   - Create required directories for books and cover images

2. **Default Admin Account**
   After setup, you can log in with these credentials:
   - Username: admin
   - Password: admin123

   **Important:** Change the admin password after first login!

3. **File Permissions**
   Ensure these directories are writable by your web server:
   ```
   assets/images/covers/
   assets/books/pdfs/
   ```

4. **Sample Data**
   The setup includes 10 sample books with descriptions. You'll need to:
   - Add actual PDF files to `assets/books/pdfs/`
   - Add cover images to `assets/images/covers/`

## Database Structure

The setup creates the following tables:

1. `users` - User accounts and authentication
2. `books` - Book information and metadata
3. `reading_list` - User's reading lists (want to read, currently reading, completed)
4. `reading_progress` - Tracks reading progress for each user/book
5. `ratings` - Book ratings and reviews
6. `login_attempts` - Tracks login attempts for security
7. `user_activity_log` - Logs user activities

## Troubleshooting

1. **Database Connection Issues**
   - Verify MySQL is running
   - Check database credentials
   - Ensure PDO extension is enabled

2. **Directory Permission Issues**
   - Check web server user has write permissions
   - Verify directory paths are correct

3. **Sample Data Issues**
   - Ensure cover images are in correct format (JPG/PNG)
   - Verify PDF files are valid and readable

## Security Notes

1. Change the default admin password immediately
2. Update database credentials in production
3. Set appropriate file permissions
4. Configure proper error reporting in production

## Support

For issues or questions:
1. Check the troubleshooting section
2. Review error logs
3. Contact system administrator 