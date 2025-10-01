<?php
require_once 'backend/config/database.php';

echo "<h1>Create Test User</h1>";

// Check if we already have users
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result['count'] > 0) {
        echo "<p style='color: blue;'>Users already exist in the database. No need to create test user.</p>";
        echo "<p>Existing users: " . $result['count'] . "</p>";

        // Show existing users
        $stmt = $pdo->query("SELECT id, username, email, entity_type, role_id FROM users");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Entity Type</th><th>Role ID</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['username']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>{$user['entity_type']}</td>";
            echo "<td>{$user['role_id']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        exit;
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error checking existing users: " . $e->getMessage() . "</p>";
}

// Create a test client
$testClient = [
    'name' => 'Test Client Company',
    'address' => '123 Test Street, Test City'
];

try {
    $stmt = $pdo->prepare("INSERT INTO clients (name, address) VALUES (?, ?)");
    $stmt->execute([$testClient['name'], $testClient['address']]);
    $clientId = $pdo->lastInsertId();
    echo "<p style='color: green;'>✓ Created test client with ID: $clientId</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Error creating client: " . $e->getMessage() . "</p>";
    exit;
}

// Create a test user for the client
$testUser = [
    'username' => 'testuser',
    'password' => 'password123',
    'email' => 'test@example.com',
    'mobile' => '+27123456789',
    'role_id' => 1, // Reporting Employee
    'entity_type' => 'client',
    'entity_id' => $clientId
];

try {
    $passwordHash = password_hash($testUser['password'], PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("
        INSERT INTO users (
            username, password_hash, email, phone, role_id, entity_type, entity_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $testUser['username'],
        $passwordHash,
        $testUser['email'],
        $testUser['mobile'],
        $testUser['role_id'],
        $testUser['entity_type'],
        $testUser['entity_id']
    ]);

    $userId = $pdo->lastInsertId();
    echo "<p style='color: green;'>✓ Created test user with ID: $userId</p>";
    echo "<p style='color: green;'>✓ Username: {$testUser['username']}</p>";
    echo "<p style='color: green;'>✓ Password: {$testUser['password']}</p>";
    echo "<p style='color: green;'>✓ Email: {$testUser['email']}</p>";

} catch (Exception $e) {
    echo "<p style='color: red;'>Error creating user: " . $e->getMessage() . "</p>";
    exit;
}

// Create a test location for the client
$testLocation = [
    'client_id' => $clientId,
    'name' => 'Main Office',
    'address' => '123 Test Street, Test City, 1234'
];

try {
    $stmt = $pdo->prepare("INSERT INTO locations (client_id, name, address) VALUES (?, ?, ?)");
    $stmt->execute([$testLocation['client_id'], $testLocation['name'], $testLocation['address']]);
    $locationId = $pdo->lastInsertId();
    echo "<p style='color: green;'>✓ Created test location with ID: $locationId</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Error creating location: " . $e->getMessage() . "</p>";
}

echo "<h2>Test Login Credentials:</h2>";
echo "<p><strong>Username:</strong> testuser</p>";
echo "<p><strong>Password:</strong> password123</p>";
echo "<p><strong>User Type:</strong> Client (Reporting Employee)</p>";

echo "<h2>Next Steps:</h2>";
echo "<ol>";
echo "<li>Go to <a href='http://localhost:8000' target='_blank'>http://localhost:8000</a></li>";
echo "<li>Click 'Client Sign In'</li>";
echo "<li>Use the credentials above to sign in</li>";
echo "<li>You should be able to access the Client Dashboard with job management features</li>";
echo "</ol>";
?>
