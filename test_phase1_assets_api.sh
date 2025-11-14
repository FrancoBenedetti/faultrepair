#!/bin/bash

# ==============================================================================
# Test Script for Phase 1 of the Asset Manager Feature
#
# This script uses curl to test the backend API endpoints created in Phase 1.
# It dynamically fetches JWT tokens before running the tests.
# ==============================================================================

# --- CONFIGURATION ---
BASE_URL="http://snappy.local/backend/api" # Adjust if your local URL is different

# --- User Credentials ---
CLIENT_ADMIN_EMAIL="franco+smarty@benedetti.co.za"
SP_ADMIN_EMAIL="simple1@simon.co"
PASSWORD="password"

# --- Entity IDs ---
CLIENT_ID="3"
APPROVED_SP_ID="14"
UNAPPROVED_SP_ID="5"
DIFFERENT_CLIENT_ID="1" # An ID for a client the SP is not approved for

# --- SCRIPT STATE ---
CLIENT_ADMIN_TOKEN=""
SP_ADMIN_TOKEN=""
ASSET_ID_TO_UPDATE=""

# --- SCRIPT HELPERS ---

# Function to get a JWT token for a user
# Usage: get_token "user@email.com" "password"
function get_token() {
    local email=$1
    local password=$2
    
    local response=$(curl -s -X POST \
        -H "Content-Type: application/json" \
        -d "{\"email\": \"$email\", \"password\": \"$password\"}" \
        "$BASE_URL/auth.php")
    
    # Extract token using jq
    local token=$(echo "$response" | jq -r '.token')
    
    if [ -z "$token" ] || [ "$token" == "null" ]; then
        echo "Error: Failed to get JWT token for $email." >&2
        echo "Response: $response" >&2
        return 1
    else
        echo "$token"
        return 0
    fi
}

# Function to make a request and print the results
# Usage: make_request "Test Description" "HTTP_METHOD" "URL" "TOKEN" "JSON_DATA"
function make_request() {
    local description=$1
    local method=$2
    local url=$3
    local token=$4
    local data=$5

    echo "----------------------------------------------------------------------" >&2
    echo "▶️  TEST: $description" >&2
    echo "▶️  $method $url" >&2
    if [ -n "$data" ]; then
        echo "▶️  DATA: $data" >&2
    fi
    echo >&2

    local body_file
    body_file=$(mktemp)
    
    local curl_opts=()
    curl_opts+=(-s)
    curl_opts+=(-o "$body_file")
    curl_opts+=(-w "%{http_code}")
    curl_opts+=(-X "$method")
    curl_opts+=(-H "Authorization: Bearer $token")

    if [ -n "$data" ]; then
        curl_opts+=(-H "Content-Type: application/json")
        curl_opts+=(-d "$data")
    fi

    http_status=$(curl "${curl_opts[@]}" "$url")
    
    body=$(cat "$body_file")
    rm "$body_file"

    echo "STATUS: $http_status" >&2
    echo "RESPONSE:" >&2
    # The jq pretty-print is removed to prevent stdout/stderr contamination.
    # The raw response body will be printed to stdout by the final printf.
    echo "----------------------------------------------------------------------" >&2
    echo >&2
    
    printf "%s\n" "$body"
}

# --- TEST EXECUTION ---

echo "--- SETUP: Fetching JWT tokens ---"
CLIENT_ADMIN_TOKEN=$(get_token "$CLIENT_ADMIN_EMAIL" "$PASSWORD")
if [ $? -ne 0 ]; then exit 1; fi
echo "Client Admin Token OK."

SP_ADMIN_TOKEN=$(get_token "$SP_ADMIN_EMAIL" "$PASSWORD")
if [ $? -ne 0 ]; then exit 1; fi
echo "SP Admin Token OK."

echo

echo "Starting Asset Manager API Tests..."

#
# 1. POST /api/assets (Create Assets)
#
echo "--- 1. TESTING ASSET CREATION (POST) ---"
# Test 1.1: Client Admin creates an asset
make_request "Client Admin creates an asset (SUCCESS)" \
    "POST" \
    "$BASE_URL/assets.php" \
    "$CLIENT_ADMIN_TOKEN" \
    '{"asset_no": "TEST-001", "item": "Laptop"}'

# Test 1.2: SP Admin creates an asset for an approved client
make_request "SP Admin creates an asset for approved client (SUCCESS)" \
    "POST" \
    "$BASE_URL/assets.php" \
    "$SP_ADMIN_TOKEN" \
    "{\"asset_no\": \"TEST-SP-001\", \"item\": \"Water Pump\", \"client_id\": \"$CLIENT_ID\"}"

# Test 1.3: SP Admin tries to create an asset for an unapproved client
make_request "SP Admin creates asset for unapproved client (FAILURE)" \
    "POST" \
    "$BASE_URL/assets.php" \
    "$SP_ADMIN_TOKEN" \
    "{\"asset_no\": \"TEST-FAIL-001\", \"item\": \"Generator\", \"client_id\": \"$DIFFERENT_CLIENT_ID\"}"

# Test 1.4: Request missing required 'item' field
make_request "Create asset with missing 'item' field (FAILURE)" \
    "POST" \
    "$BASE_URL/assets.php" \
    "$CLIENT_ADMIN_TOKEN" \
    '{"asset_no": "TEST-FAIL-002"}'

# Test 1.5: Create a duplicate asset
make_request "Create a duplicate asset (FAILURE)" \
    "POST" \
    "$BASE_URL/assets.php" \
    "$CLIENT_ADMIN_TOKEN" \
    '{"asset_no": "TEST-001", "item": "Laptop"}'


#
# 2. GET /api/assets (List Assets)
#
echo "--- 2. TESTING ASSET LISTING (GET) ---"
make_request "Client Admin lists assets (SUCCESS)" \
    "GET" \
    "$BASE_URL/assets.php?client_id=$CLIENT_ID" \
    "$CLIENT_ADMIN_TOKEN"

make_request "SP Admin lists assets for approved client (SUCCESS)" \
    "GET" \
    "$BASE_URL/assets.php?client_id=$CLIENT_ID" \
    "$SP_ADMIN_TOKEN"

make_request "SP Admin lists for unapproved client (FAILURE)" \
    "GET" \
    "$BASE_URL/assets.php?client_id=$DIFFERENT_CLIENT_ID" \
    "$SP_ADMIN_TOKEN"

make_request "List assets with missing client_id (FAILURE)" \
    "GET" \
    "$BASE_URL/assets.php" \
    "$CLIENT_ADMIN_TOKEN"


#
# 3. PUT /api/assets/{id} (Update Assets)
#
echo "--- 3. TESTING ASSET UPDATES (PUT) ---"
echo "Creating a temporary asset for update/delete tests..."
response_body=$(make_request "Create asset for PUT/DELETE test" "POST" "$BASE_URL/assets.php" "$CLIENT_ADMIN_TOKEN" '{"asset_no": "TO-UPDATE-001", "item": "Temporary Item"}')
ASSET_ID_TO_UPDATE=$(echo "$response_body" | jq -r '.asset_id' | tr -d '[:space:]')
echo "Created asset with ID: $ASSET_ID_TO_UPDATE"

if [ -n "$ASSET_ID_TO_UPDATE" ] && [ "$ASSET_ID_TO_UPDATE" != "null" ]; then
    make_request "Client Admin updates own asset (SUCCESS)" \
        "PUT" \
        "$BASE_URL/assets.php?id=$ASSET_ID_TO_UPDATE" \
        "$CLIENT_ADMIN_TOKEN" \
        '{"description": "This item has been updated."}'

    make_request "SP Admin updates client's asset (FAILURE)" \
        "PUT" \
        "$BASE_URL/assets.php?id=$ASSET_ID_TO_UPDATE" \
        "$SP_ADMIN_TOKEN" \
        '{"description": "SP trying to update."}'
else
    echo "Skipping PUT tests because asset creation for test failed."
fi


#
# 4. DELETE /api/assets/{id} (Delete Assets)
#
echo "--- 4. TESTING ASSET DELETION (DELETE) ---"
if [ -n "$ASSET_ID_TO_UPDATE" ] && [ "$ASSET_ID_TO_UPDATE" != "null" ]; then
    make_request "SP Admin deletes client's asset (FAILURE)" \
        "DELETE" \
        "$BASE_URL/assets.php?id=$ASSET_ID_TO_UPDATE" \
        "$SP_ADMIN_TOKEN"

    make_request "Client Admin deletes own asset (SUCCESS)" \
        "DELETE" \
        "$BASE_URL/assets.php?id=$ASSET_ID_TO_UPDATE" \
        "$CLIENT_ADMIN_TOKEN"

    make_request "Delete a non-existent asset (FAILURE)" \
        "DELETE" \
        "$BASE_URL/assets.php?id=$ASSET_ID_TO_UPDATE" \
        "$CLIENT_ADMIN_TOKEN"
else
    echo "Skipping DELETE tests because asset creation for test failed."
fi


#
# 5. GET Helper Endpoints
#
echo "--- 5. TESTING HELPER ENDPOINTS (GET) ---"
make_request "SP Admin gets client locations (SUCCESS)" \
    "GET" \
    "$BASE_URL/client-locations.php?client_id=$CLIENT_ID" \
    "$SP_ADMIN_TOKEN"

make_request "SP Admin gets client administrators (SUCCESS)" \
    "GET" \
    "$BASE_URL/client-administrators.php?client_id=$CLIENT_ID" \
    "$SP_ADMIN_TOKEN"

make_request "Client Admin gets their approved providers (SUCCESS)" \
    "GET" \
    "$BASE_URL/client-approved-providers.php" \
    "$CLIENT_ADMIN_TOKEN"


echo "All tests completed."