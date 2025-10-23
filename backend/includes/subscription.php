<?php
/**
 * Subscription and Monetization Management Functions
 * Updated for participant-based architecture (Snappy design)
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Get a site setting value by key
 */
function getSiteSetting($key) {
    global $pdo;

    // Check if database connection is available
    if (!$pdo) {
        error_log("Database not connected when trying to get site setting '$key'");
        return null;
    }

    try {
        $stmt = $pdo->prepare("SELECT setting_value, setting_type FROM site_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $setting = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$setting) {
            return null;
        }

        // Convert value based on type (using existing site_settings enum values)
        switch ($setting['setting_type']) {
            case 'int':
                return (int)$setting['setting_value'];
            case 'bool':
                return $setting['setting_value'] === '1' || strtolower($setting['setting_value']) === 'true';
            case 'json':
                return json_decode($setting['setting_value'], true);
            case 'string':
            default:
                // Handle decimal values stored as strings
                if (is_numeric($setting['setting_value']) && strpos($setting['setting_value'], '.') !== false) {
                    return (float)$setting['setting_value'];
                } elseif (is_numeric($setting['setting_value'])) {
                    return (int)$setting['setting_value'];
                }
                return $setting['setting_value'];
        }
    } catch (Exception $e) {
        error_log("Error getting site setting '$key': " . $e->getMessage());
        return null;
    }
}

/**
 * Set a site setting value
 */
function setSiteSetting($key, $value, $user_id = null) {
    global $pdo;

    // Check if database connection is available
    if (!$pdo) {
        error_log("Database not connected when trying to set site setting '$key'");
        return false;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO site_settings (setting_key, setting_value, updated_by_user_id)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE
                setting_value = VALUES(setting_value),
                updated_by_user_id = VALUES(updated_by_user_id)
        ");
        $stmt->execute([$key, (string)$value, $user_id]);
        return true;
    } catch (Exception $e) {
        error_log("Error setting site setting '$key': " . $e->getMessage());
        return false;
    }
}

/**
 * Get participant subscription information
 */
function getParticipantSubscription($participant_id) {
    global $pdo;

    // Check if database connection is available
    if (!$pdo) {
        error_log("Database not connected when getting subscription for participant $participant_id");
        return null;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT
                s.*,
                p.name as participant_name,
                pt.participantType
            FROM subscriptions s
            JOIN participants p ON s.participantId = p.participantId
            LEFT JOIN participant_type pt ON p.participantId = pt.participantId
            WHERE s.participantId = ?
        ");
        $stmt->execute([$participant_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting participant subscription for participant $participant_id: " . $e->getMessage());
        return null;
    }
}

/**
 * Get user subscription information (for backward compatibility)
 */
function getUserSubscription($user_id) {
    global $pdo;

    try {
        // Get participant's subscription via user
        $stmt = $pdo->prepare("
            SELECT
                s.*,
                p.name as participant_name,
                pt.participantType,
                CASE
                    WHEN pt.participantType = 'C' THEN 'client'
                    WHEN pt.participantType = 'S' THEN 'sp'
                END as user_type
            FROM users u
            JOIN subscriptions s ON u.entity_id = s.participantId
            JOIN participants p ON s.participantId = p.participantId
            LEFT JOIN participant_type pt ON p.participantId = pt.participantId
            WHERE u.userId = ?
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error getting user subscription for user $user_id: " . $e->getMessage());
        return null;
    }
}

/**
 * Get monthly usage for a subscription
 */
function getMonthlyUsageForSubscription($subscription_id, $usage_type, $month = null) {
    global $pdo;

    try {
        $month_filter = $month ?: date('Y-m');

        $stmt = $pdo->prepare("
            SELECT count
            FROM subscription_usage
            WHERE subscription_id = ? AND usage_type = ? AND usage_month = ?
        ");
        $stmt->execute([$subscription_id, $usage_type, $month_filter]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? (int)$result['count'] : 0;
    } catch (Exception $e) {
        error_log("Error getting monthly usage for subscription $subscription_id: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get monthly usage for a user (backward compatibility)
 */
function getMonthlyUsage($user_id, $usage_type, $month = null) {
    global $pdo;

    try {
        // Get user's subscription first, then get usage
        $subscription = getUserSubscription($user_id);
        if (!$subscription) {
            return 0;
        }
        return getMonthlyUsageForSubscription($subscription['id'], $usage_type, $month);
    } catch (Exception $e) {
        error_log("Error getting monthly usage for user $user_id: " . $e->getMessage());
        return 0;
    }
}

/**
 * Increment monthly usage for a subscription
 */
function incrementSubscriptionUsage($subscription_id, $usage_type) {
    global $pdo;

    try {
        $current_month = date('Y-m');

        $stmt = $pdo->prepare("
            INSERT INTO subscription_usage (subscription_id, usage_type, usage_month, count, updated_at)
            VALUES (?, ?, ?, 1, NOW())
            ON DUPLICATE KEY UPDATE
                count = count + 1,
                updated_at = NOW()
        ");
        $stmt->execute([$subscription_id, $usage_type, $current_month]);
        return true;
    } catch (Exception $e) {
        error_log("Error incrementing usage for subscription $subscription_id: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if user can perform an action based on subscription and usage
 */
function canPerformAction($user_id, $action_type) {
    $subscription = getUserSubscription($user_id);
    if (!$subscription) {
        return false;
    }

    // Check if subscription is enabled and not expired
    if (!$subscription['subscription_enabled'] || $subscription['status'] !== 'active') {
        return false;
    }

    if ($subscription['current_period_end'] && strtotime($subscription['current_period_end']) < time()) {
        return false;
    }

    $current_usage = getMonthlyUsageForSubscription($subscription['id'], $action_type);

    // For free tier users, always use up-to-date site settings limit
    if ($subscription['subscription_tier'] === 'free') {
        $limits = getUsageLimits();
        $monthly_limit = $subscription['participantType'] === 'C' ? $limits['client_free_jobs'] : $limits['provider_free_jobs'];
    } else {
        // Paid tiers can use stored limit or unlimited
        $monthly_limit = $subscription['monthly_job_limit'];
    }

    // Unlimited for paid tiers
    if ($subscription['subscription_tier'] !== 'free') {
        return true;
    }

    // Check limits for free tier
    return $current_usage < $monthly_limit;
}

/**
 * Get pricing information for subscriptions
 */
function getSubscriptionPricing() {
    return [
        'client_basic_price' => getSiteSetting('client_basic_subscription_price'),
        'provider_basic_price' => getSiteSetting('provider_basic_subscription_price'),
        'client_advanced_asset_management_price' => getSiteSetting('client_advanced_asset_management_price'),
        'client_advanced_maintenance_cost_price' => getSiteSetting('client_advanced_maintenance_cost_price'),
        'client_advanced_qr_creation_price' => getSiteSetting('client_advanced_qr_creation_price'),
        'provider_advanced_job_cost_collection_price' => getSiteSetting('provider_advanced_job_cost_collection_price'),
        'provider_advanced_health_safety_price' => getSiteSetting('provider_advanced_health_safety_price'),
        'provider_advanced_routing_price' => getSiteSetting('provider_advanced_routing_price')
    ];
}

/**
 * Get usage limits for different tiers
 */
function getUsageLimits() {
    return [
        'client_free_jobs' => getSiteSetting('client_free_jobs_per_month'),
        'provider_free_jobs' => getSiteSetting('provider_free_jobs_per_month'),
        'grace_period_days' => getSiteSetting('subscription_grace_period_days'),
        'image_retention_free_days' => getSiteSetting('job_image_retention_free_days'),
        'image_retention_basic_days' => getSiteSetting('job_image_retention_basic_days'),
        'image_retention_advanced_days' => getSiteSetting('job_image_retention_advanced_days')
    ];
}

/**
 * Check if participant has a specific advanced feature enabled
 */
function hasFeature($participant_id, $feature_name) {
    global $pdo;

    try {
        // First check if feature is enabled in participant_features table
        $stmt = $pdo->prepare("
            SELECT is_enabled, valid_until
            FROM participant_features
            WHERE participantId = ? AND feature_name = ?
        ");
        $stmt->execute([$participant_id, $feature_name]);
        $feature = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($feature) {
            if (!$feature['is_enabled']) {
                return false;
            }
            if ($feature['valid_until'] && strtotime($feature['valid_until']) < time()) {
                return false;
            }
            return true;
        }

        // If not in participant_features, check if subscription tier includes it
        $subscription = getParticipantSubscription($participant_id);
        if (!$subscription) {
            return false;
        }

        // Advanced tier participants get all advanced features by default
        if ($subscription['subscription_tier'] === 'advanced') {
            return true;
        }

        return false;
    } catch (Exception $e) {
        error_log("Error checking feature '$feature_name' for participant $participant_id: " . $e->getMessage());
        return false;
    }
}

/**
 * Enable a feature for a participant
 */
function enableParticipantFeature($participant_id, $feature_name, $valid_until = null) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            INSERT INTO participant_features (participantId, feature_name, is_enabled, valid_until, updated_at)
            VALUES (?, ?, TRUE, ?, NOW())
            ON DUPLICATE KEY UPDATE
                is_enabled = TRUE,
                valid_until = VALUES(valid_until),
                updated_at = NOW()
        ");
        $stmt->execute([$participant_id, $feature_name, $valid_until]);
        return true;
    } catch (Exception $e) {
        error_log("Error enabling feature '$feature_name' for participant $participant_id: " . $e->getMessage());
        return false;
    }
}

/**
 * Disable a feature for a participant
 */
function disableParticipantFeature($participant_id, $feature_name) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            UPDATE participant_features
            SET is_enabled = FALSE, updated_at = NOW()
            WHERE participantId = ? AND feature_name = ?
        ");
        $stmt->execute([$participant_id, $feature_name]);
        return true;
    } catch (Exception $e) {
        error_log("Error disabling feature '$feature_name' for participant $participant_id: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if user has a feature (backward compatibility - routes to participant)
 */
function hasUserFeature($user_id, $feature_name) {
    global $pdo;

    try {
        // Get user's participant ID, then check participant features
        $stmt = $pdo->prepare("SELECT entity_id FROM users WHERE userId = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return false;
        }

        return hasFeature($user['entity_id'], $feature_name);
    } catch (Exception $e) {
        error_log("Error checking user feature '$feature_name' for user $user_id: " . $e->getMessage());
        return false;
    }
}

/**
 * Update participant subscription tier
 */
function updateParticipantSubscriptionTier($participant_id, $new_tier, $admin_user_id = null) {
    global $pdo;

    try {
        // Update subscription tier
        $stmt = $pdo->prepare("
            UPDATE subscriptions
            SET
                subscription_tier = ?,
                updated_at = NOW()
            WHERE participantId = ?
        ");
        $stmt->execute([$new_tier, $participant_id]);

        // Set job limits based on tier and participant type
        $limits = getUsageLimits();
        $participant_type = getParticipantType($participant_id);

        if ($participant_type === 'C') {
            $new_limit = $new_tier === 'free' ? $limits['client_free_jobs'] : 9999;
        } else {
            $new_limit = $new_tier === 'free' ? $limits['provider_free_jobs'] : 9999;
        }

        $stmt = $pdo->prepare("
            UPDATE subscriptions
            SET monthly_job_limit = ?
            WHERE participantId = ?
        ");
        $stmt->execute([$new_limit, $participant_id]);

        return true;
    } catch (Exception $e) {
        error_log("Error updating subscription tier for participant $participant_id: " . $e->getMessage());
        return false;
    }
}

/**
 * Get participant type (C=Client, S=Service Provider)
 */
function getParticipantType($participant_id) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT participantType FROM participant_type WHERE participantId = ?");
        $stmt->execute([$participant_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['participantType'] : null;
    } catch (Exception $e) {
        error_log("Error getting participant type for $participant_id: " . $e->getMessage());
        return null;
    }
}

/**
 * Set entity/participant status (enable/disable)
 */
function setParticipantStatus($participant_id, $enable, $reason = null, $admin_user_id = null) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            UPDATE participants
            SET
                is_enabled = ?,
                disabled_reason = ?,
                disabled_at = ?,
                disabled_by_user_id = ?,
                updated_at = NOW()
            WHERE participantId = ?
        ");
        $stmt->execute([
            $enable ? 1 : 0,
            $reason ?: null,
            $enable ? null : date('Y-m-d H:i:s'),
            $enable ? null : $admin_user_id,
            $participant_id
        ]);

        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        error_log("Error updating participant status: " . $e->getMessage());
        return false;
    }
}

/**
 * Reset monthly usage counters for a subscription
 */
function resetSubscriptionUsage($subscription_id) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            DELETE FROM subscription_usage
            WHERE subscription_id = ? AND usage_month = ?
        ");
        $stmt->execute([$subscription_id, date('Y-m')]);
        return true;
    } catch (Exception $e) {
        error_log("Error resetting usage for subscription $subscription_id: " . $e->getMessage());
        return false;
    }
}



/**
 * Backward compatibility functions
 */
function enableFeature($user_id, $feature_name, $valid_until = null, $user_enabling_id = null) {
    global $pdo;

    try {
        // Get user's participant, then enable feature
        $stmt = $pdo->prepare("SELECT entity_id FROM users WHERE userId = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            return enableParticipantFeature($user['entity_id'], $feature_name, $valid_until);
        }
    } catch (Exception $e) {
        error_log("Error enabling user feature: " . $e->getMessage());
    }
    return false;
}

function disableFeature($user_id, $feature_name) {
    global $pdo;

    try {
        // Get user's participant, then disable feature
        $stmt = $pdo->prepare("SELECT entity_id FROM users WHERE userId = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            return disableParticipantFeature($user['entity_id'], $feature_name);
        }
    } catch (Exception $e) {
        error_log("Error disabling user feature: " . $e->getMessage());
    }
    return false;
}

function updateUserSubscriptionTier($user_id, $new_tier, $expires = null, $admin_user_id = null) {
    global $pdo;

    try {
        // Get user's participant, then update subscription
        $stmt = $pdo->prepare("SELECT entity_id FROM users WHERE userId = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            return updateParticipantSubscriptionTier($user['entity_id'], $new_tier, $admin_user_id);
        }
    } catch (Exception $e) {
        error_log("Error updating user subscription: " . $e->getMessage());
    }
    return false;
}

/**
 * Increment monthly usage for a user (backward compatibility)
 */
function incrementUsage($user_id, $usage_type) {
    try {
        // Get user's subscription first, then increment usage
        $subscription = getUserSubscription($user_id);
        if (!$subscription) {
            error_log("No subscription found for user $user_id when trying to increment usage for '$usage_type'");
            return false;
        }
        return incrementSubscriptionUsage($subscription['id'], $usage_type);
    } catch (Exception $e) {
        error_log("Error incrementing usage for user $user_id: " . $e->getMessage());
        return false;
    }
}


?>
