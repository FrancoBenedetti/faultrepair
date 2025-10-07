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

try {
    // Get approved clients for this service provider
    $stmt = $pdo->prepare("
        SELECT
            p.participantId as id,
            p.name,
            p.address,
            pa.created_at as approved_at,
            NULL as notes,
            COUNT(j.id) as total_jobs,
            COUNT(CASE WHEN j.job_status IN ('Reported', 'Assigned', 'In Progress') THEN 1 END) as active_jobs,
            COUNT(CASE WHEN j.job_status = 'Completed' THEN 1 END) as completed_jobs
        FROM participant_approvals pa
        JOIN participants p ON pa.client_participant_id = p.participantId
        LEFT JOIN jobs j ON j.client_location_id IN (
            SELECT id FROM locations WHERE participant_id = p.participantId
        ) AND j.assigned_provider_participant_id = ?
        WHERE pa.provider_participant_id = ?
        GROUP BY p.participantId, p.name, p.address, pa.created_at
        ORDER BY pa.created_at DESC
    ");

    $stmt->execute([$entity_id, $entity_id]);
    $approved_clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['clients' => $approved_clients]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to retrieve approved clients: ' . $e->getMessage()]);
}
?>
