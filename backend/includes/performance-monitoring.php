<?php
/**
 * Performance Monitoring & Optimization Utilities
 * Tracks system performance and provides optimization recommendations
 */

class PerformanceMonitor {

    private static $startTimes = [];
    private static $pdo;

    /**
     * Initialize performance monitoring
     */
    public static function init($pdo) {
        self::$pdo = $pdo;
        self::recordSystemMetrics();
    }

    /**
     * Start timing a specific operation
     */
    public static function startTimer($operationName) {
        self::$startTimes[$operationName] = microtime(true);
    }

    /**
     * End timing and log the operation performance
     */
    public static function endTimer($operationName, $thresholdMs = 1000) {
        if (!isset(self::$startTimes[$operationName])) {
            return;
        }

        $duration = (microtime(true) - self::$startTimes[$operationName]) * 1000; // Convert to milliseconds
        unset(self::$startTimes[$operationName]);

        // Log slow queries
        if ($duration > $thresholdMs) {
            error_log("PerformanceMonitor: SLOW OPERATION - {$operationName}: {$duration}ms");
            self::logSlowOperation($operationName, $duration);
        }

        // For API operations, log all durations
        if (strpos($operationName, 'api_') === 0) {
            error_log("PerformanceMonitor: API {$operationName}: {$duration}ms");

            if ($duration > 5000) {
                // This is extremely slow - alert administrators
                error_log("PerformanceMonitor: CRITICAL - {$operationName} took {$duration}ms - investigate immediately!");
            }
        }
    }

    /**
     * Record system metrics (memory usage, etc.)
     */
    private static function recordSystemMetrics() {
        $memoryUsage = memory_get_peak_usage(true);
        $memoryMB = round($memoryUsage / 1024 / 1024, 2);

        // Log high memory usage
        if ($memoryMB > 50) {
            error_log("PerformanceMonitor: HIGH MEMORY USAGE: {$memoryMB}MB");
        }

        // Log every 10 minutes (if called frequently)
        static $lastLog = 0;
        if (time() - $lastLog > 600) { // Every 10 minutes
            $lastLog = time();
            error_log("PerformanceMonitor: System metrics - Memory: {$memoryMB}MB, Peak: " . round(memory_get_peak_usage(true) / 1024 / 1024, 2) . "MB");
        }
    }

    /**
     * Log slow operations to database for analysis
     */
    private static function logSlowOperation($operation, $duration) {
        try {
            $stmt = self::$pdo->prepare("
                INSERT INTO performance_logs (operation, duration_ms, created_at)
                VALUES (?, ?, NOW())
            ");
            $stmt->execute([$operation, round($duration, 2)]);
        } catch (Exception $e) {
            error_log("PerformanceMonitor: Failed to log slow operation: " . $e->getMessage());
        }
    }

    /**
     * Optimize job queries with caching and indexing recommendations
     */
    public static function optimizeJobQueries() {
        $optimizations = [];

        try {
            // Check for missing indexes on frequently queried columns
            $stmt = self::$pdo->query("SHOW INDEX FROM jobs");

            $existingIndexes = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $existingIndexes[] = $row['Column_name'];
            }

            $recommendedIndexes = [
                'job_status',
                'client_id',
                'assigned_provider_participant_id',
                'created_at',
                'updated_at'
            ];

            $missingIndexes = array_diff($recommendedIndexes, $existingIndexes);
            if (!empty($missingIndexes)) {
                $optimizations[] = "Missing recommended indexes on jobs table: " . implode(', ', $missingIndexes);
            }

            // Check table size and recommend optimizations
            $stmt = self::$pdo->query("SHOW TABLE STATUS LIKE 'jobs'");
            $tableInfo = $stmt->fetch(PDO::FETCH_ASSOC);

            $tableSizeMB = round($tableInfo['Data_length'] / 1024 / 1024, 2);
            if ($tableSizeMB > 100) { // Over 100MB
                $optimizations[] = "Jobs table is large ({$tableSizeMB}MB) - consider archive strategy";
            }

        } catch (Exception $e) {
            $optimizations[] = "Error checking optimizations: " . $e->getMessage();
        }

        return $optimizations;
    }

    /**
     * Clean up old performance logs
     */
    public static function cleanupOldLogs($daysToKeep = 30) {
        try {
            $stmt = self::$pdo->prepare("
                DELETE FROM performance_logs
                WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            $stmt->execute([$daysToKeep]);
            return true;
        } catch (Exception $e) {
            error_log("PerformanceMonitor: Failed to cleanup old logs: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get performance statistics
     */
    public static function getPerformanceStats() {
        try {
            $stats = [];

            // Query performance statistics
            $stmt = self::$pdo->prepare("
                SELECT
                    COUNT(*) as total_queries,
                    AVG(duration_ms) as avg_duration,
                    MAX(duration_ms) as max_duration,
                    MIN(duration_ms) as min_duration
                FROM performance_logs
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            $stmt->execute();
            $stats['query_performance'] = $stmt->fetch(PDO::FETCH_ASSOC);

            // Slow query count
            $stmt = self::$pdo->prepare("
                SELECT COUNT(*) as slow_queries
                FROM performance_logs
                WHERE duration_ms > 1000 AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            $stmt->execute();
            $stats['slow_queries'] = $stmt->fetch(PDO::FETCH_ASSOC)['slow_queries'];

            // API endpoint performance
            $stmt = self::$pdo->prepare("
                SELECT operation, AVG(duration_ms) as avg_duration, COUNT(*) as call_count
                FROM performance_logs
                WHERE operation LIKE 'api_%'
                AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                GROUP BY operation
                ORDER BY avg_duration DESC
                LIMIT 10
            ");
            $stmt->execute();
            $stats['api_performance'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $stats;

        } catch (Exception $e) {
            error_log("PerformanceMonitor: Failed to get performance stats: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Optimize database - add recommended indexes
     */
    public static function optimizeDatabase() {
        $results = [];

        try {
            // Check current indexes
            $stmt = self::$pdo->query("SHOW INDEX FROM jobs");
            $existingIndexes = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $existingIndexes[] = $row['Column_name'];
            }

            // Indexes to add if missing
            $indexesToAdd = [
                ['column' => 'job_status', 'name' => 'idx_jobs_status'],
                ['column' => 'client_id', 'name' => 'idx_jobs_client'],
                ['column' => 'created_at', 'name' => 'idx_jobs_created'],
                ['column' => 'updated_at', 'name' => 'idx_jobs_updated']
            ];

            foreach ($indexesToAdd as $index) {
                if (!in_array($index['column'], $existingIndexes)) {
                    try {
                        $stmt = self::$pdo->prepare("CREATE INDEX {$index['name']} ON jobs ({$index['column']})");
                        $stmt->execute();
                        $results[] = "Added index {$index['name']} on jobs.{$index['column']}";
                    } catch (Exception $e) {
                        $results[] = "Failed to add index {$index['name']}: " . $e->getMessage();
                    }
                } else {
                    $results[] = "Index {$index['name']} already exists";
                }
            }

            // Optimize tables
            $stmt = self::$pdo->query("OPTIMIZE TABLE jobs, job_quotations, job_images");
            $results[] = "Optimized jobs, job_quotations, and job_images tables";

        } catch (Exception $e) {
            $results[] = "Error during optimization: " . $e->getMessage();
        }

        return $results;
    }
}

/**
 * Query optimization utilities
 */
class QueryOptimizer {

    /**
     * Get paginated jobs with optimized queries
     */
    public static function getJobsOptimized($pdo, $filters = [], $page = 1, $limit = 50) {
        $whereConditions = [];
        $params = [];

        // Base filters with optimized queries
        if (isset($filters['status'])) {
            $whereConditions[] = "j.job_status = ?";
            $params[] = $filters['status'];
        }

        if (isset($filters['client_id'])) {
            $whereConditions[] = "j.client_id = ?";
            $params[] = $filters['client_id'];
        }

        $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";
        $offset = ($page - 1) * $limit;

        // Single optimized query with JOIN instead of multiple queries
        $query = "
            SELECT
                j.id, j.item_identifier, j.fault_description, j.job_status,
                j.created_at, j.updated_at, j.contact_person,
                COALESCE(l.name, 'Default') as location_name,
                c.name as client_name,
                COUNT(ji.id) as image_count
            FROM jobs j
            LEFT JOIN locations l ON j.client_location_id = l.id
            LEFT JOIN participants c ON j.client_id = c.participantId
            LEFT JOIN job_images ji ON j.id = ji.job_id
            {$whereClause}
            GROUP BY j.id
            ORDER BY j.created_at DESC
            LIMIT {$limit} OFFSET {$offset}
        ";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get job details with related data efficiently
     */
    public static function getJobDetailsOptimized($pdo, $jobId) {
        // Single comprehensive query instead of multiple queries
        $stmt = $pdo->prepare("
            SELECT
                j.*,
                c.name as client_name,
                c.email as client_email,
                sp.name as provider_name,
                sp.participantType as provider_type,
                l.name as location_name,
                l.address as location_address,
                l.coordinates as location_coordinates,
                u.first_name,
                u.last_name,
                u.email as reporting_user_email,
                COALESCE(jq.id, NULL) as current_quotation_id,
                COALESCE(jq.quotation_amount, NULL) as quotation_amount,
                COALESCE(jq.status, NULL) as quotation_status,
                (SELECT COUNT(*) FROM job_images WHERE job_id = j.id) as image_count
            FROM jobs j
            LEFT JOIN participants c ON j.client_id = c.participantId
            LEFT JOIN participants sp ON j.assigned_provider_participant_id = sp.participantId
            LEFT JOIN locations l ON j.client_location_id = l.id
            LEFT JOIN users u ON j.reporting_user_id = u.userId
            LEFT JOIN job_quotations jq ON j.current_quotation_id = jq.id
            WHERE j.id = ?
        ");

        $stmt->execute([$jobId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Cache user permission lookups
     */
    private static $permissionCache = [];

    public static function canUserEditJobCached($pdo, $userId, $roleId, $job) {
        $cacheKey = "user_{$userId}_job_{$job['id']}_role_{$roleId}";

        if (!isset(self::$permissionCache[$cacheKey])) {
            // XS job check
            $isXSProvider = false;
            if ($job['assigned_provider_participant_id']) {
                $stmt = $pdo->prepare("
                    SELECT participantType FROM participant_type
                    WHERE participantId = ? AND participantType = 'XS'
                ");
                $stmt->execute([$job['assigned_provider_participant_id']]);
                $isXSProvider = $stmt->fetch() !== false;
            }

            $canEdit = false;

            if ($isXSProvider && $roleId === 2) {
                $canEdit = true;
            } elseif ($roleId === 1 && $job['reporting_user_id'] === $userId && $job['job_status'] === 'Reported') {
                $canEdit = true;
            } elseif ($roleId === 2 && !$isXSProvider) {
                $canEdit = in_array($job['job_status'], ['Reported', 'Declined', 'Quote Requested', 'Completed']);
            }

            self::$permissionCache[$cacheKey] = $canEdit;
        }

        return self::$permissionCache[$cacheKey];
    }
}
?>
