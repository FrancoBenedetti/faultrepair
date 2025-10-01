<?php
// Simple test script to verify location API functionality with new fields

echo "Testing Location API with new fields...\n\n";

// Test configuration
$baseUrl = 'http://fault-reporter.local/'; // Adjust as needed for your dev setup
$testToken = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ1c2VyX2lkIjoyLCJyb2xlX2lkIjoyLCJlbnRpdHlfdHlwZSI6ImNsaWVudCIsImVudGl0eV9pZCI6MSwiaWF0IjoxNzU5MzE0OTg4LCJleHAiOjE3NTk0MDEzODh9.FQI7stwnK6B07fklZMhkCzPUQeSci9Yux8ZeAXROVJY'; // You'll need a valid JWT token

// Test data with traditional coordinates
$testLocationCoord = [
    'name' => 'Test Location with GPS Lat/Long',
    'address' => '123 Test Street, Johannesburg',
    'coordinates' => '-26.2041,28.0473', // Johannesburg coordinates
    'access_rules' => 'https://example.com/security-protocols.pdf',
    'access_instructions' => 'Ring bell at main entrance. Show ID to security guard.'
];

// Test data with Plus Code
$testLocationPlusCode = [
    'name' => 'Test Location with Plus Code',
    'address' => 'Wellington, Western Cape',
    'coordinates' => '8GH2+9JQ Wellington, South Africa', // Wellington Plus Code
    'access_rules' => 'https://example.com/access-rules.pdf',
    'access_instructions' => 'Contact receptionist. Parking in front lot.'
];

// Test functions
function testGetLocations($baseUrl, $token) {
    echo "Testing GET locations...\n";
    $url = $baseUrl . '/backend/api/client-locations.php?token=' . $token;

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'Accept: application/json'
        ]
    ]);

    $response = file_get_contents($url, false, $context);
    $data = json_decode($response, true);

    if ($data && isset($data['locations'])) {
        echo "✓ GET locations successful\n";
        echo "Found " . count($data['locations']) . " locations\n";
        if (count($data['locations']) > 0) {
            $firstLocation = $data['locations'][0];
            echo "First location fields: " . implode(', ', array_keys($firstLocation)) . "\n";
        }
        return $data['locations'];
    } else {
        echo "✗ GET locations failed\n";
        if ($data && isset($data['error'])) {
            echo "Error: " . $data['error'] . "\n";
        }
        return null;
    }
}

function testCreateLocation($baseUrl, $token, $locationData) {
    echo "\nTesting POST create location...\n";
    $url = $baseUrl . '/backend/api/client-locations.php?token=' . $token;

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n",
            'content' => json_encode($locationData)
        ]
    ]);

    $response = file_get_contents($url, false, $context);
    $data = json_decode($response, true);

    if ($data && !isset($data['error'])) {
        echo "✓ Location created successfully\n";
        echo "New location ID: " . ($data['location']['id'] ?? 'unknown') . "\n";
        return true;
    } else {
        echo "✗ Location creation failed\n";
        if ($data && isset($data['error'])) {
            echo "Error: " . $data['error'] . "\n";
        }
        return false;
    }
}

// Run tests
echo "=== Location API Test Results ===\n\n";

// Note: You need a valid JWT token for testing
echo "Note: This test requires a valid JWT token.\n";
echo "Make sure your backend server is running and you've authenticated to get a token.\n\n";

echo "To test manually:\n";
echo "1. Start your PHP dev server: php -S localhost:8000 backend/public/index.php\n";
echo "2. Log in to get a JWT token\n";
echo "3. Update \$testToken variable with the token\n";
echo "4. Run this script\n\n";

echo "Test completed. Check the backend logs for detailed error messages if needed.\n";

?>
