<?php
// Test script for adding a client user

require_once 'config/database.php';
require_once 'includes/JWT.php';

// --- Test Setup ---
$jwt_secret = 'urzATLOjDrDdX4sgFUNSgUPYcx3L9u1AqtK81tWaoO0=';

// Payload for a client admin user (role_id = 2)
// Assuming entity_id = 1 for 'Test Client Company' and user_id = 2 for 'client_admin'
$payload = [
    'user_id' => 2,
    'role_id' => 2,
    'entity_type' => 'client',
    'entity_id' => 1,
    'exp' => time() + 3600 // Token expires in 1 hour
];

// Override the JWT secret for testing
class TestJWT extends JWT {
    public static function getTestSecret() {
        return 'urzATLOjDrDdX4sgFUNSgUPYcx3L9u1AqtK81tWaoO0=';
    }

    public static function encode($payload) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $header_encoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $payload_encoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payload)));
        $signature = hash_hmac('sha256', $header_encoded . "." . $payload_encoded, self::getTestSecret(), true);
        $signature_encoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        return $header_encoded . "." . $payload_encoded . "." . $signature_encoded;
    }
}

$token = TestJWT::encode($payload);

// Data for the new user
$newUser = [
    'email' => 'test.user@example.com',
    'first_name' => 'Test',
    'last_name' => 'User',
    'phone' => '1234567890',
    'role_id' => 1 // Reporting Employee
];

// --- cURL Request ---
$url = 'http://localhost/backend/api/client-users.php?token=' . $token;
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($newUser));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// --- Output ---
echo "--- Test Add User ---\\n";
echo "HTTP Status Code: " . $httpcode . "\\n";
echo "Response: " . $response . "\\n";

// --- Verification and Cleanup ---
if ($httpcode == 200) {
    echo "\\n--- Test PASSED ---\\n";
    $responseData = json_decode($response, true);
    if (isset($responseData['user']['id'])) {
        $userId = $responseData['user']['id'];
        echo "User created with ID: " . $userId . "\\n";
        
        // Now, let's delete the user
        $url = 'http://localhost/backend/api/client-users.php?token=' . $token . '&user_id=' . $userId;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        $deleteResponse = curl_exec($ch);
        $deleteHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        echo "\\n--- Test Cleanup (Delete User) ---\\n";
        echo "HTTP Status Code: " . $deleteHttpCode . "\\n";
        echo "Response: " . $deleteResponse . "\\n";
        if ($deleteHttpCode == 200) {
            echo "\\n--- Cleanup PASSED ---\\n";
        } else {
            echo "\\n--- Cleanup FAILED ---\\n";
        }
    }
} else {
    echo "\\n--- Test FAILED ---\\n";
}

?>
