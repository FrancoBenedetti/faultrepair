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

// JWT Authentication
$headers = getallheaders();
$auth_header = isset($headers['Authorization']) ? $headers['Authorization'] : '';

if (!$auth_header || !preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
    http_response_code(401);
    echo json_encode(['error' => 'Authorization header missing or invalid']);
    exit;
}

$token = $matches[1];
try {
    $payload = JWT::decode($token);
    $user_id = $payload['user_id'];
    $entity_type = $payload['entity_type'];
    $entity_id = $payload['entity_id'];
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid token']);
    exit;
}

// Verify user is a service provider
if ($entity_type !== 'service_provider') {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied. Service provider access required.']);
    exit;
}

try {
    // Build query with optional filters
    $where_conditions = ["j.assigned_provider_id = ?"];
    $params = [$entity_id];

    // Status filter
    if (isset($_GET['status']) && !empty($_GET['status'])) {
        $where_conditions[] = "j.job_status = ?";
        $params[] = $_GET['status'];
    }

    // Client filter
    if (isset($_GET['client_id']) && !empty($_GET['client_id'])) {
        $where_conditions[] = "c.id = ?";
        $params[] = $_GET['client_id'];
    }

    // Technician filter
    if (isset($_GET['technician_id']) && !empty($_GET['technician_id'])) {
        $where_conditions[] = "j.assigned_technician_id = ?";
        $params[] = $_GET['technician_id'];
    }

    $where_clause = implode(" AND ", $where_conditions);

    // Get filtered jobs assigned to this service provider
    $stmt = $pdo->prepare("
        SELECT
            j.id,
            j.item_identifier,
            j.fault_description,
            j.technician_notes,
            j.job_status,
            j.created_at,
            j.updated_at,
            j.contact_person,
            l.name as location_name,
            l.address as location_address,
            c.name as client_name,
            c.id as client_id,
            u.username as reporting_user,
            tu.username as assigned_technician,
            tu.id as assigned_technician_id,
            (SELECT COUNT(*) FROM job_images ji WHERE ji.job_id = j.id) as image_count
        FROM jobs j
        JOIN locations l ON j.client_location_id = l.id
        JOIN clients c ON l.client_id = c.id
        LEFT JOIN users u ON j.reporting_user_id = u.id
        LEFT JOIN users tu ON j.assigned_technician_id = tu.id
        WHERE {$where_clause}
        ORDER BY j.created_at DESC
    ");

    $stmt->execute($params);
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'jobs' => $jobs
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to retrieve jobs: ' . $e->getMessage()]);
}
?>
