# Testing Frontend-Backend Interaction

This guide provides comprehensive instructions for testing the Fault Reporter application's frontend-backend integration.

## Prerequisites

Before testing, ensure you have:

1. **Database Setup**:
   - MySQL server running
   - Database created using `schema.sql`
   - Database connection configured in `backend/config/database.php`

2. **PHP Environment**:
   - PHP 7.4+ installed
   - PDO MySQL extension enabled
   - Web server (Apache/Nginx) or PHP built-in server

3. **Node.js Environment**:
   - Node.js 16+ installed
   - npm or yarn package manager

## Environment Setup

### 1. Start Backend Server

#### Option A: Using PHP Built-in Server
```bash
cd backend
php -S localhost:8000
```
Backend will be available at `http://localhost:8000`

#### Option B: Using Apache/Nginx
Configure your web server to serve the `backend` directory and ensure PHP is enabled.

### 2. Start Frontend Development Server

```bash
cd frontend
npm install
npm run dev
```
Frontend will be available at `http://localhost:5173` (Vite default)

### 3. Verify CORS Configuration

Ensure your backend endpoints include proper CORS headers for frontend communication.

## Testing Methods

### Method 1: Browser-Based Testing

#### Step 1: Access the Application
1. Open browser and navigate to `http://localhost:5173`
2. You should see the Fault Reporter home page with client and service provider sections

#### Step 2: Test Client Registration
1. Click "Register as Client"
2. Fill out the registration form:
   - Company Name: "Test Company"
   - Address: "123 Test Street"
   - Username: "testclient"
   - Email: "client@test.com"
   - Password: "password123"
3. Click "Register"
4. **Expected Result**: Success message and redirect to client sign-in page

#### Step 3: Test Client Authentication
1. On the sign-in page, enter:
   - Username: "testclient"
   - Password: "password123"
2. Click "Sign In"
3. **Expected Result**: JWT token stored in localStorage (check browser dev tools)

#### Step 4: Test Service Provider Registration
1. Return to home page
2. Click "Register as Service Provider"
3. Fill out the form:
   - Company Name: "Test Services Inc"
   - Address: "456 Service Avenue"
   - Username: "testprovider"
   - Email: "provider@test.com"
   - Password: "password123"
4. Click "Register"
5. **Expected Result**: Success message and redirect to service provider sign-in

#### Step 5: Test Service Provider Authentication
1. Sign in with the service provider credentials
2. **Expected Result**: Successful authentication

### Method 2: Browser Developer Tools

#### Inspect Network Requests
1. Open browser developer tools (F12)
2. Go to Network tab
3. Perform registration/authentication actions
4. **Verify**:
   - Request URL matches backend endpoint
   - Request method is POST
   - Content-Type header is "application/json"
   - Request body contains correct JSON data
   - Response status is 200 for success
   - Response contains expected JSON data

#### Check Local Storage
1. In developer tools, go to Application/Storage > Local Storage
2. **Verify**: JWT token is stored after successful authentication

#### Monitor Console
1. Check Console tab for JavaScript errors
2. **Verify**: No CORS errors, network errors, or JavaScript exceptions

### Method 3: Command-Line Testing with cURL

#### Test Client Registration
```bash
curl -X POST http://localhost:8000/api/register-client.php \
  -H "Content-Type: application/json" \
  -d '{
    "clientName": "CLI Test Company",
    "address": "789 CLI Street",
    "username": "clitest",
    "email": "cli@test.com",
    "password": "password123"
  }'
```

**Expected Response:**
```json
{"message": "Client registered successfully"}
```

#### Test Service Provider Registration
```bash
curl -X POST http://localhost:8000/api/register-service-provider.php \
  -H "Content-Type: application/json" \
  -d '{
    "providerName": "CLI Test Services",
    "address": "321 CLI Avenue",
    "username": "cliprovider",
    "email": "cli@provider.com",
    "password": "password123"
  }'
```

**Expected Response:**
```json
{"message": "Service provider registered successfully"}
```

#### Test Authentication
```bash
curl -X POST http://localhost:8000/api/auth.php \
  -H "Content-Type: application/json" \
  -d '{
    "username": "clitest",
    "password": "password123"
  }'
```

**Expected Response:**
```json
{"token": "jwt_token_here"}
```

### Method 4: Database Verification

#### Check Database Records
After successful registration, verify database contains new records:

```sql
-- Check clients table
SELECT * FROM clients WHERE name = 'CLI Test Company';

-- Check service_providers table
SELECT * FROM service_providers WHERE name = 'CLI Test Services';

-- Check users table
SELECT * FROM users WHERE username IN ('clitest', 'cliprovider');
```

**Expected Results:**
- Client record in `clients` table
- Service provider record in `service_providers` table
- User records in `users` table with correct `entity_type` and `entity_id`

## Testing Scenarios

### Positive Test Cases

1. **Valid Registration**: All required fields provided, valid email
2. **Valid Authentication**: Correct username/password combination
3. **Unique Credentials**: No duplicate usernames or emails
4. **Proper Redirects**: Successful registration redirects to sign-in

### Negative Test Cases

#### Test Invalid Registration Data
```bash
# Missing required fields
curl -X POST http://localhost:8000/api/register-client.php \
  -H "Content-Type: application/json" \
  -d '{"username": "test"}'

# Expected: 400 Bad Request
```

#### Test Duplicate Registration
```bash
# Try to register same user twice
curl -X POST http://localhost:8000/api/register-client.php \
  -H "Content-Type: application/json" \
  -d '{
    "clientName": "Duplicate Company",
    "address": "123 Duplicate St",
    "username": "clitest",
    "email": "cli@test.com",
    "password": "password123"
  }'

# Expected: 409 Conflict
```

#### Test Invalid Authentication
```bash
curl -X POST http://localhost:8000/api/auth.php \
  -H "Content-Type: application/json" \
  -d '{
    "username": "nonexistent",
    "password": "wrongpass"
  }'

# Expected: 401 Unauthorized
```

#### Test Invalid Email Format
```bash
curl -X POST http://localhost:8000/api/register-client.php \
  -H "Content-Type: application/json" \
  -d '{
    "clientName": "Test Company",
    "address": "123 Test St",
    "username": "testuser",
    "email": "invalid-email",
    "password": "password123"
  }'

# Expected: 400 Bad Request
```

## Automated Testing Script

Create a test script `test-integration.sh`:

```bash
#!/bin/bash

BASE_URL="http://localhost:8000/api"

echo "Testing Fault Reporter Integration..."

# Test client registration
echo "1. Testing client registration..."
CLIENT_RESPONSE=$(curl -s -X POST $BASE_URL/register-client.php \
  -H "Content-Type: application/json" \
  -d '{
    "clientName": "Auto Test Company",
    "address": "123 Auto Street",
    "username": "autotest",
    "email": "auto@test.com",
    "password": "password123"
  }')

if echo "$CLIENT_RESPONSE" | grep -q "Client registered successfully"; then
    echo "✅ Client registration: PASSED"
else
    echo "❌ Client registration: FAILED"
    echo "Response: $CLIENT_RESPONSE"
fi

# Test authentication
echo "2. Testing authentication..."
AUTH_RESPONSE=$(curl -s -X POST $BASE_URL/auth.php \
  -H "Content-Type: application/json" \
  -d '{
    "username": "autotest",
    "password": "password123"
  }')

if echo "$AUTH_RESPONSE" | grep -q "token"; then
    echo "✅ Authentication: PASSED"
else
    echo "❌ Authentication: FAILED"
    echo "Response: $AUTH_RESPONSE"
fi

echo "Integration testing complete!"
```

Make it executable and run:
```bash
chmod +x test-integration.sh
./test-integration.sh
```

## Troubleshooting Common Issues

### CORS Errors
**Symptom**: Browser console shows CORS-related errors
**Solution**:
- Ensure backend endpoints include CORS headers
- Check if backend server is running on correct port
- Verify frontend is making requests to correct backend URL

### Database Connection Errors
**Symptom**: Registration/authentication fails with database errors
**Solution**:
- Verify database credentials in `backend/config/database.php`
- Ensure MySQL server is running
- Check database exists and tables are created
- Verify PDO extension is enabled

### PHP Errors
**Symptom**: 500 Internal Server Error
**Solution**:
- Check PHP error logs
- Verify all required PHP extensions are installed
- Ensure file permissions are correct
- Check syntax errors in PHP files

### Frontend Build Issues
**Symptom**: Frontend not loading or showing errors
**Solution**:
- Run `npm install` to ensure dependencies are installed
- Check for JavaScript console errors
- Verify Vite dev server is running
- Clear browser cache and reload

### Network Issues
**Symptom**: Requests failing or timing out
**Solution**:
- Verify both servers are running on correct ports
- Check firewall settings
- Ensure no proxy interfering with requests
- Test with simple curl commands first

## Performance Testing

### Load Testing with Apache Bench
```bash
# Test authentication endpoint with 100 requests, 10 concurrent
ab -n 100 -c 10 -p auth_data.json -T application/json http://localhost:8000/api/auth.php
```

### Memory Usage Monitoring
Monitor PHP memory usage during testing:
```bash
# Check PHP memory limit
php -r "echo 'Memory limit: ' . ini_get('memory_limit') . PHP_EOL;"

# Monitor processes
ps aux | grep php
```

## Security Testing

### Test Input Validation
- Try SQL injection attempts
- Test XSS payloads
- Verify password requirements
- Check email validation

### Test Rate Limiting
- Make multiple rapid requests
- Verify no DoS vulnerabilities
- Check for proper error responses

## Logging and Monitoring

### Enable PHP Error Logging
```php
// Add to PHP files for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/path/to/error.log');
```

### Monitor Database Queries
```sql
-- Enable query logging in MySQL
SET GLOBAL general_log = 'ON';
SET GLOBAL general_log_file = '/var/log/mysql/mysql.log';
```

## Continuous Integration

For automated testing in CI/CD:

```yaml
# .github/workflows/test.yml
name: Integration Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.0'
      - name: Setup MySQL
        run: |
          sudo systemctl start mysql
          mysql -u root -e "CREATE DATABASE faultreporter;"
      - name: Run tests
        run: ./test-integration.sh
```

This comprehensive testing guide ensures you can thoroughly validate the frontend-backend integration of your Fault Reporter application.
