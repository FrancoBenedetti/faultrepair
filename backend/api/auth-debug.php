<?php
// Debug version of auth.php to isolate the issue
echo "Auth Debug Starting...<br>";

// Test includes
echo "Testing includes...<br>";
try {
    require_once '../config/database.php';
    echo "✓ Database config loaded<br>";
} catch (Exception $e) {
    echo "✗ Database config failed: " . $e->getMessage() . "<br>";
    exit;
}

try {
    require_once '../includes/JWT.php';
    echo "✓ JWT class loaded<br>";
} catch (Exception $e) {
    echo "✗ JWT class failed: " . $e->getMessage() . "<br>";
    exit;
}

// Test database connection
echo "Testing database connection...<br>";
try {
    $pdo->query('SELECT 1');
    echo "✓ Database connection successful<br>";
} catch (Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "<br>";
    exit;
}

// Test user lookup
echo "Testing user lookup...<br>";
$username = isset($_POST['username']) ? $_POST['username'] : 'fred';
$password = isset($_POST['password']) ? $_POST['password'] : 'test123';

echo "Username: $username<br>";
echo "Password: $password<br>";

try {
    $stmt = $pdo->prepare("
    SELECT
        u.userId as id,
        u.password_hash,
        u.role_id,
        CASE
            WHEN pt.participantType = 'C' THEN 'client'
            WHEN pt.participantType = 'S' THEN 'service_provider'
            ELSE 'unknown'
        END as entity_type,
        u.entity_id
    FROM users u
    LEFT JOIN participant_type pt ON u.entity_id = pt.participantId
    WHERE u.username = ?
");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user) {
        echo "✓ User found<br>";
        echo "User ID: {$user['id']}<br>";
        echo "Entity Type: {$user['entity_type']}<br>";
        echo "Role ID: {$user['role_id']}<br>";

        // Test password
        $isValid = password_verify($password, $user['password_hash']);
        echo "Password valid: " . ($isValid ? 'YES' : 'NO') . "<br>";

        if ($isValid) {
            echo "✓ Authentication successful<br>";

            // Test JWT
            echo "Testing JWT...<br>";
            $payload = [
                'user_id' => $user['id'],
                'role_id' => $user['role_id'],
                'entity_type' => $user['entity_type'],
                'entity_id' => $user['entity_id'],
                'iat' => time(),
                'exp' => time() + 3600
            ];

            $token = JWT::encode($payload);
            echo "✓ JWT token generated<br>";
            echo "Token length: " . strlen($token) . "<br>";

            // Test JWT decode
            $decoded = JWT::decode($token);
            echo "✓ JWT token decoded<br>";
            echo "Decoded user_id: {$decoded['user_id']}<br>";

            // Return success
            header('Content-Type: application/json');
            echo json_encode(['token' => $token, 'debug' => 'success']);
        } else {
            echo "✗ Password verification failed<br>";
            http_response_code(401);
            echo json_encode(['error' => 'Invalid credentials']);
        }
    } else {
        echo "✗ User not found<br>";
        http_response_code(401);
        echo json_encode(['error' => 'User not found']);
    }
} catch (Exception $e) {
    echo "✗ Database query failed: " . $e->getMessage() . "<br>";
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
