<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('log_errors', true);
ini_set('error_log', $_SERVER['DOCUMENT_ROOT'].'/all-logs/client-administrators.log');

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
$sp_entity_id = $payload['entity_id']; // Service Provider's participantId

// This endpoint is for Service Providers (role 3) to get a client's admins.
if ($role_id != 3) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied. Service provider access required.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// --- Main Logic ---
try {
    if (!isset($_GET['client_id']) || empty($_GET['client_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'client_id is a required query parameter.']);
        exit;
    }
    $client_id_filter = (int)$_GET['client_id'];

    // --- Authorization: Verify the SP is approved for this client ---
    $stmt = $pdo->prepare("SELECT id FROM participant_approvals WHERE client_participant_id = ? AND provider_participant_id = ?");
    $stmt->execute([$client_id_filter, $sp_entity_id]);
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied. You are not an approved service provider for this client.']);
        exit;
    }

    // --- Fetch Data: Get all users for the client with role_id = 2 ---
    $sql = "
        SELECT
            u.userId as id,
            u.first_name,
            u.last_name,
            u.email
        FROM
            users u
        WHERE
            u.entity_id = ? AND u.role_id = 2
        ORDER BY
            u.last_name, u.first_name
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$client_id_filter]);
    $client_admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['client_administrators' => $client_admins]);

} catch (Exception $e) {
    http_response_code(500);
    error_log('Client Administrators API Error: ' . $e->getMessage());
    echo json_encode(['error' => 'An internal server error occurred.']);
}
?>
