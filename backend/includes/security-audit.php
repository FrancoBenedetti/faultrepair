<?php
/**
 * Advanced Security & Audit System
 * Provides comprehensive security monitoring, user authentication enhancements,
 * and audit logging for the fault reporting system.
 */

class SecurityAuditor {

    private static $pdo;
    private static $currentUser = null;

    /**
     * Initialize security auditing
     */
    public static function init($pdo, $userId = null, $sessionId = null) {
        self::$pdo = $pdo;

        if ($userId) {
            self::$currentUser = $userId;
            self::logAuthentication('session_start', 'success', "User session started");
        }

        // Clean up old login attempts
        self::cleanupOldAttempts();
    }

    /**
     * Enhanced JWT token validation with security logging
     */
    public static function validateAndLogToken($token) {
        try {
            $payload = JWT::decode($token);
            $userId = $payload['user_id'];

            // Get user details for security context
            $stmt = self::$pdo->prepare("
                SELECT u.userId, u.email, u.is_active, u.role_id, u.entity_type, u.entity_id,
                       pt.participantType, p.name as participant_name
                FROM users u
                LEFT JOIN participants p ON u.entity_id = p.participantId
                LEFT JOIN participant_type pt ON u.entity_id = pt.participantId
                WHERE u.userId = ?
            ");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                self::logAuthentication('token_validation', 'failure', "User not found: {$userId}");
                return null;
            }

            if (!$user['is_active']) {
                self::logAuthentication('token_validation', 'failure', "Inactive user attempted access: {$user['email']}");
                return null;
            }

            // Check for suspicious patterns
            self::checkSuspiciousActivity($userId, $payload);

            self::logAuthentication('token_validation', 'success', "Token validated for: {$user['email']}");
            return $payload;

        } catch (Exception $e) {
            self::logAuthentication('token_validation', 'failure', "Token validation failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Two-factor authentication support
     */
    public static function generateTOTPCode($userId) {
        try {
            // Get user secret key
            $stmt = self::$pdo->prepare("
                SELECT totp_secret, totp_enabled
                FROM user_security
                WHERE user_id = ?
            ");
            $stmt->execute([$userId]);
            $userSecurity = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$userSecurity || !$userSecurity['totp_enabled']) {
                return false;
            }

            // Generate TOTP code (simplified implementation)
            $secret = $userSecurity['totp_secret'];
            $time = floor(time() / 30); // 30-second windows
            $code = self::generateTOTP($secret, $time);

            return $code;

        } catch (Exception $e) {
            error_log("SecurityAuditor: TOTP generation failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verify two-factor authentication code
     */
    public static function verifyTOTPCode($userId, $code) {
        try {
            $stmt = self::$pdo->prepare("
                SELECT totp_secret, totp_enabled
                FROM user_security
                WHERE user_id = ?
            ");
            $stmt->execute([$userId]);
            $userSecurity = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$userSecurity || !$userSecurity['totp_enabled']) {
                return false;
            }

            $secret = $userSecurity['totp_secret'];

            // Check current and adjacent time windows (allow for time sync issues)
            for ($i = -1; $i <= 1; $i++) {
                $time = floor(time() / 30) + $i;
                if (self::generateTOTP($secret, $time) === $code) {
                    self::logAuthentication('2fa_verification', 'success', "2FA code verified for user: {$userId}");
                    return true;
                }
            }

            self::logAuthentication('2fa_verification', 'failure', "Invalid 2FA code for user: {$userId}");
            return false;

        } catch (Exception $e) {
            error_log("SecurityAuditor: TOTP verification failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Rate limiting for API endpoints
     */
    public static function checkRateLimit($userId, $endpoint, $limit = 100, $timeWindow = 3600) {
        try {
            // Clean up old entries
            $stmt = self::$pdo->prepare("
                DELETE FROM api_rate_limits
                WHERE created_at < DATE_SUB(NOW(), INTERVAL ? SECOND)
            ");
            $stmt->execute([$timeWindow]);

            // Get current request count
            $stmt = self::$pdo->prepare("
                SELECT COUNT(*) as request_count
                FROM api_rate_limits
                WHERE user_id = ? AND endpoint = ?
                AND created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
            ");
            $stmt->execute([$userId, $endpoint, $timeWindow]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            $currentCount = $result['request_count'];

            if ($currentCount >= $limit) {
                self::logSecurityEvent('rate_limit_exceeded', "User {$userId} exceeded rate limit on {$endpoint}", $userId);
                return false; // Rate limit exceeded
            }

            // Log this request
            $stmt = self::$pdo->prepare("
                INSERT INTO api_rate_limits (user_id, endpoint, created_at)
                VALUES (?, ?, NOW())
            ");
            $stmt->execute([$userId, $endpoint]);

            return true;

        } catch (Exception $e) {
            error_log("SecurityAuditor: Rate limit check failed: " . $e->getMessage());
            return true; // Allow request on failure to avoid blocking users
        }
    }

    /**
     * Monitor for suspicious activity patterns
     */
    private static function checkSuspiciousActivity($userId, $payload) {
        try {
            // Get recent login attempts
            $stmt = self::$pdo->prepare("
                SELECT COUNT(*) as recent_attempts, MIN(created_at) as first_attempt
                FROM authentication_log
                WHERE user_id = ? AND action = 'login_attempt' AND success = 0
                AND created_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)
            ");
            $stmt->execute([$userId]);
            $attemptData = $stmt->fetch(PDO::FETCH_ASSOC);

            // Check for brute force attempts (more than 5 failed attempts in 15 minutes)
            if ($attemptData['recent_attempts'] >= 5) {
                self::logSecurityEvent('suspicious_activity', "Brute force attack detected: {$attemptData['recent_attempts']} failed attempts for user {$userId}", $userId);

                // Could implement account lockout here
                // For now, just log the suspicious activity
            }

            // Check for impossible travel (login from multiple locations within short time)
            // This would require storing IP/location data - simplified for now

        } catch (Exception $e) {
            error_log("SecurityAuditor: Suspicious activity check failed: " . $e->getMessage());
        }
    }

    /**
     * Log authentication events
     */
    public static function logAuthentication($action, $success, $details = '') {
        try {
            $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

            $stmt = self::$pdo->prepare("
                INSERT INTO authentication_log (
                    user_id, action, success, details, ip_address, user_agent, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                self::$currentUser,
                $action,
                $success ? 1 : 0,
                $details,
                $ipAddress,
                $userAgent
            ]);

        } catch (Exception $e) {
            error_log("SecurityAuditor: Failed to log authentication event: " . $e->getMessage());
        }
    }

    /**
     * Log security events
     */
    public static function logSecurityEvent($eventType, $description, $userId = null) {
        try {
            $ipAddress = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';

            $stmt = self::$pdo->prepare("
                INSERT INTO security_events (
                    user_id, event_type, description, ip_address, created_at
                ) VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $userId ?: self::$currentUser,
                $eventType,
                $description,
                $ipAddress
            ]);

            error_log("SecurityAuditor: {$eventType} - {$description}");

        } catch (Exception $e) {
            error_log("SecurityAuditor: Failed to log security event: " . $e->getMessage());
        }
    }

    /**
     * Audit data access patterns
     */
    public static function logDataAccess($tableName, $operation, $recordId = null, $userId = null) {
        try {
            $stmt = self::$pdo->prepare("
                INSERT INTO data_access_log (
                    user_id, table_name, operation, record_id, ip_address, created_at
                ) VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $userId ?: self::$currentUser,
                $tableName,
                $operation,
                $recordId,
                $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? ''
            ]);

        } catch (Exception $e) {
            error_log("SecurityAuditor: Failed to log data access: " . $e->getMessage());
        }
    }

    /**
     * Clean up old authentication attempts
     */
    private static function cleanupOldAttempts() {
        try {
            $stmt = self::$pdo->prepare("
                DELETE FROM authentication_log
                WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            $stmt->execute();

            // Also cleanup rate limiting data
            $stmt = self::$pdo->prepare("
                DELETE FROM api_rate_limits
                WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            $stmt->execute();

        } catch (Exception $e) {
            error_log("SecurityAuditor: Cleanup failed: " . $e->getMessage());
        }
    }

    /**
     * Get security dashboard data for administrators
     */
    public static function getSecurityDashboard() {
        try {
            $stats = [];

            // Failed login attempts in last 24h
            $stmt = self::$pdo->prepare("
                SELECT COUNT(*) as failed_attempts
                FROM authentication_log
                WHERE action = 'login_attempt' AND success = 0
                AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            $stmt->execute();
            $stats['failed_logins_24h'] = $stmt->fetch(PDO::FETCH_ASSOC)['failed_attempts'];

            // Rate limiting hits
            $stmt = self::$pdo->prepare("
                SELECT COUNT(*) as rate_limit_hits
                FROM security_events
                WHERE event_type = 'rate_limit_exceeded'
                AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            $stmt->execute();
            $stats['rate_limit_hits_24h'] = $stmt->fetch(PDO::FETCH_ASSOC)['rate_limit_hits'];

            // Recent security events
            $stmt = self::$pdo->prepare("
                SELECT event_type, description, ip_address, created_at,
                       COALESCE(u.username, 'Unknown') as username
                FROM security_events se
                LEFT JOIN users u ON se.user_id = u.userId
                ORDER BY created_at DESC
                LIMIT 20
            ");
            $stmt->execute();
            $stats['recent_events'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $stats;

        } catch (Exception $e) {
            error_log("SecurityAuditor: Failed to get security dashboard: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Simple TOTP code generation (RFC 6238 compliant)
     */
    private static function generateTOTP($secret, $time) {
        $secretKey = self::base32Decode($secret);
        $timeString = pack('N*', 0) . pack('N*', $time); // 64-bit big-endian time

        $hash = hash_hmac('sha1', $timeString, $secretKey, true);
        $offset = ord($hash[19]) & 0xf;

        $code = (
            ((ord($hash[$offset]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        );

        return str_pad($code % 10**6, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Base32 decode for TOTP secret
     */
    private static function base32Decode($base32String) {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bits = '';
        $chars = str_split($base32String);

        foreach ($chars as $char) {
            $position = strpos($alphabet, $char);
            if ($position === false) {
                continue;
            }
            $bits .= str_pad(decbin($position), 5, '0', STR_PAD_LEFT);
        }

        $bytes = str_split($bits, 8);
        $decoded = '';

        foreach ($bytes as $byte) {
            if (strlen($byte) < 8) {
                break;
            }
            $decoded .= chr(bindec($byte));
        }

        return $decoded;
    }
}

/**
 * Password Policy Enforcement
 */
class PasswordPolicy {

    /**
     * Check password strength according to policy
     */
    public static function checkPasswordStrength($password) {
        $issues = [];
        $score = 0;

        // Length requirements
        if (strlen($password) < 8) {
            $issues[] = "Password must be at least 8 characters long";
        } else if (strlen($password) >= 12) {
            $score += 1;
        }

        // Character variety
        if (!preg_match('/[a-z]/', $password)) {
            $issues[] = "Password must contain lowercase letters";
        } else {
            $score += 1;
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $issues[] = "Password must contain uppercase letters";
        } else {
            $score += 1;
        }

        if (!preg_match('/[0-9]/', $password)) {
            $issues[] = "Password must contain numbers";
        } else {
            $score += 1;
        }

        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            $issues[] = "Password must contain special characters";
        } else {
            $score += 1;
        }

        // Check against common passwords
        $commonPasswords = ['password', '123456', 'qwerty', 'admin', 'letmein'];
        if (in_array(strtolower($password), $commonPasswords)) {
            $issues[] = "This password is too common";
            $score = 0;
        }

        // Dictionary check (simplified)
        if (preg_match('/(?:pass|word|admin|user|login)/i', $password)) {
            $score -= 1;
        }

        $strength = 'weak';
        if ($score >= 4) {
            $strength = 'strong';
        } else if ($score >= 2) {
            $strength = 'medium';
        }

        return [
            'is_valid' => empty($issues),
            'strength' => $strength,
            'score' => $score,
            'issues' => $issues
        ];
    }

    /**
     * Check if password was recently used
     */
    public static function checkPasswordHistory($pdo, $userId, $hashedPassword, $historyCount = 5) {
        try {
            $stmt = $pdo->prepare("
                SELECT old_password_hash
                FROM password_history
                WHERE user_id = ?
                ORDER BY changed_at DESC
                LIMIT ?
            ");
            $stmt->execute([$userId, $historyCount]);
            $oldPasswords = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($oldPasswords as $oldPass) {
                if (password_verify($hashedPassword, $oldPass['old_password_hash'])) {
                    return false; // Password was recently used
                }
            }

            return true; // Password is new

        } catch (Exception $e) {
            error_log("PasswordPolicy: Failed to check password history: " . $e->getMessage());
            return true; // Allow password on error to avoid user frustration
        }
    }

    /**
     * Update password history
     */
    public static function updatePasswordHistory($pdo, $userId, $oldPasswordHash) {
        try {
            // Insert new password history
            $stmt = $pdo->prepare("
                INSERT INTO password_history (user_id, old_password_hash, changed_at)
                VALUES (?, ?, NOW())
            ");
            $stmt->execute([$userId, $oldPasswordHash]);

            // Cleanup old history (keep last 10)
            $stmt = $pdo->prepare("
                DELETE FROM password_history
                WHERE user_id = ? AND changed_at NOT IN (
                    SELECT changed_at FROM (
                        SELECT changed_at
                        FROM password_history
                        WHERE user_id = ?
                        ORDER BY changed_at DESC
                        LIMIT 10
                    ) as recent
                )
            ");
            $stmt->execute([$userId, $userId]);

        } catch (Exception $e) {
            error_log("PasswordPolicy: Failed to update password history: " . $e->getMessage());
        }
    }
}

/**
 * Session Security Management
 */
class SessionManager {

    private static $pdo;
    private static $maxSessions = 5; // Maximum concurrent sessions per user

    /**
     * Initialize session management
     */
    public static function init($pdo) {
        self::$pdo = $pdo;
        session_start();
    }

    /**
     * Create secure session
     */
    public static function createSecureSession($userId) {
        $sessionId = session_id();
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        try {
            // Clean up old sessions for this user
            self::cleanupOldSessionsForUser($userId);

            // Get current active sessions count
            $stmt = self::$pdo->prepare("
                SELECT COUNT(*) as active_sessions
                FROM user_sessions
                WHERE user_id = ? AND expires_at > NOW()
            ");
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result['active_sessions'] >= self::$maxSessions) {
                // Remove oldest session
                $stmt = self::$pdo->prepare("
                    DELETE FROM user_sessions
                    WHERE user_id = ?
                    ORDER BY last_activity ASC
                    LIMIT 1
                ");
                $stmt->execute([$userId]);
            }

            // Insert new session
            $expiryTime = date('Y-m-d H:i:s', time() + 24*60*60); // 24 hours
            $stmt = self::$pdo->prepare("
                INSERT INTO user_sessions (
                    user_id, session_id, ip_address, user_agent, expires_at, created_at, last_activity
                ) VALUES (?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([$userId, $sessionId, $ipAddress, $userAgent, $expiryTime]);

            SecurityAuditor::logSecurityEvent('session_created', "New session created for user {$userId}", $userId);

        } catch (Exception $e) {
            error_log("SessionManager: Failed to create secure session: " . $e->getMessage());
        }
    }

    /**
     * Validate current session
     */
    public static function validateSession($userId) {
        $sessionId = session_id();
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        try {
            $stmt = self::$pdo->prepare("
                SELECT id FROM user_sessions
                WHERE user_id = ? AND session_id = ? AND expires_at > NOW()
            ");
            $stmt->execute([$userId, $sessionId]);
            $session = $stmt->fetch();

            if ($session) {
                // Update last activity
                $stmt = self::$pdo->prepare("
                    UPDATE user_sessions
                    SET last_activity = NOW(), ip_address = ?, user_agent = ?
                    WHERE id = ?
                ");
                $stmt->execute([$ipAddress, $userAgent, $session['id']]);
                return true;
            } else {
                SecurityAuditor::logSecurityEvent('session_expired', "Invalid session accessed by user {$userId}", $userId);
                return false;
            }

        } catch (Exception $e) {
            error_log("SessionManager: Failed to validate session: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Secure log out
     */
    public static function logoutUser($userId) {
        $sessionId = session_id();

        try {
            $stmt = self::$pdo->prepare("
                DELETE FROM user_sessions
                WHERE user_id = ? AND session_id = ?
            ");
            $stmt->execute([$userId, $sessionId]);

            SecurityAuditor::logSecurityEvent('session_terminated', "User logged out: {$userId}", $userId);

            session_destroy();
            return true;

        } catch (Exception $e) {
            error_log("SessionManager: Failed to logout user: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Force logout all sessions for user (security feature)
     */
    public static function forceLogoutAllSessions($userId) {
        try {
            $stmt = self::$pdo->prepare("
                DELETE FROM user_sessions
                WHERE user_id = ?
            ");
            $stmt->execute([$userId]);

            SecurityAuditor::logSecurityEvent('force_logout', "All sessions terminated for user {$userId}", $userId);
            return true;

        } catch (Exception $e) {
            error_log("SessionManager: Failed to force logout all sessions: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get active sessions for user (for admin viewing)
     */
    public static function getActiveSessions($userId = null) {
        try {
            $query = "
                SELECT us.*, u.username, u.email
                FROM user_sessions us
                LEFT JOIN users u ON us.user_id = u.userId
                WHERE expires_at > NOW()
            ";
            $params = [];

            if ($userId) {
                $query .= " AND us.user_id = ?";
                $params[] = $userId;
            }

            $query .= " ORDER BY us.last_activity DESC";

            $stmt = self::$pdo->prepare($query);
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (Exception $e) {
            error_log("SessionManager: Failed to get active sessions: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Clean up expired sessions
     */
    private static function cleanupOldSessionsForUser($userId) {
        try {
            $stmt = self::$pdo->prepare("
                DELETE FROM user_sessions
                WHERE user_id = ? AND expires_at < NOW()
            ");
            $stmt->execute([$userId]);

        } catch (Exception $e) {
            error_log("SessionManager: Cleanup failed: " . $e->getMessage());
        }
    }
}
?>
