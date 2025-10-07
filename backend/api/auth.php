<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/JWT.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['username']) || !isset($data['password'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Username and password required']);
    exit;
}

$username = $data['username'];
$password = $data['password'];

$stmt = $pdo->prepare("
    SELECT
        u.userId as id,
        u.password_hash,
        u.role_id,
        u.entity_id,
        u.is_active,
        u.email_verified,
        CASE
            WHEN pt.participantType = 'C' THEN 'client'
            WHEN pt.participantType = 'S' THEN 'service_provider'
            ELSE 'unknown'
        END as entity_type
    FROM users u
    LEFT JOIN participant_type pt ON u.entity_id = pt.participantId
    WHERE u.username = ?
");
$stmt->execute([$username]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid credentials']);
    exit;
}

if (!$user['is_active']) {
    if (!$user['email_verified']) {
        http_response_code(403);
        echo json_encode(['error' => 'Please verify your email address before signing in. Check your email for the verification link.']);
        exit;
    } else {
        http_response_code(403);
        echo json_encode(['error' => 'Your account is inactive. Please contact support.']);
        exit;
    }
}

if (!password_verify($password, $user['password_hash'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid credentials']);
    exit;
}

$payload = [
    'user_id' => $user['id'],
    'role_id' => $user['role_id'],
    'entity_type' => $user['entity_type'],
    'entity_id' => $user['entity_id'],
    'iat' => time(),
    'exp' => time() + 86400 // 24 hours
];

$token = JWT::encode($payload);

echo json_encode(['token' => $token]);
?>
