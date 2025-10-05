<?php
require_once '../config/database.php';
require_once '../includes/JWT.php';
require_once '../includes/email.php';

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

// JWT Authentication - Read from query parameter for live server compatibility
$token = $_GET['token'] ?? '';

if (!$token) {
    http_response_code(401);
    echo json_encode(['error' => 'Authorization token missing']);
    exit;
}

$payload = JWT::decode($token);

if (!$payload) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid or expired token']);
    exit;
}

// Get user details including role information
$stmt = $pdo->prepare("
    SELECT u.*, r.name as role_name
    FROM users u
    JOIN roles r ON u.role_id = r.id
    WHERE u.id = ?
");
$stmt->execute([$payload['user_id']]);
$user_data = $stmt->fetch();

if (!$user_data) {
    http_response_code(401);
    echo json_encode(['error' => 'User not found']);
    exit;
}

// Check if user is admin (Site Budget Controller or Service Provider Admin)
if (!in_array($user_data['role_name'], ['Site Budget Controller', 'Service Provider Admin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Only administrators can create invitations']);
    exit;
}

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

    // Get default expiry days from settings
    $stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'invitation_expiry_days'");
    $stmt->execute();
    $expirySetting = $stmt->fetch();
    $expiryDays = $expirySetting ? (int)$expirySetting['setting_value'] : 7;

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

    // Insert invitation
    $stmt = $pdo->prepare("
        INSERT INTO invitations (
            invitation_token, inviter_user_id, inviter_entity_type, inviter_entity_id,
            invitee_first_name, invitee_last_name, invitee_email, invitee_phone,
            communication_method, invitation_status, initial_expiry_days, expires_at,
            registration_data, notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?)
    ");

    $stmt->execute([
        $invitationToken,
        $user_data['id'],
        $user_data['entity_type'],
        $user_data['entity_id'],
        $inviteeFirstName,
        $inviteeLastName,
        $inviteeEmail,
        $inviteePhone,
        $communicationMethod,
        $expiryDays,
        $expiresAt,
        json_encode($registrationData),
        $notes
    ]);

    $invitationId = $pdo->lastInsertId();

    $pdo->commit();

    // Generate invitation URL
    $baseUrl = (DOMAIN === 'localhost' ? 'http' : 'https') . '://' . DOMAIN;
    $invitationUrl = $baseUrl . "/register-{$invitationType}?token=" . urlencode($invitationToken);

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
            'registration_data' => $registrationData
        ]
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => 'Failed to create invitation: ' . $e->getMessage()]);
}

// Helper functions
function getClientName($pdo, $clientId) {
    $stmt = $pdo->prepare("SELECT name FROM clients WHERE id = ?");
    $stmt->execute([$clientId]);
    $client = $stmt->fetch();
    return $client ? $client['name'] : 'Unknown Client';
}

function getServiceProviderName($pdo, $providerId) {
    $stmt = $pdo->prepare("SELECT name FROM service_providers WHERE id = ?");
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
?>
