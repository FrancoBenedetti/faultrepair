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
    // Get approved clients for this service provider
    $stmt = $pdo->prepare("
        SELECT
            c.id,
            c.name,
            c.address,
            cap.approved_at,
            cap.notes,
            COUNT(j.id) as total_jobs,
            COUNT(CASE WHEN j.job_status IN ('Reported', 'Assigned', 'In Progress') THEN 1 END) as active_jobs,
            COUNT(CASE WHEN j.job_status = 'Completed' THEN 1 END) as completed_jobs
        FROM client_approved_providers cap
        JOIN clients c ON cap.client_id = c.id
        LEFT JOIN jobs j ON j.client_location_id IN (
            SELECT id FROM locations WHERE client_id = c.id
        ) AND j.assigned_provider_id = ?
        WHERE cap.service_provider_id = ?
        GROUP BY c.id, c.name, c.address, cap.approved_at, cap.notes
        ORDER BY cap.approved_at DESC
    ");

    $stmt->execute([$entity_id, $entity_id]);
    $approved_clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['clients' => $approved_clients]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to retrieve approved clients: ' . $e->getMessage()]);
}
?>
