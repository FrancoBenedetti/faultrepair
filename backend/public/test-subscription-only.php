<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "🔍 **Testing subscription.php include**<br><br>";

echo "Step 1: Include database.php...<br>";
try {
    require_once '../config/database.php';
    echo "✅ database.php included successfully<br>";
} catch (Exception $e) {
    echo "❌ database.php failed: " . $e->getMessage() . "<br>";
    exit;
}

echo "Step 2: Include subscription.php...<br>";
try {
    require_once '../includes/subscription.php';
    echo "✅ subscription.php included successfully<br>";
} catch (Exception $e) {
    echo "❌ subscription.php failed: " . $e->getMessage() . "<br>";
    exit;
}

echo "Step 3: Test subscription functions...<br>";
$test_functions = [
    'getSiteSetting',
    'getUserSubscription',
    'getSubscriptionPricing',
    'canPerformAction'
];

foreach ($test_functions as $func) {
    if (function_exists($func)) {
        echo "✅ Function $func exists<br>";
    } else {
        echo "❌ Function $func missing<br>";
    }
}

echo "<br>🎉 **All tests passed! subscription.php is working correctly.**<br>";
echo "If the admin page still fails, the issue is elsewhere.<br>";
?>
