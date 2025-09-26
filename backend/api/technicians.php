<?php
require_once '../config/database.php';
require_once '../includes/JWT.php';
require_once '../includes/email.php';

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

    if (!$data || !isset($data['username']) || !isset($data['email']) ||
        !isset($data['first_name']) || !isset($data['last_name'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Required fields: username, email, first_name, last_name']);
        return;
    }

    $username = trim($data['username']);
    $email = trim($data['email']);
    $first_name = trim($data['first_name']);
    $last_name = trim($data['last_name']);
    $phone = trim($data['phone']);

    // Validation
    if (empty($username) || empty($email) || empty($first_name) || empty($last_name) || empty($phone)) {
        http_response_code(400);
        echo json_encode(['error' => 'All required fields must be non-empty']);
        return;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid email format']);
        return;
    }

    // Validate phone number (basic validation)
    if (!preg_match('/^[0-9+\-\s()]+$/', $phone)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid phone number format']);
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

        // Generate verification token
        $verificationToken = EmailService::generateVerificationToken();
        $tokenExpires = date('Y-m-d H:i:s', strtotime('+24 hours'));

        // Generate a temporary password hash (user will set their own password via email)
        $tempPassword = bin2hex(random_bytes(16)); // Generate a random temporary password
        $tempPasswordHash = password_hash($tempPassword, PASSWORD_DEFAULT);

        // Create technician user
        $stmt = $pdo->prepare("
            INSERT INTO users (
                username, password_hash, email, first_name, last_name, phone,
                role_id, entity_type, entity_id, is_active, email_verified, verification_token, token_expires, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, 4, 'service_provider', ?, FALSE, FALSE, ?, ?, NOW())
        ");
        $stmt->execute([$username, $tempPasswordHash, $email, $first_name, $last_name, $phone, $provider_id, $verificationToken, $tokenExpires]);

        $technician_id = $pdo->lastInsertId();

        $pdo->commit();

        // Send password reset email
        $emailSent = EmailService::sendVerificationEmail($email, $username, $verificationToken, true);

        if ($emailSent) {
            echo json_encode([
                'message' => 'Technician created successfully. An email has been sent to set up their password.',
                'technician_id' => $technician_id
            ]);
        } else {
            echo json_encode([
                'message' => 'Technician created successfully, but email could not be sent. Please contact support.',
                'technician_id' => $technician_id
            ]);
        }

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

        $allowedFields = ['first_name', 'last_name', 'email', 'phone', 'is_active', 'role_id'];
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                if ($field === 'role_id') {
                    $new_role_id = (int)$data[$field];
                    // Verify role exists and is appropriate for service providers
                    $stmt = $pdo->prepare("SELECT id, name FROM roles WHERE id = ? AND name IN ('Technician', 'Service Provider Admin')");
                    $stmt->execute([$new_role_id]);
                    $role = $stmt->fetch(PDO::FETCH_ASSOC);
                    if (!$role) {
                        $pdo->rollBack();
                        http_response_code(400);
                        echo json_encode(['error' => 'Invalid role selected']);
                        return;
                    }

                    // If promoting to Service Provider Admin, check email verification
                    if ($role['name'] === 'Service Provider Admin') {
                        $stmt = $pdo->prepare("SELECT email_verified, email, username FROM users WHERE id = ?");
                        $stmt->execute([$technician_id]);
                        $user_info = $stmt->fetch(PDO::FETCH_ASSOC);

                        if (!$user_info['email_verified']) {
                            // Send verification email and don't allow promotion
                            $verificationToken = EmailService::generateVerificationToken();
                            $tokenExpires = date('Y-m-d H:i:s', strtotime('+24 hours'));

                            $stmt = $pdo->prepare("UPDATE users SET verification_token = ?, token_expires = ? WHERE id = ?");
                            $stmt->execute([$verificationToken, $tokenExpires, $technician_id]);

                            $pdo->rollBack(); // Don't commit the role change

                            $emailSent = EmailService::sendVerificationEmail($user_info['email'], $user_info['username'], $verificationToken);

                            http_response_code(403);
                            echo json_encode(['error' => 'Email verification required before promoting to admin role. Verification email sent.']);
                            return;
                        }
                    }

                    $updateFields[] = "role_id = ?";
                    $updateValues[] = $new_role_id;
                } else {
                    $updateFields[] = "$field = ?";
                    $updateValues[] = $data[$field];
                }
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
