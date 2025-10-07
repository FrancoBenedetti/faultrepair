<?php
echo "🔍 **PHP Test - If you see this, PHP is working!**<br><br>";
echo "File location: " . __FILE__ . "<br>";
echo "Current working directory: " . getcwd() . "<br>";
echo "Document root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "PHP version: " . phpversion() . "<br><br>";

echo "📁 **File Checks:**<br>";
$files_to_check = [
    'backend/config/database.php',
    'backend/includes/subscription.php'
];

foreach ($files_to_check as $file) {
    $full_path = realpath($file);
    if (file_exists($file)) {
        echo "✅ $file exists (" . filesize($file) . " bytes)<br>";
    } else {
        echo "❌ $file NOT found<br>";
    }
}

echo "<br>🔗 **Next Steps:**<br>";
echo "1. Copy this URL and add ?test=1 to check database: " . $_SERVER['REQUEST_URI'] . "?test=1<br>";
echo "2. Check Apache error log: /var/log/apache2/error.log<br>";

if (isset($_GET['test'])) {
    echo "<br>🗄️ **Database Test:**<br>";
    try {
        $config_path = 'backend/config/database.php';
        if (file_exists($config_path)) {
            require_once $config_path;
            echo "✅ Config file included successfully<br>";

            if (isset($pdo)) {
                echo "✅ PDO object exists<br>";
                $stmt = $pdo->query("SELECT 42 as test");
                $result = $stmt->fetch();
                echo "✅ Database query successful: " . $result['test'] . "<br>";
            } else {
                echo "❌ PDO object not found (check database.php)<br>";
            }
        } else {
            echo "❌ Config file not found<br>";
        }
    } catch (Exception $e) {
        echo "❌ Database error: " . $e->getMessage() . "<br>";
    }
}
?>
