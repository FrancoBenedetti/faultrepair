<?php
require_once '../config/database.php';
require_once '../includes/JWT.php';

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

// Get user details
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

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['invitationToken']) || !isset($data['action'])) {
    http_response_code(400);
    echo json_encode(['error' => 'invitationToken and action are required']);
    exit;
}

$invitationToken = trim($data['invitationToken']);
$action = trim($data['action']); // 'approve', 'reject'

if (!in_array($action, ['approve', 'reject'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action. Must be approve or reject']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Get invitation details
    $stmt = $pdo->prepare("
        SELECT i.*, u.role_id as inviter_role_id
        FROM invitations i
        JOIN users u ON i.inviter_user_id = u.id
        WHERE i.invitation_token = ?
    ");
    $stmt->execute([$invitationToken]);
    $invitation = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$invitation) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['error' => 'Invitation not found']);
        exit;
    }

    // Validate that this user is the intended recipient
    if ($invitation['invitee_user_id'] !== $user_data['id']) {
        $pdo->rollBack();
        http_response_code(403);
        echo json_encode(['error' => 'You are not authorized to respond to this invitation']);
        exit;
    }

    // Check if invitation is in a state that allows response
    if (!in_array($invitation['invitation_status'], ['pending_approval', 'accessed'])) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(['error' => 'This invitation does not require approval']);
        exit;
    }

    // Process approval/rejection for service provider invitations to existing clients
    if ($invitation['inviter_entity_type'] === 'service_provider' && $invitation['invitee_entity_type'] === 'client') {
        // Check if user has authorization (budget controller)
        if ($user_data['role_name'] !== 'Site Budget Controller') {
            $pdo->rollBack();
            http_response_code(403);
            echo json_encode(['error' => 'You do not have authorization to approve or reject service provider access']);
            exit;
        }

        if ($action === 'approve') {
            // Add service provider to approved list
            $stmt = $pdo->prepare("
                INSERT INTO client_approved_providers (client_id, service_provider_id)
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE client_id = client_id
            ");
            $stmt->execute([$user_data['entity_id'], $invitation['inviter_entity_id']]);

            // Update invitation status
            $stmt = $pdo->prepare("
                UPDATE invitations
                SET invitation_status = 'completed',
                    completed_at = NOW(),
                    auto_approval_applied = TRUE,
                    access_message = ?
                WHERE id = ?
            ");
            $stmt->execute([
                "You have successfully approved {$invitation['inviter_entity_name']} as a service provider.",
                $invitation['id']
            ]);

            $message = "Service provider approved successfully";
        } else {
            // Reject the invitation
            $stmt = $pdo->prepare("
                UPDATE invitations
                SET invitation_status = 'cancelled',
                    access_message = ?
                WHERE id = ?
            ");
            $stmt->execute([
                "You have declined the invitation from {$invitation['inviter_entity_name']}.",
                $invitation['id']
            ]);

            $message = "Invitation declined";
        }
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => $message,
        'action' => $action
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => 'Failed to process invitation response: ' . $e->getMessage()]);
}
?>
