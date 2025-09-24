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
    $role_id = $payload['role_id'];
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid token']);
    exit;
}

// Verify user is a client user
if ($entity_type !== 'client') {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied. Client access required.']);
    exit;
}

// Check if user has admin permissions (Site Budget Controller role_id = 2)
$is_admin = ($role_id === 2);

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        getClientUsers($entity_id);
        break;

    case 'POST':
        addClientUser($entity_id);
        break;

    case 'PUT':
        updateClientUser($entity_id);
        break;

    case 'DELETE':
        deleteClientUser($entity_id);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

function getClientUsers($client_id) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("
            SELECT
                u.id,
                u.username,
                u.email,
                u.created_at,
                r.id as role_id,
                r.name as role_name
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE u.entity_type = 'client' AND u.entity_id = ?
            ORDER BY u.created_at DESC
        ");

        $stmt->execute([$client_id]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['users' => $users]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to retrieve users: ' . $e->getMessage()]);
    }
}

function addClientUser($client_id) {
    global $pdo, $is_admin;

    // Check if user has admin permissions
    if (!$is_admin) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied. Admin permissions required to add users.']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['username']) || !isset($data['email']) ||
        !isset($data['first_name']) || !isset($data['last_name']) || !isset($data['phone']) || !isset($data['role_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'All fields are required: username, email, first_name, last_name, phone, role_id']);
        exit;
    }

    $username = trim($data['username']);
    $email = trim($data['email']);
    $first_name = trim($data['first_name']);
    $last_name = trim($data['last_name']);
    $phone = trim($data['phone']);
    $role_id = (int)$data['role_id'];

    // Validation
    if (empty($username) || empty($email) || empty($first_name) || empty($last_name) || empty($phone)) {
        http_response_code(400);
        echo json_encode(['error' => 'All fields must be non-empty']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid email format']);
        exit;
    }

    // Validate phone number (basic validation)
    if (!preg_match('/^[0-9+\-\s()]+$/', $phone)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid phone number format']);
        exit;
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
            exit;
        }

        // Verify role exists and is appropriate for clients
        $stmt = $pdo->prepare("SELECT id, name FROM roles WHERE id = ? AND name IN ('Reporting Employee', 'Site Budget Controller')");
        $stmt->execute([$role_id]);
        $role = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$role) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(['error' => 'Invalid role selected']);
            exit;
        }

        // Generate verification token
        $verificationToken = EmailService::generateVerificationToken();
        $tokenExpires = date('Y-m-d H:i:s', strtotime('+24 hours'));

        // Insert user
        $stmt = $pdo->prepare("
            INSERT INTO users (username, password_hash, email, first_name, last_name, phone, role_id, entity_type, entity_id, is_active, email_verified, verification_token, token_expires)
            VALUES (?, NULL, ?, ?, ?, ?, ?, 'client', ?, FALSE, FALSE, ?, ?)
        ");
        $stmt->execute([$username, $email, $first_name, $last_name, $phone, $role_id, $client_id, $verificationToken, $tokenExpires]);

        $userId = $pdo->lastInsertId();

        $pdo->commit();

        // Send set password email
        $emailSent = EmailService::sendSetPasswordEmail($email, $username, $verificationToken);

        if ($emailSent) {
            echo json_encode([
                'message' => 'User added successfully. An email has been sent to set up their password.',
                'user' => [
                    'id' => $userId,
                    'username' => $username,
                    'email' => $email,
                    'role_name' => $role['name']
                ]
            ]);
        } else {
            // Email failed, but user created - admin can resend
            echo json_encode([
                'message' => 'User added successfully, but email could not be sent. Please contact support.',
                'user' => [
                    'id' => $userId,
                    'username' => $username,
                    'email' => $email,
                    'role_name' => $role['name']
                ]
            ]);
        }

    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Failed to add user: ' . $e->getMessage()]);
    }
}

function updateClientUser($client_id) {
    global $pdo, $is_admin;

    // Check if user has admin permissions
    if (!$is_admin) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied. Admin permissions required to update users.']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['user_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'User ID is required']);
        exit;
    }

    $user_id = (int)$data['user_id'];

    try {
        $pdo->beginTransaction();

        // Verify the user belongs to this client
        $stmt = $pdo->prepare("
            SELECT id FROM users
            WHERE id = ? AND entity_type = 'client' AND entity_id = ?
        ");
        $stmt->execute([$user_id, $client_id]);
        if (!$stmt->fetch()) {
            $pdo->rollBack();
            http_response_code(403);
            echo json_encode(['error' => 'Access denied. User does not belong to this client.']);
            exit;
        }

        $updateFields = [];
        $updateValues = [];

        // Handle email update
        if (isset($data['email'])) {
            $email = trim($data['email']);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $pdo->rollBack();
                http_response_code(400);
                echo json_encode(['error' => 'Invalid email format']);
                exit;
            }

            // Check if email is already taken by another user
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user_id]);
            if ($stmt->fetch()) {
                $pdo->rollBack();
                http_response_code(409);
                echo json_encode(['error' => 'Email already exists']);
                exit;
            }

            $updateFields[] = "email = ?";
            $updateValues[] = $email;
        }

        // Handle role update
        if (isset($data['role_id'])) {
            $new_role_id = (int)$data['role_id'];

            // Verify role exists and is appropriate for clients
            $stmt = $pdo->prepare("SELECT id, name FROM roles WHERE id = ? AND name IN ('Reporting Employee', 'Site Budget Controller')");
            $stmt->execute([$new_role_id]);
            $role = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$role) {
                $pdo->rollBack();
                http_response_code(400);
                echo json_encode(['error' => 'Invalid role selected']);
                exit;
            }

            // If promoting to Site Budget Controller, check email verification
            if ($role['name'] === 'Site Budget Controller') {
                $stmt = $pdo->prepare("SELECT email_verified, email, username FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user_info = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$user_info['email_verified']) {
                    // Send verification email and don't allow promotion
                    $verificationToken = EmailService::generateVerificationToken();
                    $tokenExpires = date('Y-m-d H:i:s', strtotime('+24 hours'));

                    $stmt = $pdo->prepare("UPDATE users SET verification_token = ?, token_expires = ? WHERE id = ?");
                    $stmt->execute([$verificationToken, $tokenExpires, $user_id]);

                    $pdo->rollBack(); // Don't commit the role change

                    $emailSent = EmailService::sendVerificationEmail($user_info['email'], $user_info['username'], $verificationToken);

                    http_response_code(403);
                    echo json_encode(['error' => 'Email verification required before promoting to admin role. Verification email sent.']);
                    exit;
                }
            }

            $updateFields[] = "role_id = ?";
            $updateValues[] = $new_role_id;
        }

        // Handle password update
        if (isset($data['password']) && !empty($data['password'])) {
            $password = $data['password'];
            if (strlen($password) < 6) {
                $pdo->rollBack();
                http_response_code(400);
                echo json_encode(['error' => 'Password must be at least 6 characters long']);
                exit;
            }

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $updateFields[] = "password_hash = ?";
            $updateValues[] = $passwordHash;
        }

        if (empty($updateFields)) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(['error' => 'No valid fields to update']);
            exit;
        }

        $updateValues[] = $user_id;
        $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($updateValues);

        $pdo->commit();

        echo json_encode(['message' => 'User updated successfully']);

    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update user: ' . $e->getMessage()]);
    }
}

function deleteClientUser($client_id) {
    global $pdo, $is_admin;

    // Check if user has admin permissions
    if (!$is_admin) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied. Admin permissions required to delete users.']);
        exit;
    }

    $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;

    if (!$user_id) {
        http_response_code(400);
        echo json_encode(['error' => 'User ID is required']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Verify the user belongs to this client and prevent deletion of the last admin
        $stmt = $pdo->prepare("
            SELECT u.id, r.name as role_name
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE u.id = ? AND u.entity_type = 'client' AND u.entity_id = ?
        ");
        $stmt->execute([$user_id, $client_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $pdo->rollBack();
            http_response_code(403);
            echo json_encode(['error' => 'Access denied. User does not belong to this client.']);
            exit;
        }

        // Check if this is the last user with admin privileges
        if ($user['role_name'] === 'Site Budget Controller') {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as admin_count
                FROM users u
                JOIN roles r ON u.role_id = r.id
                WHERE u.entity_type = 'client' AND u.entity_id = ? AND r.name = 'Site Budget Controller' AND u.id != ?
            ");
            $stmt->execute([$client_id, $user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($result['admin_count'] == 0) {
                $pdo->rollBack();
                http_response_code(400);
                echo json_encode(['error' => 'Cannot delete the last admin user']);
                exit;
            }
        }

        // Delete the user
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);

        $pdo->commit();

        echo json_encode(['message' => 'User deleted successfully']);

    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete user: ' . $e->getMessage()]);
    }
}
?>
