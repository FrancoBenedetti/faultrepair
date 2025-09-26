<?php
ini_set('log_errors', true);
ini_set('error_log', $_SERVER['DOCUMENT_ROOT'].'/all-logs/client-jobs.log');
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
                j.created_at,
                j.updated_at,
                j.contact_person,
                j.reporting_user_id,
                l.name as location_name,
                l.address as location_address,
                sp.name as assigned_provider_name,
                u.username as reporting_user,
                tu.username as assigned_technician,
                (SELECT COUNT(*) FROM job_images WHERE job_id = j.id) as image_count
            FROM jobs j
            JOIN locations l ON j.client_location_id = l.id
            LEFT JOIN service_providers sp ON j.assigned_provider_id = sp.id
            LEFT JOIN users u ON j.reporting_user_id = u.id
            LEFT JOIN users tu ON j.assigned_technician_id = tu.id
            WHERE l.client_id = ?
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
            $query .= " AND j.assigned_provider_id = ?";
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
                LEFT JOIN users u ON jsh.changed_by_user_id = u.id
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

        // Check if client has any locations defined
        $stmt = $pdo->prepare("SELECT COUNT(*) as location_count FROM locations WHERE client_id = ?");
        $stmt->execute([$entity_id]);
        $location_result = $stmt->fetch();
        $has_locations = $location_result['location_count'] > 0;

        // Validate required fields
        if (!isset($input['fault_description']) || empty($input['fault_description'])) {
            http_response_code(400);
            echo json_encode(['error' => "Field 'fault_description' is required"]);
            exit;
        }

        $client_location_id = null;

        // Handle location validation
        if ($has_locations) {
            // Locations exist - client_location_id is required
            if (!isset($input['client_location_id']) || empty($input['client_location_id'])) {
                http_response_code(400);
                echo json_encode(['error' => "Field 'client_location_id' is required"]);
                exit;
            }

            $client_location_id = $input['client_location_id'];

            // Verify location belongs to this client
            $stmt = $pdo->prepare("SELECT id FROM locations WHERE id = ? AND client_id = ?");
            $stmt->execute([$client_location_id, $entity_id]);
            if (!$stmt->fetch()) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid location ID']);
                exit;
            }
        } else {
            // No locations defined - create a default location entry
            // Insert default location for this client
            $stmt = $pdo->prepare("
                INSERT INTO locations (client_id, name, address)
                VALUES (?, 'Default Location', 'Client premises')
            ");
            $stmt->execute([$entity_id]);
            $client_location_id = $pdo->lastInsertId();
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

        // Verify job belongs to this client
        $stmt = $pdo->prepare("
            SELECT j.id FROM jobs j
            JOIN locations l ON j.client_location_id = l.id
            WHERE j.id = ? AND l.client_id = ?
        ");
        $stmt->execute([$job_id, $entity_id]);
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied. Job not found or does not belong to your organization.']);
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

        // Budget controllers can edit when status is 'Reported', 'Declined', or 'Quote Requested'
        if ($role_id === 2 && in_array($job['job_status'], ['Reported', 'Declined', 'Quote Requested'])) {
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

        // Handle fields that can be edited by budget controllers (including provider assignment)
        if ($role_id === 2) {
            if (isset($input['assigned_provider_id'])) {
                $updates[] = "assigned_provider_id = ?";
                $params[] = $input['assigned_provider_id'];

                error_log(__FILE__.'/'.__LINE__.'/ >>>> '.json_encode([$entity_id, $input['assigned_provider_id']]));
                error_log(__FILE__.'/'.__LINE__.'/ >>>> '."SELECT id FROM client_approved_providers WHERE client_id = ? AND service_provider_id = ?");
                // Verify provider is approved for this client
                if ($input['assigned_provider_id']) {
                    $stmt = $pdo->prepare("
                        SELECT id FROM client_approved_providers
                        WHERE client_id = ? AND service_provider_id = ?
                    ");
                    $stmt->execute([$entity_id, $input['assigned_provider_id']]);
                    if (!$stmt->fetch()) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Service provider is not approved for this client']);
                        exit;
                    }
                }
            }
        }

        if (isset($input['job_status'])) {
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
