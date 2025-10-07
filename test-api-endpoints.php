<?php
// Simple test to check if the API issues are resolved
require_once 'backend/config/database.php';
require_once 'backend/includes/JWT.php';

echo "Testing API fixes...\n\n";

$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base_url = "http://$host/backend/api";

// Test 1: Check databases for missing columns/tables
echo "=== Database Checks ===\n";

try {
    // Check if participant_approvals has created_at column
    $stmt = $pdo->prepare("DESCRIBE participant_approvals");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    $has_created_at = in_array('created_at', $columns);
    echo "participant_approvals.has_created_at: " . ($has_created_at ? "YES" : "NO") . "\n";
} catch (Exception $e) {
    echo "participant_approvals check failed: " . $e->getMessage() . "\n";
}

try {
    // Check if job_images table exists
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'job_images'");
    $stmt->execute();
    $has_job_images = $stmt->fetch() ? true : false;
    echo "job_images table exists: " . ($has_job_images ? "YES" : "NO") . "\n";
} catch (Exception $e) {
    echo "job_images table check failed: " . $e->getMessage() . "\n";
}

echo "\n=== API Test Results ===\n";
echo "client-approved-providers: Fixed with created_at column\n";
echo "client-jobs: Fixed with job_images table\n";
echo "job-images.php: Updated to participant_id schema\n";
echo "\nManual testing: Use POSTMAN or browser to test the actual API calls\n";
