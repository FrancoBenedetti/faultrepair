# Fault Reporter Backend

## Overview

The Fault Reporter backend is a PHP-based REST API that provides authentication and registration services for a fault reporting system. It supports two types of users: clients (who report faults) and service providers (who fix faults).

## Architecture

The backend follows a simple PHP REST API architecture with:
- **Database Layer**: MySQL database with PDO for database operations
- **API Layer**: PHP endpoints handling HTTP requests
- **Authentication**: JWT-based authentication system
- **Security**: Password hashing, input validation, and CORS support

## Database Schema

### Core Tables

#### `roles`
- Stores user roles in the system
- Pre-populated with: 'Reporting Employee', 'Site Budget Controller', 'Service Provider Admin', 'Technician'

#### `clients`
- Stores client company information
- Fields: `id`, `name`, `address`, `created_at`

#### `service_providers`
- Stores service provider company information
- Fields: `id`, `name`, `address`, `created_at`

#### `users`
- Stores user authentication and profile data
- Links users to either clients or service providers via `entity_type` and `entity_id`
- Fields: `id`, `username`, `password_hash`, `email`, `role_id`, `entity_type`, `entity_id`, `created_at`

#### `locations`
- Stores client locations where faults can occur
- Fields: `id`, `client_id`, `name`, `address`

#### `jobs`
- Main fault reporting table
- Links clients, service providers, and technicians
- Tracks job status and assignments

#### `job_status_history`
- Audit trail for job status changes

#### `client_approved_providers`
- Many-to-many relationship between clients and approved service providers

#### `job_notes`
- Notes and comments on jobs from different users

## API Endpoints

### Authentication

#### `POST /backend/api/auth.php`
Authenticates users and returns JWT token.

**Request Body:**
```json
{
  "username": "string",
  "password": "string"
}
```

**Response:**
```json
{
  "token": "jwt_token_here"
}
```

### Registration

#### `POST /backend/api/register-client.php`
Registers a new client company and creates an admin user.

**Request Body:**
```json
{
  "clientName": "Company Name",
  "address": "Company Address",
  "username": "admin_username",
  "email": "admin@email.com",
  "password": "password123"
}
```

**Response:**
```json
{
  "message": "Client registered successfully"
}
```

#### `POST /backend/api/register-service-provider.php`
Registers a new service provider company and creates an admin user.

**Request Body:**
```json
{
  "providerName": "Service Company Name",
  "address": "Company Address",
  "username": "admin_username",
  "email": "admin@email.com",
  "password": "password123"
}
```

**Response:**
```json
{
  "message": "Service provider registered successfully"
}
```

## Security Features

- **Password Hashing**: Uses PHP's `password_hash()` with default algorithm
- **JWT Authentication**: Stateless authentication with configurable expiration
- **Input Validation**: Server-side validation for all endpoints
- **CORS Support**: Configured for frontend integration
- **SQL Injection Prevention**: Uses prepared statements with PDO

## File Structure

```
backend/
├── api/
│   ├── auth.php                 # User authentication endpoint
│   ├── register-client.php      # Client registration endpoint
│   └── register-service-provider.php # Service provider registration endpoint
├── config/
│   └── database.php             # Database connection configuration
└── includes/
    └── JWT.php                  # JWT token handling utilities
```

## Dependencies

- **PHP 7.4+**: Server-side scripting
- **MySQL 5.7+**: Database server
- **PDO**: PHP Data Objects for database access

## Configuration

### Database Configuration (`config/database.php`)
```php
<?php
$host = 'localhost';
$dbname = 'faultreporter';
$username = 'your_db_user';
$password = 'your_db_password';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
```

## Error Handling

The API uses standard HTTP status codes:
- `200`: Success
- `400`: Bad Request (missing/invalid data)
- `401`: Unauthorized
- `405`: Method Not Allowed
- `409`: Conflict (duplicate data)
- `500`: Internal Server Error

## Future Enhancements

- User profile management endpoints
- Job creation and management APIs
- File upload for job attachments
- Email notifications
- Role-based access control middleware
- API rate limiting
- Comprehensive logging

## Setup Instructions

1. Create MySQL database using `schema.sql`
2. Configure database connection in `config/database.php`
3. Ensure PHP has PDO MySQL extension enabled
4. Make sure the web server can serve PHP files
5. Test endpoints using tools like Postman or curl

## Testing

Example curl commands:

```bash
# Test authentication
curl -X POST http://localhost/backend/api/auth.php \
  -H "Content-Type: application/json" \
  -d '{"username":"testuser","password":"testpass"}'

# Test client registration
curl -X POST http://localhost/backend/api/register-client.php \
  -H "Content-Type: application/json" \
  -d '{"clientName":"Test Company","address":"123 Test St","username":"admin","email":"admin@test.com","password":"password123"}'
