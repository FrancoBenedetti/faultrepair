<?php
// Enable maximum error logging for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
$log_file = $_SERVER['DOCUMENT_ROOT'].'/all-logs/invitation-debug.log';
error_log("=== Create Invitation Debug Script Started " . date('Y-m-d H:i:s') . " ===\n", 3, $log_file);

// Log the start of the request
error_log("=== Create Invitation Request Started ===");
error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
error_log("REQUEST_URI: " . $_SERVER['REQUEST_URI']);
error_log("__DIR__: " . __DIR__);

// Test: Try loading files one by one to find which one fails
error_log("About to load database.php");
require_once '../config/database.php';
error_log("Database.php loaded successfully");

error_log("About to load JWT.php");
require_once '../includes/JWT.php';
error_log("JWT.php loaded successfully");

error_log("About to load email.php");
require_once '../includes/email.php';
error_log("Email.php loaded successfully");

error_log("Includes loaded successfully");

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// JWT Authentication - Try Authorization header first, fallback to query parameter
error_log("Starting JWT authentication...");
$token = null;
$headers = getallheaders();
$auth_header = isset($headers['Authorization']) ? $headers['Authorization'] : '';

error_log("Headers available: " . (is_array($headers) ? count($headers) : 'none'));
error_log("Authorization header: " . ($auth_header ? substr($auth_header, 0, 20) . '...' : 'none'));

if ($auth_header && preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
    $token = $matches[1];
    error_log("Token extracted from Bearer header");
} else {
    $token = $_GET['token'] ?? '';
    error_log("Token from query parameter: " . ($token ? 'present' : 'missing'));
}

if (!$token) {
    error_log("No token found - returning 401");
    http_response_code(401);
    echo json_encode(['error' => 'Authorization token missing']);
    exit;
}

error_log("Token length: " . strlen($token));
$payload = JWT::decode($token);

if (!$payload) {
    error_log("JWT decode failed or token expired");
    http_response_code(401);
    echo json_encode(['error' => 'Invalid or expired token']);
    exit;
}

error_log("JWT decoded successfully, user_id: " . ($payload['user_id'] ?? 'missing'));

// Get user details including role information - NEW SCHEMA
$stmt = $pdo->prepare("
    SELECT u.*, r.name as role_name, p.name as participant_name, pt.participantType as entity_type
    FROM users u
    JOIN user_roles r ON u.role_id = r.roleId
    JOIN participants p ON u.entity_id = p.participantId
    JOIN participant_type pt ON p.participantId = pt.participantId
    WHERE u.userId = ?
");
$stmt->execute([$payload['user_id']]);
$user_data = $stmt->fetch();

if (!$user_data) {
    http_response_code(401);
    echo json_encode(['error' => 'User not found']);
    exit;
}

// Check if user is admin (Client Admin: roleId = 2 OR Service Provider Admin: roleId = 3)
error_log("[DEBUG] User role check: role_id={$user_data['role_id']}\n", 3, $log_file);
    if (!in_array($user_data['role_id'], [2, 3])) {  // Allow Client Admins (2) and Service Provider Admins (3) to create invitations
        error_log("[DEBUG] User is NOT an admin - role_id={$user_data['role_id']}, denying access\n", 3, $log_file);
        http_response_code(403);
        echo json_encode(['error' => 'Only administrators can create invitations']);
        exit;
    }
    error_log("[DEBUG] User is a client or service provider admin - role_id={$user_data['role_id']}, role_name={$user_data['role_name']}, allowing invitation creation\n", 3, $log_file);

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['inviteeFirstName']) || !isset($data['inviteeLastName']) ||
    !isset($data['communicationMethod']) || !isset($data['invitationType'])) {
    http_response_code(400);
    echo json_encode(['error' => 'All fields are required: inviteeFirstName, inviteeLastName, communicationMethod, invitationType']);
    exit;
}

$inviteeFirstName = trim($data['inviteeFirstName']);
$inviteeLastName = trim($data['inviteeLastName']);
$inviteeEmail = isset($data['inviteeEmail']) ? trim($data['inviteeEmail']) : null;
$inviteePhone = isset($data['inviteePhone']) ? trim($data['inviteePhone']) : null;
$communicationMethod = trim($data['communicationMethod']);
$invitationType = trim($data['invitationType']);
$notes = isset($data['notes']) ? trim($data['notes']) : null;

// Validate communication method
if (!in_array($communicationMethod, ['whatsapp', 'telegram', 'sms', 'email'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid communication method']);
    exit;
}

// Validate invitation type
if (!in_array($invitationType, ['client', 'service_provider'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid invitation type']);
    exit;
}

// Validate that we have appropriate contact info for the communication method
if ($communicationMethod === 'email' && empty($inviteeEmail)) {
    http_response_code(400);
    echo json_encode(['error' => 'Email is required for email invitations']);
    exit;
}

if (in_array($communicationMethod, ['whatsapp', 'telegram', 'sms']) && empty($inviteePhone)) {
    http_response_code(400);
    echo json_encode(['error' => 'Phone number is required for WhatsApp, Telegram, and SMS invitations']);
    exit;
}

try {
    $pdo->beginTransaction();
    error_log("[DEBUG] Started transaction\nInviter: UserID={$user_data['userId']}, Role={$user_data['role_name']}, EntityType={$user_data['entity_type']}, EntityID={$user_data['entity_id']}\nInvitee: FirstName={$inviteeFirstName}, LastName={$inviteeLastName}, Email={$inviteeEmail}, InvitationType={$invitationType}\n", 3, $log_file);

    // Check if invitee already exists as a user
    $existingUser = null;
    if ($inviteeEmail) {
        $stmt = $pdo->prepare("
            SELECT u.userId, u.first_name, u.last_name, u.entity_id, r.name as role_name
            FROM users u
            JOIN user_roles r ON u.role_id = r.roleId
            WHERE u.email = ?
        ");
        $stmt->execute([$inviteeEmail]);
        $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
        error_log("[DEBUG] Existing user lookup: Email={$inviteeEmail}, Found=" . ($existingUser ? "YES" : "NO") . ($existingUser ? ", UserID={$existingUser['userId']}, EntityID={$existingUser['entity_id']}, Role={$existingUser['role_name']}" : "") . "\n", 3, $log_file);
    } else {
        error_log("[DEBUG] No email provided - skipping existing user lookup\n", 3, $log_file);
    }

    // Get default expiry days from settings
    $stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'invitation_expiry_days'");
    $stmt->execute();
    $expirySetting = $stmt->fetch();
    $expiryDays = $expirySetting ? (int)$expirySetting['setting_value'] : 7;

    // Apply business rules based on invitation type and existing user status
    $inviteeUserId = null;
    $inviteeEntityType = null;
    $inviteeEntityId = null;
    $invitationStatus = 'pending';
    $accessMessage = null;
    $autoApprovalApplied = false;
    $autoApprovalAvailableForInvitee = false;

    if ($existingUser) {
        $inviteeUserId = $existingUser['userId'];
        $inviteeEntityId = $existingUser['entity_id'];

        // Business Rule Application
        if ($invitationType === 'client' && $user_data['entity_type'] === 'service_provider') {
            // Service Provider inviting existing client
            if ($existingUser['role_name'] === 'Site Budget Controller') {
                // Rule 1: Ask for approval to add service provider
                $accessMessage = "A Service Provider Administrator has invited you to allow access to {$user_data['first_name']} {$user_data['last_name']}. Please review and approve or decline this request.";
                $invitationStatus = 'pending_approval';
            } else {
                // Rule 1: Inform user lacks authorization - needs budget controller
                $accessMessage = "You have been invited by {$user_data['first_name']} {$user_data['last_name']}. However, you don't have authorization to approve service provider access. Please contact your organization's budget controller to complete this invitation.";
                $invitationStatus = 'requires_authorization';
            }
        } elseif ($invitationType === 'service_provider' && $user_data['entity_type'] === 'client') {
            error_log("[DEBUG] Client inviting existing service provider: InviterRoleID={$user_data['role_id']}, InviterRoleName={$user_data['role_name']}\n", 3, $log_file);
            // Client inviting existing service provider
            // Rule 2 & Rule 4: Auto-approve if inviting user is client admin (roleId = 2 or service provider admin = 3)
            if ($user_data['role_id'] === 2) {
                error_log("[DEBUG] Auto-approval logic triggered for client admin: Inserting into participant_approvals with ClientID={$user_data['entity_id']}, ProviderID={$existingUser['entity_id']}\n", 3, $log_file);
                $stmt = $pdo->prepare("
                    INSERT INTO participant_approvals (client_participant_id, provider_participant_id)
                    VALUES (?, ?)
                    ON DUPLICATE KEY UPDATE client_participant_id = client_participant_id
                ");
                $result = $stmt->execute([$user_data['entity_id'], $existingUser['entity_id']]);
                $rowsAffected = $stmt->rowCount();
                error_log("[DEBUG] participant_approvals INSERT result: Success=" . ($result ? 'YES' : 'NO') . ", RowsAffected={$rowsAffected}\n", 3, $log_file);
                $autoApprovalApplied = true;
                $invitationStatus = 'completed';
                $accessMessage = "You have been added as an approved service provider for " . getClientName($pdo, $user_data['entity_id']) . ". No further action is required.";
                error_log("[DEBUG] Auto-approval completed: Status='completed', AutoApprovalApplied=1\n", 3, $log_file);
            } else {
                error_log("[DEBUG] Auto-approval NOT applied: Inviter roleId={$user_data['role_id']} is not client admin (roleId=2)\n", 3, $log_file);
                // Rule 2: Inform that client needs to use dashboard to approve
                $accessMessage = "No registration action was needed. However, " . getClientName($pdo, $user_data['entity_id']) . " should use their dashboard to formally approve you as a service provider.";
                $invitationStatus = 'informational';
            }
        }
    }

    // Enhanced business logic for new (not existing) users
    if (!$existingUser) {
        if ($invitationType === 'service_provider' && $user_data['entity_type'] === 'client') {
            // Client inviting a new prospective service provider
            // This is handled either during invitation creation (if roleId = 2) or during registration
            // No special flag needed for new service providers - logic is in registration
            error_log("[DEBUG] Client inviting new service provider: No special auto-approval needed - handled in registration logic\n", 3, $log_file);
            $autoApprovalAvailableForInvitee = false; // Explicit false for clarity
        } elseif ($invitationType === 'client' && $user_data['entity_type'] === 'S') {
            // Service Provider inviting a new prospective client
            // Rule 3: New client should auto-approve the inviting service provider during registration
            error_log("[DEBUG] Service provider inviting new client: Setting auto_approval_available_for_invitee = TRUE for automatic approval during client registration\n", 3, $log_file);
            error_log("[DEBUG] Inviter entity_type: {$user_data['entity_type']}, Invitation type: {$invitationType}\n", 3, $log_file);
            $autoApprovalAvailableForInvitee = true;
            $accessMessage = null; // No message needed for new user invitations - handled at registration time
            error_log("[DEBUG] autoApprovalAvailableForInvitee set to TRUE\n", 3, $log_file);
        } else {
            // Default case for new users - no auto-approval
            $autoApprovalAvailableForInvitee = false;
            error_log("[DEBUG] Default case: autoApprovalAvailableForInvitee set to FALSE for inviter_type={$user_data['entity_type']}, invitation_type={$invitationType}\n", 3, $log_file);
        }
        error_log("[DEBUG] Final autoApprovalAvailableForInvitee value: " . ($autoApprovalAvailableForInvitee ? 'TRUE' : 'FALSE') . "\n", 3, $log_file);
    }

    // Generate unique invitation token
    $invitationToken = bin2hex(random_bytes(32));

    // Calculate expiry time
    $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expiryDays} days"));

    // Prepare registration data based on invitation type
    $registrationData = [
        'inviter_details' => [
            'name' => $user_data['first_name'] . ' ' . $user_data['last_name'],
            'email' => $user_data['email'],
            'phone' => $user_data['phone'],
            'entity_name' => $user_data['entity_type'] === 'client' ?
                getClientName($pdo, $user_data['entity_id']) :
                getServiceProviderName($pdo, $user_data['entity_id']),
            'entity_type' => $user_data['entity_type'],
            'entity_id' => $user_data['entity_id']
        ]
    ];

    // Ensure proper data types for database insert
    $autoApprovalApplied = $autoApprovalApplied ? 1 : 0;  // Convert boolean to int
    $expiryDays = (int)$expiryDays;  // Ensure integer type
    $inviteeUserId = $inviteeUserId ? (int)$inviteeUserId : null;  // Ensure integer or null

    // Insert invitation with enhanced fields
    $stmt = $pdo->prepare("
        INSERT INTO invitations (
            invitation_token, inviter_user_id, inviter_entity_type, inviter_entity_id,
            invitee_first_name, invitee_last_name, invitee_email, invitee_phone,
            invitee_user_id, invitee_entity_type, invitee_entity_id, auto_approval_applied, access_message,
            communication_method, invitation_status, initial_expiry_days, expires_at,
            registration_data, notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $invitationToken,
        $user_data['userId'],
        $user_data['entity_type'],      // inviter_entity_type ✅ CORRECT!
        $user_data['entity_id'],        // inviter_entity_id   ✅ CORRECT!
        $inviteeFirstName,
        $inviteeLastName,
        $inviteeEmail,
        $inviteePhone,
        $inviteeUserId,
        $inviteeEntityType,
        $inviteeEntityId,
        $autoApprovalApplied,
        $accessMessage,
        $communicationMethod,
        $invitationStatus,
        $expiryDays,
        $expiresAt,
        json_encode($registrationData),
        $notes
    ]);

    $invitationId = $pdo->lastInsertId();

    // Update invitation with the auto_approval_available_for_invitee flag if applicable (MUST happen AFTER insert!)
    if ($autoApprovalAvailableForInvitee) {
        $stmt = $pdo->prepare("UPDATE invitations SET auto_approval_available_for_invitee = 1 WHERE id = ?");
        $stmt->execute([$invitationId]);
        error_log("[DEBUG] Updated invitation {$invitationId} with auto_approval_available_for_invitee = 1\n", 3, $log_file);
    }

    $pdo->commit();

    // Generate invitation URL - now points to invitation landing page first
    $baseUrl = (DOMAIN === 'localhost' ? 'http' : 'https') . '://' . DOMAIN;
    $invitationUrl = $baseUrl . "/invitation?token=" . urlencode($invitationToken);

    echo json_encode([
        'success' => true,
        'message' => 'Invitation created successfully',
        'invitation' => [
            'id' => $invitationId,
            'token' => $invitationToken,
            'invitee_name' => $inviteeFirstName . ' ' . $inviteeLastName,
            'communication_method' => $communicationMethod,
            'invitation_type' => $invitationType,
            'expires_at' => $expiresAt,
            'invitation_url' => $invitationUrl,
            'whatsapp_url' => generateWhatsAppUrl($inviteePhone, $invitationUrl),
            'telegram_url' => generateTelegramUrl($inviteePhone, $invitationUrl),
            'sms_url' => generateSMSUrl($inviteePhone, $invitationUrl),
            'email_url' => generateEmailUrl($inviteeEmail, $invitationUrl, $inviteeFirstName),
            'registration_data' => $registrationData
        ]
    ]);

} catch (Exception $e) {
    error_log('Exception caught: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
        error_log('Transaction rolled back');
    }
    http_response_code(500);
    echo json_encode(['error' => 'Failed to create invitation: ' . $e->getMessage()]);
}

error_log("=== Create Invitation Request Completed ===");

// Helper functions - UPDATED FOR NEW SCHEMA
function getClientName($pdo, $clientId) {
    // In new schema, clientId refers to participants.participantId
    $stmt = $pdo->prepare("
        SELECT p.name FROM participants p
        JOIN participant_type pt ON p.participantId = pt.participantId
        WHERE p.participantId = ? AND pt.participantType = 'C'
    ");
    $stmt->execute([$clientId]);
    $client = $stmt->fetch();
    return $client ? $client['name'] : 'Unknown Client';
}

function getServiceProviderName($pdo, $providerId) {
    // In new schema, providerId refers to participants.participantId
    $stmt = $pdo->prepare("
        SELECT p.name FROM participants p
        JOIN participant_type pt ON p.participantId = pt.participantId
        WHERE p.participantId = ? AND pt.participantType = 'S'
    ");
    $stmt->execute([$providerId]);
    $provider = $stmt->fetch();
    return $provider ? $provider['name'] : 'Unknown Provider';
}

function generateWhatsAppUrl($phone, $invitationUrl) {
    if (!$phone) return null;

    // Remove any non-digit characters from phone
    $cleanPhone = preg_replace('/\D/', '', $phone);

    // Add country code if not present (assuming South Africa +27)
    if (strlen($cleanPhone) === 10) {
        $cleanPhone = '27' . substr($cleanPhone, 1);
    } elseif (strlen($cleanPhone) === 9) {
        $cleanPhone = '27' . $cleanPhone;
    }

    $message = "Hi! You've been invited to join our Snappy service request platform. Click here to register: " . $invitationUrl;
    $encodedMessage = urlencode($message);

    return "https://wa.me/{$cleanPhone}?text={$encodedMessage}";
}

function generateTelegramUrl($phone, $invitationUrl) {
    $message = "Hi! You've been invited to join our Snappy service request platform. Click here to register: " . $invitationUrl;
    $encodedMessage = urlencode($message);

    return "https://t.me/share/url?url=" . urlencode($invitationUrl) . "&text={$encodedMessage}";
}

function generateSMSUrl($phone, $invitationUrl) {
    if (!$phone) return null;

    // Remove any non-digit characters from phone
    $cleanPhone = preg_replace('/\D/', '', $phone);

    // Add country code if not present (assuming South Africa +27)
    if (strlen($cleanPhone) === 10) {
        $cleanPhone = '27' . substr($cleanPhone, 1);
    } elseif (strlen($cleanPhone) === 9) {
        $cleanPhone = '27' . $cleanPhone;
    }

    $message = "Hi! You've been invited to join our Snappy service request platform. Click here to register: {$invitationUrl}";
    $encodedMessage = urlencode($message);

    return "sms:{$cleanPhone}?body={$encodedMessage}";
}

function generateEmailUrl($email, $invitationUrl, $inviteeName = '') {
    if (!$email) return null;

    $greeting = $inviteeName ? "Hi {$inviteeName}," : "Hello,";
    $subject = "You're invited to join Snappy!";
    $body = "{$greeting}%0A%0AYou've been invited to join our Snappy service request platform. Click the link below to register:%0A%0A{$invitationUrl}%0A%0ARegards,%0AThe Snappy Team";

    return "mailto:{$email}?subject=" . urlencode($subject) . "&body={$body}";
}
?>
