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
            p.participantId as client_id,
            p.name as client_name
        FROM participant_approvals pa
        JOIN participants p ON pa.client_participant_id = p.participantId
        WHERE pa.provider_participant_id = ?
        ORDER BY p.name ASC
    ");

    $stmt->execute([$entity_id]);
    $approved_clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['approved_clients' => $approved_clients]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to retrieve approved clients: ' . $e->getMessage()]);
}
?>
