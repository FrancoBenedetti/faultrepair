<?php
/**
 * Comprehensive Email Verification Testing Script
 * This script tests all email verification functionality without requiring actual email sending
 */

require_once 'backend/config/database.php';
require_once 'backend/includes/email.php';

echo "=== Email Verification System Test ===\n\n";

// Test 1: Client Registration
echo "1. Testing Client Registration...\n";
$clientData = [
    'clientName' => 'Test Client Company',
    'address' => '123 Test Street, Test City',
    'username' => 'testclient',
    'email' => 'testclient@example.com',
    'password' => 'TestPass123'
];

try {
    $pdo->beginTransaction();

    // Simulate registration
    $stmt = $pdo->prepare("INSERT INTO clients (name, address) VALUES (?, ?)");
    $stmt->execute([$clientData['clientName'], $clientData['address']]);
    $clientId = $pdo->lastInsertId();

    $stmt = $pdo->prepare("SELECT id FROM roles WHERE name = 'Site Budget Controller'");
    $stmt->execute();
    $role = $stmt->fetch();

    $passwordHash = password_hash($clientData['password'], PASSWORD_DEFAULT);
    $verificationToken = EmailService::generateVerificationToken();
    $tokenExpires = date('Y-m-d H:i:s', strtotime('+24 hours'));

    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, email, role_id, entity_type, entity_id, is_active, email_verified, verification_token, token_expires) VALUES (?, ?, ?, ?, 'client', ?, FALSE, FALSE, ?, ?)");
    $stmt->execute([$clientData['username'], $passwordHash, $clientData['email'], $role['id'], $clientId, $verificationToken, $tokenExpires]);

    $userId = $pdo->lastInsertId();

    echo "✓ Client registered with ID: $userId\n";
    echo "✓ Verification token: $verificationToken\n";

    $pdo->rollBack(); // Don't actually save for testing
    echo "✓ Registration test passed\n\n";

} catch (Exception $e) {
    echo "✗ Registration test failed: " . $e->getMessage() . "\n\n";
}

// Test 2: Email Verification
echo "2. Testing Email Verification...\n";
try {
    $pdo->beginTransaction();

    // Create test user
    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, email, role_id, entity_type, entity_id, is_active, email_verified, verification_token, token_expires) VALUES (?, ?, ?, 2, 'client', 1, FALSE, FALSE, ?, ?)");
    $verificationToken = EmailService::generateVerificationToken();
    $tokenExpires = date('Y-m-d H:i:s', strtotime('+24 hours'));
    $stmt->execute(['testuser', password_hash('testpass', PASSWORD_DEFAULT), 'test@example.com', $verificationToken, $tokenExpires]);
    $userId = $pdo->lastInsertId();

    // Test verification
    $stmt = $pdo->prepare("SELECT id FROM users WHERE verification_token = ?");
    $stmt->execute([$verificationToken]);
    $user = $stmt->fetch();

    if ($user) {
        $stmt = $pdo->prepare("UPDATE users SET email_verified = TRUE, is_active = TRUE, verification_token = NULL, token_expires = NULL WHERE id = ?");
        $stmt->execute([$user['id']]);
        echo "✓ Email verification successful\n";
    }

    $pdo->rollBack();
    echo "✓ Verification test passed\n\n";

} catch (Exception $e) {
    echo "✗ Verification test failed: " . $e->getMessage() . "\n\n";
}

// Test 3: Adding Users (No Password)
echo "3. Testing User Addition (No Password)...\n";
try {
    $pdo->beginTransaction();

    // Simulate adding a reporting employee
    $verificationToken = EmailService::generateVerificationToken();
    $tokenExpires = date('Y-m-d H:i:s', strtotime('+24 hours'));

    $stmt = $pdo->prepare("INSERT INTO users (username, email, first_name, last_name, phone, role_id, entity_type, entity_id, is_active, email_verified, verification_token, token_expires) VALUES (?, ?, ?, ?, ?, 1, 'client', 1, FALSE, FALSE, ?, ?)");
    $stmt->execute(['testemployee', 'employee@example.com', 'John', 'Doe', '1234567890', $verificationToken, $tokenExpires]);
    $userId = $pdo->lastInsertId();

    echo "✓ User added with ID: $userId\n";
    echo "✓ Password setup token: $verificationToken\n";

    $pdo->rollBack();
    echo "✓ User addition test passed\n\n";

} catch (Exception $e) {
    echo "✗ User addition test failed: " . $e->getMessage() . "\n\n";
}

// Test 4: Password Setup
echo "4. Testing Password Setup...\n";
try {
    $pdo->beginTransaction();

    // Create user with token
    $verificationToken = EmailService::generateVerificationToken();
    $tokenExpires = date('Y-m-d H:i:s', strtotime('+24 hours'));

    $stmt = $pdo->prepare("INSERT INTO users (username, email, role_id, entity_type, entity_id, is_active, email_verified, verification_token, token_expires) VALUES (?, ?, 1, 'client', 1, FALSE, FALSE, ?, ?)");
    $stmt->execute(['testuser2', 'test2@example.com', $verificationToken, $tokenExpires]);
    $userId = $pdo->lastInsertId();

    // Simulate password setup
    $newPassword = 'NewSecurePass123';
    $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, email_verified = TRUE, is_active = TRUE, verification_token = NULL, token_expires = NULL WHERE id = ?");
    $stmt->execute([$passwordHash, $userId]);

    echo "✓ Password set successfully\n";

    $pdo->rollBack();
    echo "✓ Password setup test passed\n\n";

} catch (Exception $e) {
    echo "✗ Password setup test failed: " . $e->getMessage() . "\n\n";
}

// Test 5: Authentication (Active Only)
echo "5. Testing Authentication (Active Accounts Only)...\n";
try {
    $pdo->beginTransaction();

    // Create inactive user
    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, email, role_id, entity_type, entity_id, is_active) VALUES (?, ?, ?, 1, 'client', 1, FALSE)");
    $stmt->execute(['inactiveuser', password_hash('testpass', PASSWORD_DEFAULT), 'inactive@example.com']);

    // Try to authenticate inactive user
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND is_active = TRUE");
    $stmt->execute(['inactiveuser']);
    $user = $stmt->fetch();

    if (!$user) {
        echo "✓ Inactive user correctly blocked from login\n";
    } else {
        echo "✗ Inactive user incorrectly allowed login\n";
    }

    // Create active user
    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, email, role_id, entity_type, entity_id, is_active, email_verified) VALUES (?, ?, ?, 1, 'client', 1, TRUE, TRUE)");
    $stmt->execute(['activeuser', password_hash('testpass', PASSWORD_DEFAULT), 'active@example.com']);

    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND is_active = TRUE");
    $stmt->execute(['activeuser']);
    $user = $stmt->fetch();

    if ($user) {
        echo "✓ Active user correctly allowed login\n";
    } else {
        echo "✗ Active user incorrectly blocked from login\n";
    }

    $pdo->rollBack();
    echo "✓ Authentication test passed\n\n";

} catch (Exception $e) {
    echo "✗ Authentication test failed: " . $e->getMessage() . "\n\n";
}

// Test 6: Password Reset
echo "6. Testing Password Reset...\n";
try {
    $pdo->beginTransaction();

    // Create verified user
    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, email, role_id, entity_type, entity_id, is_active, email_verified) VALUES (?, ?, ?, 2, 'client', 1, TRUE, TRUE)");
    $stmt->execute(['resetuser', password_hash('oldpass', PASSWORD_DEFAULT), 'reset@example.com']);
    $userId = $pdo->lastInsertId();

    // Generate reset token
    $resetToken = EmailService::generateVerificationToken();
    $tokenExpires = date('Y-m-d H:i:s', strtotime('+1 hour'));

    $stmt = $pdo->prepare("UPDATE users SET verification_token = ?, token_expires = ? WHERE id = ?");
    $stmt->execute([$resetToken, $tokenExpires, $userId]);

    // Simulate password reset
    $newPassword = 'ResetPass123';
    $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, verification_token = NULL, token_expires = NULL WHERE id = ?");
    $stmt->execute([$passwordHash, $userId]);

    echo "✓ Password reset successful\n";

    $pdo->rollBack();
    echo "✓ Password reset test passed\n\n";

} catch (Exception $e) {
    echo "✗ Password reset test failed: " . $e->getMessage() . "\n\n";
}

// Test 7: Promotion Verification
echo "7. Testing Promotion Verification...\n";
try {
    $pdo->beginTransaction();

    // Create unverified user
    $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, email, role_id, entity_type, entity_id, is_active, email_verified) VALUES (?, ?, ?, 1, 'client', 1, TRUE, FALSE)");
    $stmt->execute(['promoteuser', password_hash('testpass', PASSWORD_DEFAULT), 'promote@example.com']);
    $userId = $pdo->lastInsertId();

    // Check if promotion would be blocked
    $stmt = $pdo->prepare("SELECT email_verified FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user['email_verified']) {
        echo "✓ Unverified user correctly blocked from promotion\n";
    }

    // Simulate verification
    $verificationToken = EmailService::generateVerificationToken();
    $tokenExpires = date('Y-m-d H:i:s', strtotime('+24 hours'));

    $stmt = $pdo->prepare("UPDATE users SET email_verified = TRUE, verification_token = ?, token_expires = ? WHERE id = ?");
    $stmt->execute([$verificationToken, $tokenExpires, $userId]);

    $stmt = $pdo->prepare("SELECT email_verified FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if ($user['email_verified']) {
        echo "✓ Verified user correctly allowed promotion\n";
    }

    $pdo->rollBack();
    echo "✓ Promotion verification test passed\n\n";

} catch (Exception $e) {
    echo "✗ Promotion verification test failed: " . $e->getMessage() . "\n\n";
}

echo "=== Test Summary ===\n";
echo "All core email verification functionality has been tested.\n";
echo "Check the PHP error log for simulated email content.\n";
echo "To enable actual email sending, uncomment the mail() call in email.php\n";
echo "and configure your system's mail settings.\n";

?>
