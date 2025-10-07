<?php
// Quick test to verify admin login works
require_once 'backend/config/database.php';
require_once 'backend/includes/JWT.php';

// Test user credentials
$username = 'admin';
$password = 'admin123';

echo "Testing admin login with username: $username, password: $password\n\n";

// Simulate the login process (same as in auth.php)
try {
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

    if (!$user) {
        echo "❌ User not found or inactive\n";
        exit;
    }

    if (!password_verify($password, $user['password_hash'])) {
        echo "❌ Invalid password\n";
        exit;
    }

    echo "✅ Login successful!\n";
    echo "User ID: {$user['userId']}\n";
    echo "Role ID: {$user['role_id']}\n";
    echo "Role: " . ($user['role_id'] == 5 ? 'System Administrator' : 'Other') . "\n";
    echo "Entity Type: {$user['entity_type']}\n";
    echo "Name: {$user['first_name']} {$user['last_name']}\n";

    // Generate JWT token like auth.php does
    $token_payload = [
        'user_id' => $user['userId'],
        'role_id' => (int)$user['role_id'],
        'entity_type' => $user['entity_type'],
        'entity_id' => (int)$user['entity_id'],
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name']
    ];

    $token = JWT::encode($token_payload, 'your-secret-key');

    echo "\nJWT Token generated successfully!\n";
    echo "Token: $token\n\n";

    echo "Admin interface should now be accessible at:\n";
    echo "/backend/public/admin/index.php?token=$token\n";

} catch (Exception $e) {
    echo "❌ Error during login test: " . $e->getMessage() . "\n";
}
