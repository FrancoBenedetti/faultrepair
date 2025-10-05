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

// Only budget controllers can manage client profiles
if ($role_id !== 2) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied. Budget Controller role required to manage client profile.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Get client profile
        getClientProfile($entity_id);
        break;

    case 'PUT':
        // Update client profile
        updateClientProfile($entity_id);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

function getClientProfile($client_id) {
    global $pdo;

    try {
        // Get basic client info
        $stmt = $pdo->prepare("
            SELECT id, name, address, website, manager_name, manager_email,
                   manager_phone, vat_number, business_registration_number,
                   description, logo_url, is_active, created_at, updated_at
            FROM clients
            WHERE id = ?
        ");
        $stmt->execute([$client_id]);
        $client = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$client) {
            http_response_code(404);
            echo json_encode(['error' => 'Client not found']);
            return;
        }

        // Calculate profile completeness
        $completeness = calculateProfileCompleteness($client);

        $response = [
            'profile' => $client,
            'profile_completeness' => $completeness
        ];

        echo json_encode($response);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to retrieve profile: ' . $e->getMessage()]);
    }
}

function updateClientProfile($client_id) {
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
            'description', 'logo_url', 'is_active'
        ];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateFields[] = "$field = ?";
                $updateValues[] = $data[$field];
            }
        }

        if (!empty($updateFields)) {
            $updateValues[] = $client_id;
            $sql = "UPDATE clients SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($updateValues);
        }

        $pdo->commit();

        // Return updated profile
        getClientProfile($client_id);

    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update profile: ' . $e->getMessage()]);
    }
}

function calculateProfileCompleteness($client) {
    $score = 0;
    $total = 100;

    // Basic information (40 points)
    $basicFields = ['name', 'address', 'description'];
    foreach ($basicFields as $field) {
        if (!empty($client[$field])) $score += 13;
    }

    // Website (10 points)
    if (!empty($client['website'])) $score += 10;

    // Manager contact (30 points)
    $managerFields = ['manager_name', 'manager_email', 'manager_phone'];
    foreach ($managerFields as $field) {
        if (!empty($client[$field])) $score += 10;
    }

    // Business details (20 points)
    $businessFields = ['vat_number', 'business_registration_number'];
    foreach ($businessFields as $field) {
        if (!empty($client[$field])) $score += 10;
    }

    return min($score, $total);
}
?>
