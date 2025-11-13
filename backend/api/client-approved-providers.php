<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('log_errors', true);
ini_set('error_log', $_SERVER['DOCUMENT_ROOT'].'/all-logs/client-approved-providers.log');

require_once '../config/database.php';
require_once '../includes/JWT.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// JWT Authentication
$auth_header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token = '';
if (preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
    $token = $matches[1];
}
if (!$token) {
    $token = $_GET['token'] ?? '';
}

if (!$token) {
    http_response_code(401);
    echo json_encode(['error' => 'Authorization token missing']);
    exit;
}

$payload = JWT::decode($token);
if ($payload === false) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid or expired token.']);
    exit;
}

$user_id = $payload['user_id'];
$role_id = $payload['role_id'];
$entity_type = $payload['entity_type'];
$client_entity_id = $payload['entity_id'];

// This endpoint is for clients to get their list of approved providers.
if ($entity_type !== 'client') {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied. Client access required.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// --- Main Logic ---
try {
    // A client_id can be passed to allow an admin to check a client's approved list.
    // For now, we will restrict to the user's own client entity.
    $client_id_filter = $client_entity_id;
    
    // --- Fetch Data: Get all approved providers for the client ---
    $sql = "
        SELECT
            p.participantId as id,
            p.name
        FROM
            participants p
        JOIN
            participant_approvals pa ON p.participantId = pa.provider_participant_id
        WHERE
            pa.client_participant_id = ?
        ORDER BY
            p.name
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$client_id_filter]);
    $approved_providers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['approved_providers' => $approved_providers]);

} catch (Exception $e) {
    http_response_code(500);
    error_log('Client Approved Providers API Error: ' . $e->getMessage());
    echo json_encode(['error' => 'An internal server error occurred.']);
}
?>