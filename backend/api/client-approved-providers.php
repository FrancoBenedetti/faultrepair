<?php
require_once '../config/database.php';
require_once '../includes/JWT.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
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
        // Get client's approved providers
        getApprovedProviders($entity_id);
        break;

    case 'POST':
        // Add provider to approved list
        addApprovedProvider($entity_id, $user_id);
        break;

    case 'DELETE':
        // Remove provider from approved list
        removeApprovedProvider($entity_id);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

function getApprovedProviders($participant_id) {
    global $pdo;

    try {
        $query = "
            SELECT
                pa.id,
                pa.provider_participant_id as service_provider_id,
                p.name, p.address, p.website, p.description, p.logo_url,
                p.manager_name,
                p.manager_email,
                p.manager_phone,
                p.vat_number,
                p.business_registration_number,
                pt.participantType as provider_type,
                pa.created_at as approved_at,
                COUNT(DISTINCT sps.service_id) as services_count,
                COUNT(DISTINCT spr.region_id) as regions_count,
                COUNT(DISTINCT j.id) as total_jobs,
                COUNT(DISTINCT CASE WHEN j.job_status IN ('Reported', 'Assigned', 'In Progress') THEN j.id END) as active_jobs,
                COUNT(DISTINCT CASE WHEN j.job_status IN ('Completed', 'Confirmed') THEN j.id END) as completed_jobs
            FROM participant_approvals pa
            JOIN participants p ON pa.provider_participant_id = p.participantId
            JOIN participant_type pt ON p.participantId = pt.participantId
            LEFT JOIN service_provider_services sps ON p.participantId = sps.service_provider_id AND pt.participantType = 'S'
            LEFT JOIN service_provider_regions spr ON p.participantId = spr.service_provider_id AND pt.participantType = 'S'
            LEFT JOIN jobs j ON pa.client_participant_id = j.client_id
                AND pa.provider_participant_id = j.assigned_provider_participant_id
            WHERE pa.client_participant_id = ? AND p.is_active = TRUE
            GROUP BY pa.id, p.participantId, pt.participantType
            ORDER BY pa.created_at DESC
        ";

        $stmt = $pdo->prepare($query);
        $stmt->execute([$participant_id]);
        $approved_providers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get detailed services and regions for each provider (if tables exist)
        foreach ($approved_providers as &$provider) {
            $provider['services'] = [];
            $provider['regions'] = [];

            try {
                // Get services
                $stmt = $pdo->prepare("
                    SELECT s.id, s.name, s.category, sps.is_primary
                    FROM services s
                    JOIN service_provider_services sps ON s.id = sps.service_id
                    WHERE sps.service_provider_id = ?
                    ORDER BY sps.is_primary DESC, s.name
                ");
                $stmt->execute([$provider['service_provider_id']]);
                $provider['services'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                // Tables might not exist yet - that's okay
                $provider['services'] = [];
            }

            try {
                // Get regions
                $stmt = $pdo->prepare("
                    SELECT r.id, r.name, r.code
                    FROM regions r
                    JOIN service_provider_regions spr ON r.id = spr.region_id
                    WHERE spr.service_provider_id = ?
                    ORDER BY r.name
                ");
                $stmt->execute([$provider['service_provider_id']]);
                $provider['regions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                // Tables might not exist yet - that's okay
                $provider['regions'] = [];
            }
        }

        echo json_encode([
            'approved_providers' => $approved_providers,
            'total_count' => count($approved_providers)
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to retrieve approved providers: ' . $e->getMessage()]);
    }
}

function addApprovedProvider($participant_id, $user_id) {
    global $pdo;

    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['service_provider_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'service_provider_id is required']);
        return;
    }

    $provider_id = (int)$data['service_provider_id'];
    $notes = isset($data['notes']) ? trim($data['notes']) : '';

    try {
        // Verify provider exists and is active (now using participants table)
        $stmt = $pdo->prepare("SELECT participantId as id, name FROM participants WHERE participantId = ? AND is_active = TRUE");
        $stmt->execute([$provider_id]);
        $provider = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$provider) {
            http_response_code(404);
            echo json_encode(['error' => 'Service provider not found or inactive']);
            return;
        }

        // Check if already approved
        $stmt = $pdo->prepare("SELECT id FROM participant_approvals WHERE client_participant_id = ? AND provider_participant_id = ?");
        $stmt->execute([$participant_id, $provider_id]);

        if ($stmt->fetch()) {
            http_response_code(409);
            echo json_encode(['error' => 'Provider already approved']);
            return;
        }

        // Insert into new participant_approvals table
        $stmt = $pdo->prepare("
            INSERT INTO participant_approvals (client_participant_id, provider_participant_id)
            VALUES (?, ?)
        ");
        $stmt->execute([$participant_id, $provider_id]);

        echo json_encode([
            'message' => 'Service provider added to approved list',
            'provider_name' => $provider['name'],
            'approved_at' => date('Y-m-d H:i:s')
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to add provider to approved list: ' . $e->getMessage()]);
    }
}

function removeApprovedProvider($participant_id) {
    global $pdo;

    // Get provider ID from URL or query parameter
    $provider_id = null;

    // Check if provider_id is in URL path (e.g., /api/client-approved-providers/123)
    $request_uri = $_SERVER['REQUEST_URI'];
    if (preg_match('/\/api\/client-approved-providers\/(\d+)/', $request_uri, $matches)) {
        $provider_id = (int)$matches[1];
    }

    // Alternative: check query parameter
    if (!$provider_id && isset($_GET['provider_id'])) {
        $provider_id = (int)$_GET['provider_id'];
    }

    if (!$provider_id) {
        http_response_code(400);
        echo json_encode(['error' => 'service_provider_id is required']);
        return;
    }

    try {
        // Verify the approval exists and belongs to this client
        $stmt = $pdo->prepare("
            SELECT p.name
            FROM participant_approvals pa
            JOIN participants p ON pa.provider_participant_id = p.participantId
            WHERE pa.client_participant_id = ? AND pa.provider_participant_id = ?
        ");
        $stmt->execute([$participant_id, $provider_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            http_response_code(404);
            echo json_encode(['error' => 'Approved provider not found']);
            return;
        }

        // Remove from approved list
        $stmt = $pdo->prepare("DELETE FROM participant_approvals WHERE client_participant_id = ? AND provider_participant_id = ?");
        $stmt->execute([$participant_id, $provider_id]);

        echo json_encode([
            'message' => 'Service provider removed from approved list',
            'provider_name' => $result['name']
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to remove provider from approved list: ' . $e->getMessage()]);
    }
}
?>
