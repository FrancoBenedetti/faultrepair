# Fault Reporter Backend

## Overview

The Fault Reporter backend is a comprehensive PHP-based REST API that provides authentication, registration, and fault management services for a complete fault reporting system. It supports two types of users: clients (who report faults) and service providers (who fix faults), with full job lifecycle management including QR code integration for streamlined fault reporting.

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
- Supports role-based access control with technicians (role 4) and admins (role 3)
- Fields: `id`, `username`, `password_hash`, `email`, `first_name`, `last_name`, `phone`, `role_id`, `entity_type`, `entity_id`, `is_active`, `created_at`

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

### Technician Management

#### `GET /backend/api/technicians.php`
Retrieves all technicians for the authenticated service provider admin.

**Headers:**
```
Authorization: Bearer <jwt_token>
```

**Response:**
```json
{
  "technicians": [
    {
      "id": 1,
      "username": "tech1",
      "email": "tech1@company.com",
      "full_name": "John Doe",
      "first_name": "John",
      "last_name": "Doe",
      "phone": "123-456-7890",
      "is_active": true,
      "created_at": "2025-01-15 10:30:00"
    }
  ]
}
```

#### `POST /backend/api/technicians.php`
Creates a new technician for the authenticated service provider.

**Headers:**
```
Authorization: Bearer <jwt_token>
Content-Type: application/json
```

**Request Body:**
```json
{
  "username": "tech1",
  "password": "password123",
  "email": "tech1@company.com",
  "first_name": "John",
  "last_name": "Doe",
  "phone": "123-456-7890"
}
```

**Response:**
```json
{
  "message": "Technician created successfully",
  "technician_id": 1
}
```

#### `PUT /backend/api/technicians.php`
Updates an existing technician.

**Headers:**
```
Authorization: Bearer <jwt_token>
Content-Type: application/json
```

**Request Body:**
```json
{
  "technician_id": 1,
  "first_name": "John",
  "last_name": "Smith",
  "phone": "123-456-7890",
  "is_active": false
}
```

**Response:**
```json
{
  "message": "Technician updated successfully"
}
```

#### `DELETE /backend/api/technicians.php?id=1`
Deletes a technician (only if they have no assigned jobs).

**Headers:**
```
Authorization: Bearer <jwt_token>
```

**Response:**
```json
{
  "message": "Technician deleted successfully"
}
```

### Client Job Management

#### `GET /backend/api/client-jobs.php`
Retrieves all jobs for the authenticated client with optional filtering.

**Headers:**
```
Authorization: Bearer <jwt_token>
```

**Query Parameters:**
- `status`: Filter by job status (optional)
- `location_id`: Filter by location ID (optional)
- `provider_id`: Filter by service provider ID (optional)
- `user_id`: Filter by reporting user ID (optional)

**Response:**
```json
{
  "jobs": [
    {
      "id": 1,
      "item_identifier": "COMPUTER-001",
      "fault_description": "Screen not working",
      "contact_person": "John Smith",
      "job_status": "Reported",
      "location_name": "Main Office",
      "assigned_provider_name": "Tech Solutions Inc",
      "reporting_user": "jane.doe",
      "created_at": "2025-01-15 09:00:00",
      "updated_at": "2025-01-15 09:00:00",
      "image_count": 2
    }
  ]
}
```

#### `POST /backend/api/client-jobs.php`
Creates a new fault report for the authenticated client.

**Headers:**
```
Authorization: Bearer <jwt_token>
Content-Type: application/json
```

**Request Body:**
```json
{
  "item_identifier": "COMPUTER-001",
  "fault_description": "Screen not working",
  "contact_person": "John Smith",
  "client_location_id": 1
}
```

**Response:**
```json
{
  "success": true,
  "message": "Job created successfully",
  "job_id": 1
}
```

#### `PUT /backend/api/client-jobs.php`
Updates an existing job (role-based permissions apply).

**Headers:**
```
Authorization: Bearer <jwt_token>
Content-Type: application/json
```

**Request Body:**
```json
{
  "job_id": 1,
  "item_identifier": "COMPUTER-001-UPDATED",
  "fault_description": "Screen and keyboard not working",
  "assigned_provider_id": 2,
  "job_status": "Assigned"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Job updated successfully"
}
```

### Technician Jobs

#### `GET /backend/api/technician-jobs.php`
Retrieves all jobs assigned to the authenticated technician.

**Headers:**
```
Authorization: Bearer <jwt_token>
```

**Response:**
```json
{
  "jobs": [
    {
      "id": 1,
      "item_identifier": "EQ-001",
      "fault_description": "Equipment malfunction",
      "technician_notes": "Replaced faulty component",
      "job_status": "Completed",
      "client_name": "ABC Corp",
      "location_name": "Main Office",
      "created_at": "2025-01-15 09:00:00",
      "updated_at": "2025-01-16 14:30:00"
    }
  ],
  "statistics": {
    "total_jobs": 5,
    "active_jobs": 2,
    "completed_jobs": 3
  }
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
│   ├── register-service-provider.php # Service provider registration endpoint
│   ├── technicians.php          # Technician management API
│   ├── technician-jobs.php      # Technician jobs API
│   ├── client-jobs.php          # Client job management (CRUD operations)
│   ├── job-status-update.php    # Job status update endpoint
│   ├── job-images.php           # Job image management
│   ├── upload-job-image.php     # Image upload handling
│   ├── service-providers.php    # Service provider management
│   ├── service-provider-profile.php # Profile management
│   ├── service-provider-approved-clients.php # Client approvals
│   ├── service-provider-client-jobs.php # Client job management
│   ├── client-users.php         # Client user management
│   ├── client-locations.php     # Client location management
│   └── no-includes-test.php     # Testing endpoint
├── config/
│   └── database.php             # Database connection configuration
├── includes/
│   └── JWT.php                  # JWT token handling utilities
├── uploads/
│   └── job_images/              # Uploaded job images storage
└── public/
    └── index.php                # Main entry point
```

## Dependencies

- **PHP 8.0+**: Server-side scripting
- **MariaDB/MySQL 5.7+**: Database server
- **PDO**: PHP Data Objects for database access
- **JWT Library**: Custom JWT implementation
- **Email functionality**: Basic email sending (requires mail server)

## Configuration

### Database Configuration (`config/database.php`)
Create the database connection file:

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

### Site Configuration (`config/site.php`)
Configure site-wide settings:

```php
<?php
define('SITE_URL', 'http://localhost');
define('JWT_SECRET', 'your-secret-key-here');
define('JWT_EXPIRY', 3600); // 1 hour
define('EMAIL_FROM', 'noreply@yourdomain.com');
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

## Additional Features

- **Email Verification**: User email verification for registration
- **Password Reset**: Secure password reset functionality
- **Job Images**: Upload and manage images for fault reports
- **QR Code Integration**: Scan QR codes for equipment identification
- **Client-Service Provider Approvals**: Managed approval system
- **Job Status History**: Complete audit trail of status changes
- **Technician Management**: Full CRUD operations for technicians
- **Multi-location Support**: Clients can have multiple locations

## Setup Instructions

1. **Database Setup**:
   - Create a MySQL/MariaDB database
   - Run `schema.sql` to create tables
   - Optionally run `database-update.sql` for additional updates

2. **Backend Configuration**:
   - Copy `config/database.php` and update with your database credentials
   - Copy `config/site.php` and configure site settings including JWT secret

3. **Server Requirements**:
   - PHP 8.0+ with PDO MySQL extension
   - Apache/Nginx web server
   - Write permissions for `uploads/` directory

4. **Web Server Setup**:
   - Configure Apache virtual host or Nginx server block
   - Ensure `.htaccess` files are processed
   - Point document root to `backend/public/`
   - Allow URL rewriting

5. **Testing**:
   - Use the included test endpoints in `backend/public/test-*.php`
   - Verify API endpoints with Postman or curl

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

# Test technician management (requires JWT token)
curl -X GET http://localhost/backend/api/technicians.php \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"

# Test technician creation
curl -X POST http://localhost/backend/api/technicians.php \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"username":"tech1","password":"password123","email":"tech1@company.com","first_name":"John","last_name":"Doe","phone":"123-456-7890"}'

# Test technician jobs
curl -X GET http://localhost/backend/api/technician-jobs.php \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
