<?php
require_once '../config/database.php';
require_once '../includes/JWT.php';

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

if (!$data || !isset($data['providerName']) || !isset($data['address']) || !isset($data['username']) || !isset($data['email']) || !isset($data['password'])) {
    http_response_code(400);
    echo json_encode(['error' => 'All fields are required: providerName, address, username, email, password']);
    exit;
}

$providerName = trim($data['providerName']);
$address = trim($data['address']);
$username = trim($data['username']);
$email = trim($data['email']);
$password = $data['password'];

if (empty($providerName) || empty($address) || empty($username) || empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['error' => 'All fields must be non-empty']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email format']);
    exit;
}

if (strlen($password) < 6) {
    http_response_code(400);
    echo json_encode(['error' => 'Password must be at least 6 characters long']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Check if username or email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        $pdo->rollBack();
        http_response_code(409);
        echo json_encode(['error' => 'Username or email already exists']);
        exit;
    }

    // Insert service provider
    $stmt = $pdo->prepare("INSERT INTO service_providers (name, address) VALUES (?, ?)");
    $stmt->execute([$providerName, $address]);
    $providerId = $pdo->lastInsertId();

    // Get default role for service providers (Service Provider Admin)
    $stmt = $pdo->prepare("SELECT id FROM roles WHERE name = 'Service Provider Admin'");
    $stmt->execute();
    $role = $stmt->fetch();
    if (!$role) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Default role not found']);
        exit;
    }

    // Hash password
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Insert user
    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, email, role_id, entity_type, entity_id) VALUES (?, ?, ?, ?, 'service_provider', ?)");
    $stmt->execute([$username, $passwordHash, $email, $role['id'], $providerId]);

    $pdo->commit();

    echo json_encode(['message' => 'Service provider registered successfully']);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Registration failed: ' . $e->getMessage()]);
}
?>
