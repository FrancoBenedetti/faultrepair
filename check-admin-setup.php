<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!-- DEBUG: Starting check-admin-setup.php -->";
// Test the include path
if (file_exists('backend/config/database.php')) {
    echo "<!-- DEBUG: Database config file exists -->";
} else {
    echo "<!-- DEBUG: Database config file NOT found! Looking for: " . realpath('backend/config/database.php') . " -->";
}

require_once 'backend/config/database.php';
echo "<!-- DEBUG: Database config included successfully -->";

echo "<h1>Admin Setup Check</h1>";

try {
    // Check if Site Administrator role exists
    echo "<h2>Roles Check:</h2>";
    $stmt = $pdo->query("SELECT id, name FROM roles ORDER BY id");
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($roles) {
        foreach ($roles as $role) {
            echo "Role ID {$role['id']}: {$role['name']}<br>";
            if ($role['id'] == 5) {
                echo "<span style='color: green;'>✓ Site Administrator role exists!</span><br>";
            }
        }
    } else {
        echo "<span style='color: red;'>No roles found!</span><br>";
    }

    // Check admin user
    echo "<h2>Admin Users Check:</h2>";
    $stmt = $pdo->prepare("SELECT id, username, email, role_id FROM users WHERE role_id = ?");
    $stmt->execute([5]);
    $admin_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($admin_users) {
        echo "Found " . count($admin_users) . " admin users:<br>";
        foreach ($admin_users as $user) {
            echo "ID: {$user['id']}, Username: {$user['username']}, Email: {$user['email']}<br>";
        }
    } else {
        echo "<span style='color: red;'>No admin users found!</span><br>";
        echo "Try running: UPDATE users SET role_id = 5 WHERE username = 'your-username';<br>";
    }

    // Check site_settings table
    echo "<h2>Site Settings Table:</h2>";
    $stmt = $pdo->query("DESCRIBE site_settings");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($columns) {
        echo "site_settings table structure:<br>";
        foreach ($columns as $column) {
            echo "• {$column['Field']} - {$column['Type']}";
            if ($column['Key']) echo " ({$column['Key']})";
            echo "<br>";
        }
    }

    // Check admin-related tables
    echo "<h2>Admin Tables Check:</h2>";
    $admin_tables = ['usage_tracking', 'user_features', 'admin_actions', 'system_health'];

    foreach ($admin_tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
            $result = $stmt->fetch();
            echo "✓ $table: {$result['count']} records<br>";
        } catch (Exception $e) {
            echo "❌ $table: Table doesn't exist or error - " . $e->getMessage() . "<br>";
        }
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>Database Error: " . $e->getMessage() . "</p>";
}
?>
