<?php
require_once '../config/database.php';
require_once '../includes/JWT.php';
require_once '../includes/email.php';

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

if (!$data || !isset($data['clientName']) || !isset($data['address']) || !isset($data['username']) || !isset($data['email']) || !isset($data['password'])) {
    http_response_code(400);
    echo json_encode(['error' => 'All fields are required: clientName, address, username, email, password']);
    exit;
}

$clientName = trim($data['clientName']);
$address = trim($data['address']);
$username = trim($data['username']);
$email = trim($data['email']);
$password = $data['password'];

if (empty($clientName) || empty($address) || empty($username) || empty($email) || empty($password)) {
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
    $stmt = $pdo->prepare("SELECT userId FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        $pdo->rollBack();
        http_response_code(409);
        echo json_encode(['error' => 'Username or email already exists']);
        exit;
    }

    // Insert client into participants table
    $stmt = $pdo->prepare("INSERT INTO participants (name, address) VALUES (?, ?)");
    $stmt->execute([$clientName, $address]);
    $clientId = $pdo->lastInsertId();

    // Set participant type as client
    $stmt = $pdo->prepare("INSERT INTO participant_type (participantId, participantType) VALUES (?, 'C')");
    $stmt->execute([$clientId]);

    // Set up default free subscription for the client
    $stmt = $pdo->prepare("INSERT INTO subscriptions (participantId, subscription_tier, status, monthly_job_limit) VALUES (?, 'free', 'active', 3)");
    $stmt->execute([$clientId]);

    // Get default role for clients (Client Admin - roleId = 2)
    $stmt = $pdo->prepare("SELECT roleId as id FROM user_roles WHERE name = 'Client Admin'");
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

    // Generate verification token
    $verificationToken = EmailService::generateVerificationToken();
    $tokenExpires = date('Y-m-d H:i:s', strtotime('+24 hours'));

    // Insert user
    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, email, role_id, entity_id, is_active, email_verified, verification_token, token_expires) VALUES (?, ?, ?, ?, ?, FALSE, FALSE, ?, ?)");
    $stmt->execute([$username, $passwordHash, $email, $role['id'], $clientId, $verificationToken, $tokenExpires]);

    $pdo->commit();

    // Send verification email
    $emailSent = EmailService::sendVerificationEmail($email, $username, $verificationToken);

    if ($emailSent) {
        echo json_encode(['message' => 'Client registered successfully. Please check your email to verify your account.']);
    } else {
        // Email failed, but registration succeeded - user can request resend
        echo json_encode(['message' => 'Client registered successfully. Email verification could not be sent. Please contact support or try logging in to resend verification email.']);
    }

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Registration failed: ' . $e->getMessage()]);
}
?>
