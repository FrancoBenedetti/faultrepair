<?php
echo "<h1>Admin Debug</h1>";

// Test database connection
echo "<h2>Database Connection Test</h2>";
try {
    require_once '../../config/database.php';
    echo "Database connection successful<br>";
    echo "PDO object exists: " . (isset($pdo) ? 'Yes' : 'No') . "<br>";
} catch (Exception $e) {
    echo "Database connection failed: " . $e->getMessage() . "<br>";
}

// Test JWT include
echo "<h2>JWT Include Test</h2>";
try {
    require_once '../../includes/JWT.php';
    echo "JWT include successful<br>";
} catch (Exception $e) {
    echo "JWT include failed: " . $e->getMessage() . "<br>";
}

// Test subscription include
echo "<h2>Subscription Include Test</h2>";
try {
    require_once '../../includes/subscription.php';
    echo "Subscription include successful<br>";
} catch (Exception $e) {
    echo "Subscription include failed: " . $e->getMessage() . "<br>";
}

// Test current state
echo "<h2>Current Request Info</h2>";
echo "Token in GET: " . (isset($_GET['token']) ? 'Present (' . strlen($_GET['token']) . ' chars)' : 'Not present') . "<br>";
echo "Request method: " . $_SERVER['REQUEST_METHOD'] . "<br>";

// Test JWT decode if token exists
if (isset($_GET['token'])) {
    echo "<h2>JWT Decode Test</h2>";
    try {
        $payload = JWT::decode($_GET['token']);
        echo "JWT decode successful<br>";
        echo "User ID: " . ($payload['user_id'] ?? 'N/A') . "<br>";
        echo "Role ID: " . ($payload['role_id'] ?? 'N/A') . "<br>";
        echo "Entity Type: " . ($payload['entity_type'] ?? 'N/A') . "<br>";
        echo "Is Admin (role_id = 5): " . (($payload['role_id'] ?? 0) === 5 ? 'Yes' : 'No') . "<br>";
    } catch (Exception $e) {
        echo "JWT decode failed: " . $e->getMessage() . "<br>";
    }
}

// Test database query
echo "<h2>Database Query Test</h2>";
try {
    if (isset($pdo)) {
        $stmt = $pdo->query("SELECT 1 as test");
        $result = $stmt->fetch();
        echo "Database query successful: " . $result['test'] . "<br>";
    } else {
        echo "PDO not available for query test<br>";
    }
} catch (Exception $e) {
    echo "Database query failed: " . $e->getMessage() . "<br>";
}

// Test site_settings
echo "<h2>Site Settings Test</h2>";
try {
    if (function_exists('getSiteSetting')) {
        $test_setting = getSiteSetting('client_free_jobs_per_month');
        echo "Site setting retrieval works: " . $test_setting . "<br>";
    } else {
        echo "getSiteSetting function not available<br>";
    }
} catch (Exception $e) {
    echo "Site settings test failed: " . $e->getMessage() . "<br>";
}

echo "<h2>File Paths Test</h2>";
echo "__FILE__: " . __FILE__ . "<br>";
echo "__DIR__: " . __DIR__ . "<br>";
echo "Config path (relative): ../../config/database.php<br>";
echo "Config path (absolute): " . realpath('../../config/database.php') . "<br>";
echo "Includes path (relative): ../../includes/JWT.php<br>";
echo "Includes path (absolute): " . realpath('../../includes/JWT.php') . "<br>";
echo "Subscription include: " . realpath('../../includes/subscription.php') . "<br>";
?>
