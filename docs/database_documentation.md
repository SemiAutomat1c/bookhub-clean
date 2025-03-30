# BookHub Database Documentation

## Entity-Relationship Diagram (ERD)

The BookHub database consists of seven main entities with their relationships:

### Core Entities
1. **Users** (Central entity)
2. **Books** (Core content)
3. **Reading Lists** (Junction between Users and Books)
4. **Reading Progress** (Tracking reading status)
5. **Reviews** (User feedback)
6. **User Preferences** (User settings)
7. **Reading Sessions** (Reading time tracking)

### Entity Relationships

#### Users Entity Relationships
- One User → Many Reading Lists (1:N)
- One User → Many Reading Progress Records (1:N)
- One User → Many Reviews (1:N)
- One User → One User Preferences (1:1)
- One User → Many Reading Sessions (1:N)

#### Books Entity Relationships
- One Book → Many Reading Lists (1:N)
- One Book → Many Reading Progress Records (1:N)
- One Book → Many Reviews (1:N)
- One Book → Many Reading Sessions (1:N)

## Database Schema

### 1. Users Table
Primary table for user management and authentication.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| user_id | INT | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| username | VARCHAR(50) | NOT NULL, UNIQUE | User's display name |
| email | VARCHAR(100) | NOT NULL, UNIQUE | User's email address |
| password_hash | VARCHAR(255) | NOT NULL | Hashed password |
| full_name | VARCHAR(100) | NOT NULL | User's full name |
| is_admin | BOOLEAN | DEFAULT FALSE | Admin status flag |
| last_login | TIMESTAMP | NULL DEFAULT NULL | Last login timestamp |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation time |
| updated_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE | Last update time |

**Additional Constraints:**
- Email format validation using REGEXP

### 2. Books Table
Stores book information and metadata.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| book_id | INT | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| title | VARCHAR(255) | NOT NULL | Book title |
| author | VARCHAR(100) | NOT NULL | Book author |
| description | TEXT | | Book description |
| genre | VARCHAR(50) | | Book genre |
| publication_year | INT | | Year published |
| cover_image | VARCHAR(255) | | Path to cover image |
| file_path | VARCHAR(255) | NOT NULL | Path to book file |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation time |
| updated_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE | Last update time |

**Indexes:**
- idx_title (title)
- idx_author (author)
- idx_genre (genre)

### 3. Reading Lists Table
Junction table managing user's book collections.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| reading_list_id | INT | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| user_id | INT | NOT NULL, FOREIGN KEY | Reference to users |
| book_id | INT | NOT NULL, FOREIGN KEY | Reference to books |
| status | ENUM | NOT NULL | Reading status |
| progress | INT | DEFAULT 0 | Reading progress % |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation time |
| updated_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE | Last update time |

**Additional Constraints:**
- Progress range check (0-100)
- Unique user-book combination
- CASCADE on delete for both foreign keys

### 4. Reading Progress Table
Detailed reading progress tracking.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| progress_id | INT | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| user_id | INT | NOT NULL, FOREIGN KEY | Reference to users |
| book_id | INT | NOT NULL, FOREIGN KEY | Reference to books |
| current_page | INT | NOT NULL | Current page number |
| total_pages | INT | NOT NULL | Total pages in book |
| last_read_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Last reading time |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation time |
| updated_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE | Last update time |

**Additional Constraints:**
- Page number validation
- CASCADE on delete for both foreign keys

### 5. Reviews Table
User reviews and ratings for books.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| review_id | INT | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| user_id | INT | NOT NULL, FOREIGN KEY | Reference to users |
| book_id | INT | NOT NULL, FOREIGN KEY | Reference to books |
| rating | INT | NOT NULL | Book rating (1-5) |
| review_text | TEXT | | Review content |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation time |
| updated_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE | Last update time |

**Additional Constraints:**
- Rating range check (1-5)
- Unique user-book review combination
- CASCADE on delete for both foreign keys

### 6. User Preferences Table
User interface and notification preferences.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| preference_id | INT | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| user_id | INT | NOT NULL, FOREIGN KEY | Reference to users |
| theme_preference | ENUM | DEFAULT 'light' | UI theme choice |
| font_size | VARCHAR(20) | DEFAULT 'medium' | Text size preference |
| notification_enabled | BOOLEAN | DEFAULT TRUE | Notifications toggle |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation time |
| updated_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE | Last update time |

**Additional Constraints:**
- Unique user preferences
- CASCADE on delete for user_id

### 7. Reading Sessions Table
Reading time tracking.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| session_id | INT | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| user_id | INT | NOT NULL, FOREIGN KEY | Reference to users |
| book_id | INT | NOT NULL, FOREIGN KEY | Reference to books |
| start_time | TIMESTAMP | NOT NULL | Session start time |
| end_time | TIMESTAMP | | Session end time |
| duration_minutes | INT | | Session duration |
| created_at | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Record creation time |

**Additional Constraints:**
- Session time validation
- CASCADE on delete for both foreign keys

## Database Views

### 1. User Reading Statistics (vw_user_reading_stats)
Provides aggregated reading statistics per user:
- Total books in reading list
- Completed books count
- Currently reading count
- Want to read count

### 2. Popular Books (vw_popular_books)
Aggregates book popularity metrics:
- Total number of readers
- Number of completed readers
- Average rating

## Database Functions

### 1. Calculate Reading Progress
```sql
fn_calculate_reading_progress(user_id, book_id)
```
Returns the current reading progress for a specific user and book.

### 2. Get Reading Streak
```sql
fn_get_reading_streak(user_id)
```
Calculates the user's reading streak over the last 30 days.

## Stored Procedures

### 1. Update Reading Status
```sql
sp_update_reading_status(user_id, book_id, progress, status)
```
Updates or creates a reading list entry for a user and book.

### 2. Get Book Recommendations
```sql
sp_get_book_recommendations(user_id, limit)
```
Returns book recommendations based on user's completed books and genres. 