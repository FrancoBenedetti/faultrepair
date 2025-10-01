#!/bin/bash

# SSL Setup Script for Fault Reporter
# Generates self-signed certificates and enables SSL for camera access

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

DOMAIN="fault-reporter.local"
SSL_DIR="/etc/ssl"
CERT_FILE="$SSL_DIR/certs/$DOMAIN.crt"
KEY_FILE="$SSL_DIR/private/$DOMAIN.key"
APACHE_SSL_CONF="/etc/apache2/sites-available/$DOMAIN-ssl.conf"

echo -e "${BLUE}🔒 Setting up SSL for $DOMAIN${NC}"
echo "====================================="

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}❌ Please run with sudo${NC}"
    exit 1
fi

# Check if Apache is installed
if ! command -v apache2ctl &> /dev/null; then
    echo -e "${RED}❌ Apache2 not found${NC}"
    exit 1
fi

# Enable SSL module
echo -e "${YELLOW}Enabling SSL module...${NC}"
a2enmod ssl
a2enmod headers

# Create SSL directory if it doesn't exist
mkdir -p "$SSL_DIR/certs" "$SSL_DIR/private"

# Generate self-signed certificate
echo -e "${YELLOW}Generating self-signed SSL certificate...${NC}"
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout "$KEY_FILE" \
    -out "$CERT_FILE" \
    -subj "/C=ZA/ST=Gauteng/L=JHB/O=Fault Reporter/CN=$DOMAIN"

# Set proper permissions
chmod 600 "$KEY_FILE"
chmod 644 "$CERT_FILE"

echo -e "${GREEN}✅ SSL certificate generated${NC}"

# Copy SSL configuration
echo -e "${YELLOW}Setting up Apache SSL configuration...${NC}"
cp apache-config-ssl.conf "$APACHE_SSL_CONF"

# Enable SSL site
a2ensite "$DOMAIN-ssl.conf"

# Test configuration
echo -e "${YELLOW}Testing Apache configuration...${NC}"
if apache2ctl configtest; then
    echo -e "${GREEN}✅ Apache configuration valid${NC}"
else
    echo -e "${RED}❌ Apache configuration error${NC}"
    exit 1
fi

# Restart Apache
echo -e "${YELLOW}Restarting Apache...${NC}"
systemctl restart apache2

if systemctl is-active --quiet apache2; then
    echo -e "${GREEN}✅ Apache restarted successfully${NC}"
else
    echo -e "${RED}❌ Apache failed to restart${NC}"
    exit 1
fi

# Test SSL
echo -e "${YELLOW}Testing SSL connection...${NC}"
sleep 2

if curl -k -s --head --fail "https://$DOMAIN" > /dev/null 2>&1; then
    echo -e "${GREEN}✅ SSL setup successful!${NC}"
    echo ""
    echo -e "${BLUE}==================================================${NC}"
    echo -e "${GREEN}🎉 HTTPS is now enabled for $DOMAIN${NC}"
    echo -e "${BLUE}==================================================${NC}"
    echo ""
    echo -e "${YELLOW}Access your application at:${NC}"
    echo -e "${GREEN}https://$DOMAIN${NC}"
    echo ""
    echo -e "${YELLOW}Note: You'll see a security warning in your browser${NC}"
    echo -e "${YELLOW}because this is a self-signed certificate.${NC}"
    echo -e "${YELLOW}Click 'Advanced' → 'Proceed to $DOMAIN (unsafe)'${NC}"
    echo ""
    echo -e "${YELLOW}Camera access should now work for QR scanning!${NC}"
else
    echo -e "${RED}❌ SSL test failed${NC}"
    echo "Check Apache logs: sudo tail -f /var/log/apache2/fault-reporter-ssl_error.log"
fi
