<?php
require_once 'backend/config/database.php';
require_once 'backend/includes/JWT.php';

echo "Checking for admin user...\n";

// Check if there's any user with role_id = 5 (System Administrator)
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE role_id = 5");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result['count'] > 0) {
        echo "Admin user already exists!\n";

        // Show existing admin users
        $stmt = $pdo->prepare("
            SELECT u.userId, u.username, u.email, u.first_name, u.last_name
            FROM users u
            WHERE u.role_id = 5
        ");
        $stmt->execute();
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "Existing admin users:\n";
        foreach ($admins as $admin) {
            echo "- {$admin['username']} ({$admin['email']}) - {$admin['first_name']} {$admin['last_name']}\n";
        }
        exit;
    }
} catch (Exception $e) {
    echo "Error checking for admin user: " . $e->getMessage() . "\n";
}

// Create admin participant
echo "Creating admin participant...\n";
try {
    $stmt = $pdo->prepare("
        INSERT INTO participants (
            name, address, is_active, is_enabled
        ) VALUES (
            'System Administration', 'System Administration', TRUE, TRUE
        )
    ");
    $stmt->execute();
    $participant_id = $pdo->lastInsertId();
    echo "Created participant ID: $participant_id\n";

    // Set participant type as admin (though this might not be necessary)
    $stmt = $pdo->prepare("
        INSERT INTO participant_type (participantId, participantType, isActive)
        VALUES (?, 'S', 'Y')
    ");
    $stmt->execute([$participant_id]);

} catch (Exception $e) {
    echo "Error creating admin participant: " . $e->getMessage() . "\n";
    exit;
}

// Create admin user
echo "Creating admin user...\n";
$username = 'admin';
$password_hash = password_hash('admin123', PASSWORD_DEFAULT);
$email = 'admin@faultreporter.com';

try {
    $stmt = $pdo->prepare("
        INSERT INTO users (
            username, password_hash, email, entity_id, role_id,
            first_name, last_name, is_active, email_verified
        ) VALUES (
            ?, ?, ?, ?, 5, 'System', 'Administrator', TRUE, TRUE
        )
    ");
    $stmt->execute([$username, $password_hash, $email, $participant_id]);

    $user_id = $pdo->lastInsertId();
    echo "Created admin user with ID: $user_id\n";
    echo "Username: $username\n";
    echo "Email: $email\n";
    echo "Password: admin123\n\n";

    echo "You can now log into the admin interface at:\n";
    echo "/backend/public/admin/index.php\n\n";

    // Optional: Generate a JWT token for testing
    $payload = [
        'user_id' => $user_id,
        'role_id' => 5,
        'entity_type' => 'service_provider',
        'entity_id' => $participant_id,
        'first_name' => 'System',
        'last_name' => 'Administrator'
    ];

    $token = JWT::encode($payload, 'your-secret-key');
    echo "Login token for testing:\n$token\n\n";

} catch (Exception $e) {
    echo "Error creating admin user: " . $e->getMessage() . "\n";
}

echo "Admin user creation completed!\n";
