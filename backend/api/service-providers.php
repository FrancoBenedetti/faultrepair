<?php
require_once '../config/database.php';
require_once '../includes/JWT.php';

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

// Optional JWT Authentication for clients - check both headers and query parameters
$token = null;

// Try to get token from Authorization header first
$headers = getallheaders();
$auth_header = isset($headers['Authorization']) ? $headers['Authorization'] : '';
if ($auth_header && preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
    $token = $matches[1];
}

// If no token in header, try query parameter (for live server compatibility)
if (!$token) {
    $token = $_GET['token'] ?? null;
}

$client_id = null;
if ($token) {
    try {
        $payload = JWT::decode($token);
        if ($payload['entity_type'] === 'client') {
            $client_id = $payload['entity_id'];
        }
    } catch (Exception $e) {
        // Invalid token, continue without authentication
    }
}

// Get query parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$service_ids = isset($_GET['services']) && !empty(trim($_GET['services'])) ? explode(',', trim($_GET['services'])) : [];
$region_ids = isset($_GET['regions']) && !empty(trim($_GET['regions'])) ? explode(',', trim($_GET['regions'])) : [];
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'name';
$sort_order = isset($_GET['order']) && strtoupper($_GET['order']) === 'DESC' ? 'DESC' : 'ASC';

// Validate sort options
$valid_sort_fields = ['name', 'created_at', 'updated_at'];
if (!in_array($sort_by, $valid_sort_fields)) {
    $sort_by = 'name';
}

try {
    // Build the main query
    $where_conditions = ["sp.is_active = TRUE"];
    $params = [];

    // Search filter
    if (!empty($search)) {
        $where_conditions[] = "(sp.name LIKE ? OR sp.description LIKE ? OR sp.address LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }

    // Service filter
    if (!empty($service_ids)) {
        $placeholders = str_repeat('?,', count($service_ids) - 1) . '?';
        $where_conditions[] = "sp.id IN (
            SELECT DISTINCT sps.service_provider_id
            FROM service_provider_services sps
            WHERE sps.service_id IN ($placeholders)
        )";
        $params = array_merge($params, $service_ids);
    }

    // Region filter
    if (!empty($region_ids)) {
        $placeholders = str_repeat('?,', count($region_ids) - 1) . '?';
        $where_conditions[] = "sp.id IN (
            SELECT DISTINCT spr.service_provider_id
            FROM service_provider_regions spr
            WHERE spr.region_id IN ($placeholders)
        )";
        $params = array_merge($params, $region_ids);
    }

    $where_clause = implode(' AND ', $where_conditions);

    // Get total count for pagination
    $count_sql = "SELECT COUNT(DISTINCT sp.id) as total FROM service_providers sp WHERE $where_clause";
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Calculate pagination
    $offset = ($page - 1) * $limit;
    $total_pages = ceil($total_count / $limit);

    // Main query to get service providers
    $sql = "
        SELECT
            sp.id,
            sp.name,
            sp.address,
            sp.website,
            sp.description,
            sp.logo_url,
            sp.created_at,
            sp.updated_at,
            COUNT(DISTINCT sps.service_id) as services_count,
            COUNT(DISTINCT spr.region_id) as regions_count
        FROM service_providers sp
        LEFT JOIN service_provider_services sps ON sp.id = sps.service_provider_id
        LEFT JOIN service_provider_regions spr ON sp.id = spr.service_provider_id
        WHERE $where_clause
        GROUP BY sp.id
        ORDER BY sp.$sort_by $sort_order
        LIMIT ? OFFSET ?
    ";

    $params[] = $limit;
    $params[] = $offset;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $providers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get detailed information for each provider
    foreach ($providers as &$provider) {
        // Get services for this provider
        $stmt = $pdo->prepare("
            SELECT s.id, s.name, s.category, sps.is_primary
            FROM services s
            JOIN service_provider_services sps ON s.id = sps.service_id
            WHERE sps.service_provider_id = ?
            ORDER BY sps.is_primary DESC, s.name
        ");
        $stmt->execute([$provider['id']]);
        $provider['services'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get regions for this provider
        $stmt = $pdo->prepare("
            SELECT r.id, r.name, r.code
            FROM regions r
            JOIN service_provider_regions spr ON r.id = spr.region_id
            WHERE spr.service_provider_id = ?
            ORDER BY r.name
        ");
        $stmt->execute([$provider['id']]);
        $provider['regions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Check if client has approved this provider
        if ($client_id) {
            $stmt = $pdo->prepare("
                SELECT id, approved_at, notes
                FROM client_approved_providers
                WHERE client_id = ? AND service_provider_id = ?
            ");
            $stmt->execute([$client_id, $provider['id']]);
            $approval = $stmt->fetch(PDO::FETCH_ASSOC);
            $provider['is_approved'] = $approval ? true : false;
            $provider['approval_details'] = $approval;
        } else {
            $provider['is_approved'] = false;
            $provider['approval_details'] = null;
        }
    }

    // Get available filters (services and regions)
    $stmt = $pdo->query("SELECT id, name, category FROM services WHERE is_active = TRUE ORDER BY category, name");
    $available_services = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->query("SELECT id, name, code FROM regions WHERE is_active = TRUE ORDER BY name");
    $available_regions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Response
    $response = [
        'providers' => $providers,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total_count' => $total_count,
            'total_pages' => $total_pages,
            'has_next' => $page < $total_pages,
            'has_prev' => $page > 1
        ],
        'filters' => [
            'services' => $available_services,
            'regions' => $available_regions,
            'applied' => [
                'search' => $search,
                'services' => array_map('intval', $service_ids),
                'regions' => array_map('intval', $region_ids),
                'sort' => $sort_by,
                'order' => $sort_order
            ]
        ]
    ];

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to retrieve service providers: ' . $e->getMessage()]);
}
?>
