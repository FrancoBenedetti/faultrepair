<?php
/**
 * User Security Management API
 * Handles security settings, password changes, and user security features
 */

require_once '../config/database.php';
require_once '../includes/JWT.php';
require_once '../includes/security-audit.php';

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
    $entity_type = $payload['entity_type'];
    $entity_id = $payload['entity_id'];
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid token']);
    exit;
}

// Initialize security auditing
SecurityAuditor::init($pdo, $user_id);

$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            // Get current user security settings
            $stmt = $pdo->prepare("
                SELECT
                    us.totp_enabled,
                    us.login_notifications,
                    us.suspicious_activity_alerts,
                    us.password_last_changed,
                    us.failed_login_attempts,
                    u.password_last_changed as user_last_changed,
                    u.last_login_at,
                    u.last_login_ip,
                    u.failed_login_count
                FROM user_security us
                RIGHT JOIN users u ON us.user_id = u.userId
                WHERE u.userId = ?
            ");
            $stmt->execute([$user_id]);
            $securitySettings = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$securitySettings) {
                // Initialize user security settings
                $stmt = $pdo->prepare("
                    INSERT INTO user_security (user_id, created_at, updated_at)
                    VALUES (?, NOW(), NOW())
                ");
                $stmt->execute([$user_id]);

                // Recreate the security settings with defaults
                $securitySettings = [
                    'totp_enabled' => 0,
                    'login_notifications' => 1,
                    'suspicious_activity_alerts' => 1,
                    'password_last_changed' => null,
                    'failed_login_attempts' => 0,
                    'user_last_changed' => null,
                    'last_login_at' => null,
                    'last_login_ip' => null,
                    'failed_login_count' => 0
                ];
            }

            // Get active sessions for current user
            $activeSessions = SessionManager::getActiveSessions($user_id);

            // Get password history count
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as history_count
                FROM password_history
                WHERE user_id = ?
            ");
            $stmt->execute([$user_id]);
            $passwordHistory = $stmt->fetch(PDO::FETCH_ASSOC);

            // Get recent security events for user
            $stmt = $pdo->prepare("
                SELECT event_type, description, ip_address, created_at
                FROM security_events
                WHERE user_id = ?
                ORDER BY created_at DESC
                LIMIT 10
            ");
            $stmt->execute([$user_id]);
            $recentEvents = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'security_settings' => [
                    'two_factor_enabled' => (bool)$securitySettings['totp_enabled'],
                    'login_notifications' => (bool)$securitySettings['login_notifications'],
                    'suspicious_activity_alerts' => (bool)$securitySettings['suspicious_activity_alerts'],
                    'password_last_changed' => $securitySettings['user_last_changed'],
                    'last_login' => [
                        'at' => $securitySettings['last_login_at'],
                        'ip' => $securitySettings['last_login_ip']
                    ]
                ],
                'security_metrics' => [
                    'failed_login_attempts' => (int)$securitySettings['failed_login_count'],
                    'password_history_count' => (int)$passwordHistory['history_count'],
                    'active_sessions_count' => count($activeSessions)
                ],
                'active_sessions' => $activeSessions,
                'recent_events' => $recentEvents
            ]);

            SecurityAuditor::logDataAccess('user_security', 'SELECT', $user_id, $user_id);
            break;

        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);

            if (!$input || !isset($input['action'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Action is required']);
                exit;
            }

            $action = $input['action'];

            switch ($action) {
                case 'setup_2fa':
                    // Generate TOTP secret and setup 2FA
                    $secret = self::generateTOTPSecret();

                    // Store secret temporarily
                    $stmt = $pdo->prepare("
                        INSERT INTO user_security (user_id, totp_secret, totp_enabled, created_at, updated_at)
                        VALUES (?, ?, 0, NOW(), NOW())
                        ON DUPLICATE KEY UPDATE totp_secret = ?, updated_at = NOW()
                    ");
                    $stmt->execute([$user_id, $secret, $secret]);

                    // Generate QR code data
                    $qrData = 'otpauth://totp/FaultReport:' . $user_id . '?secret=' . $secret . '&issuer=FaultReport';

                    echo json_encode([
                        'success' => true,
                        'message' => '2FA setup initiated',
                        'secret' => $secret,
                        'qr_code_data' => $qrData
                    ]);

                    SecurityAuditor::logSecurityEvent('2fa_setup_initiated', "User initiated 2FA setup", $user_id);
                    break;

                case 'verify_2fa_setup':
                    // Verify the setup with the first code
                    if (!isset($input['code'])) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Verification code is required']);
                        exit;
                    }

                    $code = $input['code'];

                    // Get secret from user_security
                    $stmt = $pdo->prepare("
                        SELECT totp_secret
                        FROM user_security
                        WHERE user_id = ? AND totp_enabled = 0
                    ");
                    $stmt->execute([$user_id]);
                    $userSecurity = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$userSecurity) {
                        http_response_code(400);
                        echo json_encode(['error' => '2FA setup not initiated or already enabled']);
                        exit;
                    }

                    if (!SecurityAuditor::verifyTOTPCode($user_id, $code)) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Invalid verification code']);
                        exit;
                    }

                    // Enable 2FA
                    $stmt = $pdo->prepare("
                        UPDATE user_security
                        SET totp_enabled = 1, updated_at = NOW()
                        WHERE user_id = ?
                    ");
                    $stmt->execute([$user_id]);

                    // Generate backup codes
                    $backupCodes = [];
                    for ($i = 0; $i < 5; $i++) {
                        $backupCodes[] = bin2hex(random_bytes(8));
                    }

                    $stmt = $pdo->prepare("
                        UPDATE user_security
                        SET backup_codes = ?, updated_at = NOW()
                        WHERE user_id = ?
                    ");
                    $stmt->execute([json_encode($backupCodes), $user_id]);

                    echo json_encode([
                        'success' => true,
                        'message' => '2FA successfully enabled',
                        'backup_codes' => $backupCodes // Show once only!
                    ]);

                    SecurityAuditor::logSecurityEvent('2fa_enabled', "Two-factor authentication enabled", $user_id);
                    break;

                case 'disable_2fa':
                    // Disable 2FA (requires current 2FA verification)
                    if (!isset($input['code'])) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Verification code is required']);
                        exit;
                    }

                    if (!SecurityAuditor::verifyTOTPCode($user_id, $input['code'])) {
                        SecurityAuditor::logSecurityEvent('2fa_disable_failed', "Invalid 2FA code provided for disabling", $user_id);
                        http_response_code(400);
                        echo json_encode(['error' => 'Invalid verification code']);
                        exit;
                    }

                    $stmt = $pdo->prepare("
                        UPDATE user_security
                        SET totp_enabled = 0, backup_codes = NULL, updated_at = NOW()
                        WHERE user_id = ?
                    ");
                    $stmt->execute([$user_id]);

                    echo json_encode([
                        'success' => true,
                        'message' => '2FA successfully disabled'
                    ]);

                    SecurityAuditor::logSecurityEvent('2fa_disabled', "Two-factor authentication disabled", $user_id);
                    break;

                case 'change_password':
                    // Change password with security checks
                    if (!isset($input['current_password']) || !isset($input['new_password'])) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Current and new passwords are required']);
                        exit;
                    }

                    $currentPassword = $input['current_password'];
                    $newPassword = $input['new_password'];

                    // Verify current password
                    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE userId = ?");
                    $stmt->execute([$user_id]);
                    $userRecord = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!password_verify($currentPassword, $userRecord['password_hash'])) {
                        SecurityAuditor::logAuthentication('password_change', 0, 'Incorrect current password provided');
                        http_response_code(400);
                        echo json_encode(['error' => 'Current password is incorrect']);
                        exit;
                    }

                    // Check password strength
                    $strengthCheck = PasswordPolicy::checkPasswordStrength($newPassword);
                    if (!$strengthCheck['is_valid']) {
                        http_response_code(400);
                        echo json_encode([
                            'error' => 'Password does not meet requirements',
                            'issues' => $strengthCheck['issues']
                        ]);
                        exit;
                    }

                    // Check password history (prevent reuse of last 5 passwords)
                    if (!PasswordPolicy::checkPasswordHistory($pdo, $user_id, $newPassword, 5)) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Password was recently used. Please choose a different password.']);
                        exit;
                    }

                    // Hash new password
                    $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

                    // Update password and history
                    PasswordPolicy::updatePasswordHistory($pdo, $user_id, $userRecord['password_hash']);

                    $stmt = $pdo->prepare("
                        UPDATE users
                        SET password_hash = ?, password_last_changed = NOW(), failed_login_count = 0, locked_until = NULL
                        WHERE userId = ?
                    ");
                    $stmt->execute([$newPasswordHash, $user_id]);

                    // Update user_security table
                    $stmt = $pdo->prepare("
                        UPDATE user_security
                        SET password_last_changed = NOW(), failed_login_attempts = 0, account_locked_until = NULL, updated_at = NOW()
                        WHERE user_id = ?
                    ");
                    $stmt->execute([$user_id]);

                    // Force logout from other sessions for security
                    SessionManager::forceLogoutAllSessions($user_id);

                    echo json_encode([
                        'success' => true,
                        'message' => 'Password changed successfully. Other sessions have been logged out for security.'
                    ]);

                    SecurityAuditor::logAuthentication('password_change', 1, 'Password successfully changed');
                    break;

                case 'update_security_settings':
                    // Update security preferences
                    $updates = [];

                    if (isset($input['login_notifications'])) {
                        $updates['login_notifications'] = $input['login_notifications'] ? 1 : 0;
                    }

                    if (isset($input['suspicious_activity_alerts'])) {
                        $updates['suspicious_activity_alerts'] = $input['suspicious_activity_alerts'] ? 1 : 0;
                    }

                    if (!empty($updates)) {
                        $setClause = [];
                        $params = [];

                        foreach ($updates as $field => $value) {
                            $setClause[] = "{$field} = ?";
                            $params[] = $value;
                        }

                        $params[] = $user_id;

                        $stmt = $pdo->prepare("
                            INSERT INTO user_security (user_id, " . implode(', ', array_keys($updates)) . ", created_at, updated_at)
                            VALUES (?, " . str_repeat('?, ', count($updates)) . "NOW(), NOW())
                            ON DUPLICATE KEY UPDATE
                            " . implode(', ', $setClause) . ", updated_at = NOW()
                        ");

                        // Build the VALUES array
                        $values = array_merge([$user_id], array_values($updates));

                        $stmt->execute($values);

                        echo json_encode([
                            'success' => true,
                            'message' => 'Security settings updated successfully'
                        ]);

                        SecurityAuditor::logSecurityEvent('security_settings_updated', "Updated security settings", $user_id);
                    } else {
                        echo json_encode([
                            'success' => true,
                            'message' => 'No settings to update'
                        ]);
                    }
                    break;

                default:
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid action']);
            }
            break;

        case 'PUT':
            // Admin-only security operations
            if ($role_id !== 2) {
                http_response_code(403);
                echo json_encode(['error' => 'Admin access required']);
                exit;
            }

            $input = json_decode(file_get_contents('php://input'), true);

            if (!$input || !isset($input['target_user_id']) || !isset($input['action'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Target user ID and action are required']);
                exit;
            }

            $targetUserId = $input['target_user_id'];
            $adminAction = $input['action'];

            switch ($adminAction) {
                case 'force_password_reset':
                    // Force user to reset password on next login
                    $stmt = $pdo->prepare("
                        UPDATE users
                        SET token_expires = NOW() -- This will force reset
                        WHERE userId = ?
                    ");
                    $stmt->execute([$targetUserId]);

                    echo json_encode([
                        'success' => true,
                        'message' => 'Password reset forced for user'
                    ]);

                    SecurityAuditor::logSecurityEvent('force_password_reset_admin', "Admin forced password reset for user {$targetUserId}", $user_id);
                    break;

                case 'unlock_account':
                    // Unlock a locked account
                    $stmt = $pdo->prepare("
                        UPDATE users
                        SET locked_until = NULL, failed_login_count = 0
                        WHERE userId = ?
                    ");
                    $stmt->execute([$targetUserId]);

                    $stmt = $pdo->prepare("
                        UPDATE user_security
                        SET account_locked_until = NULL, failed_login_attempts = 0
                        WHERE user_id = ?
                    ");
                    $stmt->execute([$targetUserId]);

                    echo json_encode([
                        'success' => true,
                        'message' => 'Account unlocked successfully'
                    ]);

                    SecurityAuditor::logSecurityEvent('account_unlocked_admin', "Admin unlocked account for user {$targetUserId}", $user_id);
                    break;

                case 'force_logout':
                    // Force logout all sessions for a user
                    SessionManager::forceLogoutAllSessions($targetUserId);

                    echo json_encode([
                        'success' => true,
                        'message' => 'User forced out of all sessions'
                    ]);

                    SecurityAuditor::logSecurityEvent('force_logout_admin', "Admin forced logout for all sessions of user {$targetUserId}", $user_id);
                    break;

                default:
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid admin action']);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }

} catch (Exception $e) {
    error_log("user-security.php - Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}

/**
 * Generate a random TOTP secret key
 */
function generateTOTPSecret() {
    return bin2hex(random_bytes(16));
}
?>
