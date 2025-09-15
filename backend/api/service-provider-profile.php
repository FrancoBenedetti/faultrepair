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

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Get service provider profile
        getServiceProviderProfile($entity_id);
        break;

    case 'PUT':
        // Update service provider profile
        updateServiceProviderProfile($entity_id);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

function getServiceProviderProfile($provider_id) {
    global $pdo;

    try {
        // Get basic provider info
        $stmt = $pdo->prepare("
            SELECT id, name, address, website, manager_name, manager_email,
                   manager_phone, vat_number, business_registration_number,
                   description, logo_url, is_active, created_at, updated_at
            FROM service_providers
            WHERE id = ?
        ");
        $stmt->execute([$provider_id]);
        $provider = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$provider) {
            http_response_code(404);
            echo json_encode(['error' => 'Service provider not found']);
            return;
        }

        // Get services offered
        $stmt = $pdo->prepare("
            SELECT s.id, s.name, s.description, s.category, sps.is_primary
            FROM services s
            JOIN service_provider_services sps ON s.id = sps.service_id
            WHERE sps.service_provider_id = ?
            ORDER BY sps.is_primary DESC, s.name
        ");
        $stmt->execute([$provider_id]);
        $services = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get service regions
        $stmt = $pdo->prepare("
            SELECT r.id, r.name, r.code, r.country
            FROM regions r
            JOIN service_provider_regions spr ON r.id = spr.region_id
            WHERE spr.service_provider_id = ?
            ORDER BY r.name
        ");
        $stmt->execute([$provider_id]);
        $regions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate profile completeness
        $completeness = calculateProfileCompleteness($provider, $services, $regions);

        $response = [
            'profile' => $provider,
            'services' => $services,
            'regions' => $regions,
            'profile_completeness' => $completeness
        ];

        echo json_encode($response);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to retrieve profile: ' . $e->getMessage()]);
    }
}

function updateServiceProviderProfile($provider_id) {
    global $pdo;

    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON data']);
        return;
    }

    try {
        $pdo->beginTransaction();

        // Update basic profile information
        $updateFields = [];
        $updateValues = [];

        $allowedFields = [
            'name', 'address', 'website', 'manager_name', 'manager_email',
            'manager_phone', 'vat_number', 'business_registration_number',
            'description', 'logo_url'
        ];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateFields[] = "$field = ?";
                $updateValues[] = $data[$field];
            }
        }

        if (!empty($updateFields)) {
            $updateValues[] = $provider_id;
            $sql = "UPDATE service_providers SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($updateValues);
        }

        // Update services if provided
        if (isset($data['services']) && is_array($data['services'])) {
            // Remove existing services
            $stmt = $pdo->prepare("DELETE FROM service_provider_services WHERE service_provider_id = ?");
            $stmt->execute([$provider_id]);

            // Add new services
            $stmt = $pdo->prepare("
                INSERT INTO service_provider_services (service_provider_id, service_id, is_primary)
                VALUES (?, ?, ?)
            ");

            foreach ($data['services'] as $service) {
                if (isset($service['id'])) {
                    $isPrimary = isset($service['is_primary']) ? $service['is_primary'] : false;
                    $stmt->execute([$provider_id, $service['id'], $isPrimary]);
                }
            }
        }

        // Update regions if provided
        if (isset($data['regions']) && is_array($data['regions'])) {
            // Remove existing regions
            $stmt = $pdo->prepare("DELETE FROM service_provider_regions WHERE service_provider_id = ?");
            $stmt->execute([$provider_id]);

            // Add new regions
            $stmt = $pdo->prepare("
                INSERT INTO service_provider_regions (service_provider_id, region_id)
                VALUES (?, ?)
            ");

            foreach ($data['regions'] as $region) {
                if (isset($region['id'])) {
                    $stmt->execute([$provider_id, $region['id']]);
                }
            }
        }

        $pdo->commit();

        // Return updated profile
        getServiceProviderProfile($provider_id);

    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update profile: ' . $e->getMessage()]);
    }
}

function calculateProfileCompleteness($provider, $services, $regions) {
    $score = 0;
    $total = 100;

    // Basic information (40 points)
    $basicFields = ['name', 'address', 'description', 'website'];
    foreach ($basicFields as $field) {
        if (!empty($provider[$field])) $score += 10;
    }

    // Manager contact (20 points)
    $managerFields = ['manager_name', 'manager_email', 'manager_phone'];
    foreach ($managerFields as $field) {
        if (!empty($provider[$field])) $score += 7;
    }

    // Business details (20 points)
    $businessFields = ['vat_number', 'business_registration_number'];
    foreach ($businessFields as $field) {
        if (!empty($provider[$field])) $score += 10;
    }

    // Services (10 points)
    if (count($services) > 0) $score += 10;

    // Regions (10 points)
    if (count($regions) > 0) $score += 10;

    return min($score, $total);
}
?>
