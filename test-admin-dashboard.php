<?php
// Test admin dashboard API
require_once 'backend/includes/JWT.php';

// Test credentials
$username = 'admin';
$password = 'admin123';

// Authenticate and get token (simulate auth.php)
try {
    $pdo = new PDO('mysql:host=localhost;dbname=snappy', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $stmt = $pdo->prepare("
        SELECT u.userId, u.password_hash, u.role_id, u.first_name, u.last_name,
               u.entity_id, pt.participantType,
               CASE
                   WHEN pt.participantType = 'C' THEN 'client'
                   WHEN pt.participantType = 'S' THEN 'service_provider'
               END as entity_type
        FROM users u
        LEFT JOIN participants p ON u.entity_id = p.participantId
        LEFT JOIN participant_type pt ON p.participantId = pt.participantId
        WHERE u.username = ? AND u.is_active = TRUE
    ");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password_hash'])) {
        die("❌ Authentication failed\n");
    }

    $token_payload = [
        'user_id' => $user['userId'],
        'role_id' => (int)$user['role_id'],
        'entity_type' => $user['entity_type'],
        'entity_id' => (int)$user['entity_id'],
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name']
    ];

    $token = JWT::encode($token_payload, 'your-secret-key');
    echo "✅ Admin authenticated successfully\n";

    // Test dashboard API
    echo "\n=== Testing Dashboard API ===\n";
    $url = "http://localhost/backend/api/admin.php?action=dashboard&token=" . urlencode($token);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json'
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 200) {
        $data = json_decode($response, true);
        echo "✅ Dashboard API responded with status 200\n";

        if ($data && isset($data['stats'])) {
            echo "📊 Dashboard Statistics:\n";
            foreach ($data['stats'] as $key => $value) {
                echo "  {$key}: $value\n";
            }
        } else {
            echo "❌ Invalid response format\n";
            echo "Response: " . $response . "\n";
        }
    } else {
        echo "❌ Dashboard API failed with status $http_code\n";
        echo "Response: " . $response . "\n";
    }

    // Test regions API
    echo "\n=== Testing Regions API ===\n";
    $url = "http://localhost/backend/api/admin.php?action=regions&token=" . urlencode($token);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json'
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 200) {
        $data = json_decode($response, true);
        echo "✅ Regions API responded with status 200\n";
        $regions_count = isset($data['regions']) ? count($data['regions']) : 0;
        echo "📍 Found $regions_count regions\n";
    } else {
        echo "❌ Regions API failed with status $http_code\n";
        echo "Response: " . substr($response, 0, 200) . "\n";
    }

    // Test services API
    echo "\n=== Testing Services API ===\n";
    $url = "http://localhost/backend/api/admin.php?action=services&token=" . urlencode($token);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json'
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 200) {
        $data = json_decode($response, true);
        echo "✅ Services API responded with status 200\n";
        $services_count = isset($data['services']) ? count($data['services']) : 0;
        $categories_count = isset($data['categories']) ? count($data['categories']) : 0;
        echo "🛠️ Found $services_count services in $categories_count categories\n";
    } else {
        echo "❌ Services API failed with status $http_code\n";
        echo "Response: " . substr($response, 0, 200) . "\n";
    }

} catch (Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
}
