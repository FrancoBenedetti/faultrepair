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

if (!$data || !isset($data['clientName']) || !isset($data['firstName']) || !isset($data['lastName']) || !isset($data['address']) || !isset($data['email']) || !isset($data['password'])) {
    http_response_code(400);
    echo json_encode(['error' => 'All fields are required: clientName (company name), firstName, lastName, address, email, password']);
    exit;
}

$clientName = trim($data['clientName']);
$firstName = trim($data['firstName']);
$lastName = trim($data['lastName']);
$address = trim($data['address']);
$email = trim($data['email']);
$password = $data['password'];

if (empty($clientName) || empty($firstName) || empty($lastName) || empty($address) || empty($email) || empty($password)) {
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
    $stmt = $pdo->prepare("SELECT roleId as id FROM user_roles WHERE roleId = 2");
    $stmt->execute();
    $role = $stmt->fetch();
    if (!$role) {
        error_log("[ERROR] Default role with roleId = 2 not found in user_roles table\n", 3, $_SERVER['DOCUMENT_ROOT'].'/all-logs/registration-debug.log');
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Default role not found - roleId 2 missing from user_roles table']);
        exit;
    }

    // Hash password
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Generate verification token
    $verificationToken = EmailService::generateVerificationToken();
    $tokenExpires = date('Y-m-d H:i:s', strtotime('+24 hours'));

    // Insert user with first_name and last_name
    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, email, first_name, last_name, role_id, entity_id, is_active, email_verified, verification_token, token_expires) VALUES (?, ?, ?, ?, ?, ?, ?, FALSE, FALSE, ?, ?)");
    $stmt->execute([$username, $passwordHash, $email, $firstName, $lastName, $role['id'], $clientId, $verificationToken, $tokenExpires]);
    $userId = $pdo->lastInsertId();

    // Handle invitation-based registration if token provided
    $autoApproved = false;
    if (isset($data['invitationToken']) && !empty($data['invitationToken'])) {
        error_log("[DEBUG] Processing client invitation-based registration: Token=" . substr($data['invitationToken'], 0, 10) . "...\n", 3, $_SERVER['DOCUMENT_ROOT'].'/all-logs/registration-debug.log');

        // Validate invitation token and get invitation details
        $stmt = $pdo->prepare("
            SELECT i.*, u.entity_id as inviter_entity_id
            FROM invitations i
            JOIN users u ON i.inviter_user_id = u.userId
            WHERE i.invitation_token = ? AND i.invitation_status = 'accessed'
        ");
        $stmt->execute([$data['invitationToken']]);
        $invitation = $stmt->fetch();

        if ($invitation) {
            error_log("[DEBUG] Invitation found: ID={$invitation['id']}, InviterEntityType={$invitation['inviter_entity_type']}, AutoApprovalAvailable={$invitation['auto_approval_available_for_invitee']}, InviterEntityID={$invitation['inviter_entity_id']}, ClientID={$clientId}\n", 3, $_SERVER['DOCUMENT_ROOT'].'/all-logs/registration-debug.log');

            // Mark invitation as completed
            $stmt = $pdo->prepare("UPDATE invitations SET invitation_status = 'completed', completed_at = NOW() WHERE id = ?");
            $stmt->execute([$invitation['id']]);
            error_log("[DEBUG] Invitation marked as completed\n", 3, $_SERVER['DOCUMENT_ROOT'].'/all-logs/registration-debug.log');

            // Check if there's auto-approval available for the invitee (NEW CLIENT)
            if ((int)$invitation['auto_approval_available_for_invitee'] === 1) {
                error_log("[DEBUG] Auto-approval available - approving inviting service provider for new client: ClientID={$clientId}, ProviderID={$invitation['inviter_entity_id']}\n", 3, $_SERVER['DOCUMENT_ROOT'].'/all-logs/registration-debug.log');

                // Auto-approve the inviting service provider for this new client
                $stmt = $pdo->prepare("
                    INSERT INTO participant_approvals (client_participant_id, provider_participant_id)
                    VALUES (?, ?)
                    ON DUPLICATE KEY UPDATE client_participant_id = client_participant_id
                ");
                $result = $stmt->execute([$clientId, $invitation['inviter_entity_id']]);
                $rowsAffected = $stmt->rowCount();

                error_log("[DEBUG] participant_approvals INSERT result: Success=" . ($result ? 'YES' : 'NO') . ", RowsAffected={$rowsAffected}\n", 3, $_SERVER['DOCUMENT_ROOT'].'/all-logs/registration-debug.log');

                $autoApproved = true;
                error_log("[DEBUG] Client auto-approved for service provider - flow complete\n", 3, $_SERVER['DOCUMENT_ROOT'].'/all-logs/registration-debug.log');
            } else {
                error_log("[DEBUG] No auto-approval available for this invitation\n", 3, $_SERVER['DOCUMENT_ROOT'].'/all-logs/registration-debug.log');
            }
        } else {
            error_log("[DEBUG] No valid invitation found for token\n", 3, $_SERVER['DOCUMENT_ROOT'].'/all-logs/registration-debug.log');
        }
    }

    $pdo->commit();

    // Send verification email using user ID for proper name display
    $emailSent = EmailService::sendVerificationEmail($email, $userId, $verificationToken);

    if ($emailSent) {
        echo json_encode([
            'message' => 'Client registered successfully. Please check your email to verify your account.',
            'autoApproved' => $autoApproved
        ]);
    } else {
        // Email failed, but registration succeeded - user can request resend
        echo json_encode([
            'message' => 'Client registered successfully. Email verification could not be sent. Please contact support or try logging in to resend verification email.',
            'autoApproved' => $autoApproved
        ]);
    }

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Registration failed: ' . $e->getMessage()]);
}
?>
