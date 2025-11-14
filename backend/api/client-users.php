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
    $entity_type = $payload['entity_type'];
    $entity_id = $payload['entity_id'];
    $role_id = $payload['role_id'];
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid token']);
    exit;
}

// ... (JWT authentication and payload decoding) ...

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // For GET requests, allow both client and service_provider (Role 3)
        if ($entity_type === 'client') {
            // Client can only view their own users
            $filter_role = isset($_GET['role']) ? (int)$_GET['role'] : null;
            getClientUsers($entity_id, $filter_role);
        } elseif ($entity_type === 'service_provider' && $role_id === 3) {
            // Service Provider Admin can view users for an approved client
            $target_client_id = isset($_GET['client_id']) ? (int)$_GET['client_id'] : null;
            if (!$target_client_id) {
                http_response_code(400);
                echo json_encode(['error' => 'client_id is required for service provider requests.']);
                exit;
            }
            // Verify if this service provider is approved by the target client
            $stmt = $pdo->prepare("SELECT id FROM participant_approvals WHERE client_participant_id = ? AND provider_participant_id = ?");
            $stmt->execute([$target_client_id, $entity_id]);
            if (!$stmt->fetch()) {
                http_response_code(403);
                echo json_encode(['error' => 'Access denied. Service provider not approved by this client.']);
                exit;
            }
            $filter_role = isset($_GET['role']) ? (int)$_GET['role'] : null;
            getClientUsers($target_client_id, $filter_role);
        } else {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied.']);
            exit;
        }
        break;

    case 'POST':
    case 'PUT':
    case 'DELETE':
        // For POST, PUT, DELETE, only client admins can manage their own users
        if ($entity_type !== 'client' || $role_id !== 2) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied. Client admin permissions required.']);
            exit;
        }
        // Pass client's own entity_id for these operations
        if ($method === 'POST') addClientUser($entity_id);
        if ($method === 'PUT') updateClientUser($entity_id);
        if ($method === 'DELETE') deleteClientUser($entity_id);
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

function getClientUsers($target_participant_id, $filter_role = null) {
    global $pdo;

    try {
        $sql = "
            SELECT
                u.userId as id,
                u.username,
                u.email,
                u.first_name,
                u.last_name,
                u.phone,
                u.created_at,
                ur.roleId as role_id,
                ur.name as role_name
            FROM users u
            JOIN user_roles ur ON u.role_id = ur.roleId
            WHERE u.entity_id = ?
        ";
        $params = [$target_participant_id];

        if ($filter_role !== null) {
            $sql .= " AND ur.roleId = ?";
            $params[] = (int)$filter_role;
        }

        $sql .= " ORDER BY u.created_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['users' => $users]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to retrieve users: ' . $e->getMessage()]);
    }
}

function addClientUser($client_id) {
    global $pdo;

    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['email']) ||
        !isset($data['first_name']) || !isset($data['last_name']) || !isset($data['role_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Required fields missing: email, first_name, last_name, role_id']);
        exit;
    }

    $email = trim($data['email']);
    $first_name = trim($data['first_name']);
    $last_name = trim($data['last_name']);
    $phone = trim($data['phone']);
    $role_id = (int)$data['role_id'];

    // Validation - phone is optional, convert empty to null
    if (empty($email) || empty($first_name) || empty($last_name)) {
        http_response_code(400);
        echo json_encode(['error' => 'Required fields must be non-empty: email, first_name, last_name']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid email format']);
        exit;
    }

    // Validate phone number only if provided (optional field)
    if ($phone !== '' && !preg_match('/^[0-9+\-\s()]+$/', $phone)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid phone number format']);
        exit;
    }

    // Convert empty phone to null for database storage
    $phone = ($phone === '') ? null : $phone;

    try {
        $pdo->beginTransaction();

    // Generate username from first and last names
    $username = strtolower(trim($first_name) . '.' . trim($last_name));

    // Ensure username is unique by adding a number if needed
    $baseUsername = $username;
    $counter = 1;
    $stmt = $pdo->prepare("SELECT userId FROM users WHERE username = ?");
    $stmt->execute([$username]);
    while ($stmt->fetch()) {
        $username = $baseUsername . $counter;
        $stmt->execute([$username]);
        $counter++;
    }

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT userId FROM users WHERE email = ?");
    $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $pdo->rollBack();
            http_response_code(409);
            echo json_encode(['error' => 'Email already exists']);
            exit;
        }

        // Verify role exists (role ID is the authoritative identifier - names are just display labels)
        $stmt = $pdo->prepare("SELECT roleId as id, name FROM user_roles WHERE roleId = ?");
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

        // Generate a temporary password hash (user will set their own password via email)
        $tempPassword = bin2hex(random_bytes(16)); // Generate a random temporary password
        $tempPasswordHash = password_hash($tempPassword, PASSWORD_DEFAULT);

        // Insert user (updated for new schema - no entity_type, entity_id becomes participant reference)
        $stmt = $pdo->prepare("
            INSERT INTO users (username, password_hash, email, first_name, last_name, phone, role_id, entity_id, is_active, email_verified, verification_token, token_expires)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, FALSE, FALSE, ?, ?)
        ");
        $stmt->execute([$username, $tempPasswordHash, $email, $first_name, $last_name, $phone, $role_id, $client_id, $verificationToken, $tokenExpires]);

        $userId = $pdo->lastInsertId();

        $pdo->commit();

        // Send password reset email

        $emailSent = EmailService::sendVerificationEmail($email, $userId, $verificationToken, true);

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
    global $pdo;

    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data || !isset($data['user_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'User ID is required']);
        exit;
    }

    $user_id = (int)$data['user_id'];

    try {
        $pdo->beginTransaction();

        // Verify the user belongs to this client (participant)
        $stmt = $pdo->prepare("
            SELECT userId as id FROM users
            WHERE userId = ? AND entity_id = ?
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
        $emailChanged = false;
        $newEmail = null;
        if (isset($data['email'])) {
            $email = trim($data['email']);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $pdo->rollBack();
                http_response_code(400);
                echo json_encode(['error' => 'Invalid email format']);
                exit;
            }

            // Check if email is already taken by another user
            $stmt = $pdo->prepare("SELECT userId FROM users WHERE email = ? AND userId != ?");
            $stmt->execute([$email, $user_id]);
            if ($stmt->fetch()) {
                $pdo->rollBack();
                http_response_code(409);
                echo json_encode(['error' => 'Email already exists']);
                exit;
            }

            // Check if email actually changed
            $stmt = $pdo->prepare("SELECT email, username FROM users WHERE userId = ?");
            $stmt->execute([$user_id]);
            $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($currentUser['email'] !== $email) {
                $emailChanged = true;
                $newEmail = $email;
            }

            $updateFields[] = "email = ?";
            $updateValues[] = $email;
        }

        // Handle role update
        if (isset($data['role_id'])) {
            $new_role_id = (int)$data['role_id'];

            // Verify role exists (role ID is the authoritative identifier - names are just display labels)
            $stmt = $pdo->prepare("SELECT roleId as id, name FROM user_roles WHERE roleId = ?");
            $stmt->execute([$new_role_id]);
            $role = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$role) {
                $pdo->rollBack();
                http_response_code(400);
                echo json_encode(['error' => 'Invalid role selected']);
                exit;
            }

            // If promoting to Client Admin, check email verification
            if ($role['name'] === 'Client Admin') {
                $stmt = $pdo->prepare("SELECT email_verified, email, username FROM users WHERE userId = ?");
                $stmt->execute([$user_id]);
                $user_info = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$user_info['email_verified']) {
                    // Send verification email and don't allow promotion
                    $verificationToken = EmailService::generateVerificationToken();
                    $tokenExpires = date('Y-m-d H:i:s', strtotime('+24 hours'));

                    $stmt = $pdo->prepare("UPDATE users SET verification_token = ?, token_expires = ? WHERE userId = ?");
                    $stmt->execute([$verificationToken, $tokenExpires, $user_id]);

                    $pdo->rollBack(); // Don't commit the role change

                    $emailSent = EmailService::sendVerificationEmail($user_info['email'], $user_id, $verificationToken);

                    http_response_code(403);
                    echo json_encode(['error' => 'Email verification required before promoting to admin role. Verification email sent.']);
                    exit;
                }
            }

            $updateFields[] = "role_id = ?";
            $updateValues[] = $new_role_id;
        }

        // Handle first_name update
        if (isset($data['first_name'])) {
            $first_name = trim($data['first_name']);
            // Required field cannot be empty
            if (empty($first_name)) {
                $pdo->rollBack();
                http_response_code(400);
                echo json_encode(['error' => 'First name cannot be empty']);
                exit;
            }

            $updateFields[] = "first_name = ?";
            $updateValues[] = $first_name;
        }

        // Handle last_name update
        if (isset($data['last_name'])) {
            $last_name = trim($data['last_name']);
            // Required field cannot be empty
            if (empty($last_name)) {
                $pdo->rollBack();
                http_response_code(400);
                echo json_encode(['error' => 'Last name cannot be empty']);
                exit;
            }

            $updateFields[] = "last_name = ?";
            $updateValues[] = $last_name;
        }

        // Handle phone/mobile update
        if (isset($data['mobile'])) {
            $mobile = trim($data['mobile']);
            if ($mobile === '') {
                $mobile = null;
            }

            // Validate phone number if provided
            if ($mobile !== null && !preg_match('/^[0-9+\-\s()]+$/', $mobile)) {
                $pdo->rollBack();
                http_response_code(400);
                echo json_encode(['error' => 'Invalid phone number format']);
                exit;
            }

            $updateFields[] = "phone = ?";
            $updateValues[] = $mobile;
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
        $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE userId = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($updateValues);

        $pdo->commit();

        // Send password reset email to new email address if email was changed
        if ($emailChanged && $newEmail) {
            $resetToken = EmailService::generateVerificationToken();
            $tokenExpires = date('Y-m-d H:i:s', strtotime('+1 hour')); // Password reset links expire in 1 hour

            // Update the verification token for password reset
            $stmt = $pdo->prepare("UPDATE users SET verification_token = ?, token_expires = ? WHERE userId = ?");
            $stmt->execute([$resetToken, $tokenExpires, $user_id]);

            $emailSent = EmailService::sendVerificationEmail($newEmail, $user_id, $resetToken, true);

            if ($emailSent) {
                echo json_encode(['message' => 'User updated successfully. A password reset email has been sent to the new email address.']);
            } else {
                echo json_encode(['message' => 'User updated successfully, but password reset email could not be sent to the new email address.']);
            }
        } else {
            echo json_encode(['message' => 'User updated successfully']);
        }

    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update user: ' . $e->getMessage()]);
    }
}

function deleteClientUser($client_id) {
    global $pdo;

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
            SELECT u.userId as id, ur.name as role_name
            FROM users u
            JOIN user_roles ur ON u.role_id = ur.roleId
            WHERE u.userId = ? AND u.entity_id = ?
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
        if ($user['role_name'] === 'Client Admin') {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as admin_count
                FROM users u
                JOIN user_roles ur ON u.role_id = ur.roleId
                WHERE u.entity_id = ? AND ur.name = 'Client Admin' AND u.userId != ?
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
        $stmt = $pdo->prepare("DELETE FROM users WHERE userId = ?");
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
