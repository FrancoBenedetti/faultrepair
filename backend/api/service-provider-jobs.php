<?php
ini_set('log_errors', true);
ini_set('error_log', $_SERVER['DOCUMENT_ROOT'].'/all-logs/service-provider-jobs.log');
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

// Build query based on parameters instead of role
// If technician_id parameter is passed, filter by that technician, else show all for provider
error_log("service-provider-jobs.php - Processing request, user_id: $user_id, entity_id: $entity_id");

try {
    // Base condition - always filter by provider participant
    $where_conditions = ["j.assigned_provider_participant_id = ?"];
    $params = [$entity_id];

    // If technician_id is passed, filter by specific technician (technician view)
    if (isset($_GET['technician_id']) && !empty($_GET['technician_id'])) {
        $where_conditions[] = "j.assigned_technician_user_id = ?";
        $params[] = $_GET['technician_id'];
        error_log("service-provider-jobs.php - Technician filter applied for technician_id: " . $_GET['technician_id']);
    }

    // Additional filters
    if (isset($_GET['status']) && !empty($_GET['status'])) {
        $where_conditions[] = "j.job_status = ?";
        $params[] = $_GET['status'];
    }

    if (isset($_GET['client_id']) && !empty($_GET['client_id'])) {
        $where_conditions[] = "p.participantId = ?";
        $params[] = $_GET['client_id'];
    }

    // Filter for quote-related jobs if requested
    if (isset($_GET['quote_jobs']) && $_GET['quote_jobs'] === 'true') {
        $where_conditions[] = "j.job_status IN ('Quote Requested', 'Quote Provided')";
        $params[] = $_GET['quote_jobs'];
    }

    $where_clause = implode(" AND ", $where_conditions);
    error_log("service-provider-jobs.php - WHERE clause: $where_clause, params: " . json_encode($params));

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
            j.assigned_technician_user_id,
            l.name as location_name,
            l.address as location_address,
            l.coordinates as location_coordinates,
            l.access_rules as location_access_rules,
            l.access_instructions as location_access_instructions,
            p.name as client_name,
            p.participantId as client_id,
            u.username as reporting_user,
            CONCAT(tu.first_name, ' ', tu.last_name) as assigned_technician,
            tu.userId as assigned_technician_user_id,
            (SELECT COUNT(*) FROM job_images ji WHERE ji.job_id = j.id) as image_count
        FROM jobs j
        JOIN locations l ON j.client_location_id = l.id
        JOIN participants p ON l.participant_id = p.participantId
        LEFT JOIN users u ON j.reporting_user_id = u.userId
        LEFT JOIN users tu ON j.assigned_technician_user_id = tu.userId
        WHERE {$where_clause}
        ORDER BY j.created_at DESC
    ");

    $stmt->execute($params);
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    error_log("service-provider-jobs.php - Found " . count($jobs) . " jobs");
    error_log("service-provider-jobs.php - First few jobs raw data:");
    for ($i = 0; $i < min(3, count($jobs)); $i++) {
        error_log("service-provider-jobs.php - Job " . ($i + 1) . ": " . json_encode([
            'id' => $jobs[$i]['id'] ?? 'missing',
            'assigned_technician_user_id' => $jobs[$i]['assigned_technician_user_id'] ?? 'missing',
            'assigned_technician' => $jobs[$i]['assigned_technician'] ?? 'missing'
        ]));
    }

    echo json_encode([
        'jobs' => $jobs
    ]);

} catch (Exception $e) {
    error_log("service-provider-jobs.php - Admin query failed: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to retrieve jobs: ' . $e->getMessage()]);
}
?>
