<?php
ini_set('log_errors', true);
ini_set('error_log', $_SERVER['DOCUMENT_ROOT'].'/all-logs/site-settings.log');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/JWT.php';

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

// Always allow access to role settings - all authenticated users can read role names
// Only admin modifications are restricted (POST/PUT operations would check permissions)
// All GET requests return role settings for everyone

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        // Get role display names from site settings
        global $pdo;

        $stmt = $pdo->prepare("
            SELECT
                setting_key,
                setting_value,
                setting_type,
                description
            FROM site_settings
            WHERE setting_key IN ('role_display_name_1', 'role_display_name_2', 'role_display_name_3', 'role_display_name_4', 'role_display_name_5')
            ORDER BY setting_key
        ");
        $stmt->execute();
        $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format as simple key-value pairs for frontend
        $role_settings = [];
        foreach ($settings as $setting) {
            // Extract role ID from setting_key (role_display_name_1 -> 1, role_display_name_2 -> 2)
            preg_match('/role_display_name_(\d+)/', $setting['setting_key'], $matches);
            if ($matches && isset($matches[1])) {
                $role_id = (int)$matches[1];
                $role_settings[$role_id] = $setting['setting_value'];
            }
        }

        // Get roles from the user_roles table for accuracy
        $stmt = $pdo->prepare("SELECT roleId as id, name FROM user_roles ORDER BY roleId");
        $stmt->execute();
        $roles_from_db = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Create default_roles array from database
        $default_roles = [];
        foreach ($roles_from_db as $role) {
            $default_roles[$role['id']] = $role['name'];
        }

        // Merge defaults with stored settings (stored settings override defaults, but empty strings are ignored)
        $final_role_settings = [];
        foreach ($default_roles as $id => $name) {
            $stored_value = $role_settings[$id] ?? null;
            $final_role_settings[$id] = (!empty($stored_value) && $stored_value !== '') ? $stored_value : $name;
        }

        echo json_encode(['role_settings' => $final_role_settings]);

    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }

} catch (Exception $e) {
    error_log("Site settings API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?>
