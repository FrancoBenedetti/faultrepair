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

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// JWT Authentication
$headers = getallheaders();
$auth_header = isset($headers['Authorization']) ? $headers['Authorization'] : '';

if (!$auth_header || !preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
    http_response_code(401);
    echo json_encode(['error' => 'Authorization header missing or invalid']);
    exit;
}

$token = $matches[1];
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

// Verify user is a technician or service provider admin
if ($entity_type !== 'service_provider' || !in_array($role_id, [3, 4])) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied. Technician or admin access required.']);
    exit;
}

// Get request body
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['job_id']) || !isset($input['status'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Job ID and status are required']);
    exit;
}

$job_id = (int)$input['job_id'];
$new_status = $input['status'];
$notes = isset($input['notes']) ? trim($input['notes']) : '';

try {
    // Verify the job exists and belongs to the user's service provider
    $stmt = $pdo->prepare("
        SELECT j.id, j.assigned_technician_id, j.job_status, t.service_provider_id
        FROM jobs j
        JOIN technicians t ON j.assigned_technician_id = t.user_id
        WHERE j.id = ?
    ");
    $stmt->execute([$job_id]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$job) {
        http_response_code(404);
        echo json_encode(['error' => 'Job not found']);
        exit;
    }

    // For technicians, verify they are assigned to this job
    if ($role_id === 4 && $job['assigned_technician_id'] !== $user_id) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied. You are not assigned to this job.']);
        exit;
    }

    // For admins, verify the job belongs to their service provider
    if ($role_id === 3 && $job['service_provider_id'] !== $entity_id) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied. Job does not belong to your service provider.']);
        exit;
    }

    // Update job status
    $stmt = $pdo->prepare("
        UPDATE jobs
        SET job_status = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$new_status, $job_id]);

    // Insert status history
    $stmt = $pdo->prepare("
        INSERT INTO job_status_history (job_id, status, changed_by_user_id, notes, changed_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$job_id, $new_status, $user_id, $notes]);

    echo json_encode([
        'success' => true,
        'message' => 'Job status updated successfully'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update job status: ' . $e->getMessage()]);
}
?>
