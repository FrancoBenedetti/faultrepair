<?php
require_once 'backend/config/database.php';

echo "<h1>Check Existing Users</h1>";

// Get all users
try {
    $stmt = $pdo->query("SELECT id, username, email, entity_type, role_id, is_active FROM users ORDER BY id");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<p>Found " . count($users) . " users in the database:</p>";

    if (count($users) > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 20px 0;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th style='padding: 8px;'>ID</th>";
        echo "<th style='padding: 8px;'>Username</th>";
        echo "<th style='padding: 8px;'>Email</th>";
        echo "<th style='padding: 8px;'>Entity Type</th>";
        echo "<th style='padding: 8px;'>Role ID</th>";
        echo "<th style='padding: 8px;'>Active</th>";
        echo "</tr>";

        foreach ($users as $user) {
            echo "<tr>";
            echo "<td style='padding: 8px;'>{$user['id']}</td>";
            echo "<td style='padding: 8px;'>{$user['username']}</td>";
            echo "<td style='padding: 8px;'>{$user['email']}</td>";
            echo "<td style='padding: 8px;'>{$user['entity_type']}</td>";
            echo "<td style='padding: 8px;'>{$user['role_id']}</td>";
            echo "<td style='padding: 8px;'>" . ($user['is_active'] ? 'Yes' : 'No') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>No users found in database!</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error fetching users: " . $e->getMessage() . "</p>";
}

// Test authentication with a sample user
echo "<h2>Test Authentication</h2>";

if (count($users) > 0) {
    $testUser = $users[0]; // Test with first user

    echo "<p>Testing authentication with user: <strong>{$testUser['username']}</strong></p>";
    echo "<p>Password to try: <strong>test123</strong></p>";

    // Get the password hash from database
    try {
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$testUser['id']]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($userData) {
            $passwordHash = $userData['password_hash'];
            echo "<p>Password hash from database: <code>" . substr($passwordHash, 0, 20) . "...</code></p>";

            // Test password verification
            $testPassword = 'test123';
            $isValid = password_verify($testPassword, $passwordHash);

            if ($isValid) {
                echo "<p style='color: green;'>✓ Password 'test123' is CORRECT for user '{$testUser['username']}'</p>";
            } else {
                echo "<p style='color: red;'>✗ Password 'test123' is INCORRECT for user '{$testUser['username']}'</p>";

                // Try a few common passwords
                $commonPasswords = ['password', 'password123', 'admin', 'admin123', 'test', '123456'];
                echo "<p>Testing common passwords:</p>";
                echo "<ul>";
                foreach ($commonPasswords as $pwd) {
                    if (password_verify($pwd, $passwordHash)) {
                        echo "<li style='color: green;'>✓ Password: <strong>$pwd</strong></li>";
                        break;
                    }
                }
                echo "</ul>";
            }
        } else {
            echo "<p style='color: red;'>Could not retrieve password hash for user</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error testing password: " . $e->getMessage() . "</p>";
    }
}

echo "<h2>Available Login Credentials</h2>";
echo "<p>Based on the users above, here are the login credentials:</p>";

// Get client users (entity_type = 'client')
try {
    $stmt = $pdo->prepare("SELECT username, email FROM users WHERE entity_type = 'client' AND is_active = 1");
    $stmt->execute();
    $clientUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($clientUsers) > 0) {
        echo "<h3>Client Users (use for Client Sign In):</h3>";
        echo "<ul>";
        foreach ($clientUsers as $user) {
            echo "<li><strong>Username:</strong> {$user['username']} | <strong>Email:</strong> {$user['email']} | <strong>Password:</strong> test123</li>";
        }
        echo "</ul>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error fetching client users: " . $e->getMessage() . "</p>";
}

// Get service provider users
try {
    $stmt = $pdo->prepare("SELECT username, email FROM users WHERE entity_type = 'service_provider' AND is_active = 1");
    $stmt->execute();
    $spUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($spUsers) > 0) {
        echo "<h3>Service Provider Users (use for Service Provider Sign In):</h3>";
        echo "<ul>";
        foreach ($spUsers as $user) {
            echo "<li><strong>Username:</strong> {$user['username']} | <strong>Email:</strong> {$user['email']} | <strong>Password:</strong> test123</li>";
        }
        echo "</ul>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error fetching service provider users: " . $e->getMessage() . "</p>";
}

echo "<h2>Next Steps</h2>";
echo "<ol>";
echo "<li>Go to <a href='http://localhost:8000' target='_blank'>http://localhost:8000</a></li>";
echo "<li>Choose the appropriate sign-in option (Client or Service Provider)</li>";
echo "<li>Use one of the username/password combinations listed above</li>";
echo "<li>If authentication still fails, check the browser console for JavaScript errors</li>";
echo "</ol>";
?>
