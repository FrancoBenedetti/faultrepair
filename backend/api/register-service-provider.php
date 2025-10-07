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

// Check for invitation token
$invitationToken = isset($data['invitationToken']) ? trim($data['invitationToken']) : null;

$requiredFields = ['providerName', 'address', 'username', 'email', 'password'];
if (!$invitationToken) {
    // Regular registration requires all fields
    if (!$data || count(array_intersect_key(array_flip($requiredFields), $data)) !== count($requiredFields)) {
        http_response_code(400);
        echo json_encode(['error' => 'All fields are required: providerName, address, username, email, password']);
        exit;
    }
} else {
    // Invitation-based registration - some fields may be pre-filled
    $requiredFieldsForInvitation = ['username', 'email', 'password'];
    if (!$data || count(array_intersect_key(array_flip($requiredFieldsForInvitation), $data)) !== count($requiredFieldsForInvitation)) {
        http_response_code(400);
        echo json_encode(['error' => 'Username, email, and password are required for invitation-based registration']);
        exit;
    }
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
    $stmt = $pdo->prepare("SELECT userId FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        $pdo->rollBack();
        http_response_code(409);
        echo json_encode(['error' => 'Username or email already exists']);
        exit;
    }

    // Insert service provider into participants
    $stmt = $pdo->prepare("INSERT INTO participants (name, address) VALUES (?, ?)");
    $stmt->execute([$providerName, $address]);
    $providerId = $pdo->lastInsertId();

    // Add participant type
    $stmt = $pdo->prepare("INSERT INTO participant_type (participantId, participantType) VALUES (?, 'S')");
    $stmt->execute([$providerId]);

    // Set up default free subscription for the service provider
    $stmt = $pdo->prepare("INSERT INTO subscriptions (participantId, subscription_tier, status, monthly_job_limit) VALUES (?, 'free', 'active', 4)");
    $stmt->execute([$providerId]);

    // Get default role for service providers (Service Provider Admin)
    $stmt = $pdo->prepare("SELECT roleId as id FROM user_roles WHERE name = 'Service Provider Admin'");
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
    $stmt->execute([$username, $passwordHash, $email, $role['id'], $providerId, $verificationToken, $tokenExpires]);

    $userId = $pdo->lastInsertId();

    // Handle invitation-based registration
    if ($invitationToken) {
        // Validate invitation token and get invitation details
        $stmt = $pdo->prepare("
            SELECT i.*, u.entity_id as inviter_entity_id
            FROM invitations i
            JOIN users u ON i.inviter_user_id = u.id
            WHERE i.invitation_token = ? AND i.invitation_status = 'accessed'
        ");
        $stmt->execute([$invitationToken]);
        $invitation = $stmt->fetch();

        if ($invitation) {
            // Mark invitation as completed
            $stmt = $pdo->prepare("UPDATE invitations SET invitation_status = 'completed', completed_at = NOW() WHERE id = ?");
            $stmt->execute([$invitation['id']]);

            // If invited by a client, auto-approve this service provider for that client
            if ($invitation['inviter_entity_type'] === 'client') {
                $stmt = $pdo->prepare("
                    INSERT INTO participant_approvals (client_participant_id, provider_participant_id)
                    VALUES (?, ?)
                    ON DUPLICATE KEY UPDATE client_participant_id = client_participant_id
                ");
                $stmt->execute([$invitation['inviter_entity_id'], $providerId]);
            }
        }
    }

    $pdo->commit();

    // Send verification email
    $emailSent = EmailService::sendVerificationEmail($email, $username, $verificationToken);

    if ($emailSent) {
        echo json_encode(['message' => 'Service provider registered successfully. Please check your email to verify your account.']);
    } else {
        // Email failed, but registration succeeded - user can request resend
        echo json_encode(['message' => 'Service provider registered successfully. Email verification could not be sent. Please contact support or try logging in to resend verification email.']);
    }

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Registration failed: ' . $e->getMessage()]);
}
?>
