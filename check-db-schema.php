<?php
require_once 'backend/config/database.php';

echo "=== Database Schema Analysis ===\n\n";

try {
    $pdo->query("USE snappy");

    // Get all tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "Total tables found: " . count($tables) . "\n\n";

    foreach ($tables as $table) {
        echo "Table: $table\n";

        // Get table structure
        $stmt = $pdo->query("DESCRIBE $table");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "  Columns (" . count($columns) . "):\n";
        foreach ($columns as $col) {
            echo "    - {$col['Field']}: {$col['Type']} " .
                 ($col['Null'] == 'NO' ? 'NOT NULL' : 'NULL') .
                 (!empty($col['Key']) ? " [{$col['Key']}]" : '') . "\n";
        }
        echo "\n";
    }

    // Check for specific tables needed for admin
    $admin_tables = [
        'site_settings',
        'user_roles',
        'admin_actions',
        'regions',
        'services',
        'service_provider_services',
        'service_provider_regions',
        'admin_actions'
    ];

    echo "=== Admin Table Status ===\n";
    foreach ($admin_tables as $table) {
        $exists = in_array($table, $tables);
        echo "$table: " . ($exists ? "✅ EXISTS" : "❌ MISSING") . "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
