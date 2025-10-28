<?php
ini_set('log_errors', true);
ini_set('error_log', $_SERVER['DOCUMENT_ROOT'].'/all-logs/service-provider-jobs.log');
require_once '../config/database.php';
require_once '../includes/JWT.php';
require_once '../includes/performance-monitoring.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

$method = $_SERVER['REQUEST_METHOD'];

if (!in_array($method, ['GET', 'PUT'])) {
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

// Verify user is a service provider
if ($entity_type !== 'service_provider') {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied. Service provider access required.']);
    exit;
}

// Build query based on parameters instead of role
// If technician_id parameter is passed, filter by that technician, else show all for provider
error_log("service-provider-jobs.php - Processing request, user_id: $user_id, entity_id: $entity_id");

try {
    // Initialize performance monitoring
    PerformanceMonitor::init($pdo);

    if ($method === 'GET') {
        // Start performance timing
        PerformanceMonitor::startTimer('api_service_provider_jobs_get');
        // Base condition - always filter by provider participant
        $where_conditions = ["j.assigned_provider_participant_id = ?"];
        $params = [$entity_id];

        // If technician_id is passed, filter by specific technician (technician view)
        if (isset($_GET['technician_id']) && !empty($_GET['technician_id'])) {
            $where_conditions[] = "j.assigned_technician_user_id = ?";
            $params[] = $_GET['technician_id'];
            error_log("service-provider-jobs.php - Technician filter applied for technician_id: " . $_GET['technician_id']);
        }

        // Additional filters
        if (isset($_GET['status']) && !empty($_GET['status'])) {
            $where_conditions[] = "j.job_status = ?";
            $params[] = $_GET['status'];
        }

        if (isset($_GET['client_id']) && !empty($_GET['client_id'])) {
            $where_conditions[] = "p.participantId = ?";
            $params[] = $_GET['client_id'];
        }

        // Archive status filter for service providers
        if (isset($_GET['archive_status'])) {
            if ($_GET['archive_status'] === 'active') {
                $where_conditions[] = "j.archived_by_service_provider = 0 OR j.archived_by_service_provider IS NULL";
            } elseif ($_GET['archive_status'] === 'archived') {
                $where_conditions[] = "j.archived_by_service_provider = 1";
            }
        } else {
            // Default to active jobs only (matching client behavior)
            $where_conditions[] = "j.archived_by_service_provider = 0 OR j.archived_by_service_provider IS NULL";
        }

    // Filter for quote-related jobs if requested
    if (isset($_GET['quote_jobs']) && $_GET['quote_jobs'] === 'true') {
        $where_conditions[] = "j.job_status IN ('Quote Requested', 'Quote Provided')";
        $params[] = $_GET['quote_jobs'];
    }

    // Special case: return technicians instead of jobs
    if (isset($_GET['get_technicians']) && $_GET['get_technicians'] === '1') {
        error_log("service-provider-jobs.php - Returning technicians for entity_id: $entity_id");

        $stmt = $pdo->prepare("
            SELECT
                u.userId,
                u.username,
                u.email,
                u.first_name,
                u.last_name,
                u.phone,
                u.role_id
            FROM users u
            WHERE u.entity_type = 'service_provider'
              AND u.entity_id = ?
              AND u.role_id IN (3, 4)
            ORDER BY u.role_id ASC, u.created_at DESC
        ");
        $stmt->execute([$entity_id]);
        $technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['technicians' => $technicians]);
        return; // Exit here, don't return jobs
    }

    $where_clause = implode(" AND ", $where_conditions);
    error_log("service-provider-jobs.php - WHERE clause: $where_clause, params: " . json_encode($params));

        // Get filtered jobs assigned to this service provider
        // CRITICAL: Exclude XS (external) providers from SP dashboard access
        $stmt = $pdo->prepare("
            SELECT
                j.id,
                j.client_id,
                j.item_identifier,
                j.fault_description,
                j.technician_notes,
                j.job_status,
                j.client_location_id,
                j.quotation_deadline,
                j.quotation_deadline as due_date,
                j.archived_by_service_provider,
                j.created_at,
                j.updated_at,
                j.contact_person,
                j.assigned_technician_user_id,
                CASE
                    WHEN j.client_location_id IS NULL THEN 'Default'
                    ELSE l.name
                END as location_name,
                l.address as location_address,
                l.coordinates as location_coordinates,
                l.access_rules as location_access_rules,
                l.access_instructions as location_access_instructions,
                p.name as client_name,
                p.participantId as client_participant_id,
                u.username as reporting_user,
                CONCAT(tu.first_name, ' ', tu.last_name) as assigned_technician,
                tu.userId as assigned_technician_user_id,
                (SELECT COUNT(*) FROM job_images ji WHERE ji.job_id = j.id) as image_count
            FROM jobs j
            LEFT JOIN participants p ON j.client_id = p.participantId
            LEFT JOIN locations l ON j.client_location_id = l.id
            LEFT JOIN users u ON j.reporting_user_id = u.userId
            LEFT JOIN users tu ON j.assigned_technician_user_id = tu.userId
            LEFT JOIN participant_type pt ON j.assigned_provider_participant_id = pt.participantId
            WHERE {$where_clause}
            AND (pt.participantType IS NOT NULL AND pt.participantType != 'XS')
            ORDER BY j.created_at DESC
        ");

        $stmt->execute($params);
        $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        error_log("service-provider-jobs.php - Found " . count($jobs) . " jobs");
        error_log("service-provider-jobs.php - First few jobs raw data:");
        for ($i = 0; $i < min(3, count($jobs)); $i++) {
            error_log("service-provider-jobs.php - Job " . ($i + 1) . ": " . json_encode([
                'id' => $jobs[$i]['id'] ?? 'missing',
                'assigned_technician_user_id' => $jobs[$i]['assigned_technician_user_id'] ?? 'missing',
                'assigned_technician' => $jobs[$i]['assigned_technician'] ?? 'missing'
            ]));
        }

        echo json_encode([
            'jobs' => $jobs
        ]);

        // End performance timing for GET
        PerformanceMonitor::endTimer('api_service_provider_jobs_get');

    } elseif ($method === 'PUT') {
        // Start performance timing for PUT
        PerformanceMonitor::startTimer('api_service_provider_jobs_put');
    // Update job (for service provider technicians and supervisors)
    $input = json_decode(file_get_contents('php://input'), true);

    error_log("service-provider-jobs.php - PUT request input: " . json_encode($input));

    if (!$input || !isset($input['job_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Job ID is required']);
        exit;
    }

    $job_id = (int)$input['job_id'];

    // Verify job belongs to this service provider AND is not an XS (external) provider job
    $stmt = $pdo->prepare("
        SELECT j.id FROM jobs j
        LEFT JOIN participant_type pt ON j.assigned_provider_participant_id = pt.participantId
        WHERE j.id = ?
        AND j.assigned_provider_participant_id = ?
        AND (pt.participantType != 'XS' OR pt.participantType IS NULL)
    ");
    $stmt->execute([$job_id, $entity_id]);
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied. Job not found, does not belong to your service provider, or is not accessible.']);
        exit;
    }

    // Get job details to check ownership and status
    $stmt = $pdo->prepare("
        SELECT j.assigned_technician_user_id, j.job_status FROM jobs j
        WHERE j.id = ?
    ");
    $stmt->execute([$job_id]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$job) {
        http_response_code(404);
        echo json_encode(['error' => 'Job not found']);
        exit;
    }

    $canEdit = false;
    $canArchive = false;

    // Service Provider Role 3 (Supervisor) can archive ANY job regardless of status
    if ($role_id === 3) {
        $canArchive = true;
        // Can also edit for core job management
        if (in_array($job['job_status'], ['Assigned', 'In Progress', 'Incomplete', 'Quote Requested', 'Quote Provided'])) {
            $canEdit = true;
        }
    }

    // Service Provider Role 4 (Technician) can edit:
    // - Notes for jobs assigned to them when in progress
    // - Status changes (completed, cannot repair) for jobs assigned to them
    if ($role_id === 4 && $job['assigned_technician_user_id'] === $user_id) {
        if (in_array($job['job_status'], ['In Progress'])) {
            $canEdit = true;
        }
    }

    // Check if this is an archive operation - requires archive permission, not edit permission
    $isArchiveOperation = isset($input['archived_by_service_provider']);

    if ($isArchiveOperation && !$canArchive) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied. You do not have permission to archive this job.']);
        exit;
    }

    // For non-archive operations, check general edit permission
    if (!$isArchiveOperation && !$canEdit) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied. You do not have permission to edit this job.']);
        exit;
    }

    $updates = [];
    $params = [];

    // Handle archiving by service provider admins (Role 3 only)
    if ($role_id === 3 && isset($input['archived_by_service_provider'])) {
        // Convert boolean/false values properly
        $archiveValue = $input['archived_by_service_provider'];
        if ($archiveValue === false || $archiveValue === 0 || $archiveValue === '0' || $archiveValue === 'false') {
            $archiveValue = 0; // Unarchive
        } elseif ($archiveValue === true || $archiveValue === 1 || $archiveValue === '1' || $archiveValue === 'true') {
            $archiveValue = 1; // Archive
        } else {
            $archiveValue = 0; // Default to unarchive for safety
        }

        $updates[] = "archived_by_service_provider = ?";
        $params[] = $archiveValue;

        error_log("service-provider-jobs.php - Archive operation: setting archived_by_service_provider = $archiveValue for job_id $job_id");
    }

    // Handle technician assignment (Role 3 only)
    if ($role_id === 3 && isset($input['assigned_technician_user_id'])) {
        $technician_id = $input['assigned_technician_user_id'];

        // Convert empty string to NULL for database
        if ($technician_id === '') {
            $technician_id = null;
        }

        // Verify technician belongs to this service provider (allow both supervisors and technicians)
        if ($technician_id) {
            $stmt = $pdo->prepare("
                SELECT u.userId FROM users u
                WHERE u.userId = ? AND u.entity_id = ? AND u.role_id IN (3, 4)
            ");
            $stmt->execute([$technician_id, $entity_id]);
            if (!$stmt->fetch()) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid technician assignment - technician does not belong to your organization']);
                exit;
            }
        }

        $updates[] = "assigned_technician_user_id = ?";
        $params[] = $technician_id;
    }

    // Handle technician notes (both roles can update notes, but different permissions)
    if (isset($input['technician_notes'])) {
        $updates[] = "technician_notes = ?";
        $params[] = $input['technician_notes'];
    }

    // Handle state change requests
    if (isset($input['request_state_change'])) {
        $allowedTransitions = [];

        if ($role_id === 3) {
            // Supervisor transitions
            $supervisorTransitions = [
                'Assigned' => ['in_progress'],
                'Quote Requested' => ['quote_provided', 'unable_to_quote'],
                'Incomplete' => ['in_progress', 'completed'],
            ];
            $allowedTransitions = $supervisorTransitions[$job['job_status']] ?? [];
        } elseif ($role_id === 4) {
            // Technician transitions
            $technicianTransitions = [
                'In Progress' => ['completed', 'cannot_repair'],
            ];
            $allowedTransitions = $technicianTransitions[$job['job_status']] ?? [];
        }

        if (!in_array($input['request_state_change'], $allowedTransitions)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid state transition for your role: ' . $input['request_state_change']]);
            exit;
        }

        // Map frontend state transition keys to actual job statuses
        $stateMapping = [
            'in_progress' => 'In Progress',
            'quote_provided' => 'Quote Provided',
            'unable_to_quote' => 'Unable to quote',
            'completed' => 'Completed',
            'cannot_repair' => 'Cannot repair'
        ];

        if (isset($stateMapping[$input['request_state_change']])) {
            $updates[] = "job_status = ?";
            $params[] = $stateMapping[$input['request_state_change']];
        }
    }

    if (isset($input['job_status'])) {
        // For direct status updates (mainly for quotes)
        $updates[] = "job_status = ?";
        $params[] = $input['job_status'];
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

    // Insert status history if status changed
    if (isset($input['job_status'])) {
        $stmt = $pdo->prepare("
            INSERT INTO job_status_history (job_id, status, changed_by_user_id)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$job_id, $input['job_status'], $user_id]);
    } elseif (isset($input['request_state_change'])) {
        $stateMapping = [
            'in_progress' => 'In Progress',
            'quote_provided' => 'Quote Provided',
            'unable_to_quote' => 'Unable to quote',
            'completed' => 'Completed',
            'cannot_repair' => 'Cannot repair'
        ];
        if (isset($stateMapping[$input['request_state_change']])) {
            $stmt = $pdo->prepare("
                INSERT INTO job_status_history (job_id, status, changed_by_user_id)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$job_id, $stateMapping[$input['request_state_change']], $user_id]);
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Job updated successfully'
    ]);

    // End performance timing for PUT
    PerformanceMonitor::endTimer('api_service_provider_jobs_put');

    // Send notifications for status changes
    if (isset($input['job_status']) || isset($input['request_state_change'])) {
        require_once '../includes/job-notifications.php';

        $newStatus = $input['job_status'] ?? $stateMapping[$input['request_state_change']] ?? null;
        if ($newStatus) {
            JobNotifications::notifyJobStatusChange($job_id, $job['job_status'], $newStatus, $user_id);
        }
    }

}

} catch (Exception $e) {
    error_log("service-provider-jobs.php - Admin query failed: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to retrieve jobs: ' . $e->getMessage()]);
}
?>
