<?php
require_once '../config/database.php';
require_once '../includes/JWT.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT, OPTIONS');
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

try {
    $payload = JWT::decode($token);
    $user_id = $payload['user_id'];
    $role_id = $payload['role_id'];
    $entity_type = $payload['entity_type'];
    $entity_id = $payload['entity_id'];
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid token']);
    exit;
}

// Only clients can respond to quotes
if ($entity_type !== 'client' || $role_id !== 2) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied. Client admin access required.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get request body
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['quote_id']) || !isset($input['response'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Quote ID and response are required']);
    exit;
}

$quote_id = (int)$input['quote_id'];
$response = $input['response']; // 'accepted' or 'rejected'
$response_notes = isset($input['response_notes']) ? trim($input['response_notes']) : '';

if (!in_array($response, ['accepted', 'rejected'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Response must be either "accepted" or "rejected"']);
    exit;
}

try {
    // Verify the quote exists and belongs to a job for this client
    $stmt = $pdo->prepare("
        SELECT
            jq.id,
            jq.status,
            jq.job_id,
            j.job_status,
            j.assigned_provider_participant_id,
            sp.name as provider_name
        FROM job_quotations jq
        JOIN jobs j ON jq.job_id = j.id
        JOIN locations l ON j.client_location_id = l.id
        JOIN participants sp ON j.assigned_provider_participant_id = sp.participantId
        WHERE jq.id = ? AND l.participant_id = ?
    ");
    $stmt->execute([$quote_id, $entity_id]);
    $quote = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$quote) {
        http_response_code(404);
        echo json_encode(['error' => 'Quote not found or does not belong to your organization']);
        exit;
    }

    // Check if quote is in correct status for response
    if ($quote['status'] !== 'submitted') {
        http_response_code(400);
        echo json_encode(['error' => 'Quote has already been responded to']);
        exit;
    }

    $new_quote_status = $response === 'accepted' ? 'accepted' : 'rejected';
    $new_job_status = $response === 'accepted' ? 'Assigned' : 'Rejected';

    // Update quote status and response
    $stmt = $pdo->prepare("
        UPDATE job_quotations
        SET status = ?, responded_at = NOW(), response_notes = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$new_quote_status, $response_notes, $quote_id]);

    // Update job status
    $stmt = $pdo->prepare("
        UPDATE jobs SET job_status = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$new_job_status, $quote['job_id']]);

    // Clear current quotation reference if rejected
    if ($response === 'rejected') {
        $stmt = $pdo->prepare("
            UPDATE jobs SET current_quotation_id = NULL, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$quote['job_id']]);
    }

    // Insert quotation history
    $stmt = $pdo->prepare("
        INSERT INTO job_quotation_history (quotation_id, action, changed_by_user_id, notes, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$quote_id, $new_quote_status, $user_id, $response_notes]);

    // Insert job status history
    $stmt = $pdo->prepare("
        INSERT INTO job_status_history (job_id, status, changed_by_user_id, changed_at)
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$quote['job_id'], $new_job_status, $user_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Quote ' . $response . ' successfully',
        'new_job_status' => $new_job_status
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to process quote response: ' . $e->getMessage()]);
}
?>
