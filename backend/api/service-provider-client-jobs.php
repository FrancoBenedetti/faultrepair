<?php
require_once '../config/database.php';
require_once '../includes/JWT.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
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
    $entity_type = $payload['entity_type'];
    $entity_id = $payload['entity_id'];
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid token']);
    exit;
}

// Verify user is a service provider
if ($entity_type !== 'service_provider') {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied. Service provider access required.']);
    exit;
}

// Get client_id from query parameters
$client_id = isset($_GET['client_id']) ? (int)$_GET['client_id'] : null;

if (!$client_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Client ID is required']);
    exit;
}

// Verify that this client has approved this service provider
$stmt = $pdo->prepare("
    SELECT id FROM participant_approvals
    WHERE client_participant_id = ? AND provider_participant_id = ?
");
$stmt->execute([$client_id, $entity_id]);
$approval = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$approval) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied. Client has not approved this service provider.']);
    exit;
}

try {
    if ($method === 'GET') {
        // Get jobs for this client and service provider
        $stmt = $pdo->prepare("
            SELECT
                j.id,
                j.item_identifier,
                j.fault_description,
                j.technician_notes,
                j.job_status,
                j.created_at,
                j.updated_at,
                j.archived_by_service_provider,
                l.name as location_name,
                l.address as location_address,
                p.name as client_name,
                u.username as reporting_user,
                tu.username as assigned_technician
            FROM jobs j
            JOIN locations l ON j.client_location_id = l.id
            JOIN participants p ON l.participant_id = p.participantId
            LEFT JOIN users u ON j.reporting_user_id = u.id
            LEFT JOIN users tu ON j.assigned_technician_user_id = tu.id
            WHERE j.assigned_provider_participant_id = ? AND l.participant_id = ?
            ORDER BY j.created_at DESC
        ");

        $stmt->execute([$entity_id, $client_id]);
        $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get job status history for each job
        foreach ($jobs as &$job) {
            $stmt = $pdo->prepare("
                SELECT
                    jsh.status,
                    jsh.changed_at,
                    u.username as changed_by
                FROM job_status_history jsh
                LEFT JOIN users u ON jsh.changed_by_user_id = u.id
                WHERE jsh.job_id = ?
                ORDER BY jsh.changed_at DESC
            ");
            $stmt->execute([$job['id']]);
            $job['status_history'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Get client information
        $stmt = $pdo->prepare("
            SELECT participantId as id, name, address
            FROM participants
            WHERE participantId = ?
        ");
        $stmt->execute([$client_id]);
        $client = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'client' => $client,
            'jobs' => $jobs
        ]);

    } elseif ($method === 'PUT') {
        // Update job (for archiving by service provider admin)
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input || !isset($input['job_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Job ID is required']);
            exit;
        }

        $job_id = (int)$input['job_id'];

        // Verify job belongs to this service provider and client
        $stmt = $pdo->prepare("
            SELECT j.id FROM jobs j
            JOIN locations l ON j.client_location_id = l.id
            WHERE j.id = ? AND j.assigned_provider_participant_id = ? AND l.participant_id = ?
        ");
        $stmt->execute([$job_id, $entity_id, $client_id]);
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied. Job not found or does not belong to this service provider.']);
            exit;
        }

        $updates = [];
        $params = [];

        // Handle archiving by service provider admin
        if (isset($input['archived_by_service_provider'])) {
            $updates[] = "archived_by_service_provider = ?";
            $params[] = $input['archived_by_service_provider'] ? 1 : 0;
        }

        if (empty($updates)) {
            http_response_code(400);
            echo json_encode(['error' => 'No valid fields to update']);
            exit;
        }

        $params[] = $job_id;

        $stmt = $pdo->prepare("
            UPDATE jobs SET " . implode(', ', $updates) . ", updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute($params);

        echo json_encode([
            'success' => true,
            'message' => 'Job updated successfully'
        ]);

    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
