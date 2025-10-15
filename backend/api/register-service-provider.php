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

$requiredFields = ['providerName', 'firstName', 'lastName', 'address', 'email', 'password'];
if (!$invitationToken) {
    // Regular registration requires all fields
    if (!$data || count(array_intersect_key(array_flip($requiredFields), $data)) !== count($requiredFields)) {
        http_response_code(400);
        echo json_encode(['error' => 'All fields are required: providerName (company name), firstName, lastName, address, email, password']);
        exit;
    }
} else {
    // Invitation-based registration - some fields may be pre-filled
    $requiredFieldsForInvitation = ['firstName', 'lastName', 'email', 'password'];
    if (!$data || count(array_intersect_key(array_flip($requiredFieldsForInvitation), $data)) !== count($requiredFieldsForInvitation)) {
        http_response_code(400);
        echo json_encode(['error' => 'First name, last name, email and password are required for invitation-based registration']);
        exit;
    }
}

$providerName = trim($data['providerName']);
$firstName = trim($data['firstName']);
$lastName = trim($data['lastName']);
$address = trim($data['address']);
$email = trim($data['email']);
$password = $data['password'];

if (empty($providerName) || empty($firstName) || empty($lastName) || empty($address) || empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['error' => 'All fields must be non-empty']);
    exit;
}

// Since we're using email-only authentication, generate a username from email
// Remove @ and domain to create a username
$username = preg_replace('/@.*/', '', $email);

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

    // Check if email already exists (allow duplicate usernames since we're generating them)
    $stmt = $pdo->prepare("SELECT userId FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $pdo->rollBack();
        http_response_code(409);
        echo json_encode(['error' => 'Email already exists']);
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

    // Get default role for service providers (role_id 3 = Service Provider Admin)
    // First user created for a service provider becomes admin, subsequent users are technicians
    $stmt = $pdo->prepare("SELECT COUNT(*) as user_count FROM users WHERE entity_type = 'service_provider' AND entity_id = ?");
    $stmt->execute([$providerId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    $default_role = ($result['user_count'] == 0) ? 3 : 4; // 3 = Admin, 4 = Technician

    // Hash password
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Generate verification token
    $verificationToken = EmailService::generateVerificationToken();
    $tokenExpires = date('Y-m-d H:i:s', strtotime('+24 hours'));

    // Insert user with first_name and last_name
    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, email, first_name, last_name, role_id, entity_id, entity_type, is_active, email_verified, verification_token, token_expires) VALUES (?, ?, ?, ?, ?, ?, ?, 'service_provider', FALSE, FALSE, ?, ?)");
    $stmt->execute([$username, $passwordHash, $email, $firstName, $lastName, $default_role, $providerId, $verificationToken, $tokenExpires]);
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

    // Send verification email using user ID for proper name display
    $emailSent = EmailService::sendVerificationEmail($email, $userId, $verificationToken);

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
