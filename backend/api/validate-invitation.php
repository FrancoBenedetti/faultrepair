<?php
require_once '../config/database.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$token = isset($_GET['token']) ? trim($_GET['token']) : '';

if (empty($token)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invitation token is required']);
    exit;
}

try {
    // Get invitation details with enhanced fields for existing user scenarios - NEW SCHEMA
    $stmt = $pdo->prepare("
        SELECT i.*, u.first_name, u.last_name, u.email as inviter_email,
               u.phone as inviter_phone,
               CASE
                   WHEN i.inviter_entity_type = 'client' THEN p.name
                   WHEN i.inviter_entity_type = 'service_provider' THEN p.name
               END as inviter_entity_name
        FROM invitations i
        JOIN users u ON i.inviter_user_id = u.userId
        JOIN participants p ON i.inviter_entity_id = p.participantId
        WHERE i.invitation_token = ? AND i.invitation_status != 'expired'
    ");
    $stmt->execute([$token]);
    $invitation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$invitation) {
        http_response_code(404);
        echo json_encode(['error' => 'Invalid or expired invitation']);
        exit;
    }

    // Check if invitation has expired
    if (strtotime($invitation['expires_at']) < time()) {
        // Mark as expired
        $stmt = $pdo->prepare("UPDATE invitations SET invitation_status = 'expired' WHERE id = ?");
        $stmt->execute([$invitation['id']]);

        http_response_code(410);
        echo json_encode(['error' => 'Invitation has expired']);
        exit;
    }

    // Update access tracking
    $currentTime = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare("
        UPDATE invitations
        SET invitation_status = 'accessed',
            accessed_at = COALESCE(accessed_at, ?),
            last_activity_at = ?
        WHERE id = ?
    ");
    $stmt->execute([$currentTime, $currentTime, $invitation['id']]);

    // Log access
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $stmt = $pdo->prepare("
        INSERT INTO invitation_access_log (invitation_id, ip_address, user_agent)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$invitation['id'], $ipAddress, $userAgent]);

    // Update the invitation status in our response
    $invitation['invitation_status'] = 'accessed';

    echo json_encode([
        'success' => true,
        'invitation' => [
            'id' => $invitation['id'],
            'token' => $invitation['invitation_token'],
            'invitee_name' => $invitation['invitee_first_name'] . ' ' . $invitation['invitee_last_name'],
            'invitee_email' => $invitation['invitee_email'],
            'invitee_phone' => $invitation['invitee_phone'],
            'invitation_type' => determineInvitationType($invitation),
            'invitation_status' => $invitation['invitation_status'],
            'expires_at' => $invitation['expires_at'],
            'auto_approval_applied' => (bool)$invitation['auto_approval_applied'],
            'access_message' => $invitation['access_message'],
            'inviter_details' => [
                'name' => $invitation['first_name'] . ' ' . $invitation['last_name'],
                'email' => $invitation['inviter_email'],
                'phone' => $invitation['inviter_phone'],
                'entity_name' => $invitation['inviter_entity_name'],
                'entity_type' => $invitation['inviter_entity_type']
            ],
            'registration_data' => json_decode($invitation['registration_data'], true)
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to validate invitation: ' . $e->getMessage()]);
}

// Helper function to determine invitation type based on inviter
function determineInvitationType($invitation) {
    // If inviter is a client inviting a service provider
    if ($invitation['inviter_entity_type'] === 'client') {
        return 'service_provider';
    }
    // If inviter is a service provider inviting a client
    if ($invitation['inviter_entity_type'] === 'service_provider') {
        return 'client';
    }
    return 'unknown';
}
?>
