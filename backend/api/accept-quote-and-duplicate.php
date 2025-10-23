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

// Only clients can accept quotes
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
if (!$input || !isset($input['quote_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Quote ID is required']);
    exit;
}

$quote_id = (int)$input['quote_id'];

try {
    $pdo->beginTransaction();

    // Verify the quote exists and belongs to this client
    $stmt = $pdo->prepare("
        SELECT
            jq.id,
            jq.status,
            jq.job_id,
            jq.quotation_description,
            j.client_location_id,
            j.item_identifier,
            j.fault_description,
            j.contact_person,
            j.reporting_user_id,
            j.quotation_required,
            j.quotation_deadline,
            j.assigned_provider_participant_id,
            j.assigned_technician_user_id
        FROM job_quotations jq
        JOIN jobs j ON jq.job_id = j.id
        LEFT JOIN locations l ON j.client_location_id = l.id
        WHERE jq.id = ? AND j.reporting_user_id IN (
            SELECT u.userId FROM users u WHERE u.entity_id = ?
        )
    ");
    $stmt->execute([$quote_id, $entity_id]);
    $quote = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$quote) {
        http_response_code(404);
        echo json_encode(['error' => 'Quote not found or does not belong to your organization']);
        exit;
    }

    // Check if quote is in correct status for acceptance
    if ($quote['status'] !== 'submitted') {
        http_response_code(400);
        echo json_encode(['error' => 'Quote has already been responded to']);
        exit;
    }

    // Get the original job description and create modified description
    $original_description = $quote['fault_description'];
    $quote_number = "Q" . $quote_id;
    $modified_description = $original_description . " (Quote " . $quote_number . " Accepted)";

    // Create the new job by duplicating the original
    $stmt = $pdo->prepare("
        INSERT INTO jobs (
            client_location_id,
            item_identifier,
            fault_description,
            reporting_user_id,
            contact_person,
            job_status,
            quotation_required,
            quotation_deadline,
            current_quotation_id,
            assigned_provider_participant_id,
            assigned_technician_user_id,
            created_at,
            updated_at
        ) SELECT
            client_location_id,
            item_identifier,
            '$modified_description',
            reporting_user_id,
            contact_person,
            'Assigned',
            0,
            NULL,
            ?,
            assigned_provider_participant_id,
            assigned_technician_user_id,
            NOW(),
            NOW()
        FROM jobs WHERE id = ?
    ");
    $stmt->execute([$quote_id, $quote['job_id']]);
    $new_job_id = $pdo->lastInsertId();

    // Copy job_images records to the new job
    $stmt = $pdo->prepare("
        INSERT INTO job_images (job_id, filename, original_filename, file_path, file_size, mime_type, uploaded_by, display_order, uploaded_at)
        SELECT ?, filename, original_filename, file_path, file_size, mime_type, uploaded_by, display_order, NOW()
        FROM job_images WHERE job_id = ?
        ORDER BY display_order
    ");
    $stmt->execute([$new_job_id, $quote['job_id']]);

    // Copy job status history to the new job
    $stmt = $pdo->prepare("
        INSERT INTO job_status_history (job_id, status, changed_by_user_id, changed_at)
        SELECT ?, status, changed_by_user_id, NOW()
        FROM job_status_history WHERE job_id = ?
        ORDER BY changed_at
    ");
    $stmt->execute([$new_job_id, $quote['job_id']]);

    // Add initial status history entry for the new job
    $stmt = $pdo->prepare("
        INSERT INTO job_status_history (job_id, status, changed_by_user_id, changed_at)
        VALUES (?, 'Assigned', ?, NOW())
    ");
    $stmt->execute([$new_job_id, $user_id]);

    // Update the quote to accepted status
    $stmt = $pdo->prepare("
        UPDATE job_quotations
        SET status = 'accepted', responded_at = NOW(), response_notes = 'Accepted - created job #{$new_job_id}',
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$quote_id]);

    // Insert quotation history
    $stmt = $pdo->prepare("
        INSERT INTO job_quotation_history (quotation_id, action, changed_by_user_id, notes, created_at)
        VALUES (?, 'accepted', ?, ?, NOW())
    ");
    $stmt->execute([$quote_id, $user_id, "Accepted quote and created new job #$new_job_id"]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Quote accepted successfully. New job created.',
        'new_job_id' => $new_job_id,
        'quote_id' => $quote_id
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Failed to accept quote: ' . $e->getMessage()]);
}
?>
