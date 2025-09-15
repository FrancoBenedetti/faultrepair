# Apache2 Setup for Fault Reporter Integration Testing

This guide provides step-by-step instructions for setting up Apache2 virtual host to serve both frontend and backend from the same domain for functional testing on ZorinOS 17.3.

## Prerequisites

- ZorinOS 17.3 with Apache2 installed
- PHP 7.4+ with PDO MySQL extension
- MySQL/MariaDB server
- Node.js 16+ for building frontend
- Database created using `schema.sql`

## Step 1: Project Structure Setup

Create a directory structure for Apache hosting:

```bash
# Create web root directory
sudo mkdir -p /var/www/fault-reporter

# Copy project files
sudo cp -r /home/franco/Projects/fault-reporter/* /var/www/fault-reporter/

# Set proper ownership
sudo chown -R www-data:www-data /var/www/fault-reporter
sudo chmod -R 755 /var/www/fault-reporter
```

## Step 2: Build Frontend for Production

```bash
cd /var/www/fault-reporter/frontend

# Install dependencies
npm install

# Build for production
npm run build
```

This creates a `dist/` directory with optimized frontend files.

## Step 3: Configure Apache Virtual Host

Create virtual host configuration:

```bash
sudo nano /etc/apache2/sites-available/fault-reporter.conf
```

Add the following configuration:

```apache
<VirtualHost *:80>
    ServerName fault-reporter.local
    ServerAlias www.fault-reporter.local
    DocumentRoot /var/www/fault-reporter

    # Enable PHP processing
    <FilesMatch \.php$>
        SetHandler application/x-httpd-php
    </FilesMatch>

    # Frontend (serve built files)
    Alias / /var/www/fault-reporter/frontend/dist/
    <Directory /var/www/fault-reporter/frontend/dist>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Backend API
    Alias /backend /var/www/fault-reporter/backend
    <Directory /var/www/fault-reporter/backend>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted

        # Enable PHP for backend
        php_value include_path "/var/www/fault-reporter/backend"
    </Directory>

    # API endpoints
    Alias /api /var/www/fault-reporter/backend/api
    <Directory /var/www/fault-reporter/backend/api>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Logs
    ErrorLog ${APACHE_LOG_DIR}/fault-reporter_error.log
    CustomLog ${APACHE_LOG_DIR}/fault-reporter_access.log combined

    # Security headers
    <IfModule mod_headers.c>
        Header always set X-Content-Type-Options nosniff
        Header always set X-Frame-Options DENY
        Header always set X-XSS-Protection "1; mode=block"
    </IfModule>
</VirtualHost>
```

## Step 4: Enable Virtual Host

```bash
# Enable the site
sudo a2ensite fault-reporter.conf

# Disable default site (optional)
sudo a2dissite 000-default.conf

# Enable required modules
sudo a2enmod rewrite
sudo a2enmod headers
sudo a2enmod alias

# Restart Apache
sudo systemctl restart apache2
```

## Step 5: Configure Hosts File

Add local domain to hosts file:

```bash
sudo nano /etc/hosts
```

Add this line:
```
127.0.0.1    fault-reporter.local www.fault-reporter.local
```

## Step 6: Database Configuration

Update database configuration for production:

```bash
sudo nano /var/www/fault-reporter/backend/config/database.php
```

Ensure it points to your MySQL database:

```php
<?php
$host = 'localhost';
$dbname = 'faultreporter';
$username = 'your_db_user';
$password = 'your_db_password';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed");
}
?>
```

## Step 7: Update Frontend API URLs

Since frontend and backend are on the same domain, update API calls to use relative URLs:

```bash
# The frontend should already work with relative URLs like:
/backend/api/auth.php
/backend/api/register-client.php
```

If you need to update any absolute URLs in the frontend code, change them to relative paths.

## Step 8: Set Proper Permissions

```bash
# Ensure proper permissions
sudo chown -R www-data:www-data /var/www/fault-reporter
sudo find /var/www/fault-reporter -type f -exec chmod 644 {} \;
sudo find /var/www/fault-reporter -type d -exec chmod 755 {} \;

# Special permissions for backend directories that might need write access
sudo chmod 775 /var/www/fault-reporter/backend
```

## Step 9: Test Apache Configuration

```bash
# Test configuration syntax
sudo apache2ctl configtest

# If syntax is OK, restart Apache
sudo systemctl restart apache2

# Check Apache status
sudo systemctl status apache2
```

## Step 10: Functional Testing

### Access the Application

Open browser and navigate to: `http://fault-reporter.local`

### Test User Flows

#### Client Registration Flow:
1. Click "Register as Client"
2. Fill form: Company Name, Address, Username, Email, Password
3. Click "Register"
4. **Expected**: Success message and redirect to sign-in
5. Sign in with credentials
6. **Expected**: Successful authentication

#### Service Provider Registration Flow:
1. Click "Register as Service Provider"
2. Fill form: Company Name, Address, Username, Email, Password
3. Click "Register"
4. **Expected**: Success message and redirect to sign-in
5. Sign in with credentials
6. **Expected**: Successful authentication

### Verify Database Records

Check that registrations create proper records:

```sql
-- Connect to MySQL
mysql -u your_user -p faultreporter

-- Check clients
SELECT * FROM clients ORDER BY created_at DESC LIMIT 5;

-- Check service providers
SELECT * FROM service_providers ORDER BY created_at DESC LIMIT 5;

-- Check users
SELECT u.username, u.email, u.entity_type, u.entity_id,
       CASE WHEN u.entity_type = 'client' THEN c.name ELSE sp.name END as company_name
FROM users u
LEFT JOIN clients c ON u.entity_type = 'client' AND u.entity_id = c.id
LEFT JOIN service_providers sp ON u.entity_type = 'service_provider' AND u.entity_id = sp.id
ORDER BY u.created_at DESC LIMIT 10;
```

## Troubleshooting

### Common Issues

#### 1. 403 Forbidden Error
```bash
# Check permissions
ls -la /var/www/fault-reporter

# Fix permissions
sudo chown -R www-data:www-data /var/www/fault-reporter
sudo chmod -R 755 /var/www/fault-reporter
```

#### 2. PHP Files Not Executing
```bash
# Check if PHP module is enabled
sudo a2enmod php8.1  # or your PHP version
sudo systemctl restart apache2

# Verify PHP configuration
php -v
```

#### 3. Database Connection Errors
```bash
# Test database connection
mysql -u your_user -p -e "SELECT 1;"

# Check MySQL service
sudo systemctl status mysql

# Check database exists
mysql -u your_user -p -e "SHOW DATABASES;"
```

#### 4. CORS Issues
Since frontend and backend are on the same domain, CORS shouldn't be an issue. If you encounter CORS errors:

```apache
# Add to virtual host configuration
<IfModule mod_headers.c>
    Header set Access-Control-Allow-Origin "*"
    Header set Access-Control-Allow-Methods "GET, POST, OPTIONS"
    Header set Access-Control-Allow-Headers "Content-Type, Authorization"
</IfModule>
```

#### 5. Frontend Not Loading
```bash
# Check if dist directory exists
ls -la /var/www/fault-reporter/frontend/dist/

# Rebuild frontend if needed
cd /var/www/fault-reporter/frontend
npm run build
```

#### 6. API Endpoints Not Working
```bash
# Test API directly
curl http://fault-reporter.local/api/auth.php

# Check Apache error logs
sudo tail -f /var/log/apache2/fault-reporter_error.log

# Check PHP error logs
sudo tail -f /var/log/apache2/error.log
```

### Debug Commands

```bash
# Check Apache modules
apache2ctl -M

# Check virtual host configuration
apache2ctl -S

# Test PHP processing
echo "<?php phpinfo(); ?>" | sudo tee /var/www/fault-reporter/test.php
# Then visit: http://fault-reporter.local/test.php

# Check file permissions
find /var/www/fault-reporter -type f -name "*.php" -exec ls -la {} \;
```

## Performance Optimization

### Enable Compression
```bash
sudo a2enmod deflate
sudo systemctl restart apache2
```

### Enable Caching
```apache
# Add to virtual host
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType image/png "access plus 1 month"
</IfModule>
```

## Security Considerations

### SSL Configuration (Optional)
```bash
# Enable SSL module
sudo a2enmod ssl

# Get SSL certificate (Let's Encrypt example)
sudo apt install certbot python3-certbot-apache
sudo certbot --apache -d fault-reporter.local

# Update virtual host to use HTTPS
sudo nano /etc/apache2/sites-available/fault-reporter.conf
# Change <VirtualHost *:80> to <VirtualHost *:443>
# Add SSL directives
```

### Database Security
```php
// Use environment variables for database credentials
$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'faultreporter';
$username = getenv('DB_USER') ?: 'fault_user';
$password = getenv('DB_PASS') ?: '';
```

## Monitoring and Logs

```bash
# Monitor Apache access logs
sudo tail -f /var/log/apache2/fault-reporter_access.log

# Monitor error logs
sudo tail -f /var/log/apache2/fault-reporter_error.log

# Check PHP errors
sudo tail -f /var/log/apache2/error.log
```

## Backup and Recovery

```bash
# Backup database
mysqldump -u your_user -p faultreporter > faultreporter_backup.sql

# Backup files
sudo tar -czf faultreporter_backup.tar.gz /var/www/fault-reporter

# Restore database
mysql -u your_user -p faultreporter < faultreporter_backup.sql
```

## Testing Checklist

- [ ] Apache virtual host configured and enabled
- [ ] Domain resolves to localhost
- [ ] Frontend loads at http://fault-reporter.local
- [ ] Client registration form works
- [ ] Service provider registration form works
- [ ] Authentication works for both user types
- [ ] Database records created correctly
- [ ] No CORS errors in browser console
- [ ] No PHP errors in logs
- [ ] Proper error handling for invalid inputs

This setup provides a production-like environment for testing your Fault Reporter application's frontend-backend integration on your ZorinOS development system.
