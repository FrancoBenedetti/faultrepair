<?php
/**
 * System Performance Monitoring API
 * Provides performance statistics and health checks for administrators only
 */

require_once '../config/database.php';
require_once '../includes/JWT.php';
require_once '../includes/performance-monitoring.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Authentication required
$token = $_GET['token'] ?? '';

if (!$token) {
    http_response_code(401);
    echo json_encode(['error' => 'Authorization token missing']);
    exit;
}

try {
    $payload = JWT::decode($token);
    $user_id = $payload['user_id'];
    $role_id = $payload['role_id'];
    $entity_type = $payload['entity_type'];

    // Only allow admin users (role_id = 2 for client admins)
    if ($role_id !== 2) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied. Admin access required.']);
        exit;
    }

} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid token']);
    exit;
}

try {
    // Initialize performance monitoring
    PerformanceMonitor::init($pdo);

    // What kind of report? (default: 24h, also supports 'week', 'month')
    $timeframe = isset($_GET['timeframe']) ? $_GET['timeframe'] : '24h';

    // Calculate timeframe
    $dateModifier = '';
    switch ($timeframe) {
        case 'week':
            $dateModifier = 'INTERVAL 7 DAY';
            $intervalDays = 7;
            break;
        case 'month':
            $dateModifier = 'INTERVAL 30 DAY';
            $intervalDays = 30;
            break;
        case '24h':
        default:
            $dateModifier = 'INTERVAL 24 HOUR';
            $intervalDays = 1;
            break;
    }

    // Get comprehensive performance statistics
    $stats = PerformanceMonitor::getPerformanceStats();

    // Get database optimization recommendations
    $optimizationSuggestions = PerformanceMonitor::optimizeJobQueries();

    // Get system health metrics
    $healthMetrics = [];

    // Database connection check
    try {
        $stmt = $pdo->query("SELECT 1");
        $stmt->execute();
        $healthMetrics['database_connection'] = 'healthy';
    } catch (Exception $e) {
        $healthMetrics['database_connection'] = 'unhealthy';
        $healthMetrics['database_error'] = $e->getMessage();
    }

    // Check required tables exist
    $requiredTables = ['jobs', 'users', 'participants', 'job_quotations', 'performance_logs'];
    foreach ($requiredTables as $table) {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table");
            $stmt->execute();
            $healthMetrics['tables'][$table] = 'exists';
        } catch (Exception $e) {
            $healthMetrics['tables'][$table] = 'missing';
        }
    }

    // Get recent error logs (last 100 entries)
    try {
        $stmt = $pdo->prepare("
            SELECT operation, duration_ms, created_at
            FROM performance_logs
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ORDER BY created_at DESC
            LIMIT 100
        ");
        $stmt->execute([$intervalDays]);
        $recentLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $recentLogs = [];
    }

    // Get job workflow s
    $workflowStats = [];

    try {
        // Job status distribution
        $stmt = $pdo->prepare("
            SELECT job_status, COUNT(*) as count, MAX(created_at) as last_updated
            FROM jobs
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY job_status
            ORDER BY count DESC
        ");
        $stmt->execute([$intervalDays]);
        $jobStatuses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Average time in workflow status
        $stmt = $pdo->prepare("
            SELECT
                job_status,
                AVG(TIMESTAMPDIFF(HOUR, created_at, COALESCE(updated_at, NOW()))) as avg_hours_in_status
            FROM jobs
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY job_status
        ");
        $stmt->execute([$intervalDays]);
        $avgTimeInStatus = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Notification delivery rates (simplified)
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total_notifications_sent
            FROM job_status_history
            WHERE changed_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        $stmt->execute([$intervalDays]);
        $notifications = $stmt->fetch(PDO::FETCH_ASSOC);

        $workflowStats = [
            'job_status_distribution' => $jobStatuses,
            'avg_time_in_status' => $avgTimeInStatus,
            'notification_activity' => $notifications
        ];

    } catch (Exception $e) {
        $workflowStats = ['error' => 'Could not retrieve workflow statistics: ' . $e->getMessage()];
    }

    // Compile response
    $response = [
        'timestamp' => date('Y-m-d H:i:s'),
        'timeframe' => $timeframe,
        'system_health' => $healthMetrics,
        'performance_stats' => $stats,
        'workflow_stats' => $workflowStats,
        'optimization_suggestions' => $optimizationSuggestions,
        'recent_performance_logs' => array_slice($recentLogs, 0, 50), // Limit for performance
        'recommendations' => []
    ];

    // Add actionable recommendations
    $recommendations = [];

    if ($stats && isset($stats['slow_queries']) && $stats['slow_queries'] > 10) {
        $recommendations[] = [
            'priority' => 'high',
            'type' => 'database',
            'title' => 'High number of slow queries detected',
            'description' => "Found {$stats['slow_queries']} queries taking >1 second in the last {$intervalDays} days",
            'action' => 'Review performance logs and add missing database indexes'
        ];
    }

    if ($stats && isset($stats['query_performance']['avg_duration']) && $stats['query_performance']['avg_duration'] > 500) {
        $recommendations[] = [
            'priority' => 'medium',
            'type' => 'performance',
            'title' => 'Elevated average query time',
            'description' => "Average query time: {$stats['query_performance']['avg_duration']}ms",
            'action' => 'Check database indexes and consider query optimization'
        ];
    }

    if (!empty($optimizationSuggestions)) {
        foreach ($optimizationSuggestions as $suggestion) {
            $recommendations[] = [
                'priority' => 'medium',
                'type' => 'database',
                'title' => 'Database optimization available',
                'description' => $suggestion,
                'action' => 'Run auto-optimize cron job or apply manual optimization'
            ];
        }
    }

    if (count($recommendations) === 0) {
        $recommendations[] = [
            'priority' => 'low',
            'type' => 'info',
            'title' => 'System performing well',
            'description' => 'No critical issues detected',
            'action' => 'Continue monitoring regularly'
        ];
    }

    $response['recommendations'] = $recommendations;

    echo json_encode($response);

} catch (Exception $e) {
    error_log("system-performance.php - Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to retrieve performance statistics',
        'details' => $e->getMessage()
    ]);
}
?>
