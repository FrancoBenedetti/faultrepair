<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "🔍 **PHP Test - If you see this, PHP is working!**<br><br>";
echo "File location: " . __FILE__ . "<br>";
echo "Current working directory: " . getcwd() . "<br>";
echo "Document root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "PHP version: " . phpversion() . "<br><br>";

echo "📁 **File Checks:**<br>";
$files_to_check = [
    '../config/database.php',
    '../includes/subscription.php'
];

foreach ($files_to_check as $file) {
    $full_path = realpath($file);
    if (file_exists($file)) {
        echo "✅ $file exists (" . (file_exists($file) ? filesize($file) : 0) . " bytes)<br>";
    } else {
        echo "❌ $file NOT found<br>";
    }
}

echo "<br>🔗 **Next Steps:**<br>";
echo "1. Copy this URL and add ?test=1 to check database: <strong>http://fault-reporter.local/backend/public/admin-debug.php?test=1</strong><br>";
echo "2. Check Apache error log: <code>tail -f /var/log/apache2/error.log</code><br>";
echo "3. For admin tests, visit: <a href='admin/'>Site Manager</a><br>";

if (isset($_GET['test'])) {
    echo "<br>🗄️ **Database Test:**<br>";
    try {
        $config_path = '../config/database.php';
        if (file_exists($config_path)) {
            require_once $config_path;
            echo "✅ Config file included successfully<br>";

            if (isset($pdo)) {
                echo "✅ PDO object exists<br>";
                $stmt = $pdo->query("SELECT 42 as test");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                echo "✅ Database query successful: " . $result['test'] . "<br>";
            } else {
                echo "❌ PDO object not found (check ../config/database.php)<br>";
            }
        } else {
            echo "❌ Config file not found<br>";
        }
    } catch (Exception $e) {
        echo "❌ Database error: " . $e->getMessage() . "<br>";
    }
}

echo "<br>🎯 **Admin Setup Check:**<br>";
echo "• Visit: <a href='admin/'>Site Manager Login</a> (Username: siteadmin, Password: admin123)<br>";
echo "• View: <a href='test-api-endpoints.php'>API Tests</a><br>";
?>
