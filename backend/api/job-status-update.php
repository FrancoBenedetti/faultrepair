<?php
require_once '../config/database.php';
require_once '../includes/JWT.php';
require_once '../includes/subscription.php';
require_once '../includes/job-status-validation.php';

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
$technician_notes = isset($input['technician_notes']) ? trim($input['technician_notes']) : '';
$technician_id = isset($input['technician_id']) ? (int)$input['technician_id'] : null;

    // Validate technician assignment for "In Progress" status
    if ($new_status === 'In Progress' && !$technician_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Technician assignment is required when setting job status to "In Progress"']);
        exit;
    }

    // Validate "Cannot repair" status requires a reason
    if ($new_status === 'Cannot repair' && empty($technician_notes)) {
        http_response_code(400);
        echo json_encode(['error' => 'A reason must be provided when marking a job as "Cannot repair"']);
        exit;
    }

    // Validate "Incomplete" status requires notes when service provider is making changes
    if ($new_status === 'Incomplete' && $entity_type === 'service_provider' && empty($technician_notes)) {
        http_response_code(400);
        echo json_encode(['error' => 'Notes are required when service provider updates an incomplete job']);
        exit;
    }

try {
    // Verify the job exists and belongs to the user's service provider
    $stmt = $pdo->prepare("
        SELECT j.id, j.assigned_technician_user_id, j.job_status, j.assigned_provider_participant_id as service_provider_id
        FROM jobs j
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
    if ($role_id === 4 && $job['assigned_technician_user_id'] !== $user_id) {
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

    // Validate status transition using business rules
    $validator = new JobStatusValidator($pdo);
    $validationData = [
        'notes' => $technician_notes,
        'technician_id' => $technician_id
    ];

    $validation = $validator->validateTransition(
        $job_id,
        $job['job_status'],
        $new_status,
        $role_id,
        $entity_type,
        $validationData
    );

    if (!$validation['valid']) {
        http_response_code(400);
        echo json_encode(['error' => $validation['error']]);
        exit;
    }

    // Check subscription limits for job acceptance
    if ($new_status === 'In Progress' && $job['job_status'] !== 'In Progress') {
        // This is a job acceptance - check if service provider can accept more jobs
        if (!canPerformAction($user_id, 'jobs_accepted')) {
            $limits = getUsageLimits();
            $current_usage = getMonthlyUsage($user_id, 'jobs_accepted');

            http_response_code(429); // Rate limit exceeded
            echo json_encode([
                'error' => 'Job acceptance limit reached for this month',
                'current_usage' => $current_usage,
                'monthly_limit' => $limits['sp_free_jobs'],
                'message' => 'Upgrade to Basic subscription for unlimited job acceptances or wait until next month.'
            ]);
            exit;
        }

        // Track job acceptance usage
        incrementUsage($user_id, 'jobs_accepted');
    }
    // Update job status, technician assignment, and technician notes if provided
    if ($technician_id) {
        // Verify the technician belongs to the same service provider
        $stmt = $pdo->prepare("
            SELECT u.userId as id
            FROM users u
            LEFT JOIN participant_type pt ON u.entity_id = pt.participantId
            WHERE u.userId = ? AND u.role_id = 4 AND pt.participantType = 'S' AND u.entity_id = ?
        ");
        $stmt->execute([$technician_id, $entity_id]);
        if (!$stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid technician assignment']);
            exit;
        }

        $stmt = $pdo->prepare("
            UPDATE jobs
            SET job_status = ?, assigned_technician_user_id = ?, technician_notes = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$new_status, $technician_id, $technician_notes, $job_id]);
    } else {
        $stmt = $pdo->prepare("
            UPDATE jobs
            SET job_status = ?, technician_notes = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$new_status, $technician_notes, $job_id]);
    }

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
