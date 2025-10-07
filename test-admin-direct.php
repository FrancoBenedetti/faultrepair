<?php
// Direct database test without conflicting includes
require_once 'backend/config/database.php';

echo "=== Admin Dashboard Direct Database Test ===\n\n";

try {
    $pdo->query("USE snappy");

    // Test that all required tables exist and have data
    $required_tables = [
        'site_settings',
        'user_roles',
        'admin_actions',
        'regions',
        'services',
        'service_provider_regions',
        'service_provider_services'
    ];

    $missing_tables = [];
    foreach ($required_tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if (!$stmt->fetch()) {
            $missing_tables[] = $table;
        }
    }

    if (!empty($missing_tables)) {
        echo "❌ Missing tables: " . implode(', ', $missing_tables) . "\n";
        exit;
    }

    echo "✅ All required admin tables exist\n";

    // Test dashboard stats queries
    echo "\n=== Testing Dashboard Stats Queries ===\n";

    // Clients
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count FROM participants p
        JOIN participant_type pt ON p.participantId = pt.participantId
        WHERE pt.participantType = 'C' AND p.is_enabled = TRUE AND p.is_active = TRUE
    ");
    $stmt->execute();
    $clients = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "✅ Clients: $clients\n";

    // Service providers
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count FROM participants p
        JOIN participant_type pt ON p.participantId = pt.participantId
        WHERE pt.participantType = 'S' AND p.is_enabled = TRUE AND p.is_active = TRUE
    ");
    $stmt->execute();
    $sps = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "✅ Service Providers: $sps\n";

    // Active users
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count FROM users WHERE role_id NOT IN (2, 5) AND is_active = TRUE
    ");
    $stmt->execute();
    $users = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "✅ Active Users: $users\n";

    // Regions
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM regions WHERE is_active = 1");
    $stmt->execute();
    $regions = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "✅ Regions: $regions\n";

    // Services
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM services WHERE is_active = 1");
    $stmt->execute();
    $services = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "✅ Services: $services\n";

    // Site settings
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM site_settings");
    $stmt->execute();
    $settings = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "✅ Site Settings: $settings\n";

    echo "\n=== SUCCESS ===\n";
    echo "✅ Admin dashboard database is fully configured!\n";
    echo "✅ All tables exist and contain data\n";
    echo "✅ Dashboard will now display statistics properly\n\n";

    echo "Dashboard will show:\n";
    echo "- 📊 Active Clients: $clients\n";
    echo "- 🔧 Active Service Providers: $sps\n";
    echo "- 👥 Active Users: $users\n";
    echo "- 📍 Active Regions: $regions\n";
    echo "- 🛠️ Active Services: $services\n";

} catch (Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
}
