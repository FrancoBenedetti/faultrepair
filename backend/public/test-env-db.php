<?php
/**
 * Environment Variables and Database Accessibility Test
 * This script checks if environment variables are loaded and if the database is accessible.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/site.php';

echo "<h1>Environment and Database Test</h1>";
echo "<p>Testing environment variables and database connection.</p>";

echo "<h2>Environment Variables</h2>";
$requiredEnvVars = ['SITE_NAME']; // Add more if needed, based on .env

foreach ($requiredEnvVars as $var) {
    if (defined($var)) {
        echo "<p>$var: " . constant($var) . "</p>";
    } elseif (isset($_ENV[$var])) {
        echo "<p>$var: " . $_ENV[$var] . "</p>";
    } else {
        echo "<p style='color: red;'>$var: Not set</p>";
    }
}

echo "<h2>Database Connection</h2>";
try {
    // Test connection by running a simple query
    $stmt = $pdo->query("SELECT 1");
    $result = $stmt->fetch();
    if ($result) {
        echo "<p style='color: green;'>Database connection successful. Test query executed.</p>";
    } else {
        echo "<p style='color: red;'>Database connection failed: No result from test query.</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database connection failed: " . $e->getMessage() . "</p>";
}

echo "<h2>Additional Checks</h2>";
// Check if .env file exists
$envFile = __DIR__ . '/../../.env';
if (file_exists($envFile)) {
    echo "<p>.env file exists.</p>";
} else {
    echo "<p style='color: orange;'>.env file not found.</p>";
}

// Check PHP version
echo "<p>PHP Version: " . phpversion() . "</p>";

echo "<p>Test completed.</p>";
?>
