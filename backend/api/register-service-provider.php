<?php
require_once '../config/database.php';
require_once '../includes/JWT.php';
require_once '../includes/email.php';

$log_file = $_SERVER['DOCUMENT_ROOT'].'/all-logs/registration-debug.log';
error_log("=== Registration Debug Script Started " . date('Y-m-d H:i:s') . " ===\n", 3, $log_file);

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
error_log("[DEBUG] Registration request: InvitationToken=" . ($invitationToken ? substr($invitationToken, 0, 10) . '...' : 'NONE') . ", Email={$email}, ProviderName={$providerName}\n", 3, $log_file);

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
            JOIN users u ON i.inviter_user_id = u.userId
            WHERE i.invitation_token = ? AND i.invitation_status = 'accessed'
        ");
        $stmt->execute([$invitationToken]);
        $invitation = $stmt->fetch();

        if ($invitation) {
            // Mark invitation as completed
            $stmt = $pdo->prepare("UPDATE invitations SET invitation_status = 'completed', completed_at = NOW() WHERE id = ?");
            $stmt->execute([$invitation['id']]);

            error_log("[DEBUG] Processing invitation-based registration: InvitationID={$invitation['id']}, Status={$invitation['invitation_status']}, InviterEntityType={$invitation['inviter_entity_type']}, AutoApprovalApplied={$invitation['auto_approval_applied']}, AutoApprovalAvailableForInvitee={$invitation['auto_approval_available_for_invitee']}\n", 3, $log_file);

            // If invited by a client and auto-approval is available, check invitation status
            if ($invitation['inviter_entity_type'] === 'client' || $invitation['inviter_entity_type'] === 'C') {
                error_log("[DEBUG] Client invited service provider: Checking approval logic\n", 3, $log_file);
                if ($invitation['auto_approval_applied']) {
                    error_log("[DEBUG] Auto-approval already applied during invitation creation - no further action needed\n", 3, $log_file);
                    // Auto-approval was already applied when invitation was created
                    // No action needed here
                } elseif ($invitation['auto_approval_available_for_invitee']) {
                    error_log("[DEBUG] Auto-approval available for invitee during registration - applying now with ClientID={$invitation['inviter_entity_id']}, ProviderID={$providerId}\n", 3, $log_file);
                    // Auto-approval is available for the invitee to apply during registration
                    $stmt = $pdo->prepare("
                        INSERT INTO participant_approvals (client_participant_id, provider_participant_id)
                        VALUES (?, ?)
                        ON DUPLICATE KEY UPDATE client_participant_id = client_participant_id
                    ");
                    $insertResult = $stmt->execute([$invitation['inviter_entity_id'], $providerId]);
                    $insertRowsAffected = $stmt->rowCount();
                    error_log("[DEBUG] participant_approvals INSERT result during registration: Success=" . ($insertResult ? 'YES' : 'NO') . ", RowsAffected={$insertRowsAffected}, ClientID={$invitation['inviter_entity_id']}, ProviderID={$providerId}\n", 3, $log_file);
                } else {
                    // NEW: Apply auto-approval for client admins (roleId = 2) inviting existing service providers
                    error_log("[DEBUG] Checking for auto-approval from client admin: retrieving inviter role\n", 3, $log_file);
                    $stmt = $pdo->prepare("SELECT u.role_id FROM users u WHERE u.userId = ?");
                    $stmt->execute([$invitation['inviter_user_id']]);
                    $inviterUser = $stmt->fetch();

                    if ($inviterUser && $inviterUser['role_id'] === 2) {
                        error_log("[DEBUG] Inviter is client admin (roleId=2) - applying auto-approval with ClientID={$invitation['inviter_entity_id']}, ProviderID={$providerId}\n", 3, $log_file);
                        $stmt = $pdo->prepare("
                            INSERT INTO participant_approvals (client_participant_id, provider_participant_id)
                            VALUES (?, ?)
                            ON DUPLICATE KEY UPDATE client_participant_id = client_participant_id
                        ");
                        $insertResult = $stmt->execute([$invitation['inviter_entity_id'], $providerId]);
                        $insertRowsAffected = $stmt->rowCount();
                        error_log("[DEBUG] participant_approvals INSERT result for client admin auto-approval: Success=" . ($insertResult ? 'YES' : 'NO') . ", RowsAffected={$insertRowsAffected}, ClientID={$invitation['inviter_entity_id']}, ProviderID={$providerId}\n", 3, $log_file);
                    } else {
                        error_log("[DEBUG] Inviter roleId={$inviterUser['role_id']} is not client admin - no auto-approval applied\n", 3, $log_file);
                    }
                }
            } else {
                error_log("[DEBUG] Not a client invitation - no approval logic applied\n", 3, $log_file);
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
