#!/bin/bash

# Fault Reporter Apache2 Setup Script
# This script automates the Apache2 virtual host setup for ZorinOS 17.3

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
PROJECT_DIR="/var/www/fault-reporter"
VHOST_CONF="/etc/apache2/sites-available/fault-reporter.conf"
HOSTS_FILE="/etc/hosts"
DOMAIN="fault-reporter.local"

echo -e "${BLUE}🚀 Fault Reporter Apache2 Setup${NC}"
echo "=================================="

# Function to check if running as root
check_root() {
    if [ "$EUID" -ne 0 ]; then
        echo -e "${RED}❌ Please run this script with sudo${NC}"
        echo "Usage: sudo $0"
        exit 1
    fi
}

# Function to print status
print_status() {
    echo -e "${GREEN}✅ $1${NC}"
}

# Function to print warning
print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

# Function to print error
print_error() {
    echo -e "${RED}❌ $1${NC}"
}

# Check if running as root
check_root

# Check if Apache2 is installed
if ! command -v apache2ctl &> /dev/null; then
    print_error "Apache2 is not installed. Please install it first:"
    echo "sudo apt update && sudo apt install apache2"
    exit 1
fi

# Check if PHP is installed
if ! command -v php &> /dev/null; then
    print_error "PHP is not installed. Please install it first:"
    echo "sudo apt install php libapache2-mod-php php-mysql"
    exit 1
fi

print_status "Prerequisites check passed"

# Create project directory
echo -e "\n${YELLOW}Creating project directory...${NC}"
mkdir -p "$PROJECT_DIR"
cp -r ./* "$PROJECT_DIR/" 2>/dev/null || true
chown -R www-data:www-data "$PROJECT_DIR"
chmod -R 755 "$PROJECT_DIR"
print_status "Project directory created at $PROJECT_DIR"

# Build frontend
echo -e "\n${YELLOW}Building frontend for production...${NC}"
if [ -d "$PROJECT_DIR/frontend" ]; then
    cd "$PROJECT_DIR/frontend"

    # Check if node_modules exists
    if [ ! -d "node_modules" ]; then
        echo "Installing npm dependencies..."
        npm install
    fi

    echo "Building production version..."
    npm run build

    if [ -d "dist" ]; then
        print_status "Frontend built successfully"
    else
        print_error "Frontend build failed"
        exit 1
    fi
else
    print_error "Frontend directory not found"
    exit 1
fi

# Create Apache virtual host configuration
echo -e "\n${YELLOW}Creating Apache virtual host configuration...${NC}"
cat > "$VHOST_CONF" << EOF
<VirtualHost *:80>
    ServerName $DOMAIN
    ServerAlias www.$DOMAIN
    DocumentRoot $PROJECT_DIR

    # Enable PHP processing
    <FilesMatch \.php$>
        SetHandler application/x-httpd-php
    </FilesMatch>

    # Frontend (serve built files)
    Alias / $PROJECT_DIR/frontend/dist/
    <Directory $PROJECT_DIR/frontend/dist>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Backend API
    Alias /backend $PROJECT_DIR/backend
    <Directory $PROJECT_DIR/backend>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted

        # Enable PHP for backend
        php_value include_path "$PROJECT_DIR/backend"
    </Directory>

    # API endpoints
    Alias /api $PROJECT_DIR/backend/api
    <Directory $PROJECT_DIR/backend/api>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # Logs
    ErrorLog \${APACHE_LOG_DIR}/fault-reporter_error.log
    CustomLog \${APACHE_LOG_DIR}/fault-reporter_access.log combined

    # Security headers
    <IfModule mod_headers.c>
        Header always set X-Content-Type-Options nosniff
        Header always set X-Frame-Options DENY
        Header always set X-XSS-Protection "1; mode=block"
    </IfModule>
</VirtualHost>
EOF

print_status "Virtual host configuration created"

# Enable the site
echo -e "\n${YELLOW}Enabling virtual host...${NC}"
a2ensite fault-reporter.conf

# Enable required modules
echo -e "\n${YELLOW}Enabling Apache modules...${NC}"
a2enmod rewrite
a2enmod headers
a2enmod alias

# Enable PHP module (try different versions)
PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
if [ -n "$PHP_VERSION" ]; then
    a2enmod "php$PHP_VERSION" 2>/dev/null || a2enmod php 2>/dev/null || true
fi

print_status "Apache modules enabled"

# Add domain to hosts file
echo -e "\n${YELLOW}Adding domain to hosts file...${NC}"
if ! grep -q "$DOMAIN" "$HOSTS_FILE"; then
    echo "127.0.0.1    $DOMAIN www.$DOMAIN" >> "$HOSTS_FILE"
    print_status "Domain added to hosts file"
else
    print_status "Domain already exists in hosts file"
fi

# Test Apache configuration
echo -e "\n${YELLOW}Testing Apache configuration...${NC}"
if apache2ctl configtest; then
    print_status "Apache configuration is valid"
else
    print_error "Apache configuration has errors"
    exit 1
fi

# Restart Apache
echo -e "\n${YELLOW}Restarting Apache...${NC}"
systemctl restart apache2

if systemctl is-active --quiet apache2; then
    print_status "Apache restarted successfully"
else
    print_error "Failed to restart Apache"
    exit 1
fi

# Create test PHP file
echo -e "\n${YELLOW}Creating test PHP file...${NC}"
cat > "$PROJECT_DIR/test.php" << 'EOF'
<?php
phpinfo();
?>
EOF

chown www-data:www-data "$PROJECT_DIR/test.php"

# Final instructions
echo -e "\n${BLUE}==================================================${NC}"
echo -e "${GREEN}🎉 Apache setup completed successfully!${NC}"
echo -e "${BLUE}==================================================${NC}"
echo ""
echo -e "${YELLOW}Next steps:${NC}"
echo "1. Update database configuration:"
echo "   sudo nano $PROJECT_DIR/backend/config/database.php"
echo ""
echo "2. Access your application:"
echo "   http://$DOMAIN"
echo ""
echo "3. Test PHP processing:"
echo "   http://$DOMAIN/test.php"
echo ""
echo "4. Test the application:"
echo "   - Register as a client"
echo "   - Register as a service provider"
echo "   - Test authentication flows"
echo ""
echo "5. Check logs if issues occur:"
echo "   sudo tail -f /var/log/apache2/fault-reporter_error.log"
echo ""
echo -e "${BLUE}==================================================${NC}"
echo -e "${GREEN}Happy testing! 🚀${NC}"
echo -e "${BLUE}==================================================${NC}"

# Test the setup
echo -e "\n${YELLOW}Testing setup...${NC}"
sleep 2

if curl -s --head --fail "http://$DOMAIN" > /dev/null 2>&1; then
    print_status "Application is accessible at http://$DOMAIN"
else
    print_warning "Application may not be accessible yet. Please check Apache logs."
fi

if curl -s --head --fail "http://$DOMAIN/test.php" > /dev/null 2>&1; then
    print_status "PHP processing is working"
else
    print_warning "PHP processing may not be working. Check PHP module is enabled."
fi
