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
    $role_id = $payload['role_id'];
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
    switch ($method) {
        case 'GET':
            // Get all locations for this client
            $stmt = $pdo->prepare("
                SELECT
                    l.id,
                    l.name,
                    l.address,
                    l.coordinates,
                    l.access_rules,
                    l.access_instructions,
                    COUNT(j.id) as job_count
                FROM locations l
                LEFT JOIN jobs j ON l.id = j.client_location_id
                WHERE l.client_id = ?
                GROUP BY l.id, l.name, l.address, l.coordinates, l.access_rules, l.access_instructions
                ORDER BY l.name ASC
            ");

            $stmt->execute([$entity_id]);
            $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'locations' => $locations,
                'default_location' => count($locations) === 0
            ]);
            break;

        case 'POST':
            // Create new location (Budget Controller only)
            if ($role_id !== 2) {
                http_response_code(403);
                echo json_encode(['error' => 'Access denied. Budget Controller role required.']);
                exit;
            }

            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data || !isset($data['name'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Location name is required']);
                exit;
            }

            $name = trim($data['name']);
            $address = isset($data['address']) ? trim($data['address']) : '';
            $coordinates = isset($data['coordinates']) ? trim($data['coordinates']) : '';
            $access_rules = isset($data['access_rules']) ? trim($data['access_rules']) : '';
            $access_instructions = isset($data['access_instructions']) ? trim($data['access_instructions']) : '';

            if (empty($name)) {
                http_response_code(400);
                echo json_encode(['error' => 'Location name cannot be empty']);
                exit;
            }

            // Validate coordinates format if provided (accepts both lat/long and Plus Codes)
            if (!empty($coordinates)) {
                $isLatLong = preg_match('/^-?\d{1,3}\.?\d*,\s*-?\d{1,3}\.?\d*$/', $coordinates);
                $isPlusCode = preg_match('/^[A-Z0-9]{4,6}\+[A-Z0-9]{2,3}(\s+.*)?$/', $coordinates);

                if (!$isLatLong && !$isPlusCode) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Coordinates must be in latitude,longitude format (e.g., -26.1234,28.5678) or Plus Code format (e.g., 2FGJ+4Q villes, France)']);
                    exit;
                }
            }

            // Validate access_rules URL format if provided
            if (!empty($access_rules) && !filter_var($access_rules, FILTER_VALIDATE_URL)) {
                http_response_code(400);
                echo json_encode(['error' => 'Site Information URL must be a valid URL']);
                exit;
            }

            // Check if location name already exists for this client
            $stmt = $pdo->prepare("SELECT id FROM locations WHERE client_id = ? AND name = ?");
            $stmt->execute([$entity_id, $name]);
            if ($stmt->fetch()) {
                http_response_code(409);
                echo json_encode(['error' => 'Location name already exists']);
                exit;
            }

            // Create new location
            $stmt = $pdo->prepare("
                INSERT INTO locations (client_id, name, address, coordinates, access_rules, access_instructions)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$entity_id, $name, $address, $coordinates, $access_rules, $access_instructions]);

            $location_id = $pdo->lastInsertId();

            echo json_encode([
                'message' => 'Location created successfully',
                'location' => [
                    'id' => $location_id,
                    'name' => $name,
                    'address' => $address,
                    'job_count' => 0
                ]
            ]);
            break;

        case 'PUT':
            // Update location (Budget Controller only)
            if ($role_id !== 2) {
                http_response_code(403);
                echo json_encode(['error' => 'Access denied. Budget Controller role required.']);
                exit;
            }

            $data = json_decode(file_get_contents('php://input'), true);

            if (!$data || !isset($data['location_id']) || !isset($data['name'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Location ID and name are required']);
                exit;
            }

            $location_id = $data['location_id'];
            $name = trim($data['name']);
            $address = isset($data['address']) ? trim($data['address']) : '';
            $coordinates = isset($data['coordinates']) ? trim($data['coordinates']) : '';
            $access_rules = isset($data['access_rules']) ? trim($data['access_rules']) : '';
            $access_instructions = isset($data['access_instructions']) ? trim($data['access_instructions']) : '';

            if (empty($name)) {
                http_response_code(400);
                echo json_encode(['error' => 'Location name cannot be empty']);
                exit;
            }

            // Validate coordinates format if provided (accepts both lat/long and Plus Codes)
            if (!empty($coordinates)) {
                $isLatLong = preg_match('/^-?\d{1,3}\.?\d*,\s*-?\d{1,3}\.?\d*$/', $coordinates);
                $isPlusCode = preg_match('/^[A-Z0-9]{4,6}\+[A-Z0-9]{2,3}(\s+.*)?$/', $coordinates);

                if (!$isLatLong && !$isPlusCode) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Coordinates must be in latitude,longitude format (e.g., -26.1234,28.5678) or Plus Code format (e.g., 2FGJ+4Q villes, France)']);
                    exit;
                }
            }

            // Validate access_rules URL format if provided
            if (!empty($access_rules) && !filter_var($access_rules, FILTER_VALIDATE_URL)) {
                http_response_code(400);
                echo json_encode(['error' => 'Site Information URL must be a valid URL']);
                exit;
            }

            // Verify location belongs to this client
            $stmt = $pdo->prepare("SELECT id FROM locations WHERE id = ? AND client_id = ?");
            $stmt->execute([$location_id, $entity_id]);
            if (!$stmt->fetch()) {
                http_response_code(404);
                echo json_encode(['error' => 'Location not found']);
                exit;
            }

            // Check if new name conflicts with existing location
            $stmt = $pdo->prepare("SELECT id FROM locations WHERE client_id = ? AND name = ? AND id != ?");
            $stmt->execute([$entity_id, $name, $location_id]);
            if ($stmt->fetch()) {
                http_response_code(409);
                echo json_encode(['error' => 'Location name already exists']);
                exit;
            }

            // Update location
            $stmt = $pdo->prepare("
                UPDATE locations
                SET name = ?, address = ?, coordinates = ?, access_rules = ?, access_instructions = ?
                WHERE id = ? AND client_id = ?
            ");
            $stmt->execute([$name, $address, $coordinates, $access_rules, $access_instructions, $location_id, $entity_id]);

            echo json_encode(['message' => 'Location updated successfully']);
            break;

        case 'DELETE':
            // Delete location (Budget Controller only)
            if ($role_id !== 2) {
                http_response_code(403);
                echo json_encode(['error' => 'Access denied. Budget Controller role required.']);
                exit;
            }

            $location_id = isset($_GET['location_id']) ? (int)$_GET['location_id'] : 0;

            if (!$location_id) {
                http_response_code(400);
                echo json_encode(['error' => 'Location ID is required']);
                exit;
            }

            // Check if location has any associated jobs
            $stmt = $pdo->prepare("SELECT COUNT(*) as job_count FROM jobs WHERE client_location_id = ?");
            $stmt->execute([$location_id]);
            $result = $stmt->fetch();

            if ($result['job_count'] > 0) {
                http_response_code(409);
                echo json_encode(['error' => 'Cannot delete location with associated fault reports']);
                exit;
            }

            // Verify location belongs to this client
            $stmt = $pdo->prepare("SELECT id FROM locations WHERE id = ? AND client_id = ?");
            $stmt->execute([$location_id, $entity_id]);
            if (!$stmt->fetch()) {
                http_response_code(404);
                echo json_encode(['error' => 'Location not found']);
                exit;
            }

            // Delete location
            $stmt = $pdo->prepare("DELETE FROM locations WHERE id = ? AND client_id = ?");
            $stmt->execute([$location_id, $entity_id]);

            echo json_encode(['message' => 'Location deleted successfully']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
