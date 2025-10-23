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

// Only clients can confirm job completion
if ($entity_type !== 'client' || !in_array($role_id, [1, 2])) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied. Client access required.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get request body
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['job_id']) || !isset($input['action'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Job ID and action are required']);
    exit;
}

$job_id = (int)$input['job_id'];
$action = $input['action']; // 'confirm' or 'reject'
$confirmation_notes = isset($input['notes']) ? trim($input['notes']) : '';

if (!in_array($action, ['confirm', 'reject'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Action must be either "confirm" or "reject"']);
    exit;
}

try {
    // Verify the job exists and belongs to this client
    $stmt = $pdo->prepare("
        SELECT
            j.id,
            j.job_status,
            j.assigned_provider_participant_id,
            sp.name as provider_name
        FROM jobs j
        LEFT JOIN locations l ON j.client_location_id = l.id
        JOIN participants sp ON j.assigned_provider_participant_id = sp.participantId
        WHERE j.id = ? AND (l.participant_id = ? OR j.client_location_id IS NULL)
    ");
    $stmt->execute([$job_id, $entity_id]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$job) {
        http_response_code(404);
        echo json_encode(['error' => 'Job not found or does not belong to your organization']);
        exit;
    }

    // Check if job is in correct status for confirmation
    if ($job['job_status'] !== 'Completed') {
        http_response_code(400);
        echo json_encode(['error' => 'Job must be in "Completed" status to be confirmed']);
        exit;
    }

    $new_job_status = $action === 'confirm' ? 'Confirmed' : 'Incomplete';

    // Update job status
    $stmt = $pdo->prepare("
        UPDATE jobs SET job_status = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$new_job_status, $job_id]);

        // Insert job status history with notes
        $stmt = $pdo->prepare("
            INSERT INTO job_status_history (job_id, status, changed_by_user_id, notes, changed_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$job_id, $new_job_status, $user_id, $confirmation_notes]);

        echo json_encode([
            'success' => true,
            'message' => 'Job ' . $action . 'ed successfully',
            'new_job_status' => $new_job_status
        ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to process job confirmation: ' . $e->getMessage()]);
}
?>
