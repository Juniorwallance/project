# Highlights Backend Documentation

This document describes the backend implementation for the highlights functionality of the ScrumLife Rugby Community Platform.

## Overview

The highlights backend provides a complete API for managing rugby video highlights, including CRUD operations, filtering, search, user interactions, and newsletter subscriptions.

## Files Structure

```
├── highlights_api.php          # Main API endpoints
├── highlights_schema.sql       # Database schema
├── setup_highlights.php       # Database setup script
├── admin_highlights.php       # Admin interface
├── highlights.js              # Frontend JavaScript
└── HIGHLIGHTS_BACKEND_README.md # This documentation
```

## Database Schema

### Tables

1. **highlights** - Main highlights table
   - `id` (INT, Primary Key)
   - `title` (VARCHAR) - Highlight title
   - `description` (TEXT) - Highlight description
   - `thumbnail` (VARCHAR) - Thumbnail image URL
   - `video_url` (VARCHAR) - Video URL
   - `duration` (VARCHAR) - Video duration (e.g., "2:45")
   - `categories` (VARCHAR) - Comma-separated categories
   - `tags` (VARCHAR) - Comma-separated tags
   - `featured` (BOOLEAN) - Whether highlight is featured
   - `views` (INT) - View count
   - `likes` (INT) - Like count
   - `posted_date` (TIMESTAMP) - When highlight was posted
   - `created_at` (TIMESTAMP) - Creation timestamp
   - `updated_at` (TIMESTAMP) - Last update timestamp

2. **user_saved_highlights** - User's saved highlights
   - `id` (INT, Primary Key)
   - `user_id` (INT, Foreign Key) - References users.id
   - `highlight_id` (INT, Foreign Key) - References highlights.id
   - `saved_at` (TIMESTAMP) - When highlight was saved

3. **newsletter_subscriptions** - Newsletter subscriptions
   - `id` (INT, Primary Key)
   - `email` (VARCHAR) - Subscriber email
   - `active` (BOOLEAN) - Subscription status
   - `subscribed_at` (TIMESTAMP) - Subscription date
   - `unsubscribed_at` (TIMESTAMP) - Unsubscription date

## API Endpoints

### GET Endpoints

#### `GET /highlights_api.php`
Get all highlights with filtering and pagination.

**Query Parameters:**
- `category` (string) - Filter by category (try, tackle, kick, recent, classic, all)
- `search` (string) - Search in title and description
- `sort` (string) - Sort field (posted_date, views, likes, title)
- `page` (int) - Page number (default: 1)
- `limit` (int) - Items per page (default: 12)

**Response:**
```json
{
  "highlights": [...],
  "totalPages": 5,
  "currentPage": 1,
  "total": 60
}
```

#### `GET /highlights_api.php/{id}`
Get a single highlight by ID and increment view count.

**Response:**
```json
{
  "id": 1,
  "title": "Amazing Try",
  "description": "...",
  "thumbnail": "image.jpg",
  "video_url": "video.mp4",
  "duration": "2:45",
  "categories": "try,recent",
  "tags": "championship",
  "featured": true,
  "views": 1000,
  "likes": 50,
  "posted_date": "2025-01-01 12:00:00"
}
```

#### `GET /highlights_api.php/featured`
Get featured highlights (limit 6).

#### `GET /highlights_api.php/categories`
Get all available categories.

#### `GET /highlights_api.php/statistics`
Get highlight statistics.

### POST Endpoints

#### `POST /highlights_api.php`
Create a new highlight (requires authentication).

**Request Body:**
```json
{
  "title": "Highlight Title",
  "description": "Description",
  "thumbnail": "thumbnail.jpg",
  "video_url": "video.mp4",
  "duration": "2:45",
  "categories": ["try", "recent"],
  "tags": "championship, national",
  "featured": true
}
```

#### `POST /highlights_api.php/{id}/like`
Like a highlight (requires authentication).

#### `POST /highlights_api.php/{id}/save`
Save highlight to user's list (requires authentication).

#### `POST /highlights_api.php/subscribe`
Subscribe to newsletter.

**Request Body:**
```json
{
  "email": "user@example.com"
}
```

#### `POST /highlights_api.php/unsubscribe`
Unsubscribe from newsletter.

### PUT Endpoints

#### `PUT /highlights_api.php/{id}`
Update a highlight (requires authentication).

### DELETE Endpoints

#### `DELETE /highlights_api.php/{id}`
Delete a highlight (requires authentication).

#### `DELETE /highlights_api.php/{id}/save`
Remove highlight from saved list (requires authentication).

## Setup Instructions

1. **Database Setup:**
   ```bash
   # Run the setup script
   php setup_highlights.php
   ```

2. **Configure Database:**
   - Update `db.php` with your database credentials
   - Ensure the `rugby_network` database exists

3. **Test the API:**
   ```bash
   # Test getting highlights
   curl "http://localhost/highlights_api.php"
   
   # Test getting featured highlights
   curl "http://localhost/highlights_api.php/featured"
   ```

## Frontend Integration

The `highlights.js` file provides a complete frontend integration:

- **HighlightsManager class** - Main controller for highlights functionality
- **Filter handling** - Category filtering and search
- **User interactions** - Watch, save, like functionality
- **Newsletter subscription** - Email subscription handling
- **Real-time updates** - Dynamic content loading

## Admin Interface

The `admin_highlights.php` provides a web-based admin interface for:

- Creating new highlights
- Editing existing highlights
- Deleting highlights
- Managing highlight metadata

Access: `http://localhost/admin_highlights.php`

## Features

### Core Features
- ✅ CRUD operations for highlights
- ✅ Category filtering (try, tackle, kick, recent, classic)
- ✅ Search functionality
- ✅ Pagination
- ✅ View tracking
- ✅ Like system
- ✅ Save highlights for users
- ✅ Featured highlights
- ✅ Newsletter subscription

### Advanced Features
- ✅ Responsive design
- ✅ Real-time notifications
- ✅ Error handling
- ✅ Input validation
- ✅ SQL injection protection
- ✅ CORS support
- ✅ Admin interface

## Security Considerations

1. **Authentication** - Currently uses session-based auth (can be upgraded to JWT)
2. **Input Validation** - All inputs are validated and sanitized
3. **SQL Injection** - Uses prepared statements
4. **XSS Protection** - Output is properly escaped
5. **CORS** - Configured for cross-origin requests

## Performance Optimizations

1. **Database Indexes** - Created on frequently queried columns
2. **Pagination** - Limits data transfer
3. **Caching** - Can be implemented for frequently accessed data
4. **Image Optimization** - Thumbnails for faster loading

## Future Enhancements

1. **Video Streaming** - Integration with video streaming services
2. **Advanced Search** - Full-text search with Elasticsearch
3. **User Profiles** - Enhanced user management
4. **Analytics** - Detailed view and engagement analytics
5. **Mobile App** - API ready for mobile applications
6. **Real-time Updates** - WebSocket integration for live updates

## Troubleshooting

### Common Issues

1. **Database Connection Error:**
   - Check database credentials in `db.php`
   - Ensure MySQL server is running
   - Verify database exists

2. **API Not Responding:**
   - Check PHP error logs
   - Verify file permissions
   - Test with simple GET request

3. **Frontend Not Loading:**
   - Check browser console for JavaScript errors
   - Verify `highlights.js` is loaded
   - Test API endpoints directly

### Debug Mode

Enable debug mode by adding to the top of `highlights_api.php`:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## Support

For issues or questions:
1. Check the error logs
2. Test API endpoints individually
3. Verify database connectivity
4. Check browser console for frontend issues