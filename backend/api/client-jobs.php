<?php
ini_set('log_errors', true);
ini_set('error_log', $_SERVER['DOCUMENT_ROOT'].'/all-logs/client-jobs.log');
require_once '../config/database.php';
require_once '../includes/JWT.php';
require_once '../includes/subscription.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
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

// Verify user is a client
if ($entity_type !== 'client') {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied. Client access required.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        // Get jobs for this client with optional filtering
        $status_filter = isset($_GET['status']) ? $_GET['status'] : null;
        $location_filter = isset($_GET['location_id']) ? (int)$_GET['location_id'] : null;
        $provider_filter = isset($_GET['provider_id']) ? (int)$_GET['provider_id'] : null;
        $user_filter = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;

        $query = "
            SELECT
                j.id,
                j.item_identifier,
                j.fault_description,
                j.technician_notes,
                j.job_status,
                j.client_location_id,
                j.created_at,
                j.updated_at,
                j.contact_person,
                j.reporting_user_id,
                j.archived_by_client,
                j.current_quotation_id,
                CASE
                    WHEN j.client_location_id IS NULL THEN 'Default'
                    ELSE l.name
                END as location_name,
                l.address as location_address,
                sp.name as assigned_provider_name,
                u.username as reporting_user,
                CONCAT(tu.first_name, ' ', tu.last_name) as assigned_technician,
                (SELECT COUNT(*) FROM job_images WHERE job_id = j.id) as image_count,
                CASE
                    WHEN j.current_quotation_id IS NOT NULL THEN jq.id
                    ELSE NULL
                END as quotation_id,
                CASE
                    WHEN j.current_quotation_id IS NOT NULL THEN jq.quotation_amount
                    ELSE NULL
                END as quotation_amount,
                CASE
                    WHEN j.current_quotation_id IS NOT NULL THEN jq.status
                    ELSE NULL
                END as quotation_status,
                CASE
                    WHEN j.current_quotation_id IS NOT NULL THEN jq.valid_until
                    ELSE NULL
                END as quotation_valid_until,
                CASE
                    WHEN j.current_quotation_id IS NOT NULL THEN qsp.name
                    ELSE NULL
                END as quotation_provider_name
            FROM jobs j
            LEFT JOIN locations l ON j.client_location_id = l.id
            LEFT JOIN participants sp ON j.assigned_provider_participant_id = sp.participantId
            LEFT JOIN users u ON j.reporting_user_id = u.userId
            LEFT JOIN users tu ON j.assigned_technician_user_id = tu.userId
            LEFT JOIN job_quotations jq ON j.current_quotation_id = jq.id
            LEFT JOIN participants qsp ON jq.provider_participant_id = qsp.participantId
            WHERE (l.participant_id = ? OR j.client_location_id IS NULL)
        ";

        $params = [$entity_id];

        if ($status_filter) {
            $query .= " AND j.job_status = ?";
            $params[] = $status_filter;
        }

        if ($location_filter) {
            $query .= " AND j.client_location_id = ?";
            $params[] = $location_filter;
        }

        if ($provider_filter) {
            $query .= " AND j.assigned_provider_participant_id = ?";
            $params[] = $provider_filter;
        }

        if ($user_filter) {
            $query .= " AND j.reporting_user_id = ?";
            $params[] = $user_filter;
        }

        $query .= " ORDER BY j.created_at DESC";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get job status history for each job
        foreach ($jobs as &$job) {
            $stmt = $pdo->prepare("
                SELECT
                    jsh.status,
                    jsh.changed_at,
                    u.username as changed_by
                FROM job_status_history jsh
                LEFT JOIN users u ON jsh.changed_by_user_id = u.userId
                WHERE jsh.job_id = ?
                ORDER BY jsh.changed_at DESC
            ");
            $stmt->execute([$job['id']]);
            $job['status_history'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        echo json_encode(['jobs' => $jobs]);

    } elseif ($method === 'POST') {
        // Create new job
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON input']);
            exit;
        }

        // First check if client account is enabled
        $stmt = $pdo->prepare("SELECT is_enabled, disabled_reason FROM participants WHERE participantId = ?");
        $stmt->execute([$entity_id]);
        $participant = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$participant['is_enabled']) {
            http_response_code(403);
            echo json_encode([
                'error' => 'Account is administratively disabled',
                'disabled_reason' => $participant['disabled_reason'] ?? 'Account suspended by administrator'
            ]);
            exit;
        }

        // Validate required fields
        if (!isset($input['fault_description']) || empty($input['fault_description'])) {
            http_response_code(400);
            echo json_encode(['error' => "Field 'fault_description' is required"]);
            exit;
        }

        // Check subscription limits before creating job
        if (!canPerformAction($user_id, 'jobs_created')) {
            $limits = getUsageLimits();
            $current_usage = getMonthlyUsage($user_id, 'jobs_created');

            http_response_code(429); // Rate limit exceeded
            echo json_encode([
                'error' => 'Job creation limit reached for this month',
                'current_usage' => $current_usage,
                'monthly_limit' => $limits['client_free_jobs'],
                'message' => 'Upgrade to Basic subscription for unlimited jobs or wait until next month.'
            ]);
            exit;
        }

        // Handle location validation - use NULL for default location (no specific location)
        $client_location_id = $input['client_location_id'] ?? null;

        // Convert '0' string to NULL for default location
        if ($client_location_id === '0' || $client_location_id === 0) {
            $client_location_id = null;
        }

        // If location is provided, verify it belongs to this client
        if ($client_location_id !== null) {
            $stmt = $pdo->prepare("SELECT id FROM locations WHERE id = ? AND participant_id = ?");
            $stmt->execute([$client_location_id, $entity_id]);
            if (!$stmt->fetch()) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid location ID']);
                exit;
            }
        }

        // Insert new job
        $stmt = $pdo->prepare("
            INSERT INTO jobs (
                client_location_id,
                item_identifier,
                fault_description,
                reporting_user_id,
                contact_person,
                job_status
            ) VALUES (?, ?, ?, ?, ?, 'Reported')
        ");

        $stmt->execute([
            $client_location_id,
            $input['item_identifier'] ?? null,
            $input['fault_description'],
            $user_id,
            $input['contact_person'] ?? null
        ]);

        $job_id = $pdo->lastInsertId();

        // Insert status history
        $stmt = $pdo->prepare("
            INSERT INTO job_status_history (job_id, status, changed_by_user_id)
            VALUES (?, 'Reported', ?)
        ");
        $stmt->execute([$job_id, $user_id]);

        // Track job creation usage
        $subscription = getUserSubscription($user_id);
        if ($subscription) {
            incrementSubscriptionUsage($subscription['id'], 'jobs_created');
        }

        echo json_encode([
            'success' => true,
            'message' => 'Job created successfully',
            'job_id' => $job_id
        ]);

    } elseif ($method === 'PUT') {
        // Update job (for assignment/reassignment)
        $input = json_decode(file_get_contents('php://input'), true);

        error_log(__FILE__.'/'.__LINE__.'/ >>>> '.json_encode($input));

        if (!$input || !isset($input['job_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Job ID is required']);
            exit;
        }

        $job_id = (int)$input['job_id'];

        // Verify job belongs to this client (either via location or as a client-created job)
        $stmt = $pdo->prepare("
            SELECT j.id FROM jobs j
            LEFT JOIN locations l ON j.client_location_id = l.id
            WHERE j.id = ? AND (
                l.participant_id = ? OR
                (j.client_location_id IS NULL AND j.reporting_user_id IN (
                    SELECT u.userId FROM users u WHERE u.entity_id = ?
                ))
            )
        ");
        $stmt->execute([$job_id, $entity_id, $entity_id]);
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied. Job not found or does not belong to your organization.']);
            exit;
        }

        // First check if client account is enabled
        $stmt = $pdo->prepare("SELECT is_enabled, disabled_reason FROM participants WHERE participantId = ?");
        $stmt->execute([$entity_id]);
        $participant = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$participant['is_enabled']) {
            http_response_code(403);
            echo json_encode([
                'error' => 'Account is administratively disabled',
                'disabled_reason' => $participant['disabled_reason'] ?? 'Account suspended by administrator'
            ]);
            exit;
        }

        // Check permissions based on user role and job status
        $canEdit = false;

        // Get job details to check ownership and status
        $stmt = $pdo->prepare("
            SELECT j.reporting_user_id, j.job_status FROM jobs j
            WHERE j.id = ?
        ");
        $stmt->execute([$job_id]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$job) {
            http_response_code(404);
            echo json_encode(['error' => 'Job not found']);
            exit;
        }

        // Reporting employees can edit their own jobs when status is 'Reported'
        if ($role_id === 1 && $job['reporting_user_id'] === $user_id && $job['job_status'] === 'Reported') {
            $canEdit = true;
        }

        // Budget controllers can edit when status is 'Reported', 'Declined', 'Quote Requested', or 'Completed'
        if ($role_id === 2 && in_array($job['job_status'], ['Reported', 'Declined', 'Quote Requested', 'Completed'])) {
            $canEdit = true;
        }

        if (!$canEdit) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied. You do not have permission to edit this job.']);
            exit;
        }

        $updates = [];
        $params = [];
        error_log(__FILE__.'/'.__LINE__.'/ >>>> '.json_encode($role_id));
        // Handle fields that can be edited by reporting employees when status is 'Reported'
        if ($role_id === 1 && $job['job_status'] === 'Reported') {
            if (isset($input['item_identifier'])) {
                $updates[] = "item_identifier = ?";
                $params[] = $input['item_identifier'];
            }

            if (isset($input['fault_description'])) {
                $updates[] = "fault_description = ?";
                $params[] = $input['fault_description'];
            }

            if (isset($input['contact_person'])) {
                $updates[] = "contact_person = ?";
                $params[] = $input['contact_person'];
            }

            // Reporting employees CANNOT assign providers - only budget controllers can
        }

        // Also handle basic fields that can be edited by budget controllers when status is 'Reported'
        if ($role_id === 2 && $job['job_status'] === 'Reported') {
            if (isset($input['item_identifier'])) {
                $updates[] = "item_identifier = ?";
                $params[] = $input['item_identifier'];
            }

            if (isset($input['fault_description'])) {
                $updates[] = "fault_description = ?";
                $params[] = $input['fault_description'];
            }

            if (isset($input['contact_person'])) {
                $updates[] = "contact_person = ?";
                $params[] = $input['contact_person'];
            }
        }

        // Handle fields that can be edited by budget controllers (including provider assignment and archiving)
        if ($role_id === 2) {
            if (isset($input['assigned_provider_id'])) {
                $updates[] = "assigned_provider_participant_id = ?";
                $params[] = $input['assigned_provider_id'];

                error_log(__FILE__.'/'.__LINE__.'/ >>>> '.json_encode([$entity_id, $input['assigned_provider_id']]));
                error_log(__FILE__.'/'.__LINE__.'/ >>>> '."SELECT id FROM participant_approvals WHERE client_participant_id = ? AND provider_participant_id = ?");
                // Verify provider is approved for this client
                if ($input['assigned_provider_id']) {
                    $stmt = $pdo->prepare("
                        SELECT id FROM participant_approvals
                        WHERE client_participant_id = ? AND provider_participant_id = ?
                    ");
                    $stmt->execute([$entity_id, $input['assigned_provider_id']]);
                    if (!$stmt->fetch()) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Service provider is not approved for this client']);
                        exit;
                    }
                }
            }

            // Handle archiving by client budget controllers
            if (isset($input['archived_by_client'])) {
                $updates[] = "archived_by_client = ?";
                $params[] = $input['archived_by_client'] ? 1 : 0;
            }

            // Handle quote request workflow
            if (isset($input['request_quote']) && $input['request_quote'] === true) {
                if (!$input['assigned_provider_id']) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Service provider must be selected to request a quote']);
                    exit;
                }

                // Verify provider is approved for this client
                $stmt = $pdo->prepare("
                    SELECT id FROM participant_approvals
                    WHERE client_participant_id = ? AND provider_participant_id = ?
                ");
                $stmt->execute([$entity_id, $input['assigned_provider_id']]);
                if (!$stmt->fetch()) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Service provider is not approved for this client']);
                    exit;
                }

                $updates[] = "assigned_provider_participant_id = ?";
                $params[] = $input['assigned_provider_id'];

                // Set status to Quote Requested instead of Assigned
                $status_index = array_search("job_status = ?", $updates);
                if ($status_index !== false) {
                    $params[$status_index] = 'Quote Requested';
                } else {
                    $updates[] = "job_status = ?";
                    $params[] = 'Quote Requested';
                }
            }

            // Handle quote request with deadline (new workflow)
            if (isset($input['quote_by_date']) && !empty($input['quote_by_date'])) {
                // Validate quote_by_date format and range
                $quoteDate = DateTime::createFromFormat('Y-m-d', $input['quote_by_date']);
                if (!$quoteDate) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Invalid quote_by_date format. Expected YYYY-MM-DD']);
                    exit;
                }

                $minDate = new DateTime();
                $minDate->modify('+1 day');
                $maxDate = new DateTime();
                $maxDate->modify('+90 days');

                if ($quoteDate < $minDate || $quoteDate > $maxDate) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Quote by date must be between tomorrow and 90 days from now']);
                    exit;
                }

                // Map quote_by_date to jobs.quotation_deadline field
                $updates[] = "quotation_deadline = ?";
                $params[] = $input['quote_by_date'];

                // If action is "Quote Requested", set the status
                if (isset($input['action']) && $input['action'] === 'Quote Requested') {
                    // Set status to Quote Requested if not already set
                    $status_index = array_search("job_status = ?", $updates);
                    if ($status_index === false) {
                        $updates[] = "job_status = ?";
                        $params[] = 'Quote Requested';
                    }
                }
            }
        }

        if (isset($input['job_status'])) {
            $updates[] = "job_status = ?";
            $params[] = $input['job_status'];
        }

        // Handle state change requests (even without other field updates)
        if (isset($input['request_state_change'])) {
            // Map frontend state transition keys to actual job statuses
            $stateMapping = [
                'assigned' => 'Assigned',
                'quote_requested' => 'Quote Requested',
                'rejected' => 'Rejected',
                'confirmed' => 'Confirmed',
                'incomplete' => 'Incomplete',
                'declined' => 'Declined',
                'completed' => 'Completed',
                'cannot_repair' => 'Cannot repair'
            ];

            if (isset($stateMapping[$input['request_state_change']])) {
                $updates[] = "job_status = ?";
                $params[] = $stateMapping[$input['request_state_change']];
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid state transition: ' . $input['request_state_change']]);
                exit;
            }
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
            // Also insert status history for state change requests
            $stateMapping = [
                'assigned' => 'Assigned',
                'quote_requested' => 'Quote Requested',
                'rejected' => 'Rejected',
                'confirmed' => 'Confirmed',
                'incomplete' => 'Incomplete',
                'declined' => 'Declined',
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

    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
