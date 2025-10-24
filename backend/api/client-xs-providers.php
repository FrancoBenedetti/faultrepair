<?php
require_once '../config/database.php';
require_once '../includes/JWT.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
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

switch ($method) {
    case 'GET':
        // Get client's XS providers
        getXSProviders($entity_id);
        break;

    case 'POST':
        // Create new XS provider
        createXSProvider($entity_id, $user_id);
        break;

    case 'PUT':
        // Update XS provider
        updateXSProvider($entity_id);
        break;

    case 'DELETE':
        // Delete XS provider
        deleteXSProvider($entity_id);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

function getXSProviders($client_participant_id) {
    global $pdo;

    try {
        $query = "
            SELECT
                p.participantId,
                p.name,
                p.address,
                p.website,
                p.manager_name,
                p.manager_email,
                p.manager_phone,
                p.description,
                p.logo_url,
                p.created_at,
                p.updated_at
            FROM participants p
            JOIN participant_type pt ON p.participantId = pt.participantId
            WHERE pt.participantType = 'XS'
            AND p.is_active = TRUE
        ";

        // Optional: filter by client if specified (for future use)
        if (isset($_GET['client_specific'])) {
            // For now, XS providers are shared - but we might want to filter by approved clients later
        }

        $query .= " ORDER BY p.created_at DESC";

        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $xs_providers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'xs_providers' => $xs_providers,
            'total_count' => count($xs_providers)
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to retrieve XS providers: ' . $e->getMessage()]);
    }
}

function createXSProvider($client_participant_id, $user_id) {
    global $pdo;

    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON input']);
        return;
    }

    // Validate required fields
    $required_fields = ['name', 'manager_name', 'manager_email'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            http_response_code(400);
            echo json_encode(['error' => "Field '$field' is required"]);
            return;
        }
    }

    try {
        // Create participant record
        $stmt = $pdo->prepare("
            INSERT INTO participants (
                name, address, website, manager_name, manager_email, manager_phone,
                description, logo_url, is_active, is_enabled
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, TRUE, TRUE)
        ");

        $stmt->execute([
            trim($data['name']),
            isset($data['address']) ? trim($data['address']) : null,
            isset($data['website']) ? trim($data['website']) : null,
            trim($data['manager_name']),
            trim($data['manager_email']),
            isset($data['manager_phone']) ? trim($data['manager_phone']) : null,
            isset($data['description']) ? trim($data['description']) : null,
            isset($data['logo_url']) ? trim($data['logo_url']) : null
        ]);

        $provider_id = $pdo->lastInsertId();

        // Create participant type as XS
        $stmt = $pdo->prepare("
            INSERT INTO participant_type (participantId, participantType, isActive)
            VALUES (?, 'XS', 'Y')
        ");
        $stmt->execute([$provider_id]);

        // Auto-approve for the client that created it
        $stmt = $pdo->prepare("
            INSERT INTO participant_approvals (client_participant_id, provider_participant_id)
            VALUES (?, ?)
        ");
        $stmt->execute([$client_participant_id, $provider_id]);

        echo json_encode([
            'success' => true,
            'message' => 'External service provider created successfully',
            'provider_id' => $provider_id,
            'created_at' => date('Y-m-d H:i:s')
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create XS provider: ' . $e->getMessage()]);
    }
}

function updateXSProvider($client_participant_id) {
    global $pdo;

    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['provider_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'provider_id is required']);
        return;
    }

    $provider_id = (int)$data['provider_id'];

    // Basic validation for required fields
    $required_fields = ['name', 'manager_name', 'manager_email'];
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            http_response_code(400);
            echo json_encode(['error' => "Field '$field' is required"]);
            return;
        }
    }

    try {
        // Verify this is an XS provider and it's approved for this client
        $stmt = $pdo->prepare("
            SELECT p.participantId, pt.participantType
            FROM participants p
            JOIN participant_type pt ON p.participantId = pt.participantId
            JOIN participant_approvals pa ON p.participantId = pa.provider_participant_id
            WHERE p.participantId = ?
            AND pt.participantType = 'XS'
            AND pa.client_participant_id = ?
            AND p.is_active = TRUE
        ");
        $stmt->execute([$provider_id, $client_participant_id]);
        $provider = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$provider) {
            http_response_code(404);
            echo json_encode(['error' => 'XS provider not found or not approved for this client']);
            return;
        }

        // Update participant record
        $stmt = $pdo->prepare("
            UPDATE participants SET
                name = ?,
                address = ?,
                website = ?,
                manager_name = ?,
                manager_email = ?,
                manager_phone = ?,
                description = ?,
                logo_url = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE participantId = ?
        ");

        $stmt->execute([
            trim($data['name']),
            isset($data['address']) ? trim($data['address']) : null,
            isset($data['website']) ? trim($data['website']) : null,
            trim($data['manager_name']),
            trim($data['manager_email']),
            isset($data['manager_phone']) ? trim($data['manager_phone']) : null,
            isset($data['description']) ? trim($data['description']) : null,
            isset($data['logo_url']) ? trim($data['logo_url']) : null,
            $provider_id
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'External service provider updated successfully'
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update XS provider: ' . $e->getMessage()]);
    }
}

function deleteXSProvider($client_participant_id) {
    global $pdo;

    // Get provider ID from URL path (e.g., /api/client-xs-providers/123)
    $request_uri = $_SERVER['REQUEST_URI'];
    if (!preg_match('/\/api\/client-xs-providers\/(\d+)/', $request_uri, $matches)) {
        http_response_code(400);
        echo json_encode(['error' => 'provider_id is required in URL path']);
        return;
    }

    $provider_id = (int)$matches[1];

    try {
        // Verify this is an XS provider owned by this client
        $stmt = $pdo->prepare("
            SELECT p.participantId, p.name, pt.participantType
            FROM participants p
            JOIN participant_type pt ON p.participantId = pt.participantId
            JOIN participant_approvals pa ON p.participantId = pa.provider_participant_id
            WHERE p.participantId = ?
            AND pt.participantType = 'XS'
            AND pa.client_participant_id = ?
            AND p.is_active = TRUE
        ");
        $stmt->execute([$provider_id, $client_participant_id]);
        $provider = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$provider) {
            http_response_code(404);
            echo json_encode(['error' => 'XS provider not found or not accessible']);
            return;
        }

        // Check if provider is used in any jobs - don't allow deletion if it is
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as job_count
            FROM jobs
            WHERE assigned_provider_participant_id = ?
        ");
        $stmt->execute([$provider_id]);
        $job_count = $stmt->fetch(PDO::FETCH_ASSOC)['job_count'];

        if ($job_count > 0) {
            http_response_code(409);
            echo json_encode([
                'error' => 'Cannot delete XS provider - it is assigned to active jobs',
                'job_count' => $job_count
            ]);
            return;
        }

        // Soft delete - disable rather than remove completely
        $stmt = $pdo->prepare("
            UPDATE participants SET
                is_active = FALSE,
                disabled_reason = 'Deleted by client',
                disabled_at = CURRENT_TIMESTAMP,
                disabled_by_user_id = {$client_participant_id}
            WHERE participantId = ?
        ");
        $stmt->execute([$provider_id]);

        // Remove from approvals (but don't cascade delete jobs as they remain for audit)
        $stmt = $pdo->prepare("
            DELETE FROM participant_approvals
            WHERE client_participant_id = ? AND provider_participant_id = ?
        ");
        $stmt->execute([$client_participant_id, $provider_id]);

        echo json_encode([
            'success' => true,
            'message' => 'External service provider removed successfully',
            'provider_name' => $provider['name']
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete XS provider: ' . $e->getMessage()]);
    }
}
?>
