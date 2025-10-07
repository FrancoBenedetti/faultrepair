<?php
// Simple test script to call service provider profile API

// Manual token generation for testing
require_once 'backend/includes/JWT.php';

$payload = [
    'user_id' => 6, // quickfix-admin userId
    'role_id' => 4,
    'entity_type' => 'service_provider',
    'entity_id' => 3, // QuickFix participantId
    'iat' => time(),
    'exp' => time() + 86400
];

$token = JWT::encode($payload);
echo "Got token: " . $token . "\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://fault-reporter.local/backend/api/service-provider-profile.php?token=' . $token);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$profile_response = curl_exec($ch);
echo "Profile API response:\n";
echo $profile_response . "\n";

curl_close($ch);

// Test service-providers API
$ch2 = curl_init();
curl_setopt($ch2, CURLOPT_URL, 'http://fault-reporter.local/backend/api/service-providers.php');
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);

$providers_response = curl_exec($ch2);
echo "\nService Providers API response:\n";
echo $providers_response . "\n";

curl_close($ch2);
?>
