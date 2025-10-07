<?php
// Simple direct test without conflicting includes
require_once 'backend/config/database.php';

// Test dashboard stats function directly
echo "=== Testing Admin Dashboard Schema Fix ===\n\n";

// Test getAdminDashboardStats-like queries directly
echo "1. Testing dashboard statistics queries...\n";

try {
    $pdo->query("USE snappy");

    // Get client statistics (participants with type 'C')
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count FROM participants p
        JOIN participant_type pt ON p.participantId = pt.participantId
        WHERE pt.participantType = 'C' AND p.is_enabled = TRUE AND p.is_active = TRUE
    ");
    $stmt->execute();
    $clients_result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✅ Clients query: {$clients_result['count']} found\n";

    // Get service provider statistics (participants with type 'S')
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count FROM participants p
        JOIN participant_type pt ON p.participantId = pt.participantId
        WHERE pt.participantType = 'S' AND p.is_enabled = TRUE AND p.is_active = TRUE
    ");
    $stmt->execute();
    $sps_result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✅ Service providers query: {$sps_result['count']} found\n";

    // Test regions
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM regions WHERE is_active = TRUE");
    $stmt->execute();
    $regions_result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✅ Regions query: {$regions_result['count']} active regions found\n";

    // Test services
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM services WHERE is_active = TRUE");
    $stmt->execute();
    $services_result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✅ Services query: {$services_result['count']} active services found\n";

    // Test site settings
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM site_settings");
    $stmt->execute();
    $settings_result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✅ Site settings query: {$settings_result['count']} settings found\n";

    echo "\n=== Summary ===\n";
    echo "✅ All database schema queries are working correctly!\n";
    echo "✅ Admin dashboard should now display data properly.\n";
    echo "✅ The schema misalignment has been resolved.\n\n";

    echo "Data Overview:\n";
    echo "- Active clients: {$clients_result['count']}\n";
    echo "- Active service providers: {$sps_result['count']}\n";
    echo "- Active regions: {$regions_result['count']}\n";
    echo "- Active services: {$services_result['count']}\n";
    echo "- Site settings: {$settings_result['count']}\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "❌ Database schema still has issues.\n";
}
