<?php
/**
 * Security Dashboard API
 * Provides comprehensive security monitoring and management for administrators
 */

require_once '../config/database.php';
require_once '../includes/JWT.php';
require_once '../includes/security-audit.php';

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

// JWT Authentication
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
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid token']);
    exit;
}

// Only allow admin users (role_id = 2 for client admins)
if ($role_id !== 2) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied. Admin access required.']);
    exit;
}

// Initialize security auditing
SecurityAuditor::init($pdo, $user_id);

try {
    // Get security dashboard data
    $securityData = SecurityAuditor::getSecurityDashboard();

    // Get additional security metrics
    $timeframe = isset($_GET['timeframe']) ? $_GET['timeframe'] : '24h';

    // Calculate timeframe
    switch ($timeframe) {
        case 'week':
            $intervalHours = 7 * 24;
            $intervalText = '7 days';
            break;
        case 'month':
            $intervalHours = 30 * 24;
            $intervalText = '30 days';
            break;
        case '24h':
        default:
            $intervalHours = 24;
            $intervalText = '24 hours';
            break;
    }

    // Get locked accounts
    $stmt = $pdo->prepare("
        SELECT
            u.userId,
            u.email,
            u.username,
            u.locked_until,
            u.failed_login_count,
            pt.participantType,
            p.name as participant_name
        FROM users u
        LEFT JOIN participants p ON u.entity_id = p.participantId
        LEFT JOIN participant_type pt ON u.entity_id = pt.participantId
        WHERE u.locked_until > NOW()
        ORDER BY u.locked_until DESC
        LIMIT 10
    ");
    $stmt->execute();
    $lockedAccounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get users with 2FA enabled
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as totp_enabled_count
        FROM user_security
        WHERE totp_enabled = 1
    ");
    $stmt->execute();
    $totpStats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get active sessions count
    $activeSessionsCount = count(SessionManager::getActiveSessions());

    // Get users with weak passwords (simple heuristic)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as weak_password_count
        FROM users u
        LEFT JOIN user_security us ON u.userId = us.user_id
        WHERE us.password_last_changed IS NULL
        OR us.password_last_changed < DATE_SUB(NOW(), INTERVAL 90 DAY)
    ");
    $stmt->execute();
    $weakPasswords = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get API rate limiting violations
    $stmt = $pdo->prepare("
        SELECT endpoint, COUNT(*) as violation_count
        FROM security_events
        WHERE event_type = 'rate_limit_exceeded'
        AND created_at >= DATE_SUB(NOW(), INTERVAL {$intervalHours} HOUR)
        GROUP BY endpoint
        ORDER BY violation_count DESC
        LIMIT 5
    ");
    $stmt->execute();
    $rateLimitViolations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get suspicious IP addresses
    $stmt = $pdo->prepare("
        SELECT ip_address, COUNT(*) as event_count
        FROM security_events
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$intervalHours} HOUR)
        GROUP BY ip_address
        HAVING event_count > 5
        ORDER BY event_count DESC
        LIMIT 10
    ");
    $stmt->execute();
    $suspiciousIPs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get user activity trends
    $stmt = $pdo->prepare("
        SELECT
            DATE_FORMAT(created_at, '%Y-%m-%d') as date,
            COUNT(*) as login_attempts,
            SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) as successful_logins,
            SUM(CASE WHEN success = 0 THEN 1 ELSE 0 END) as failed_logins
        FROM authentication_log
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$intervalHours} HOUR)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m-%d')
        ORDER BY date DESC
        LIMIT 7
    ");
    $stmt->execute();
    $authenticationTrends = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get authentication attempts by type
    $stmt = $pdo->prepare("
        SELECT action, COUNT(*) as count, AVG(success) as success_rate
        FROM authentication_log
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL {$intervalHours} HOUR)
        GROUP BY action
        ORDER BY count DESC
    ");
    $stmt->execute();
    $authAttemptsByType = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Compile comprehensive security report
    $response = [
        'timestamp' => date('Y-m-d H:i:s'),
        'timeframe' => $timeframe,
        'timeframe_description' => $intervalText,
        'security_overview' => [
            'failed_logins_24h' => $securityData ? $securityData['failed_logins_24h'] : 0,
            'rate_limit_hits_24h' => $securityData ? $securityData['rate_limit_hits_24h'] : 0,
            'locked_accounts' => count($lockedAccounts),
            'active_sessions' => $activeSessionsCount,
            'users_with_2fa' => $totpStats['totp_enabled_count'],
            'users_weak_passwords' => $weakPasswords['weak_password_count']
        ],
        'threats_and_incidents' => [
            'locked_accounts' => $lockedAccounts,
            'rate_limit_violations' => $rateLimitViolations,
            'suspicious_ips' => $suspiciousIPs
        ],
        'activity_metrics' => [
            'authentication_trends' => $authenticationTrends,
            'auth_attempts_by_type' => $authAttemptsByType
        ],
        'security_health_score' => self::calculateSecurityHealthScore($securityData, [
            'locked_accounts_count' => count($lockedAccounts),
            'rate_limit_violations' => count($rateLimitViolations),
            'suspicious_ips_count' => count($suspiciousIPs),
            'totp_enabled' => $totpStats['totp_enabled_count'],
            'weak_passwords' => $weakPasswords['weak_password_count']
        ]),
        'recommendations' => self::generateSecurityRecommendations([
            'failed_logins' => $securityData ? $securityData['failed_logins_24h'] : 0,
            'locked_accounts' => count($lockedAccounts),
            'rate_limits' => $securityData ? $securityData['rate_limit_hits_24h'] : 0,
            'totp_enabled' => $totpStats['totp_enabled_count'],
            'weak_passwords' => $weakPasswords['weak_password_count'],
            'suspicious_activity' => count($suspiciousIPs)
        ])
    ];

    // Include recent events if available
    if ($securityData && isset($securityData['recent_events'])) {
        $response['recent_security_events'] = array_slice($securityData['recent_events'], 0, 20);
    }

    echo json_encode($response);

    SecurityAuditor::logSecurityEvent('security_dashboard_accessed', "Admin accessed security dashboard", $user_id);

} catch (Exception $e) {
    error_log("security-dashboard.php - Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to retrieve security dashboard data']);
}

/**
 * Calculate an overall security health score (0-100)
 */
function calculateSecurityHealthScore($securityData, $additionalMetrics) {
    $score = 100;

    // Deduct points based on security issues
    if ($securityData) {
        $score -= min($securityData['failed_logins_24h'] * 2, 20); // Max 20 points for failed logins
        $score -= min($securityData['rate_limit_hits_24h'] * 5, 15); // Max 15 points for rate limits
    }

    $score -= min($additionalMetrics['locked_accounts_count'] * 10, 25); // Max 25 points for locked accounts
    $score -= min($additionalMetrics['rate_limit_violations'] * 3, 10); // Max 10 points for rate limit violations
    $score -= min($additionalMetrics['suspicious_ips_count'] * 5, 15); // Max 15 points for suspicious IPs
    $score -= min($additionalMetrics['weak_passwords'] * 2, 10); // Max 10 points for weak passwords

    // Bonus points for security features
    $score += min($additionalMetrics['totp_enabled'] * 0.5, 10); // Max 10 points for 2FA usage

    return max(0, min(100, round($score)));
}

/**
 * Generate security recommendations based on current state
 */
function generateSecurityRecommendations($metrics) {
    $recommendations = [];

    if ($metrics['failed_logins'] > 10) {
        $recommendations[] = [
            'priority' => 'critical',
            'category' => 'authentication',
            'title' => 'High Failed Login Attempts Detected',
            'description' => "{$metrics['failed_logins']} failed login attempts in the last 24 hours",
            'action' => 'Investigate brute force attacks and implement stricter login policies',
            'impact' => 'Account security compromised'
        ];
    }

    if ($metrics['locked_accounts'] > 2) {
        $recommendations[] = [
            'priority' => 'high',
            'category' => 'authentication',
            'title' => 'Multiple Accounts Locked',
            'description' => "{$metrics['locked_accounts']} accounts are currently locked",
            'action' => 'Review lockout policies and consider extending lockout duration',
            'impact' => 'Users unable to access system'
        ];
    }

    if ($metrics['rate_limits'] > 20) {
        $recommendations[] = [
            'priority' => 'medium',
            'category' => 'performance',
            'title' => 'High Rate Limiting Activity',
            'description' => "{$metrics['rate_limits']} rate limit violations detected",
            'action' => 'Review API usage patterns or increase rate limits if necessary',
            'impact' => 'Legitimate users experiencing rate limiting'
        ];
    }

    if ($metrics['totp_enabled'] < 5) {
        $recommendations[] = [
            'priority' => 'medium',
            'category' => 'security',
            'title' => 'Low 2FA Adoption',
            'description' => "Only {$metrics['totp_enabled']} users have 2FA enabled",
            'action' => 'Encourage or mandate two-factor authentication for all users',
            'impact' => 'Improved account security'
        ];
    }

    if ($metrics['weak_passwords'] > 10) {
        $recommendations[] = [
            'priority' => 'high',
            'category' => 'password_policy',
            'title' => 'Many Users Need Password Updates',
            'description' => "{$metrics['weak_passwords']} users have weak or outdated passwords",
            'action' => 'Force password resets for users with weak passwords',
            'impact' => 'Strengthen overall system security'
        ];
    }

    if ($metrics['suspicious_activity'] > 0) {
        $recommendations[] = [
            'priority' => 'critical',
            'category' => 'threat_detection',
            'title' => 'Suspicious IP Addresses Detected',
            'description' => "{$metrics['suspicious_activity']} IP addresses flagged for suspicious activity",
            'action' => 'Review IP blocking rules and implement additional security measures',
            'impact' => 'System under potential attack vectors'
        ];
    }

    // Always include positive recommendations if no critical issues
    if (empty($recommendations)) {
        $recommendations[] = [
            'priority' => 'low',
            'category' => 'maintenance',
            'title' => 'Security Systems Operating Normally',
            'description' => 'All security metrics within acceptable ranges',
            'action' => 'Continue monitoring and maintain current security practices',
            'impact' => 'System security maintained'
        ];
    }

    return $recommendations;
}
?>
