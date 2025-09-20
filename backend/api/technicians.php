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
    $role_id = $payload['role_id'];
    $entity_type = $payload['entity_type'];
    $entity_id = $payload['entity_id'];
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid token']);
    exit;
}

// Verify user is a service provider admin
if ($entity_type !== 'service_provider' || $role_id !== 3) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied. Service provider admin access required.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        getTechnicians($entity_id);
        break;

    case 'POST':
        createTechnician($entity_id);
        break;

    case 'PUT':
        updateTechnician($entity_id);
        break;

    case 'DELETE':
        deleteTechnician($entity_id);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

function getTechnicians($provider_id) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            SELECT
                u.id,
                u.username,
                u.email,
                u.created_at,
                CONCAT(u.first_name, ' ', u.last_name) as full_name,
                u.first_name,
                u.last_name,
                u.phone,
                u.is_active
            FROM users u
            WHERE u.entity_type = 'service_provider'
            AND u.entity_id = ?
            AND u.role_id = 4
            ORDER BY u.created_at DESC
        ");
        $stmt->execute([$provider_id]);
        $technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['technicians' => $technicians]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to retrieve technicians: ' . $e->getMessage()]);
    }
}

function createTechnician($provider_id) {
    global $pdo;

    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['username']) || !isset($data['password']) ||
        !isset($data['email']) || !isset($data['first_name']) || !isset($data['last_name'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Required fields: username, password, email, first_name, last_name']);
        return;
    }

    $username = trim($data['username']);
    $password = $data['password'];
    $email = trim($data['email']);
    $first_name = trim($data['first_name']);
    $last_name = trim($data['last_name']);
    $phone = isset($data['phone']) ? trim($data['phone']) : null;

    // Validation
    if (empty($username) || empty($password) || empty($email) || empty($first_name) || empty($last_name)) {
        http_response_code(400);
        echo json_encode(['error' => 'All required fields must be non-empty']);
        return;
    }

    if (strlen($password) < 6) {
        http_response_code(400);
        echo json_encode(['error' => 'Password must be at least 6 characters long']);
        return;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid email format']);
        return;
    }

    try {
        $pdo->beginTransaction();

        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $pdo->rollBack();
            http_response_code(409);
            echo json_encode(['error' => 'Username or email already exists']);
            return;
        }

        // Create technician user
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO users (
                username, password_hash, email, first_name, last_name, phone,
                role_id, entity_type, entity_id, is_active, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, 4, 'service_provider', ?, 1, NOW())
        ");
        $stmt->execute([$username, $hashed_password, $email, $first_name, $last_name, $phone, $provider_id]);

        $technician_id = $pdo->lastInsertId();

        $pdo->commit();

        echo json_encode([
            'message' => 'Technician created successfully',
            'technician_id' => $technician_id
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create technician: ' . $e->getMessage()]);
    }
}

function updateTechnician($provider_id) {
    global $pdo;

    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['technician_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Technician ID is required']);
        return;
    }

    $technician_id = (int)$data['technician_id'];

    try {
        $pdo->beginTransaction();

        // Verify technician belongs to this service provider
        $stmt = $pdo->prepare("
            SELECT id FROM users
            WHERE id = ? AND entity_type = 'service_provider' AND entity_id = ? AND role_id = 4
        ");
        $stmt->execute([$technician_id, $provider_id]);
        if (!$stmt->fetch()) {
            $pdo->rollBack();
            http_response_code(404);
            echo json_encode(['error' => 'Technician not found or access denied']);
            return;
        }

        // Build update query dynamically
        $updateFields = [];
        $updateValues = [];

        $allowedFields = ['first_name', 'last_name', 'email', 'phone', 'is_active'];
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateFields[] = "$field = ?";
                $updateValues[] = $data[$field];
            }
        }

        if (!empty($updateFields)) {
            $updateValues[] = $technician_id;
            $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($updateValues);
        }

        $pdo->commit();

        echo json_encode(['message' => 'Technician updated successfully']);

    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update technician: ' . $e->getMessage()]);
    }
}

function deleteTechnician($provider_id) {
    global $pdo;

    $technician_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

    if (!$technician_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Technician ID is required']);
        return;
    }

    try {
        $pdo->beginTransaction();

        // Verify technician belongs to this service provider
        $stmt = $pdo->prepare("
            SELECT id FROM users
            WHERE id = ? AND entity_type = 'service_provider' AND entity_id = ? AND role_id = 4
        ");
        $stmt->execute([$technician_id, $provider_id]);
        if (!$stmt->fetch()) {
            $pdo->rollBack();
            http_response_code(404);
            echo json_encode(['error' => 'Technician not found or access denied']);
            return;
        }

        // Check if technician has assigned jobs
        $stmt = $pdo->prepare("SELECT COUNT(*) as job_count FROM jobs WHERE assigned_technician_id = ?");
        $stmt->execute([$technician_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['job_count'] > 0) {
            $pdo->rollBack();
            http_response_code(409);
            echo json_encode(['error' => 'Cannot delete technician with assigned jobs. Reassign jobs first.']);
            return;
        }

        // Delete technician
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$technician_id]);

        $pdo->commit();

        echo json_encode(['message' => 'Technician deleted successfully']);

    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete technician: ' . $e->getMessage()]);
    }
}
?>
