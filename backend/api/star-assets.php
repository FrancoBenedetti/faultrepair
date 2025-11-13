<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', true);
ini_set('error_log', $_SERVER['DOCUMENT_ROOT'].'/all-logs/debug.log');

require_once '../config/database.php';
require_once '../includes/JWT.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// --- Authentication ---
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
$entity_id = $payload['entity_id'];
$entity_type = $payload['entity_type'];

// Only client-side users can star assets for their own lists
if ($entity_type !== 'client') {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied. This feature is for clients only.']);
    exit;
}

// --- Input Validation ---
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['asset_ids']) || !is_array($data['asset_ids']) || empty($data['asset_ids'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No asset IDs provided.']);
    exit;
}

if (!isset($data['star'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Star status not provided.']);
    exit;
}

$asset_ids = $data['asset_ids'];
$star_status = $data['star'] ? 1 : 0;
$placeholders = implode(',', array_fill(0, count($asset_ids), '?'));

// --- Database Update ---
try {
    $pdo->beginTransaction();

    $sql = "UPDATE assets SET star = ? WHERE id IN ($placeholders) AND list_owner_id = ?";
    
    $stmt = $pdo->prepare($sql);
    
    // Parameters for binding
    $params = array_merge([$star_status], $asset_ids, [$entity_id]);
    
    $stmt->execute($params);
    
    $affected_rows = $stmt->rowCount();

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => "Successfully updated {$affected_rows} assets.",
        'affected_rows' => $affected_rows
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    error_log("Star Assets DB Error: " . $e->getMessage());
    echo json_encode(['error' => 'Database error occurred while updating assets.']);
    exit;
}
?>
