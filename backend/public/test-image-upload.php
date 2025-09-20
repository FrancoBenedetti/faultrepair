<?php
// Test script for image upload functionality
echo "<h1>Image Upload Test</h1>";

// Check if uploads directory exists and is writable
$upload_dir = '../uploads/job_images/';
echo "<h2>Upload Directory Check</h2>";
echo "Directory: $upload_dir<br>";
echo "Exists: " . (file_exists($upload_dir) ? 'YES' : 'NO') . "<br>";
echo "Writable: " . (is_writable($upload_dir) ? 'YES' : 'NO') . "<br>";

// List files in upload directory
echo "<h2>Files in Upload Directory</h2>";
if (file_exists($upload_dir)) {
    $files = scandir($upload_dir);
    echo "<ul>";
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            echo "<li>$file</li>";
        }
    }
    echo "</ul>";
}

// Check database connection
echo "<h2>Database Connection Test</h2>";
try {
    require_once '../config/database.php';
    echo "Database connection: SUCCESS<br>";
    echo "PDO object: " . (isset($pdo) ? 'EXISTS' : 'NOT FOUND') . "<br>";
} catch (Exception $e) {
    echo "Database connection: FAILED - " . $e->getMessage() . "<br>";
}

// Check JWT include
echo "<h2>JWT Include Test</h2>";
try {
    require_once '../includes/JWT.php';
    echo "JWT include: SUCCESS<br>";
} catch (Exception $e) {
    echo "JWT include: FAILED - " . $e->getMessage() . "<br>";
}

echo "<h2>Test Complete</h2>";
?>
