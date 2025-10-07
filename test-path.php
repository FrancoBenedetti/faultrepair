<?php
echo "Current directory: " . __DIR__ . "\n";
echo "Working directory: " . getcwd() . "\n";
echo "File path: " . __FILE__ . "\n\n";

echo "Testing backend/includes/subscription.php path from here:\n";
$path = 'backend/includes/subscription.php';
echo "Realpath of '$path': " . realpath($path) . "\n";
echo "File exists '$path': " . (file_exists($path) ? 'YES' : 'NO') . "\n\n";

echo "Testing relative path '../config/database.php' from backend/includes/:\n";
$path2 = 'backend/includes/../config/database.php';
echo "Realpath of '$path2': " . realpath($path2) . "\n";
echo "File exists '$path2': " . (file_exists($path2) ? 'YES' : 'NO') . "\n\n";

echo "Testing absolute path from backend/includes/subscription.php perspective:\n";
$absolute_path = __DIR__ . '/../config/database.php';
echo "Absolute path: $absolute_path\n";
echo "Realpath: " . realpath($absolute_path) . "\n";
echo "File exists (absolute): " . (file_exists($absolute_path) ? 'YES' : 'NO') . "\n";
?>
