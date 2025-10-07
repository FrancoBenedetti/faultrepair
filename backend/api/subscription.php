<?php
ini_set('log_errors', true);
ini_set('error_log', $_SERVER['DOCUMENT_ROOT'].'/all-logs/subscription.log');
require_once '../config/database.php';
require_once '../includes/JWT.php';
require_once '../includes/subscription.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// JWT Authentication - Read from query parameter for live server compatibility
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

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $action = $_GET['action'] ?? 'subscription';

        switch ($action) {
            case 'subscription':
                // Get user's subscription info, pricing, and usage
                $subscription = getUserSubscription($user_id);
                $pricing = getSubscriptionPricing();
                $limits = getUsageLimits();
                $usage = [
                    'jobs_created' => getMonthlyUsage($user_id, 'jobs_created'),
                    'jobs_accepted' => getMonthlyUsage($user_id, 'jobs_accepted')
                ];

                echo json_encode([
                    'subscription' => $subscription,
                    'pricing' => $pricing,
                    'limits' => $limits,
                    'current_usage' => $usage
                ]);
                break;

            case 'features':
                // Get user's enabled features
                $user_features = [];
                $feature_names = [
                    'asset_management_qr',
                    'maintenance_cost_analysis',
                    'sp_qr_codes',
                    'job_cost_collection',
                    'health_safety_assessment',
                    'technician_routing'
                ];

                foreach ($feature_names as $feature) {
                    $user_features[$feature] = hasFeature($user_id, $feature);
                }

                echo json_encode(['features' => $user_features]);
                break;

            case 'settings':
                // Admin only: Get all site settings
                if (!in_array($role_id, [2, 3])) { // Allow Site Budget Controllers and Service Provider Admins for now
                    http_response_code(403);
                    echo json_encode(['error' => 'Access denied. Admin access required.']);
                    exit;
                }

                global $pdo;
                $stmt = $pdo->query("SELECT setting_key, setting_value, setting_type, description FROM site_settings");
                $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo json_encode(['settings' => $settings]);
                break;

            default:
                http_response_code(400);
                echo json_encode(['error' => 'Invalid action']);
                break;
        }

    } elseif ($method === 'POST') {
        $action = $_GET['action'] ?? '';
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON input']);
            exit;
        }

        switch ($action) {
            case 'upgrade_subscription':
                // Upgrade user's subscription tier
                $new_tier = $input['tier'] ?? 'basic';
                $expires = $input['expires'] ?? date('Y-m-d', strtotime('+1 month'));

                if (!in_array($new_tier, ['basic', 'advanced'])) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid tier']);
                    exit;
                }

                if (updateUserSubscriptionTier($user_id, $new_tier, $expires, $user_id)) {
                    echo json_encode(['success' => true, 'message' => 'Subscription updated successfully']);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to update subscription']);
                }
                break;

            case 'enable_feature':
                // Enable an advanced feature for user
                $feature_name = $input['feature_name'] ?? '';

                if (!$feature_name) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Feature name required']);
                    exit;
                }

                // Here you'd typically validate payment before enabling
                // For now, just enable it
                if (enableFeature($user_id, $feature_name, null, $user_id)) {
                    echo json_encode(['success' => true, 'message' => 'Feature enabled successfully']);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to enable feature']);
                }
                break;

            case 'update_setting':
                // Admin only: Update site setting
                if (!in_array($role_id, [2, 3])) {
                    http_response_code(403);
                    echo json_encode(['error' => 'Access denied. Admin access required.']);
                    exit;
                }

                $setting_key = $input['setting_key'] ?? '';
                $setting_value = $input['setting_value'] ?? '';

                if (!$setting_key) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Setting key required']);
                    exit;
                }

                if (setSiteSetting($setting_key, $setting_value, $user_id)) {
                    echo json_encode(['success' => true, 'message' => 'Setting updated successfully']);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to update setting']);
                }
                break;

            default:
                http_response_code(400);
                echo json_encode(['error' => 'Invalid action']);
                break;
        }

    } elseif ($method === 'PUT') {
        // Could be used for subscription renewal, feature renewal, etc.
        http_response_code(405);
        echo json_encode(['error' => 'Method not implemented']);

    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
