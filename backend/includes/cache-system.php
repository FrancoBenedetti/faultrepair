<?php
/**
 * Simple Query Result Caching System
 * Caches expensive queries to improve performance
 */

class QueryCache {

    private static $cache = [];
    private static $cacheExpiry = [];

    /**
     * Get cached data if valid, otherwise return null
     */
    public static function get($cacheKey) {
        if (!isset(self::$cache[$cacheKey])) {
            return null;
        }

        // Check if cache is expired
        if (isset(self::$cacheExpiry[$cacheKey]) && time() > self::$cacheExpiry[$cacheKey]) {
            unset(self::$cache[$cacheKey]);
            unset(self::$cacheExpiry[$cacheKey]);
            return null;
        }

        return self::$cache[$cacheKey];
    }

    /**
     * Store data in cache with expiry time
     */
    public static function set($cacheKey, $data, $ttlSeconds = 300) { // Default 5 minutes
        self::$cache[$cacheKey] = $data;
        self::$cacheExpiry[$cacheKey] = time() + $ttlSeconds;
    }

    /**
     * Clear specific cache entry
     */
    public static function clear($cacheKey) {
        if (isset(self::$cache[$cacheKey])) {
            unset(self::$cache[$cacheKey]);
            unset(self::$cacheExpiry[$cacheKey]);
        }
    }

    /**
     * Clear all expired cache entries
     */
    public static function cleanup() {
        $now = time();
        $expiredKeys = [];

        foreach (self::$cacheExpiry as $key => $expiry) {
            if ($now > $expiry) {
                $expiredKeys[] = $key;
            }
        }

        foreach ($expiredKeys as $key) {
            unset(self::$cache[$key]);
            unset(self::$cacheExpiry[$key]);
        }

        return count($expiredKeys);
    }

    /**
     * Get cache statistics
     */
    public static function getStats() {
        $totalEntries = count(self::$cache);
        $expiredEntries = 0;

        foreach (self::$cacheExpiry as $expiry) {
            if (time() > $expiry) {
                $expiredEntries++;
            }
        }

        return [
            'total_entries' => $totalEntries,
            'expired_entries' => $expiredEntries,
            'valid_entries' => $totalEntries - $expiredEntries
        ];
    }

    /**
     * Get cache hit ratio (requires tracking implementation)
     */
    public static function getHitRatio() {
        return ['message' => 'Cache hit ratio tracking not implemented yet'];
    }
}

/**
 * Cached Database Query Utilities
 */
class CachedQuery {

    private static $pdo;

    /**
     * Initialize with PDO connection
     */
    public static function init($pdo) {
        self::$pdo = $pdo;
    }

    /**
     * Execute query with caching
     */
    public static function queryCached($sql, $params = [], $cacheKey = null, $cacheTtl = 300) {
        // Generate cache key if not provided
        if ($cacheKey === null) {
            $cacheKey = md5($sql . serialize($params));
        }

        // Check cache first
        $cachedResult = QueryCache::get($cacheKey);
        if ($cachedResult !== null) {
            return $cachedResult;
        }

        // Execute query
        try {
            $stmt = self::$pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Cache the result
            QueryCache::set($cacheKey, $result, $cacheTtl);

            return $result;
        } catch (Exception $e) {
            error_log("CachedQuery::queryCached - Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get job counts with caching
     */
    public static function getJobCountsCached($userId, $roleId, $entityId) {
        $cacheKey = "job_counts_{$userId}_{$roleId}_{$entityId}";
        $cacheTtl = 120; // 2 minutes for status counts

        $cached = QueryCache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        // Count jobs by status for this provider/client
        $counts = [];

        try {
            if ($roleId === 3) { // Service Provider Admin
                $stmt = self::$pdo->prepare("
                    SELECT job_status, COUNT(*) as count
                    FROM jobs j
                    LEFT JOIN participant_type pt ON j.assigned_provider_participant_id = pt.participantId
                    WHERE j.assigned_provider_participant_id = ? AND (pt.participantType != 'XS' OR pt.participantType IS NULL)
                    GROUP BY job_status
                ");
            } else { // Client Admin
                $stmt = self::$pdo->prepare("
                    SELECT job_status, COUNT(*) as count
                    FROM jobs j
                    WHERE j.client_id = ?
                    GROUP BY job_status
                ");
            }

            $stmt->execute([$entityId]);
            $counts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Convert to associative array
            $formattedCounts = [];
            foreach ($counts as $count) {
                $formattedCounts[$count['job_status']] = (int)$count['count'];
            }

            QueryCache::set($cacheKey, $formattedCounts, $cacheTtl);
            return $formattedCounts;

        } catch (Exception $e) {
            error_log("CachedQuery::getJobCountsCached - Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get provider approval counts with caching
     */
    public static function getProviderApprovalsCached($clientId) {
        $cacheKey = "provider_approvals_{$clientId}";
        $cacheTtl = 300; // 5 minutes

        $cached = QueryCache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        try {
            $stmt = self::$pdo->prepare("
                SELECT COUNT(*) as total_approved
                FROM participant_approvals pa
                WHERE pa.client_participant_id = ?
            ");
            $stmt->execute([$clientId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $count = (int)($result['total_approved'] ?? 0);

            QueryCache::set($cacheKey, $count, $cacheTtl);
            return $count;

        } catch (Exception $e) {
            error_log("CachedQuery::getProviderApprovalsCached - Error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Invalidate cache for specific patterns
     */
    public static function invalidateCache($pattern) {
        $keysToRemove = [];

        foreach (array_keys(QueryCache::getStats()) as $cacheKey) {
            // This is a simplified pattern matching - in production you'd want regex or more sophisticated matching
            if (strpos($cacheKey, $pattern) !== false) {
                QueryCache::clear($cacheKey);
            }
        }
    }
}

/**
 * Disk Space Monitoring
 */
class DiskMonitor {

    /**
     * Get disk usage statistics
     */
    public static function getDiskStats($path = null) {
        $path = $path ?: $_SERVER['DOCUMENT_ROOT'];

        $free = disk_free_space($path);
        $total = disk_total_space($path);
        $used = $total - $free;

        $freeMB = round($free / 1024 / 1024, 2);
        $totalMB = round($total / 1024 / 1024, 2);
        $usedMB = round($used / 1024 / 1024, 2);
        $usedPercent = round(($used / $total) * 100, 2);

        return [
            'path' => $path,
            'total_mb' => $totalMB,
            'used_mb' => $usedMB,
            'free_mb' => $freeMB,
            'used_percent' => $usedPercent,
            'warning' => $usedPercent > 85,
            'critical' => $usedPercent > 95
        ];
    }

    /**
     * Check if disk space is getting low
     */
    public static function checkDiskSpace($path = null) {
        $stats = self::getDiskStats($path);

        $warnings = [];

        if ($stats['used_percent'] > 95) {
            $warnings[] = 'CRITICAL: Disk usage over 95% - immediate action required';
        } elseif ($stats['used_percent'] > 85) {
            $warnings[] = 'WARNING: Disk usage over 85% - consider cleanup';
        }

        return [
            'disk_stats' => $stats,
            'warnings' => $warnings
        ];
    }

    /**
     * Get application-specific storage usage
     */
    public static function getApplicationStorageStats() {
        $stats = [];

        $uploadsDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads';
        $logsDir = $_SERVER['DOCUMENT_ROOT'] . '/all-logs';

        // Uploads directory size
        $uploadsSize = self::getDirectorySize($uploadsDir);
        $stats['uploads'] = [
            'size_mb' => round($uploadsSize / 1024 / 1024, 2),
            'images_count' => count(glob($uploadsDir . '/job_images/*')),
            'quotes_count' => count(glob($uploadsDir . '/quotes/*'))
        ];

        // Logs directory size
        $logsSize = self::getDirectorySize($logsDir);
        $stats['logs'] = [
            'size_mb' => round($logsSize / 1024 / 1024, 2),
            'files_count' => count(glob($logsDir . '/*'))
        ];

        // Database file size (approximate)
        try {
            $dbFile = $_SERVER['DOCUMENT_ROOT'] . '/snappy-dev.sql';
            if (file_exists($dbFile)) {
                $stats['database_dump'] = [
                    'size_mb' => round(filesize($dbFile) / 1024 / 1024, 2)
                ];
            }
        } catch (Exception $e) {
            $stats['database_dump'] = ['error' => $e->getMessage()];
        }

        return $stats;
    }

    /**
     * Calculate directory size recursively
     */
    private static function getDirectorySize($directory) {
        $size = 0;

        if (!is_dir($directory)) {
            return $size;
        }

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)) as $file) {
            $size += $file->getSize();
        }

        return $size;
    }
}
?>
