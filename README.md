# Equipment Fault Reporting System

A comprehensive web-based fault reporting system designed for small businesses and home owners with appliances and catering equipment. The system facilitates seamless fault reporting, job assignment to service providers, and complete repair tracking through a defined workflow.

## 🎯 Overview

This application provides a complete solution for equipment maintenance and repair management with the following key features:

- **Dual User System**: Separate interfaces for clients (who report faults) and service providers (who fix faults)
- **Role-Based Access Control**: Comprehensive user roles with specific permissions
- **QR Code Integration**: Scan equipment QR codes for instant fault reporting
- **Mobile-Optimized**: Fully functional on mobile devices for field technicians
- **Image Support**: Upload photos of faults for better documentation
- **Workflow Management**: Complete job lifecycle tracking with status updates
- **Multi-Location Support**: Organizations can manage multiple physical locations

## 🏗️ Architecture

This is a full-stack web application built with:

- **Backend**: PHP (vanilla, no frameworks) REST API
- **Frontend**: Vue.js 3 single-page application
- **Database**: MariaDB/MySQL relational database
- **Deployment**: Standard LAMP stack

## 👥 User Roles

### Client Side

#### **Reporting Employee**

- Create and submit fault reports
- View own submitted reports
- Edit reports before assignment
- Upload fault images

#### **Site Budget Controller** ⭐

- All Reporting Employee permissions
- Acts as initial user for new client organizations
- Manage team members (add Reporting Employees)
- Assign/reassign service providers
- Approve job completion
- View comprehensive reports

### Service Provider Side

#### **Service Provider Admin** ⭐

- Manage company technicians
- View all jobs assigned to company
- Assign jobs to specific technicians
- Update job statuses
- View comprehensive reports

#### **Technician**

- View assigned jobs only
- Add repair notes
- Update job status (In Progress → Repaired/Not Repairable)
- Mobile-optimized interface

## 🔄 Job Workflow

Jobs progress through these statuses:

1. **Reported** → Client reports fault
2. **Assigned** → Service provider assigned by Budget Controller
3. **In Progress** → Technician starts work
4. **Quote Provided** → Optional quotation stage
5. **Quote Accepted** → Client approves quotation
6. **Repaired** → Technician completes repair
7. **Payment Requested** → Service provider requests payment
8. **Completed** → Budget Controller confirms completion
9. **Declined** → Service provider rejects job (edge case)

## 📱 Technology Stack

### Backend

- **PHP 8.0+**: Server-side scripting
- **MariaDB/MySQL**: Relational database
- **PDO**: Database abstraction layer
- **JWT**: Authentication tokens
- **Custom REST API**: No external frameworks

### Frontend

- **Vue.js 3**: Progressive framework
- **Vue Router 4**: Client-side routing
- **Axios**: HTTP client
- **TailwindCSS**: Utility-first styling
- **Vite**: Build tool and dev server
- **html5-qrcode**: QR scanning
- **Material Symbols**: Icon library

### Infrastructure

- **Apache/Nginx**: Web server
- **Standard LAMP Stack**: Linux, Apache, MySQL, PHP

## 🚀 Key Features

- ✅ User registration and email verification
- ✅ JWT-based authentication
- ✅ Role-based access control
- ✅ QR code scanning for equipment identification
- ✅ Image upload for fault documentation
- ✅ Real-time job status updates
- ✅ Technician management system
- ✅ Multi-location support
- ✅ Mobile-responsive design
- ✅ Audit trails and status history
- ✅ Password reset functionality

## 📁 Project Structure

```
fault-reporter/
├── backend/               # PHP REST API
│   ├── api/              # API endpoints
│   ├── config/           # Database and site configuration
│   ├── includes/         # Shared utilities (JWT, email)
│   ├── public/           # Web server document root
│   └── uploads/          # File uploads (job images)
├── frontend/             # Vue.js SPA
│   ├── src/
│   │   ├── views/        # Page components
│   │   ├── components/   # Reusable components
│   │   ├── utils/        # API utilities
│   │   └── router/       # Routing configuration
│   └── public/           # Static assets
├── public/               # Production frontend build
├── database/             # Database files
│   ├── schema.sql        # Database schema
│   └── database-update.sql # Schema updates
├── setup-*.sh            # Setup scripts (Apache, SSL)
└── README.md             # This file
```

## 🛠️ Quick Start

### Prerequisites

- PHP 8.0+ with PDO MySQL extension
- MariaDB/MySQL 5.7+
- Node.js 16+ and npm
- Apache/Nginx web server

### Installation

1. **Database Setup**
   
   ```bash
   # Create database and run schema
   mysql -u root -p < schema.sql
   # Optional updates
   mysql -u root -p < database-update.sql
   ```

2. **Backend Configuration**
   
   ```bash
   cd backend/config
   cp database.php.example database.php
   cp site.php.example site.php
   # Edit files with your credentials
   ```

3. **Frontend Setup**
   
   ```bash
   cd frontend
   npm install
   npm run build
   ```

4. **Web Server Configuration**
   
   - Configure virtual host pointing to `backend/public/`
   - Enable URL rewriting
   - Set up SSL (optional but recommended)

### Development

```bash
# Start frontend dev server
cd frontend
npm run dev

# Backend runs directly via web server
# Configure Apache/Nginx to serve backend/public/
```

## 🔧 API Endpoints

### Authentication

- `POST /backend/api/auth.php` - User login
- `POST /backend/api/register-client.php` - Client registration
- `POST /backend/api/register-service-provider.php` - Provider registration

### Job Management

- `GET /backend/api/client-jobs.php` - List client jobs
- `POST /backend/api/client-jobs.php` - Create new job
- `PUT /backend/api/client-jobs.php` - Update job
- `GET /backend/api/technician-jobs.php` - List technician jobs

### User Management

- `GET /backend/api/technicians.php` - List technicians
- `POST /backend/api/technicians.php` - Create technician
- `PUT /backend/api/technicians.php` - Update technician
- `DELETE /backend/api/technicians.php` - Delete technician

## 🔒 Security

- **Password Hashing**: bcrypt via PHP password_hash()
- **JWT Authentication**: Stateless token-based auth
- **Input Validation**: Server-side validation on all endpoints
- **SQL Injection Prevention**: Prepared statements with PDO
- **CORS Configuration**: Controlled cross-origin requests
- **Role-Based Permissions**: Comprehensive access control

## 📊 Database Schema

Key tables:

- `users` - Authentication and role management
- `clients` - Client company information
- `service_providers` - Provider company information
- `jobs` - Core fault reports and status
- `job_status_history` - Complete audit trail
- `technicians` - Service provider employees
- `locations` - Client physical locations

## 🚀 Deployment

### Production Setup

1. Configure production database
2. Update site configuration files
3. Build frontend: `npm run build`
4. Copy frontend build to `public/`
5. Configure web server for SSL
6. Set up file permissions for uploads

### Environment Variables

- Database credentials
- JWT secret key
- Site URL and email settings
- File upload paths

## 🤝 Contributing

1. Follow established code patterns
2. Implement proper error handling
3. Add comprehensive comments
4. Test on multiple browsers/devices
5. Update documentation as needed

## 📝 License

This project is proprietary software. All rights reserved.

## 📞 Support

For technical support or questions:

- Check the backend/README.md and frontend/README.md
- Review setup documentation in setup-*.sh files
- Test endpoints using provided curl examples

---

**Built for reliability, designed for efficiency.**
