#!/bin/bash

# Fault Reporter Integration Testing Script
# This script tests the frontend-backend integration

# Configuration
BACKEND_URL="http://localhost:8000/api"
FRONTEND_URL="http://localhost:5173"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Test counter
TESTS_RUN=0
TESTS_PASSED=0
TESTS_FAILED=0

# Function to print test results
print_result() {
    local test_name=$1
    local result=$2
    local details=$3

    ((TESTS_RUN++))
    if [ "$result" = "PASS" ]; then
        ((TESTS_PASSED++))
        echo -e "${GREEN}✅ $test_name: PASSED${NC}"
    else
        ((TESTS_FAILED++))
        echo -e "${RED}❌ $test_name: FAILED${NC}"
        if [ -n "$details" ]; then
            echo -e "${RED}   Details: $details${NC}"
        fi
    fi
}

# Function to check if service is running
check_service() {
    local url=$1
    local service_name=$2

    if curl -s --head --fail "$url" > /dev/null 2>&1; then
        echo -e "${GREEN}✅ $service_name is running at $url${NC}"
        return 0
    else
        echo -e "${RED}❌ $service_name is not accessible at $url${NC}"
        return 1
    fi
}

echo -e "${BLUE}🚀 Starting Fault Reporter Integration Tests${NC}"
echo "=================================================="

# Check if services are running
echo -e "\n${YELLOW}Checking service availability...${NC}"

BACKEND_RUNNING=0
FRONTEND_RUNNING=0

if check_service "$BACKEND_URL/auth.php" "Backend API"; then
    BACKEND_RUNNING=1
fi

if check_service "$FRONTEND_URL" "Frontend"; then
    FRONTEND_RUNNING=1
fi

if [ $BACKEND_RUNNING -eq 0 ] || [ $FRONTEND_RUNNING -eq 0 ]; then
    echo -e "\n${RED}❌ Required services are not running. Please start them first:${NC}"
    echo "Backend: cd backend && php -S localhost:8000"
    echo "Frontend: cd frontend && npm run dev"
    exit 1
fi

echo -e "\n${YELLOW}Running API endpoint tests...${NC}"

# Test 1: Client Registration
echo -e "\n${BLUE}Test 1: Client Registration${NC}"
CLIENT_RESPONSE=$(curl -s -w "\nHTTP_STATUS:%{http_code}" -X POST "$BACKEND_URL/register-client.php" \
  -H "Content-Type: application/json" \
  -d '{
    "clientName": "Integration Test Company",
    "address": "123 Integration Street",
    "username": "integration_test_'$(date +%s)'",
    "email": "integration_'$(date +%s)'@test.com",
    "password": "password123"
  }')

CLIENT_HTTP_STATUS=$(echo "$CLIENT_RESPONSE" | grep "HTTP_STATUS:" | cut -d: -f2)
CLIENT_BODY=$(echo "$CLIENT_RESPONSE" | sed '/HTTP_STATUS:/d')

if [ "$CLIENT_HTTP_STATUS" = "200" ] && echo "$CLIENT_BODY" | grep -q "Client registered successfully"; then
    print_result "Client Registration" "PASS"
else
    print_result "Client Registration" "FAIL" "HTTP $CLIENT_HTTP_STATUS: $CLIENT_BODY"
fi

# Test 2: Service Provider Registration
echo -e "\n${BLUE}Test 2: Service Provider Registration${NC}"
PROVIDER_RESPONSE=$(curl -s -w "\nHTTP_STATUS:%{http_code}" -X POST "$BACKEND_URL/register-service-provider.php" \
  -H "Content-Type: application/json" \
  -d '{
    "providerName": "Integration Test Services",
    "address": "456 Integration Avenue",
    "username": "provider_test_'$(date +%s)'",
    "email": "provider_'$(date +%s)'@test.com",
    "password": "password123"
  }')

PROVIDER_HTTP_STATUS=$(echo "$PROVIDER_RESPONSE" | grep "HTTP_STATUS:" | cut -d: -f2)
PROVIDER_BODY=$(echo "$PROVIDER_RESPONSE" | sed '/HTTP_STATUS:/d')

if [ "$PROVIDER_HTTP_STATUS" = "200" ] && echo "$PROVIDER_BODY" | grep -q "Service provider registered successfully"; then
    print_result "Service Provider Registration" "PASS"
else
    print_result "Service Provider Registration" "FAIL" "HTTP $PROVIDER_HTTP_STATUS: $PROVIDER_BODY"
fi

# Test 3: Authentication
echo -e "\n${BLUE}Test 3: Authentication${NC}"
# Use the username from the first test
TEST_USERNAME="integration_test_$(date +%s)"
AUTH_RESPONSE=$(curl -s -w "\nHTTP_STATUS:%{http_code}" -X POST "$BACKEND_URL/auth.php" \
  -H "Content-Type: application/json" \
  -d '{
    "username": "'$TEST_USERNAME'",
    "password": "password123"
  }')

AUTH_HTTP_STATUS=$(echo "$AUTH_RESPONSE" | grep "HTTP_STATUS:" | cut -d: -f2)
AUTH_BODY=$(echo "$AUTH_RESPONSE" | sed '/HTTP_STATUS:/d')

if [ "$AUTH_HTTP_STATUS" = "200" ] && echo "$AUTH_BODY" | grep -q "token"; then
    print_result "Authentication" "PASS"
else
    print_result "Authentication" "FAIL" "HTTP $AUTH_HTTP_STATUS: $AUTH_BODY"
fi

# Test 4: Invalid Authentication
echo -e "\n${BLUE}Test 4: Invalid Authentication${NC}"
INVALID_AUTH_RESPONSE=$(curl -s -w "\nHTTP_STATUS:%{http_code}" -X POST "$BACKEND_URL/auth.php" \
  -H "Content-Type: application/json" \
  -d '{
    "username": "nonexistent_user",
    "password": "wrongpassword"
  }')

INVALID_AUTH_HTTP_STATUS=$(echo "$INVALID_AUTH_RESPONSE" | grep "HTTP_STATUS:" | cut -d: -f2)

if [ "$INVALID_AUTH_HTTP_STATUS" = "401" ]; then
    print_result "Invalid Authentication Handling" "PASS"
else
    print_result "Invalid Authentication Handling" "FAIL" "Expected 401, got $INVALID_AUTH_HTTP_STATUS"
fi

# Test 5: Duplicate Registration Prevention
echo -e "\n${BLUE}Test 5: Duplicate Registration Prevention${NC}"
DUPLICATE_RESPONSE=$(curl -s -w "\nHTTP_STATUS:%{http_code}" -X POST "$BACKEND_URL/register-client.php" \
  -H "Content-Type: application/json" \
  -d '{
    "clientName": "Duplicate Test Company",
    "address": "123 Duplicate Street",
    "username": "integration_test_'$(date +%s)'",
    "email": "integration_'$(date +%s)'@test.com",
    "password": "password123"
  }')

# Register first time
DUPLICATE_HTTP_STATUS=$(echo "$DUPLICATE_RESPONSE" | grep "HTTP_STATUS:" | cut -d: -f2)

if [ "$DUPLICATE_HTTP_STATUS" = "200" ]; then
    # Try to register again with same credentials
    DUPLICATE_RESPONSE2=$(curl -s -w "\nHTTP_STATUS:%{http_code}" -X POST "$BACKEND_URL/register-client.php" \
      -H "Content-Type: application/json" \
      -d '{
        "clientName": "Duplicate Test Company 2",
        "address": "456 Duplicate Street",
        "username": "integration_test_'$(date +%s)'",
        "email": "integration_'$(date +%s)'@test.com",
        "password": "password123"
      }')

    DUPLICATE_HTTP_STATUS2=$(echo "$DUPLICATE_RESPONSE2" | grep "HTTP_STATUS:" | cut -d: -f2)

    if [ "$DUPLICATE_HTTP_STATUS2" = "409" ]; then
        print_result "Duplicate Registration Prevention" "PASS"
    else
        print_result "Duplicate Registration Prevention" "FAIL" "Expected 409, got $DUPLICATE_HTTP_STATUS2"
    fi
else
    print_result "Duplicate Registration Prevention" "FAIL" "First registration failed"
fi

# Test 6: CORS Headers
echo -e "\n${BLUE}Test 6: CORS Headers${NC}"
CORS_RESPONSE=$(curl -s -I -X OPTIONS "$BACKEND_URL/auth.php" \
  -H "Origin: http://localhost:5173" \
  -H "Access-Control-Request-Method: POST")

if echo "$CORS_RESPONSE" | grep -q "Access-Control-Allow-Origin"; then
    print_result "CORS Headers" "PASS"
else
    print_result "CORS Headers" "FAIL" "CORS headers not found"
fi

# Test 7: Input Validation
echo -e "\n${BLUE}Test 7: Input Validation${NC}"
VALIDATION_RESPONSE=$(curl -s -w "\nHTTP_STATUS:%{http_code}" -X POST "$BACKEND_URL/register-client.php" \
  -H "Content-Type: application/json" \
  -d '{
    "clientName": "",
    "address": "",
    "username": "",
    "email": "invalid-email",
    "password": "123"
  }')

VALIDATION_HTTP_STATUS=$(echo "$VALIDATION_RESPONSE" | grep "HTTP_STATUS:" | cut -d: -f2)

if [ "$VALIDATION_HTTP_STATUS" = "400" ]; then
    print_result "Input Validation" "PASS"
else
    print_result "Input Validation" "FAIL" "Expected 400, got $VALIDATION_HTTP_STATUS"
fi

# Summary
echo -e "\n${BLUE}==================================================${NC}"
echo -e "${BLUE}🧪 Test Summary${NC}"
echo -e "${BLUE}==================================================${NC}"
echo "Tests Run: $TESTS_RUN"
echo -e "Tests Passed: ${GREEN}$TESTS_PASSED${NC}"
echo -e "Tests Failed: ${RED}$TESTS_FAILED${NC}"

if [ $TESTS_FAILED -eq 0 ]; then
    echo -e "\n${GREEN}🎉 All tests passed! Frontend-backend integration is working correctly.${NC}"
else
    echo -e "\n${RED}⚠️  Some tests failed. Check the details above and fix any issues.${NC}"
    echo -e "${YELLOW}💡 Common solutions:${NC}"
    echo "   - Ensure database is set up correctly"
    echo "   - Check PHP error logs"
    echo "   - Verify CORS configuration"
    echo "   - Test with browser developer tools"
fi

echo -e "\n${BLUE}📖 For more detailed testing instructions, see TESTING.md${NC}"
echo -e "${BLUE}==================================================${NC}"
