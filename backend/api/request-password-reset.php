<?php
require_once '../config/database.php';
require_once '../includes/email.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['email'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Email is required']);
    exit;
}

$email = trim($data['email']);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email format']);
    exit;
}

try {
    // Find user by email
    $stmt = $pdo->prepare("SELECT id, username, email_verified FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        // Don't reveal if email exists or not for security
        echo json_encode(['message' => 'If the email is registered and verified, a password reset link has been sent.']);
        exit;
    }

    if (!$user['email_verified']) {
        // Email not verified, can't reset password
        echo json_encode(['message' => 'If the email is registered and verified, a password reset link has been sent.']);
        exit;
    }

    // Generate reset token
    $resetToken = EmailService::generateVerificationToken();
    $tokenExpires = date('Y-m-d H:i:s', strtotime('+1 hour')); // Shorter for password reset

    // Update user with reset token
    $stmt = $pdo->prepare("UPDATE users SET verification_token = ?, token_expires = ? WHERE id = ?");
    $stmt->execute([$resetToken, $tokenExpires, $user['id']]);

    // Send reset email
    $emailSent = EmailService::sendVerificationEmail($user['email'], $user['username'], $resetToken, true);

    if ($emailSent) {
        echo json_encode(['message' => 'Password reset link sent to your email.']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to send reset email. Please try again later.']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Password reset request failed: ' . $e->getMessage()]);
}
?>
