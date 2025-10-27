<?php
ini_set('log_errors', true);
ini_set('error_log', $_SERVER['DOCUMENT_ROOT'].'/all-logs/admin.log');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/JWT.php';
require_once __DIR__ . '/../includes/subscription.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
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

// Verify user has admin access (Site Administrator or Site Budget Controller)
if ($role_id !== 5 && $role_id !== 2) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied. Administrative access required.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// For DELETE method, infer action from parameters
if ($method === 'DELETE' && !$action) {
    if (isset($_GET['id'])) {
        $action = 'delete_region'; // Assume it's delete_region if id is present
    }
}

try {
    if ($method === 'GET') {
        switch ($action) {
            case 'usage':
                // Get usage statistics
                $month = $_GET['month'] ?? date('Y-m');
                $user_type = $_GET['user_type'] ?? null;

                $usage_data = getDetailedUsageStats($month, $user_type);
                echo json_encode(['usage' => $usage_data]);
                break;

            case 'settings':
                // Get all site settings
                global $pdo;
                try {
                    $stmt = $pdo->query("
                        SELECT
                            ss.id,
                            setting_key,
                            setting_value,
                            setting_type,
                            description,
                            updated_at,
                            CONCAT(u.first_name, ' ', u.last_name) as updated_by
                        FROM site_settings ss
                        LEFT JOIN users u ON ss.updated_by_user_id = u.userId
                        ORDER BY setting_key
                    ");
                    $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    error_log("Admin API - Settings count: " . count($settings));
                    error_log("Admin API - First setting: " . json_encode($settings[0] ?? 'none'));
                    echo json_encode(['settings' => $settings]);
                } catch (Exception $e) {
                    error_log("Admin API - Settings error: " . $e->getMessage());
                    echo json_encode(['error' => 'Database error: ' . $e->getMessage(), 'settings' => []]);
                }
                break;

            case 'users':
                // Get user management data
                $filter = $_GET['filter'] ?? 'all'; // all, clients, sps, disabled
                $search = $_GET['search'] ?? '';

                $user_data = getUserManagementData($filter, $search);
                echo json_encode(['users' => $user_data]);
                break;

            case 'dashboard':
                // Get dashboard statistics
                $stats = getAdminDashboardStats();
                echo json_encode(['stats' => $stats]);
                break;

            case 'audit':
                // Get audit log
                $limit = (int)($_GET['limit'] ?? 50);
                $offset = (int)($_GET['offset'] ?? 0);

                global $pdo;
                $stmt = $pdo->prepare("
                    SELECT
                        aa.id, aa.admin_user_id, aa.action_type, aa.target_type, aa.target_id,
                        aa.target_identifier, aa.old_value, aa.new_value, aa.notes,
                        aa.ip_address, aa.user_agent, aa.created_at,
                        CONCAT(u.first_name, ' ', u.last_name) as admin_name
                    FROM admin_actions aa
                    JOIN users u ON aa.admin_user_id = u.userId
                    ORDER BY aa.created_at DESC
                    LIMIT ? OFFSET ?
                ");
                $stmt->execute([$limit, $offset]);
                $audit_log = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo json_encode(['audit_log' => $audit_log]);
                break;

            case 'regions':
                // Get regions management data
                $search = $_GET['search'] ?? '';
                $active_only = isset($_GET['active_only']) && $_GET['active_only'] === 'true';

                global $pdo;
                $where_clause = '';
                $params = [];

                if ($search) {
                    $where_clause = "WHERE (name LIKE ? OR code LIKE ? OR country LIKE ?)";
                    $params = array_fill(0, 3, "%$search%");
                }

                if ($active_only) {
                    $where_clause = $where_clause ? $where_clause . " AND is_active = TRUE" : "WHERE is_active = TRUE";
                }

                $stmt = $pdo->prepare("
                    SELECT id, name, code, country, is_active, created_at
                    FROM regions
                    $where_clause
                    ORDER BY name ASC
                ");
                $stmt->execute($params);
                $regions = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo json_encode(['regions' => $regions]);
                break;

            case 'services':
                // Get services management data
                $search = $_GET['search'] ?? '';
                $category = $_GET['category'] ?? '';
                $active_only = isset($_GET['active_only']) && $_GET['active_only'] === 'true';

                global $pdo;
                $where_conditions = [];
                $params = [];

                if ($search) {
                    $where_conditions[] = "(name LIKE ? OR description LIKE ?)";
                    $params[] = "%$search%";
                    $params[] = "%$search%";
                }

                if ($category) {
                    $where_conditions[] = "category = ?";
                    $params[] = $category;
                }

                if ($active_only) {
                    $where_conditions[] = "is_active = TRUE";
                }

                $where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

                $stmt = $pdo->prepare("
                    SELECT id, name, category, description, is_active, created_at
                    FROM services
                    $where_clause
                    ORDER BY category ASC, name ASC
                ");
                $stmt->execute($params);
                $services = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Get available categories
                $stmt = $pdo->prepare("SELECT DISTINCT category FROM services WHERE is_active = TRUE ORDER BY category");
                $stmt->execute();
                $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

                echo json_encode(['services' => $services, 'categories' => $categories]);
                break;

            case 'provider_services':
                // Get provider services assignments
                $provider_id = (int)($_GET['provider_id'] ?? 0);

                if (!$provider_id) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Provider ID required']);
                    exit;
                }

                global $pdo;
                $stmt = $pdo->prepare("
                    SELECT
                        s.id, s.name, s.category, s.description,
                        CASE WHEN sps.service_id IS NOT NULL THEN TRUE ELSE FALSE END as assigned
                    FROM services s
                    LEFT JOIN service_provider_services sps ON s.id = sps.service_id AND sps.service_provider_id = ?
                    WHERE s.is_active = TRUE
                    ORDER BY s.category ASC, s.name ASC
                ");
                $stmt->execute([$provider_id]);
                $services = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo json_encode(['provider_services' => $services]);
                break;

            case 'provider_regions':
                // Get provider regions assignments
                $provider_id = (int)($_GET['provider_id'] ?? 0);

                if (!$provider_id) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Provider ID required']);
                    exit;
                }

                global $pdo;
                $stmt = $pdo->prepare("
                    SELECT
                        r.id, r.name, r.code, r.country,
                        CASE WHEN spr.region_id IS NOT NULL THEN TRUE ELSE FALSE END as assigned
                    FROM regions r
                    LEFT JOIN service_provider_regions spr ON r.id = spr.region_id AND spr.service_provider_id = ?
                    WHERE r.is_active = TRUE
                    ORDER BY r.country ASC, r.name ASC
                ");
                $stmt->execute([$provider_id]);
                $regions = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo json_encode(['provider_regions' => $regions]);
                break;

            case 'service_providers':
                // Get service providers for assignment management
                $search = $_GET['search'] ?? '';

                global $pdo;
                $where_clause = '';
                $params = [];

                if ($search) {
                    $where_clause = "WHERE (p.name LIKE ? OR p.manager_email LIKE ?)";
                    $params = ["%$search%", "%$search%"];
                }

                $stmt = $pdo->prepare("
                    SELECT
                        p.participantId as id, p.name, p.manager_name, p.manager_email, p.is_active, p.is_enabled,
                        COUNT(DISTINCT sps.service_id) as assigned_services_count,
                        COUNT(DISTINCT spr.region_id) as assigned_regions_count
                    FROM participants p
                    JOIN participant_type pt ON p.participantId = pt.participantId
                    LEFT JOIN service_provider_services sps ON p.participantId = sps.service_provider_id
                    LEFT JOIN service_provider_regions spr ON p.participantId = spr.service_provider_id
                    WHERE pt.participantType = 'S'
                    $where_clause
                    GROUP BY p.participantId
                    ORDER BY p.name ASC
                ");
                $stmt->execute($params);
                $providers = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo json_encode(['service_providers' => $providers]);
                break;

            case 'participants':
                // Get all participants (clients and service providers)
                $search = $_GET['search'] ?? '';
                $type_filter = $_GET['type'] ?? 'all';
                $status_filter = $_GET['status'] ?? 'all';

                global $pdo;
                $where_conditions = [];
                $params = [];

                // Add participant type filter
                if ($type_filter !== 'all') {
                    $participant_type = $type_filter === 'client' ? 'C' : 'S';
                    $where_conditions[] = "pt.participantType = ?";
                    $params[] = $participant_type;
                }

                // Add status filter
                if ($status_filter !== 'all') {
                    $is_enabled = $status_filter === 'enabled' ? 1 : 0;
                    $where_conditions[] = "p.is_enabled = ?";
                    $params[] = $is_enabled;
                }

                // Add search filter
                if ($search) {
                    $where_conditions[] = "(p.name LIKE ? OR p.manager_name LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR p.manager_email LIKE ?)";
                    $search_param = "%$search%";
                    $params = array_merge($params, array_fill(0, 6, $search_param));
                }

                $where_clause = $where_conditions ? "AND " . implode(" AND ", $where_conditions) : "";

                $stmt = $pdo->prepare("
                    SELECT DISTINCT
                        p.participantId as entity_id,
                        p.name,
                        pt.participantType,
                        CASE
                            WHEN pt.participantType = 'C' THEN 'client'
                            WHEN pt.participantType = 'S' THEN 'service_provider'
                        END as entity_type,
                        p.manager_name,
                        COALESCE(p.manager_name, CONCAT(u.first_name, ' ', u.last_name)) as manager_name_display,
                        COALESCE(p.manager_email, u.email) as manager_email,
                        MIN(u.email) as user_email,
                        p.is_enabled,
                        p.is_active,
                        s.subscription_tier,
                        COALESCE(SUM(CASE WHEN su.usage_type = 'jobs_created' THEN su.count END), 0) +
                        COALESCE(SUM(CASE WHEN su.usage_type = 'jobs_accepted' THEN su.count END), 0) as monthly_usage,
                        p.created_at
                    FROM participants p
                    JOIN participant_type pt ON p.participantId = pt.participantId
                    LEFT JOIN users u ON p.participantId = u.entity_id
                    LEFT JOIN subscriptions s ON p.participantId = s.participantId AND s.status = 'active'
                    LEFT JOIN subscription_usage su ON s.id = su.subscription_id AND su.usage_month = ?
                    WHERE p.is_active = TRUE
                    {$where_clause}
                    GROUP BY p.participantId, p.name, pt.participantType, p.manager_name, p.manager_email,
                             p.is_enabled, p.is_active, s.subscription_tier, p.created_at
                    ORDER BY pt.participantType, p.created_at DESC
                ");
                array_unshift($params, date('Y-m')); // Add current month as first parameter
                $stmt->execute($params);
                $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo json_encode(['participants' => $participants]);
                break;

            case 'participant_details':
                // Get detailed participant information
                $participant_id = (int)($_GET['id'] ?? 0);

                if (!$participant_id) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Participant ID required']);
                    exit;
                }

                global $pdo;

                try {
                    // Get basic participant info
                    $stmt = $pdo->prepare("
                        SELECT
                            p.participantId,
                            p.name,
                            pt.participantType,
                            CASE
                                WHEN pt.participantType = 'C' THEN 'client'
                                WHEN pt.participantType = 'S' THEN 'service_provider'
                            END as entity_type,
                            p.manager_name,
                            p.manager_email,
                            p.is_enabled,
                            p.is_active,
                            p.created_at,
                            s.subscription_tier,
                            s.subscription_enabled,
                            s.monthly_job_limit
                        FROM participants p
                        JOIN participant_type pt ON p.participantId = pt.participantId
                        LEFT JOIN subscriptions s ON p.participantId = s.participantId
                        WHERE p.participantId = ?
                    ");
                    $stmt->execute([$participant_id]);
                    $participant = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$participant) {
                        http_response_code(404);
                        echo json_encode(['error' => 'Participant not found']);
                        exit;
                    }

                    // Get associated users
                    $stmt = $pdo->prepare("
                        SELECT
                            u.userId,
                            u.username,
                            u.first_name,
                            u.last_name,
                            u.email,
                            u.is_active as user_active,
                            r.name as role_name,
                            u.created_at as user_created
                        FROM users u
                        LEFT JOIN user_roles r ON u.role_id = r.roleId
                        WHERE u.entity_id = ?
                        ORDER BY u.created_at DESC
                    ");
                    $stmt->execute([$participant_id]);
                    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    // Get locations (if client)
                    $locations = [];
                    if ($participant['participantType'] === 'C') {
                        $stmt = $pdo->prepare("
                            SELECT
                                l.id,
                                l.name,
                                l.address,
                                l.access_instructions,
                                r.name as region_name,
                                r.code as region_code
                            FROM locations l
                            LEFT JOIN regions r ON l.region_id = r.id
                            WHERE l.participant_id = ?
                            ORDER BY l.created_at DESC
                        ");
                        $stmt->execute([$participant_id]);
                        $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    }

                    // Get assigned regions (if service provider)
                    $assigned_regions = [];
                    if ($participant['participantType'] === 'S') {
                        $stmt = $pdo->prepare("
                            SELECT
                                r.id,
                                r.name,
                                r.code,
                                r.country
                            FROM service_provider_regions spr
                            JOIN regions r ON spr.region_id = r.id
                            WHERE spr.service_provider_id = ? AND r.is_active = TRUE
                            ORDER BY r.name
                        ");
                        $stmt->execute([$participant_id]);
                        $assigned_regions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    }

                    // Get assigned services (if service provider)
                    $assigned_services = [];
                    if ($participant['participantType'] === 'S') {
                        $stmt = $pdo->prepare("
                            SELECT
                                s.id,
                                s.name,
                                s.category
                            FROM service_provider_services sps
                            JOIN services s ON sps.service_id = s.id
                            WHERE sps.service_provider_id = ? AND s.is_active = TRUE
                            ORDER BY s.category, s.name
                        ");
                        $stmt->execute([$participant_id]);
                        $assigned_services = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    }

                    // Get recent jobs/activity
                    if ($participant['participantType'] === 'C') {
                        // For clients, get jobs directly via client_id relationship
                        $stmt = $pdo->prepare("
                            SELECT
                                j.id,
                                j.item_identifier,
                                j.fault_description,
                                j.job_status,
                                j.created_at,
                                j.updated_at
                            FROM jobs j
                            WHERE j.client_id = ?
                            ORDER BY j.created_at DESC
                            LIMIT 10
                        ");
                        $stmt->execute([$participant_id]);
                        $recent_jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    } elseif ($participant['participantType'] === 'S') {
                        // For service providers, get jobs they've been assigned to using client_id for relationships
                        $stmt = $pdo->prepare("
                            SELECT
                                j.id,
                                j.item_identifier,
                                j.fault_description,
                                j.job_status,
                                j.created_at,
                                j.updated_at,
                                p.name as client_name
                            FROM jobs j
                            JOIN participants p ON j.client_id = p.participantId
                            WHERE j.assigned_provider_participant_id = ?
                            ORDER BY j.created_at DESC
                            LIMIT 10
                        ");
                        $stmt->execute([$participant_id]);
                        $recent_jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    } else {
                        $recent_jobs = [];
                    }

                    // Get monthly usage
                    $current_month = date('Y-m');
                    if ($participant['entity_type'] === 'client') {
                        $stmt = $pdo->prepare("
                            SELECT COALESCE(SUM(su.count), 0) as monthly_usage
                            FROM subscription_usage su
                            JOIN subscriptions s ON su.subscription_id = s.id
                            WHERE s.participantId = ? AND su.usage_type = 'jobs_created' AND su.usage_month = ?
                        ");
                    } else {
                        $stmt = $pdo->prepare("
                            SELECT COALESCE(SUM(su.count), 0) as monthly_usage
                            FROM subscription_usage su
                            JOIN subscriptions s ON su.subscription_id = s.id
                            WHERE s.participantId = ? AND su.usage_type = 'jobs_accepted' AND su.usage_month = ?
                        ");
                    }
                    $stmt->execute([$participant_id, $current_month]);
                    $usage_result = $stmt->fetch(PDO::FETCH_ASSOC);
                    $monthly_usage = (int)$usage_result['monthly_usage'];

                    echo json_encode([
                        'success' => true,
                        'participant' => $participant,
                        'users' => $users,
                        'locations' => $locations,
                        'assigned_regions' => $assigned_regions,
                        'assigned_services' => $assigned_services,
                        'recent_jobs' => $recent_jobs,
                        'monthly_usage' => $monthly_usage
                    ]);

                } catch (Exception $e) {
                    error_log(__FILE__ . '/' . __LINE__ . '/ Error getting participant details: ' . $e->getMessage());
                    http_response_code(500);
                    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
                }
                break;

            default:
                http_response_code(400);
                echo json_encode(['error' => 'Invalid action']);
                break;
        }

    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON input']);
            exit;
        }

        switch ($action) {
            case 'update_setting':
                // Update a site setting
                $setting_key = $input['setting_key'] ?? '';
                $setting_value = $input['setting_value'] ?? '';

                if (!$setting_key) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Setting key required']);
                    exit;
                }

                // Get old value for audit log
                $old_value = getSiteSetting($setting_key);

                if (setSiteSetting($setting_key, $setting_value, $user_id)) {
                    // Log the action
                    logAdminAction(
                        $user_id,
                        'update_setting',
                        'site_setting',
                        null,
                        $setting_key,
                        $old_value,
                        $setting_value,
                        "Updated setting: $setting_key"
                    );

                    echo json_encode(['success' => true, 'message' => 'Setting updated successfully']);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to update setting']);
                }
                break;

            case 'reset_usage':
                // Reset monthly usage counters (admin function)
                $user_id_to_reset = $input['user_id'] ?? null;

                if ($user_id_to_reset) {
                    if (resetUserUsage($user_id_to_reset)) {
                        logAdminAction(
                            $user_id,
                            'reset_usage',
                            'user',
                            $user_id_to_reset,
                            null,
                            null,
                            'reset',
                            "Reset monthly usage for user ID $user_id_to_reset"
                        );
                        echo json_encode(['success' => true, 'message' => 'Usage reset successfully']);
                    } else {
                        http_response_code(500);
                        echo json_encode(['error' => 'Failed to reset usage']);
                    }
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'User ID required']);
                }
                break;

            case 'create_region':
                // Create new region
                $name = trim($input['name'] ?? '');
                $code = trim($input['code'] ?? '');
                $country = trim($input['country'] ?? 'South Africa');

                if (!$name || !$code) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Name and code are required']);
                    exit;
                }

                global $pdo;
                $stmt = $pdo->prepare("
                    INSERT INTO regions (name, code, country, is_active)
                    VALUES (?, ?, ?, TRUE)
                ");

                if ($stmt->execute([$name, $code, $country])) {
                    $new_id = $pdo->lastInsertId();
                    logAdminAction(
                        $user_id,
                        'create_region',
                        'region',
                        $new_id,
                        "$name ($code)",
                        null,
                        $new_id,
                        "Created region: $name"
                    );
                    echo json_encode(['success' => true, 'message' => 'Region created successfully', 'id' => $new_id]);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to create region']);
                }
                break;

            case 'create_service':
                // Create new service
                $name = trim($input['name'] ?? '');
                $category = trim($input['category'] ?? '');
                $description = trim($input['description'] ?? '');

                if (!$name || !$category) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Name and category are required']);
                    exit;
                }

                global $pdo;
                $stmt = $pdo->prepare("
                    INSERT INTO services (name, category, description, is_active)
                    VALUES (?, ?, ?, TRUE)
                ");

                if ($stmt->execute([$name, $category, $description])) {
                    $new_id = $pdo->lastInsertId();
                    logAdminAction(
                        $user_id,
                        'create_service',
                        'service',
                        $new_id,
                        "$name ($category)",
                        null,
                        $new_id,
                        "Created service: $name"
                    );
                    echo json_encode(['success' => true, 'message' => 'Service created successfully', 'id' => $new_id]);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to create service']);
                }
                break;

            case 'assign_provider_service':
                // Assign service to service provider
                $provider_id = (int)($input['provider_id'] ?? 0);
                $service_id = (int)($input['service_id'] ?? 0);
                $assign = (bool)($input['assign'] ?? true);

                if (!$provider_id || !$service_id) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Provider ID and Service ID are required']);
                    exit;
                }

                global $pdo;

                if ($assign) {
                    // Assign service
                    $stmt = $pdo->prepare("
                        INSERT IGNORE INTO service_provider_services (service_provider_id, service_id)
                        VALUES (?, ?)
                    ");
                    $success = $stmt->execute([$provider_id, $service_id]);
                    $action = 'assign_service';
                    $message = 'Service assigned to provider successfully';
                } else {
                    // Unassign service
                    $stmt = $pdo->prepare("
                        DELETE FROM service_provider_services
                        WHERE service_provider_id = ? AND service_id = ?
                    ");
                    $success = $stmt->execute([$provider_id, $service_id]);
                    $action = 'unassign_service';
                    $message = 'Service unassigned from provider successfully';
                }

                if ($success) {
                    logAdminAction(
                        $user_id,
                        $action,
                        'provider_service',
                        $provider_id,
                        null,
                        null,
                        $assign ? 'assigned' : 'unassigned',
                        ($assign ? 'Assigned' : 'Unassigned') . " service $service_id to provider $provider_id"
                    );
                    echo json_encode(['success' => true, 'message' => $message]);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to update service assignment']);
                }
                break;

            case 'assign_provider_region':
                // Assign region to service provider
                $provider_id = (int)($input['provider_id'] ?? 0);
                $region_id = (int)($input['region_id'] ?? 0);
                $assign = (bool)($input['assign'] ?? true);

                if (!$provider_id || !$region_id) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Provider ID and Region ID are required']);
                    exit;
                }

                global $pdo;

                if ($assign) {
                    // Assign region
                    $stmt = $pdo->prepare("
                        INSERT IGNORE INTO service_provider_regions (service_provider_id, region_id)
                        VALUES (?, ?)
                    ");
                    $success = $stmt->execute([$provider_id, $region_id]);
                    $action = 'assign_region';
                    $message = 'Region assigned to provider successfully';
                } else {
                    // Unassign region
                    $stmt = $pdo->prepare("
                        DELETE FROM service_provider_regions
                        WHERE service_provider_id = ? AND region_id = ?
                    ");
                    $success = $stmt->execute([$provider_id, $region_id]);
                    $action = 'unassign_region';
                    $message = 'Region unassigned from provider successfully';
                }

                if ($success) {
                    logAdminAction(
                        $user_id,
                        $action,
                        'provider_region',
                        $provider_id,
                        null,
                        null,
                        $assign ? 'assigned' : 'unassigned',
                        ($assign ? 'Assigned' : 'Unassigned') . " region $region_id to provider $provider_id"
                    );
                    echo json_encode(['success' => true, 'message' => $message]);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to update region assignment']);
                }
                break;

            default:
                http_response_code(400);
                echo json_encode(['error' => 'Invalid action']);
                break;
        }

    } elseif ($method === 'PUT') {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON input']);
            exit;
        }

        switch ($action) {
            case 'enable_client':
            case 'disable_client':
            case 'enable_sp':
            case 'disable_sp':
                $target_type = strpos($action, 'client') !== false ? 'client' : 'service_provider';
                $enable = strpos($action, 'enable') !== false;

                $target_id = $input['entity_id'] ?? null;
                $reason = $input['reason'] ?? '';

                if (!$target_id) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Entity ID required']);
                    exit;
                }

                if (setEntityStatus($target_type, $target_id, $enable, $reason, $user_id)) {
                    logAdminAction(
                        $user_id,
                        $enable ? 'enable_' . ($target_type === 'client' ? 'client' : 'sp') : 'disable_' . ($target_type === 'client' ? 'client' : 'sp'),
                        $target_type,
                        $target_id,
                        null,
                        null,
                        $enable ? 'enabled' : 'disabled',
                        $reason
                    );

                    echo json_encode([
                        'success' => true,
                        'message' => ucfirst($target_type) . ($enable ? ' enabled' : ' disabled') . ' successfully'
                    ]);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to update entity status']);
                }
                break;

            case 'update_region':
                // Update region
                $id = (int)($input['id'] ?? 0);
                $name = trim($input['name'] ?? '');
                $code = trim($input['code'] ?? '');
                $country = trim($input['country'] ?? 'South Africa');
                $is_active = (bool)($input['is_active'] ?? true);

                if (!$id || !$name || !$code) {
                    http_response_code(400);
                    echo json_encode(['error' => 'ID, name and code are required']);
                    exit;
                }

                global $pdo;

                // Get old values for audit
                $stmt = $pdo->prepare("SELECT name, code, country, is_active FROM regions WHERE id = ?");
                $stmt->execute([$id]);
                $old_region = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$old_region) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Region not found']);
                    exit;
                }

                $stmt = $pdo->prepare("
                    UPDATE regions SET name = ?, code = ?, country = ?, is_active = ? WHERE id = ?
                ");

                if ($stmt->execute([$name, $code, $country, $is_active ? 1 : 0, $id])) {
                    logAdminAction(
                        $user_id,
                        'update_region',
                        'region',
                        $id,
                        "$name ($code)",
                        json_encode($old_region),
                        json_encode(['name' => $name, 'code' => $code, 'country' => $country, 'is_active' => $is_active]),
                        "Updated region: $name"
                    );
                    echo json_encode(['success' => true, 'message' => 'Region updated successfully']);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to update region']);
                }
                break;

            case 'update_service':
                // Update service
                $id = (int)($input['id'] ?? 0);
                $name = trim($input['name'] ?? '');
                $category = trim($input['category'] ?? '');
                $description = trim($input['description'] ?? '');
                $is_active = (bool)($input['is_active'] ?? true);

                if (!$id || !$name || !$category) {
                    http_response_code(400);
                    echo json_encode(['error' => 'ID, name and category are required']);
                    exit;
                }

                global $pdo;

                // Get old values for audit
                $stmt = $pdo->prepare("SELECT name, category, description, is_active FROM services WHERE id = ?");
                $stmt->execute([$id]);
                $old_service = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$old_service) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Service not found']);
                    exit;
                }

                $stmt = $pdo->prepare("
                    UPDATE services SET name = ?, category = ?, description = ?, is_active = ? WHERE id = ?
                ");

                if ($stmt->execute([$name, $category, $description, $is_active ? 1 : 0, $id])) {
                    logAdminAction(
                        $user_id,
                        'update_service',
                        'service',
                        $id,
                        "$name ($category)",
                        json_encode($old_service),
                        json_encode(['name' => $name, 'category' => $category, 'description' => $description, 'is_active' => $is_active]),
                        "Updated service: $name"
                    );
                    echo json_encode(['success' => true, 'message' => 'Service updated successfully']);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to update service']);
                }
                break;

            default:
                http_response_code(400);
                echo json_encode(['error' => 'Invalid action']);
                break;
        }

    } elseif ($method === 'DELETE') {

        switch ($action) {
            case 'delete_region':
                // Soft delete region (deactivate)
                $id = (int)($_GET['id'] ?? 0);

                // DEBUG: Log the received ID
                error_log(__FILE__ . '/' . __LINE__ . '/ DELETE action: ' . $action . ', method: ' . $method . ', id: ' . $id);

                if (!$id) {
                    error_log(__FILE__ . '/' . __LINE__ . '/ ERROR: No region ID provided in _GET: ' . json_encode($_GET));
                    http_response_code(400);
                    echo json_encode(['error' => 'Region ID required']);
                    exit;
                }

                global $pdo;

                try {
                    // Check if region exists first
                    $stmt = $pdo->prepare("SELECT id, name, is_active FROM regions WHERE id = ?");
                    $stmt->execute([$id]);
                    $region = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$region) {
                        error_log(__FILE__ . '/' . __LINE__ . '/ ERROR: Region ID ' . $id . ' not found in database');
                        http_response_code(404);
                        echo json_encode(['error' => 'Region not found']);
                        exit;
                    }

                    if (!$region['is_active']) {
                        error_log(__FILE__ . '/' . __LINE__ . '/ INFO: Region ID ' . $id . ' already inactive');
                        echo json_encode(['success' => true, 'message' => 'Region already deactivated']);
                        exit;
                    }

                    // Check if region is being used
                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM service_provider_regions WHERE region_id = ?");
                    $stmt->execute([$id]);
                    $usage = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($usage['count'] > 0) {
                        error_log(__FILE__ . '/' . __LINE__ . '/ ERROR: Cannot delete region ID ' . $id . ' - in use by ' . $usage['count'] . ' providers');
                        http_response_code(409);
                        echo json_encode(['error' => 'Cannot delete region that is assigned to service providers']);
                        exit;
                    }

                    $stmt = $pdo->prepare("UPDATE regions SET is_active = FALSE WHERE id = ?");
                    error_log(__FILE__ . '/' . __LINE__ . '/ EXECUTING: UPDATE regions SET is_active = FALSE WHERE id = ' . $id);

                    $result = $stmt->execute([$id]);
                    $rows_affected = $stmt->rowCount();

                    error_log(__FILE__ . '/' . __LINE__ . '/ EXECUTION RESULT: ' . ($result ? 'TRUE' : 'FALSE') . ', rows_affected: ' . $rows_affected);

                    if ($result && $rows_affected > 0) {
                        error_log(__FILE__ . '/' . __LINE__ . '/ SUCCESS: Region ID ' . $id . ' (' . $region['name'] . ') deactivated');
                        logAdminAction(
                            $user_id,
                            'delete_region',
                            'region',
                            $id,
                            $region['name'],
                            'active',
                            'inactive',
                            "Deactivated region: " . $region['name']
                        );
                        echo json_encode(['success' => true, 'message' => 'Region deactivated successfully']);
                    } else {
                        error_log(__FILE__ . '/' . __LINE__ . '/ ERROR: Failed to execute deactivation for region ID ' . $id . ' - no rows affected');
                        http_response_code(500);
                        echo json_encode(['error' => 'Failed to deactivate region - no rows affected']);
                    }
                } catch (Exception $e) {
                    error_log(__FILE__ . '/' . __LINE__ . '/ EXCEPTION during region deletion: ' . $e->getMessage());
                    error_log(__FILE__ . '/' . __LINE__ . '/ Exception details: ' . $e->getTraceAsString());
                    http_response_code(500);
                    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
                }
                break;

            case 'delete_service':
                // Soft delete service (deactivate)
                $id = (int)($_GET['id'] ?? 0);

                if (!$id) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Service ID required']);
                    exit;
                }

                global $pdo;

                // Check if service is being used
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM service_provider_services WHERE service_id = ?");
                $stmt->execute([$id]);
                $usage = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($usage['count'] > 0) {
                    http_response_code(409);
                    echo json_encode(['error' => 'Cannot delete service that is assigned to service providers']);
                    exit;
                }

                $stmt = $pdo->prepare("UPDATE services SET is_active = FALSE WHERE id = ?");

                if ($stmt->execute([$id])) {
                    logAdminAction(
                        $user_id,
                        'delete_service',
                        'service',
                        $id,
                        null,
                        'active',
                        'inactive',
                        "Deactivated service ID: $id"
                    );
                    echo json_encode(['success' => true, 'message' => 'Service deactivated successfully']);
                } else {
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to deactivate service']);
                }
                break;

            default:
                http_response_code(400);
                echo json_encode(['error' => 'Invalid action']);
                break;
        }

    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }

} catch (Exception $e) {
    error_log("Admin API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}

/**
 * Helper functions for admin operations
 */

function getDetailedUsageStats($month, $user_type = null) {
    global $pdo;

    $where_conditions = ["u.is_active = TRUE"];
    $params = [];

    // We don't have a usage_tracking table yet, so return empty array for now
    // This prevents the dashboard from crashing while maintaining functionality

    // TODO: Implement proper usage tracking once we have the table structure
    return [];
}

function getUserManagementData($filter, $search) {
    global $pdo;

    $where_conditions = ["u.role_id NOT IN (6, 5)"]; // Exclude administrators and budget controllers from user management
    $params = [];

    // Add search condition - now searches users, participants, and their associated data
    if ($search) {
        $where_conditions[] = "(u.username LIKE ? OR u.email LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR p.name LIKE ? OR p.manager_email LIKE ?)";
        $search_param = "%$search%";
        $params = array_fill(0, 6, $search_param);
    }

    // Add filter conditions
    switch ($filter) {
        case 'clients':
            $where_conditions[] = "pt.participantType = 'C'";
            break;
        case 'sps':
            $where_conditions[] = "pt.participantType = 'S'";
            break;
        case 'disabled':
            $where_conditions[] = "p.is_enabled = FALSE";
            break;
    }

    $where_clause = implode(" AND ", $where_conditions);

    $stmt = $pdo->prepare("
        SELECT
            u.userId as user_id,
            u.username,
            u.first_name,
            u.last_name,
            u.email,
            u.is_active as user_active,
            u.role_id,
            r.name as role_name,
            p.participantId,
            p.name as participant_name,
            pt.participantType,
            CASE
                WHEN pt.participantType = 'C' THEN 'client'
                WHEN pt.participantType = 'S' THEN 'service_provider'
            END as entity_type,
            p.is_enabled as participant_enabled,
            p.disabled_reason as participant_disabled_reason,
            s.subscription_tier,
            s.monthly_job_limit,
            COALESCE(SUM(CASE WHEN su.usage_type = 'jobs_created' THEN su.count END), 0) as monthly_jobs_created,
            COALESCE(SUM(CASE WHEN su.usage_type = 'jobs_accepted' THEN su.count END), 0) as monthly_jobs_accepted
        FROM users u
        LEFT JOIN participants p ON u.entity_id = p.participantId
        LEFT JOIN participant_type pt ON p.participantId = pt.participantId
        LEFT JOIN user_roles r ON u.role_id = r.roleId
        LEFT JOIN subscriptions s ON p.participantId = s.participantId
        LEFT JOIN subscription_usage su ON s.id = su.subscription_id AND su.usage_month = ?
        WHERE {$where_clause}
        GROUP BY u.userId, u.username, u.first_name, u.last_name, u.email, u.is_active, u.role_id, r.name,
                 p.participantId, p.name, pt.participantType, p.is_enabled, p.disabled_reason,
                 s.subscription_tier, s.monthly_job_limit
        ORDER BY pt.participantType, p.created_at DESC
    ");
    array_unshift($params, date('Y-m')); // Add current month as first parameter
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAdminDashboardStats() {
    global $pdo;

    try {
        // Get client statistics (participants with type 'C')
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count FROM participants p
            JOIN participant_type pt ON p.participantId = pt.participantId
            WHERE pt.participantType = 'C' AND p.is_enabled = TRUE AND p.is_active = TRUE
        ");
        $stmt->execute();
        $clients_result = $stmt->fetch(PDO::FETCH_ASSOC);

        // Get service provider statistics (participants with type 'S')
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count FROM participants p
            JOIN participant_type pt ON p.participantId = pt.participantId
            WHERE pt.participantType = 'S' AND p.is_enabled = TRUE AND p.is_active = TRUE
        ");
        $stmt->execute();
        $sps_result = $stmt->fetch(PDO::FETCH_ASSOC);

        // Get active users (excluding admin users)
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count FROM users WHERE role_id NOT IN (6, 5) AND is_active = TRUE
        ");
        $stmt->execute();
        $users_result = $stmt->fetch(PDO::FETCH_ASSOC);

        // Get paying users (those with active subscriptions)
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT s.participantId) as count
            FROM subscriptions s
            WHERE s.subscription_tier IN ('basic', 'advanced') AND s.status = 'active'
        ");
        $stmt->execute();
        $paying_result = $stmt->fetch(PDO::FETCH_ASSOC);

        // Calculate monthly revenue estimate
        $stmt = $pdo->prepare("
            SELECT
                COALESCE(SUM(CASE WHEN ss.setting_key = 'client_basic_subscription_price' THEN CAST(ss.setting_value AS DECIMAL(10,2)) END), 0) +
                COALESCE(SUM(CASE WHEN ss.setting_key = 'provider_basic_subscription_price' THEN CAST(ss.setting_value AS DECIMAL(10,2)) END), 0) as revenue_estimate
            FROM site_settings ss
            WHERE ss.setting_key IN ('client_basic_subscription_price', 'provider_basic_subscription_price')
        ");
        $stmt->execute();
        $revenue_result = $stmt->fetch(PDO::FETCH_ASSOC);

        // Get total jobs created/reported using direct client_id relationship
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count FROM jobs j
            WHERE DATE_FORMAT(j.created_at, '%Y-%m') = ?
        ");
        $jobs_result = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'active_clients' => (int)$clients_result['count'],
            'active_sps' => (int)$sps_result['count'],
            'active_users' => (int)$users_result['count'],
            'paying_users' => (int)$paying_result['count'],
            'monthly_revenue_estimate' => (float)$revenue_result['revenue_estimate'],
            'total_jobs_created' => (int)$jobs_result['count'],
            'total_jobs_accepted' => 0 // TODO: Implement job acceptance tracking
        ];

    } catch (Exception $e) {
        error_log("Dashboard stats error: " . $e->getMessage());
        // Return fallback values if query fails
        return [
            'active_clients' => 0,
            'active_sps' => 0,
            'active_users' => 0,
            'paying_users' => 0,
            'monthly_revenue_estimate' => 0.00,
            'total_jobs_created' => 0,
            'total_jobs_accepted' => 0
        ];
    }
}

function setEntityStatus($entity_type, $entity_id, $enable, $reason, $admin_user_id) {
    global $pdo;

    try {
        // Update the participants table directly since we use unified structure
        $stmt = $pdo->prepare("
            UPDATE participants
            SET
                is_enabled = ?,
                disabled_reason = ?,
                disabled_at = ?,
                disabled_by_user_id = ?
            WHERE participantId = ?
        ");
        $stmt->execute([
            $enable ? 1 : 0,
            $reason ?: null,
            $enable ? null : date('Y-m-d H:i:s'),
            $enable ? null : $admin_user_id,
            $entity_id
        ]);

        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        error_log("Error updating entity status: " . $e->getMessage());
        return false;
    }
}

function resetUserUsage($user_id) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("DELETE FROM usage_tracking WHERE user_id = ? AND usage_month = ?");
        $stmt->execute([$user_id, date('Y-m')]);
        return true;
    } catch (Exception $e) {
        error_log("Error resetting user usage: " . $e->getMessage());
        return false;
    }
}

function logAdminAction($admin_user_id, $action_type, $target_type, $target_id, $target_identifier, $old_value, $new_value, $notes) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            INSERT INTO admin_actions (
                admin_user_id, action_type, target_type, target_id,
                target_identifier, old_value, new_value, notes,
                ip_address, user_agent
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $admin_user_id,
            $action_type,
            $target_type,
            $target_id,
            $target_identifier,
            $old_value,
            $new_value,
            $notes,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (Exception $e) {
        error_log("Error logging admin action: " . $e->getMessage());
    }
}
?>
