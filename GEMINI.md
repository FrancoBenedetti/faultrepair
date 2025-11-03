# Project Overview

This is a full-stack web application for reporting and managing equipment faults. It is designed for small businesses and homeowners.

**Key Features:**

*   Dual user system for clients and service providers.
*   Role-based access control.
*   QR code integration for fault reporting.
*   Mobile-optimized interface.
*   Image support for fault documentation.
*   Complete job lifecycle tracking.
*   Multi-location support for organizations.

**Architecture:**

*   **Backend:** PHP (vanilla) REST API.
*   **Frontend:** Vue.js 3 single-page application.
*   **Database:** MariaDB/MySQL.
*   **Deployment:** Standard LAMP stack.

# Building and Running

## Prerequisites

*   PHP 8.0+ with PDO MySQL extension
*   MariaDB/MySQL 5.7+
*   Node.js 16+ and npm
*   Apache/Nginx web server

## Installation

1.  **Database Setup**

    ```bash
    # Create database and run schema
    mysql -u root -p < snappy-dev.sql
    ```

2.  **Backend Configuration**

    ```bash
    cd backend/config
    cp database.php.example database.php
    cp site.php.example site.php
    # Edit files with your credentials
    ```

3.  **Frontend Setup**

    ```bash
    cd frontend
    npm install
    npm run build
    ```

4.  **Web Server Configuration**

    *   Configure virtual host pointing to `backend/public/`
    *   Enable URL rewriting
    *   Set up SSL (optional but recommended)

    Example Apache configuration can be found in `apache-config-example.conf`.

## Development

```bash
# Start frontend dev server
cd frontend
npm run dev

# Backend runs directly via web server
# Configure Apache/Nginx to serve backend/public/
```

To build the application and check for build errors, use the `snappy-build.sh` script. This script builds the frontend and copies the built files and creates symlinks to the backend, snappy-admin, all-logs, and uploads directories into `~/Projects/snappy/public`.

```bash
./snappy-build.sh
```

# Development Conventions

*   **Backend:** The backend is a vanilla PHP REST API. Code should be well-documented and follow modern PHP best practices.
*   **Frontend:** The frontend is a Vue.js 3 application. It uses Vue Router for routing and Axios for HTTP requests. Components should be modular and reusable.
*   **Styling:** TailwindCSS is used for styling.
*   **Security:** The application uses JWT for authentication and has a comprehensive security system. All user input should be validated on the server-side.
*   **Database:** The database schema is defined in `snappy-dev.sql`. Changes to the schema should be made in a new SQL file and applied manually.
