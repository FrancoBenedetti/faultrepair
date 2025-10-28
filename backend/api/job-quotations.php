<?php
require_once '../config/database.php';
require_once '../includes/JWT.php';

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

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        // Get quotes for jobs - different behavior based on user role
        $job_id = isset($_GET['job_id']) ? (int)$_GET['job_id'] : null;

        if ($entity_type === 'service_provider') {
            // Service providers can see quotes they've submitted
            if ($role_id === 3) { // Dispatcher/Admin
                $query = "
                    SELECT
                        jq.id,
                        jq.job_id,
                        jq.quotation_amount,
                        jq.quotation_description,
                        jq.quotation_document_url,
                        jq.valid_until,
                        jq.status,
                        jq.submitted_at,
                        jq.responded_at,
                        jq.response_notes,
                        jq.created_at,
                        j.item_identifier,
                        j.fault_description,
                        j.job_status,
                        COALESCE(l.name, 'Default Location (Client Premises)') as location_name,
                        'Client Company' as client_name
                    FROM job_quotations jq
                    JOIN jobs j ON jq.job_id = j.id
                    LEFT JOIN locations l ON j.client_location_id = l.id
                    WHERE jq.provider_participant_id = ?
                ";
                $params = [$entity_id];

                if ($job_id) {
                    $query .= " AND jq.job_id = ?";
                    $params[] = $job_id;
                }

                $query .= " ORDER BY jq.created_at DESC";

                $stmt = $pdo->prepare($query);
                $stmt->execute($params);
                $quotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo json_encode(['quotes' => $quotes]);
            } else {
                http_response_code(403);
                echo json_encode(['error' => 'Access denied. Admin access required.']);
            }
        } elseif ($entity_type === 'client') {
            // Clients can see quotes for their jobs using direct client_id
            $query = "
                SELECT
                    jq.id,
                    jq.job_id,
                    jq.quotation_amount,
                    jq.quotation_description,
                    jq.quotation_document_url,
                    jq.valid_until,
                    jq.status,
                    jq.submitted_at,
                    jq.created_at,
                    j.item_identifier,
                    j.fault_description,
                    j.job_status,
                    sp.name as provider_name
                FROM job_quotations jq
                JOIN jobs j ON jq.job_id = j.id
                JOIN participants sp ON jq.provider_participant_id = sp.participantId
                WHERE j.client_id = ?
            ";
            $params = [$entity_id];

            if ($job_id) {
                $query .= " AND jq.job_id = ?";
                $params[] = $job_id;
            }

            $query .= " ORDER BY jq.created_at DESC";

            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $quotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['quotes' => $quotes]);
        } else {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied. Invalid entity type.']);
        }

    } elseif ($method === 'POST') {
        // Create new quote - only service provider admins can do this
        if ($entity_type !== 'service_provider' || $role_id !== 3) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied. Service provider admin access required.']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input || !isset($input['job_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Job ID is required']);
            exit;
        }

        $job_id = (int)$input['job_id'];

        // Verify the job is assigned to this service provider and in correct status
        $stmt = $pdo->prepare("
            SELECT j.id, j.job_status, j.assigned_provider_participant_id
            FROM jobs j
            WHERE j.id = ? AND j.assigned_provider_participant_id = ?
            AND j.job_status IN ('Quote Requested', 'Assigned')
        ");
        $stmt->execute([$job_id, $entity_id]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$job) {
            http_response_code(404);
            echo json_encode(['error' => 'Job not found or not in quotable status']);
            exit;
        }

        // Check if quote already exists
        $stmt = $pdo->prepare("
            SELECT id FROM job_quotations
            WHERE job_id = ? AND provider_participant_id = ?
        ");
        $stmt->execute([$job_id, $entity_id]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['error' => 'Quote already exists for this job']);
            exit;
        }

        // Validate required fields
        if (!isset($input['quotation_amount']) || !isset($input['quotation_description'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Quotation amount and description are required']);
            exit;
        }

        // Insert new quote
        $stmt = $pdo->prepare("
            INSERT INTO job_quotations (
                job_id,
                provider_participant_id,
                quotation_amount,
                quotation_description,
                quotation_document_url,
                valid_until,
                status,
                submitted_at,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, 'submitted', NOW(), NOW())
        ");

        $stmt->execute([
            $job_id,
            $entity_id,
            $input['quotation_amount'],
            $input['quotation_description'],
            $input['quotation_document_url'] ?? null,
            $input['valid_until'] ?? null
        ]);

        $quote_id = $pdo->lastInsertId();

        // Update job status to 'Quote Provided' if it was 'Quote Requested'
        if ($job['job_status'] === 'Quote Requested') {
            $stmt = $pdo->prepare("
                UPDATE jobs SET job_status = 'Quote Provided', updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$job_id]);

            // Update current quotation reference
            $stmt = $pdo->prepare("
                UPDATE jobs SET current_quotation_id = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$quote_id, $job_id]);
        }

        // Insert quotation history
        $stmt = $pdo->prepare("
            INSERT INTO job_quotation_history (quotation_id, action, changed_by_user_id, created_at)
            VALUES (?, 'submitted', ?, NOW())
        ");
        $stmt->execute([$quote_id, $user_id]);

        // Insert job status history
        $stmt = $pdo->prepare("
            INSERT INTO job_status_history (job_id, status, changed_by_user_id, changed_at)
            VALUES (?, 'Quote Provided', ?, NOW())
        ");
        $stmt->execute([$job_id, $user_id]);

        echo json_encode([
            'success' => true,
            'message' => 'Quote submitted successfully',
            'quote_id' => $quote_id
        ]);

    } elseif ($method === 'PUT') {
        $input = json_decode(file_get_contents('php://input'), true);

        // Handle client quote responses (reject, request new quote)
        if ($entity_type === 'client' && in_array($role_id, [1, 2])) {
            if (!$input || !isset($input['quote_id']) || !isset($input['action'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Quote ID and action are required']);
                exit;
            }

            $quote_id = (int)$input['quote_id'];
            $action = $input['action'];
            $notes = $input['notes'] ?? '';

            // Validate action
            if (!in_array($action, ['reject', 'request', 'accept'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid action. Must be "reject", "request", or "accept".']);
                exit;
            }

            // Verify the quote belongs to this client's organization using direct client_id
            $stmt = $pdo->prepare("
                SELECT jq.id, jq.status, jq.job_id
                FROM job_quotations jq
                JOIN jobs j ON jq.job_id = j.id
                WHERE jq.id = ?
                AND j.client_id = ?
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

            // Get current job status to validate state transitions
            $stmt = $pdo->prepare("SELECT job_status FROM jobs WHERE id = ?");
            $stmt->execute([$quote['job_id']]);
            $current_job = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$current_job || $current_job['job_status'] !== 'Quote Provided') {
                http_response_code(400);
                echo json_encode(['error' => 'Quote operations can only be performed when job is in "Quote Provided" status']);
                exit;
            }

            $pdo->beginTransaction();

            try {
                if ($action === 'accept') {
                    // Handle quote acceptance - set quote to accepted and job to Assigned
                    $stmt = $pdo->prepare("
                        UPDATE job_quotations
                        SET status = 'accepted', responded_at = NOW(), response_notes = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$notes, $quote_id]);

                    // Insert quotation history
                    $stmt = $pdo->prepare("
                        INSERT INTO job_quotation_history (quotation_id, action, changed_by_user_id, notes, created_at)
                        VALUES (?, 'accepted', ?, ?, NOW())
                    ");
                    $stmt->execute([$quote_id, $user_id, $notes]);

                    // Update job status to 'Assigned'
                    $stmt = $pdo->prepare("
                        UPDATE jobs SET job_status = 'Assigned', quotation_deadline = NULL, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$quote['job_id']]);

                    // Update current quotation reference to ensure it points to accepted quote
                    $stmt = $pdo->prepare("
                        UPDATE jobs SET current_quotation_id = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$quote_id, $quote['job_id']]);

                    // Insert job status history
                    $stmt = $pdo->prepare("
                        INSERT INTO job_status_history (job_id, status, changed_by_user_id, changed_at, notes)
                        VALUES (?, 'Assigned', ?, NOW(), ?)
                    ");
                    $stmt->execute([$quote['job_id'], $user_id, "Quote accepted: " . $notes]);

                } else {
                    // Handle reject/request actions
                    $status = $action === 'reject' ? 'rejected' : 'expired';
                    $history_action = $action === 'reject' ? 'rejected' : 'expired';

                    // Update quote status
                    $stmt = $pdo->prepare("
                        UPDATE job_quotations
                        SET status = ?, responded_at = NOW(), response_notes = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$status, $notes, $quote_id]);

                    // Insert quotation history
                    $stmt = $pdo->prepare("
                        INSERT INTO job_quotation_history (quotation_id, action, changed_by_user_id, notes, created_at)
                        VALUES (?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([$quote_id, $history_action, $user_id, $notes]);

                    if ($action === 'reject') {
                        // For quote rejection, change job status to 'Rejected' (terminal state)
                        $stmt = $pdo->prepare("
                            UPDATE jobs SET job_status = 'Rejected', quotation_deadline = NULL, updated_at = NOW()
                            WHERE id = ?
                        ");
                        $stmt->execute([$quote['job_id']]);

                        // Insert job status history
                        $stmt = $pdo->prepare("
                            INSERT INTO job_status_history (job_id, status, changed_by_user_id, changed_at, notes)
                            VALUES (?, 'Rejected', ?, NOW(), ?)
                        ");
                        $stmt->execute([$quote['job_id'], $user_id, "Quote rejected: " . $notes]);
                    } elseif ($action === 'request') {
                        // For requote request, change job status back to 'Quote Requested'
                        $stmt = $pdo->prepare("
                            UPDATE jobs SET job_status = 'Quote Requested', updated_at = NOW()
                            WHERE id = ?
                        ");
                        $stmt->execute([$quote['job_id']]);

                        // Insert job status history
                        $stmt = $pdo->prepare("
                            INSERT INTO job_status_history (job_id, status, changed_by_user_id, changed_at, notes)
                            VALUES (?, 'Quote Requested', ?, NOW(), ?)
                        ");
                        $stmt->execute([$quote['job_id'], $user_id, "Re-quote requested: " . $notes]);
                    }
                }

                $pdo->commit();

                $message = $action === 'accept' ? 'Quote accepted and job assigned' :
                          ($action === 'reject' ? 'Quote rejected successfully' : 'Quote expired successfully');

                echo json_encode([
                    'success' => true,
                    'message' => $message
                ]);

            } catch (Exception $e) {
                $pdo->rollBack();
                http_response_code(500);
                echo json_encode(['error' => 'Failed to process quote response: ' . $e->getMessage()]);
            }

            exit;
        }

        // Update existing quote - only service provider admins can update their quotes
        if ($entity_type !== 'service_provider' || $role_id !== 3) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied. Service provider admin access required.']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input || !isset($input['quote_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Quote ID is required']);
            exit;
        }

        $quote_id = (int)$input['quote_id'];

        // Verify the quote belongs to this service provider
        $stmt = $pdo->prepare("
            SELECT jq.id, jq.status FROM job_quotations jq
            WHERE jq.id = ? AND jq.provider_participant_id = ?
        ");
        $stmt->execute([$quote_id, $entity_id]);
        $quote = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$quote) {
            http_response_code(404);
            echo json_encode(['error' => 'Quote not found']);
            exit;
        }

        // Only allow updates for draft quotes
        if ($quote['status'] !== 'draft') {
            http_response_code(400);
            echo json_encode(['error' => 'Only draft quotes can be updated']);
            exit;
        }

        $updates = [];
        $params = [];

        if (isset($input['quotation_amount'])) {
            $updates[] = "quotation_amount = ?";
            $params[] = $input['quotation_amount'];
        }

        if (isset($input['quotation_description'])) {
            $updates[] = "quotation_description = ?";
            $params[] = $input['quotation_description'];
        }

        if (isset($input['quotation_document_url'])) {
            $updates[] = "quotation_document_url = ?";
            $params[] = $input['quotation_document_url'];
        }

        if (isset($input['valid_until'])) {
            $updates[] = "valid_until = ?";
            $params[] = $input['valid_until'];
        }

        if (empty($updates)) {
            http_response_code(400);
            echo json_encode(['error' => 'No valid fields to update']);
            exit;
        }

        $params[] = $quote_id;

        $stmt = $pdo->prepare("
            UPDATE job_quotations SET " . implode(', ', $updates) . ", updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute($params);

        // Insert quotation history
        $stmt = $pdo->prepare("
            INSERT INTO job_quotation_history (quotation_id, action, changed_by_user_id, notes, created_at)
            VALUES (?, 'updated', ?, ?, NOW())
        ");
        $stmt->execute([$quote_id, $user_id, $input['notes'] ?? 'Quote updated']);

        echo json_encode([
            'success' => true,
            'message' => 'Quote updated successfully'
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
