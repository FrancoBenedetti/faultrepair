<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('log_errors', true);
ini_set('error_log', $_SERVER['DOCUMENT_ROOT'].'/all-logs/assets.log');

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
$auth_header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token = '';
if (preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
    $token = $matches[1];
}

if (!$token) {
    // Fallback to query parameter for live server compatibility
    $token = $_GET['token'] ?? '';
}

if (!$token) {
    http_response_code(401);
    echo json_encode(['error' => 'Authorization token missing']);
    exit;
}

$payload = JWT::decode($token);
if ($payload === false) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid or expired token.']);
    exit;
}

$user_id = $payload['user_id'];
$role_id = $payload['role_id'];
$entity_type = $payload['entity_type'];
$entity_id = $payload['entity_id']; // This is the participantId

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        if (!isset($_GET['client_id']) || empty($_GET['client_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'client_id is a required query parameter.']);
            exit;
        }
        $client_id_filter = (int)$_GET['client_id'];

        // --- Authorization Check ---
        $has_permission = false;
        if ($role_id == 1 && $entity_id == $client_id_filter) { // Regular client user
            $has_permission = true;
        } elseif ($role_id == 2 && $entity_id == $client_id_filter) {
            // Client admin can view their own assets.
            $has_permission = true;
        } elseif ($role_id == 3) {
            // Service provider can view assets of clients they are approved for.
            $stmt = $pdo->prepare("SELECT id FROM participant_approvals WHERE client_participant_id = ? AND provider_participant_id = ?");
            $stmt->execute([$client_id_filter, $entity_id]);
            if ($stmt->fetch()) {
                $has_permission = true;
            }
        }

        if (!$has_permission) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied. You do not have permission to view assets for this client.']);
            exit;
        }

        // --- Fetch Data ---
        $sql = "
            SELECT
                a.id,
                a.list_owner_id,
                p.name as list_owner_name,
                a.client_id,
                a.asset_no,
                a.item,
                a.description,
                a.location_id,
                l.name as location_name,
                a.manager_id,
                CONCAT(u.first_name, ' ', u.last_name) as manager_name,
                a.sp_id,
                sp.name as sp_name,
                a.star,
                a.status,
                a.created_at,
                a.updated_at
            FROM
                assets a
            JOIN
                participants p ON a.list_owner_id = p.participantId
            LEFT JOIN
                locations l ON a.location_id = l.id
            LEFT JOIN
                users u ON a.manager_id = u.userId
            LEFT JOIN
                participants sp ON a.sp_id = sp.participantId
            WHERE
                a.client_id = ?
            ORDER BY
                a.list_owner_id, a.id
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$client_id_filter]);
        $assets = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // --- Group Results ---
        $grouped_lists = [];
        foreach ($assets as $asset) {
            $owner_id = $asset['list_owner_id'];
            if (!isset($grouped_lists[$owner_id])) {
                $grouped_lists[$owner_id] = [
                    'list_owner_id' => $owner_id,
                    'list_owner_name' => $asset['list_owner_name'],
                    'assets' => []
                ];
            }
            $grouped_lists[$owner_id]['assets'][] = $asset;
        }

        echo json_encode([
            'client_id' => $client_id_filter,
            'asset_lists' => array_values($grouped_lists) // Re-index the array
        ]);

    } elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON input']);
            exit;
        }

        // --- Validation ---
        if (!isset($input['asset_no']) || empty($input['asset_no'])) {
            http_response_code(400);
            echo json_encode(['error' => "Field 'asset_no' is required"]);
            exit;
        }
        if (!isset($input['item']) || empty($input['item'])) {
            http_response_code(400);
            echo json_encode(['error' => "Field 'item' is required"]);
            exit;
        }

        $list_owner_id = $entity_id;
        $client_id = null;

        if ($role_id == 2) { // Client Administrator
            $client_id = $entity_id;
        } elseif ($role_id == 3) { // Service Provider Administrator
            if (!isset($input['client_id']) || empty($input['client_id'])) {
                http_response_code(400);
                echo json_encode(['error' => "Field 'client_id' is required for Service Providers"]);
                exit;
            }
            $client_id = $input['client_id'];

            // Verify provider is approved for this client
            $stmt = $pdo->prepare("SELECT id FROM participant_approvals WHERE client_participant_id = ? AND provider_participant_id = ?");
            $stmt->execute([$client_id, $list_owner_id]);
            if (!$stmt->fetch()) {
                http_response_code(403);
                echo json_encode(['error' => 'You are not an approved service provider for this client.']);
                exit;
            }
        }

        if (!$client_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Could not determine client ID.']);
            exit;
        }
        
        // Optional fields
        $description = $input['description'] ?? null;
        $location_id = empty($input['location_id']) ? null : $input['location_id'];
        $manager_id = empty($input['manager_id']) ? null : $input['manager_id'];
        $sp_id = empty($input['sp_id']) ? null : $input['sp_id'];
        $status = $input['status'] ?? 'active';

        // If the list owner is an SP, sp_id is their own ID
        if ($role_id == 3) {
            $sp_id = $list_owner_id;
        }

        $sql = "INSERT INTO assets (list_owner_id, client_id, asset_no, item, description, location_id, manager_id, sp_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        
        try {
            $stmt->execute([
                $list_owner_id,
                $client_id,
                $input['asset_no'],
                $input['item'],
                $description,
                $location_id,
                $manager_id,
                $sp_id,
                $status
            ]);

            $asset_id = $pdo->lastInsertId();

            http_response_code(201);
            echo json_encode([
                'success' => true,
                'message' => 'Asset created successfully',
                'asset_id' => $asset_id
            ]);

        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) { // Duplicate entry
                http_response_code(409);
                echo json_encode(['error' => 'An asset with this number already exists for this client in this list.']);
            } else {
                throw $e;
            }
        }

    } elseif ($method === 'PUT') {
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Asset ID is required in the query string.']);
            exit;
        }
        $asset_id = (int)$_GET['id'];

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON input']);
            exit;
        }

        // --- Authorization Check ---
        $stmt = $pdo->prepare("SELECT list_owner_id FROM assets WHERE id = ?");
        $stmt->execute([$asset_id]);
        $asset = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$asset) {
            http_response_code(404);
            echo json_encode(['error' => 'Asset not found.']);
            exit;
        }

        if ($asset['list_owner_id'] !== $entity_id) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied. You are not the owner of this asset list.']);
            exit;
        }

        // --- Dynamic Update Query ---
        $allowed_fields = ['asset_no', 'item', 'description', 'location_id', 'manager_id', 'sp_id', 'star', 'status'];
        $updates = [];
        $params = [];

        foreach ($allowed_fields as $field) {
            if (isset($input[$field])) {
                $updates[] = "$field = ?";
                $params[] = $input[$field];
            }
        }

        if (empty($updates)) {
            http_response_code(400);
            echo json_encode(['error' => 'No valid fields to update provided.']);
            exit;
        }

        $params[] = $asset_id;
        $sql = "UPDATE assets SET " . implode(', ', $updates) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);

        try {
            $stmt->execute($params);
            echo json_encode(['success' => true, 'message' => 'Asset updated successfully.']);
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) { // Duplicate entry
                http_response_code(409);
                echo json_encode(['error' => 'An asset with this number already exists for this client in this list.']);
            } else {
                throw $e;
            }
        }

    } elseif ($method === 'DELETE') {
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Asset ID is required in the query string.']);
            exit;
        }
        $asset_id = (int)$_GET['id'];

        // --- Authorization Check ---
        $stmt = $pdo->prepare("SELECT list_owner_id FROM assets WHERE id = ?");
        $stmt->execute([$asset_id]);
        $asset = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$asset) {
            http_response_code(404);
            echo json_encode(['error' => 'Asset not found.']);
            exit;
        }

        if ($asset['list_owner_id'] !== $entity_id) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied. You are not the owner of this asset list.']);
            exit;
        }

        // --- Delete Operation ---
        $stmt = $pdo->prepare("DELETE FROM assets WHERE id = ?");
        $stmt->execute([$asset_id]);

        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Asset deleted successfully.']);
        } else {
            // This case might occur if the asset was deleted between the check and the delete command (race condition)
            http_response_code(404);
            echo json_encode(['error' => 'Asset not found or already deleted.']);
        }

    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }

} catch (Exception $e) {
    http_response_code(500);
    error_log('Assets API Error: ' . $e->getMessage());
    echo json_encode(['error' => 'An internal server error occurred.']);
}
?>
