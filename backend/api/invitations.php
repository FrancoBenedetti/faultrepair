<?php
require_once '../config/database.php';
require_once '../includes/JWT.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
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
    JOIN user_roles r ON u.role_id = r.roleId
    WHERE u.userId = ?
");
$stmt->execute([$payload['user_id']]);
$user_data = $stmt->fetch();

if (!$user_data) {
    http_response_code(401);
    echo json_encode(['error' => 'User not found']);
    exit;
}

// Check if user is admin (Site Budget Controller or Service Provider Admin)
if (!in_array($user_data['role_id'], [2, 3])) {  // 2 = Site Budget Controller (Client Admin), 3 = Service Provider Admin
    http_response_code(403);
    echo json_encode(['error' => 'Only administrators can manage invitations']);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get invitations for current user
        $stmt = $pdo->prepare("
            SELECT i.*, u.first_name, u.last_name, u.email as inviter_email
            FROM invitations i
            JOIN users u ON i.inviter_user_id = u.userId
            WHERE i.inviter_user_id = ?
            ORDER BY i.created_at DESC
        ");
        $stmt->execute([$user_data['id']]);
        $invitations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Update status for expired invitations
        foreach ($invitations as &$invitation) {
            if ($invitation['invitation_status'] === 'accessed' &&
                $invitation['last_activity_at'] &&
                strtotime($invitation['last_activity_at']) < strtotime('-1 hour')) {
                $invitation['invitation_status'] = 'expired';
            } elseif ($invitation['invitation_status'] !== 'completed' &&
                     $invitation['expires_at'] < date('Y-m-d H:i:s')) {
                $invitation['invitation_status'] = 'expired';
            }
        }

        echo json_encode([
            'success' => true,
            'invitations' => $invitations
        ]);

    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data || !isset($data['action']) || !isset($data['invitation_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Action and invitation_id are required']);
            exit;
        }

        $action = $data['action'];
        $invitationId = (int)$data['invitation_id'];

        // Verify the invitation belongs to current user
        $stmt = $pdo->prepare("
            SELECT * FROM invitations
            WHERE id = ? AND inviter_user_id = ?
        ");
        $stmt->execute([$invitationId, $user_data['id']]);
        $invitation = $stmt->fetch();

        if (!$invitation) {
            http_response_code(404);
            echo json_encode(['error' => 'Invitation not found']);
            exit;
        }

        if ($action === 'send') {
            // Update invitation status to sent and set sent_at timestamp
            $stmt = $pdo->prepare("
                UPDATE invitations
                SET invitation_status = 'sent', sent_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$invitationId]);

            // Generate URLs for sharing
            $baseUrl = (DOMAIN === 'localhost' ? 'http' : 'https') . '://' . DOMAIN;
            $invitationUrl = $baseUrl . "/register-{$invitation['communication_method']}?token=" . urlencode($invitation['invitation_token']);

            $response = [
                'success' => true,
                'message' => 'Invitation marked as sent',
                'invitation_url' => $invitationUrl,
                'communication_method' => $invitation['communication_method']
            ];

            // Add method-specific URLs for all methods
            $response['whatsapp_url'] = generateWhatsAppUrl($invitation['invitee_phone'] ?? null, $invitationUrl);
            $response['telegram_url'] = generateTelegramUrl($invitation['invitee_phone'] ?? null, $invitationUrl);
            $response['sms_url'] = generateSMSUrl($invitation['invitee_phone'] ?? null, $invitationUrl);
            $response['email_url'] = generateEmailUrl($invitation['invitee_email'] ?? null, $invitationUrl, $invitation['invitee_first_name'] ?? '');

            echo json_encode($response);

        } elseif ($action === 'cancel') {
            // Cancel the invitation
            $stmt = $pdo->prepare("
                UPDATE invitations
                SET invitation_status = 'cancelled'
                WHERE id = ?
            ");
            $stmt->execute([$invitationId]);

            echo json_encode([
                'success' => true,
                'message' => 'Invitation cancelled successfully'
            ]);

        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action. Supported actions: send, cancel']);
            exit;
        }
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to manage invitations: ' . $e->getMessage()]);
}

// Helper functions
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
