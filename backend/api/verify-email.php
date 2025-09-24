<?php
require_once '../config/database.php';
require_once '../includes/email.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

$method = $_SERVER['REQUEST_METHOD'];
$token = isset($_GET['token']) ? trim($_GET['token']) : (isset($_POST['token']) ? trim($_POST['token']) : null);
$action = isset($_GET['action']) ? trim($_GET['action']) : (isset($_POST['action']) ? trim($_POST['action']) : null);

if (!$token) {
    http_response_code(400);
    echo json_encode(['error' => 'Verification token is required']);
    exit;
}

try {
    // Find user by verification token
    $stmt = $pdo->prepare("SELECT id, username, email, token_expires, email_verified FROM users WHERE verification_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid or expired verification token']);
        exit;
    }

    // Check if token has expired
    $now = new DateTime();
    $expires = new DateTime($user['token_expires']);

    if ($now > $expires) {
        http_response_code(400);
        echo json_encode(['error' => 'Verification token has expired']);
        exit;
    }

    if ($action === 'reset') {
        // Handle password reset - allow password change
        $data = json_decode(file_get_contents('php://input'), true);
        $newPassword = isset($data['password']) ? $data['password'] : null;

        if (!$newPassword || strlen($newPassword) < 6) {
            http_response_code(400);
            echo json_encode(['error' => 'Password must be at least 6 characters long']);
            exit;
        }

        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, verification_token = NULL, token_expires = NULL WHERE id = ?");
        $stmt->execute([$passwordHash, $user['id']]);

        echo json_encode(['message' => 'Password reset successfully. You can now log in with your new password.']);
    } elseif ($action === 'set_password') {
        // Handle setting password for new users (technicians/reporters)
        $data = json_decode(file_get_contents('php://input'), true);
        $newPassword = isset($data['password']) ? $data['password'] : null;

        if (!$newPassword || strlen($newPassword) < 6) {
            http_response_code(400);
            echo json_encode(['error' => 'Password must be at least 6 characters long']);
            exit;
        }

        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, email_verified = TRUE, is_active = TRUE, verification_token = NULL, token_expires = NULL WHERE id = ?");
        $stmt->execute([$passwordHash, $user['id']]);

        echo json_encode(['message' => 'Account activated successfully. You can now log in.']);
    } else {
        // Handle regular email verification
        if ($user['email_verified']) {
            echo json_encode(['message' => 'Email already verified']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE users SET email_verified = TRUE, is_active = TRUE, verification_token = NULL, token_expires = NULL WHERE id = ?");
        $stmt->execute([$user['id']]);

        echo json_encode(['message' => 'Email verified successfully. Your account is now active.']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Verification failed: ' . $e->getMessage()]);
}
?>
