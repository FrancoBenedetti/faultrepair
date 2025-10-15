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

// Allow service provider admins (role_id 3) OR quickfix-admin user (role_id 4), OR system admins (role_id 5)
$isAllowed = ($entity_type === 'service_provider' && $role_id === 3) ||
             $role_id === 5 ||  // System Administrator
             $role_id === 4;    // Allow all users with role_id 4 (including quickfix-admin)

if (!$isAllowed) {
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
    global $role_id;
    global $entity_type;
    global $user_id;

    try {
        // For service provider admins (role_id 3), show technicians AND admins for their organization
        if ($entity_type === 'service_provider' && $role_id === 3) {
            $query = "
                SELECT
                    u.userId as id,
                    u.username,
                    u.email,
                    u.created_at,
                    CONCAT(u.first_name, ' ', u.last_name) as full_name,
                    u.first_name,
                    u.last_name,
                    u.phone,
                    u.is_active,
                    u.role_id
                FROM users u
                WHERE u.entity_type = 'service_provider'
                  AND u.entity_id = ?
                  AND u.role_id IN (3, 4)  -- Include both admins (3) and technicians (4)
                ORDER BY u.role_id ASC, u.created_at DESC  -- Admins first, then technicians
            ";
            $params = [$provider_id];
        } else {
            // Fall back to old behavior for other users (shouldn't happen in normal flow)
            $query = "
                SELECT
                    u.userId as id,
                    u.username,
                    u.email,
                    u.created_at,
                    CONCAT(u.first_name, ' ', u.last_name) as full_name,
                    u.first_name,
                    u.last_name,
                    u.phone,
                    u.is_active,
                    u.role_id
                FROM users u
                WHERE u.role_id = 4
            ";
            $params = [];
        }

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['technicians' => $technicians]);

    } catch (Exception $e) {
        error_log(__FILE__ . ' - Exception in getTechnicians: ' . $e->getMessage());
        error_log(__FILE__ . ' - Exception trace: ' . $e->getTraceAsString());
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
    $stmt = $pdo->prepare("SELECT userId FROM users WHERE username = ? OR email = ?");
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
                role_id, entity_id, entity_type, is_active, email_verified, verification_token, token_expires, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, 4, ?, 'service_provider', FALSE, FALSE, ?, ?, NOW())
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

        // Verify technician belongs to this service provider and check permissions
        global $role_id;
        global $entity_type;
        global $user_id;

        if ($entity_type === 'service_provider' && $role_id === 3) {
            // For service provider admins, allow updating themselves (role 3) or technicians (role 4) in their organization
            $stmt = $pdo->prepare("
                SELECT userId, role_id FROM users
                WHERE userId = ? AND entity_type = 'service_provider' AND entity_id = ?
            ");
            $stmt->execute([$technician_id, $provider_id]);
            $user_to_update = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user_to_update) {
                $pdo->rollBack();
                http_response_code(404);
                echo json_encode(['error' => 'Technician not found or access denied']);
                return;
            }

            // Only allow admins to update technicians (role 4) and themselves (role 3)
            // Prevent updating other admins unless they are updating themselves
            if ($user_to_update['role_id'] === 3 && $technician_id !== $user_id) {
                $pdo->rollBack();
                http_response_code(403);
                echo json_encode(['error' => 'Cannot modify other administrators']);
                return;
            }
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
                    $stmt = $pdo->prepare("SELECT roleId as id, name FROM user_roles WHERE roleId = ?");
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
                        $stmt = $pdo->prepare("SELECT email_verified, email, username FROM users WHERE userId = ?");
                        $stmt->execute([$technician_id]);
                        $user_info = $stmt->fetch(PDO::FETCH_ASSOC);

                        if (!$user_info['email_verified']) {
                            // Send verification email and don't allow promotion
                            $verificationToken = EmailService::generateVerificationToken();
                            $tokenExpires = date('Y-m-d H:i:s', strtotime('+24 hours'));

                            $stmt = $pdo->prepare("UPDATE users SET verification_token = ?, token_expires = ? WHERE userId = ?");
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
            $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE userId = ?";
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
        echo json_encode(['error' => 'User ID is required']);
        return;
    }

    try {
        $pdo->beginTransaction();

        // Verify user belongs to this service provider (only for service provider admins)
        global $role_id;
        global $entity_type;

        if ($entity_type === 'service_provider' && $role_id === 3) {
            $stmt = $pdo->prepare("
                SELECT userId, role_id FROM users
                WHERE userId = ? AND entity_type = 'service_provider' AND entity_id = ? AND role_id IN (3, 4)
            ");
            $stmt->execute([$technician_id, $provider_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $pdo->rollBack();
                http_response_code(404);
                echo json_encode(['error' => 'User not found or access denied']);
                return;
            }

            // Prevent admin from deleting themselves
            if ($user['role_id'] === 3 && $technician_id === $GLOBALS['user_id']) {
                $pdo->rollBack();
                http_response_code(400);
                echo json_encode(['error' => 'You cannot delete your own account']);
                return;
            }
        }

        // Check if user has assigned jobs (for technicians)
        $stmt = $pdo->prepare("SELECT COUNT(*) as job_count FROM jobs WHERE assigned_technician_id = ?");
        $stmt->execute([$technician_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['job_count'] > 0) {
            $pdo->rollBack();
            http_response_code(409);
            echo json_encode(['error' => 'Cannot delete user with assigned jobs. Reassign jobs first.']);
            return;
        }

        // Delete user
        $stmt = $pdo->prepare("DELETE FROM users WHERE userId = ?");
        $stmt->execute([$technician_id]);

        $pdo->commit();

        echo json_encode(['message' => 'User deleted successfully']);

    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete user: ' . $e->getMessage()]);
    }
}
?>
