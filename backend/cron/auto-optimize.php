<?php
/**
 * Cron Job: Auto-optimize database indexes and cleanup
 * Run weekly (e.g., every Sunday at 3 AM)
 * Command: php /path/to/backend/cron/auto-optimize.php
 */

ini_set('log_errors', true);
ini_set('error_log', $_SERVER['DOCUMENT_ROOT'].'/all-logs/cron-auto-optimize.log');

// Include required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/performance-monitoring.php';

try {
    // Log the start of the cron job
    error_log("[" . date('Y-m-d H:i:s') . "] Starting database auto-optimization...");

    // Initialize performance monitoring
    PerformanceMonitor::init($pdo);

    // Step 1: Add missing indexes
    error_log("[" . date('Y-m-d H:i:s') . "] Checking and adding missing indexes...");
    $optimizationResults = PerformanceMonitor::optimizeDatabase();

    foreach ($optimizationResults as $result) {
        error_log("[" . date('Y-m-d H:i:s') . "] $result");
    }

    // Step 2: Cleanup old performance logs (keep last 30 days)
    error_log("[" . date('Y-m-d H:i:s') . "] Cleaning up old performance logs...");
    $cleanupResult = PerformanceMonitor::cleanupOldLogs(30);

    if ($cleanupResult) {
        error_log("[" . date('Y-m-d H:i:s') . "] Successfully cleaned up old performance logs");
    } else {
        error_log("[" . date('Y-m-d H:i:s') . "] Error cleaning up old performance logs");
    }

    // Step 3: Analyze tables for optimization recommendations
    error_log("[" . date('Y-m-d H:i:s') . "] Analyzing tables for optimization...");
    $optimizationSuggestions = PerformanceMonitor::optimizeJobQueries();

    foreach ($optimizationSuggestions as $suggestion) {
        error_log("[" . date('Y-m-d H:i:s') . "] SUGGESTION: $suggestion");
    }

    // Step 4:Generate performance report for the week
    error_log("[" . date('Y-m-d H:i:s') . "] Generating performance report...");
    $stats = PerformanceMonitor::getPerformanceStats();

    if ($stats) {
        error_log("[" . date('Y-m-d H:i:s') . "] Performance Report:");
        error_log("[" . date('Y-m-d H:i:s') . "] Total API calls in last 24h: " . ($stats['query_performance']['total_queries'] ?? 'N/A'));
        error_log("[" . date('Y-m-d H:i:s') . "] Average query time: " . ($stats['query_performance']['avg_duration'] ?? 'N/A') . "ms");
        error_log("[" . date('Y-m-d H:i:s') . "] Slow queries (>1s): " . ($stats['slow_queries'] ?? 0));
        error_log("[" . date('Y-m-d H:i:s') . "] Top slow API endpoints:");

        if (isset($stats['api_performance'])) {
            foreach (array_slice($stats['api_performance'], 0, 3) as $api) {
                error_log("[" . date('Y-m-d H:i:s') . "] - {$api['operation']}: {$api['avg_duration']}ms ({$api['call_count']} calls)");
            }
        }
    } else {
        error_log("[" . date('Y-m-d H:i:s') . "] Error generating performance report");
    }

    error_log("[" . date('Y-m-d H:i:s') . "] Database auto-optimization completed successfully");
    echo "SUCCESS: Database optimization completed\n";

} catch (Exception $e) {
    error_log("[" . date('Y-m-d H:i:s') . "] Exception in auto-optimize cron job: " . $e->getMessage());
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>
