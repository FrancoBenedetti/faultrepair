<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('log_errors', true);
ini_set('error_log', $_SERVER['DOCUMENT_ROOT'].'/all-logs/upload-assets-csv.log');

require_once '../config/database.php';
require_once '../includes/JWT.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
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

// Only client admins (role 2) and service provider admins (role 3) can upload CSVs.
if ($role_id < 2) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied. Administrator access required.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['error' => 'No CSV file uploaded or upload error.']);
        exit;
    }

    $file = $_FILES['csv_file']['tmp_name'];
    $handle = fopen($file, 'r');

    if ($handle === FALSE) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to open uploaded CSV file.']);
        exit;
    }

    $header = fgetcsv($handle);
    if ($header === FALSE) {
        http_response_code(400);
        echo json_encode(['error' => 'CSV file is empty or could not read header.']);
        fclose($handle);
        exit;
    }

    // Define expected columns and their required status
    $expected_columns = [
        'asset_no' => true,
        'item' => true,
        'description' => false,
        'location_id' => false,
        'manager_id' => false,
        'status' => false,
    ];

    // For Client Admins, sp_id can be provided in the CSV
    if ($role_id == 2) {
        $expected_columns['sp_id'] = false;
    }

    // Map CSV header to expected columns
    $column_map = [];
    foreach ($header as $index => $col_name) {
        $trimmed_col_name = trim($col_name);
        if (array_key_exists($trimmed_col_name, $expected_columns)) {
            $column_map[$trimmed_col_name] = $index;
        }
    }

    // Check for required columns
    foreach ($expected_columns as $col_name => $required) {
        if ($required && !isset($column_map[$col_name])) {
            http_response_code(400);
            echo json_encode(['error' => "Required column '{$col_name}' not found in CSV header."]);
            fclose($handle);
            exit;
        }
    }

    $list_owner_id = $entity_id;
    $client_id = null;

    if ($role_id == 2) { // Client Administrator
        $client_id = $entity_id;
    } elseif ($role_id == 3) { // Service Provider Administrator
        if (empty($_POST['client_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Client ID is required. Please select a client.']);
            exit;
        }
        $client_id = (int)$_POST['client_id'];

        // Verify provider is approved for this client
        $stmt = $pdo->prepare("SELECT id FROM participant_approvals WHERE client_participant_id = ? AND provider_participant_id = ?");
        $stmt->execute([$client_id, $list_owner_id]);
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(['error' => "You are not an approved service provider for the selected client."]);
            exit;
        }
    }

    if (!$client_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Could not determine Client ID.']);
        exit;
    }

    $success_count = 0;
    $error_messages = [];
    $line_num = 1; // Start after header

    $pdo->beginTransaction();

    try {
        while (($row = fgetcsv($handle)) !== FALSE) {
            $line_num++;
            $data = [];
            foreach ($column_map as $col_name => $index) {
                $data[$col_name] = $row[$index] ?? null;
            }

            // Basic validation
            if (empty($data['asset_no'])) {
                $error_messages[] = "Line {$line_num}: 'asset_no' cannot be empty.";
                continue;
            }
            if (empty($data['item'])) {
                $error_messages[] = "Line {$line_num}: 'item' cannot be empty.";
                continue;
            }

            // Prepare data for DB
            $asset_no = trim($data['asset_no']);
            $item = trim($data['item']);
            $description = trim($data['description']) ?: null;
            $location_id = (int)$data['location_id'] ?: null;
            $manager_id = (int)$data['manager_id'] ?: null;
            $sp_id = (int)$data['sp_id'] ?: null;
            $status = trim($data['status'] ?? '') ?: 'active';

            // If the list owner is an SP, sp_id is their own ID
            if ($role_id == 3) {
                $sp_id = $list_owner_id;
            }

            // Check if asset already exists (unique on list_owner_id, client_id, asset_no)
            $stmt = $pdo->prepare("SELECT id FROM assets WHERE list_owner_id = ? AND client_id = ? AND asset_no = ?");
            $stmt->execute([$list_owner_id, $client_id, $asset_no]);
            $existing_asset = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing_asset) {
                // Update existing asset
                $sql = "UPDATE assets SET item = ?, description = ?, location_id = ?, manager_id = ?, sp_id = ?, status = ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $item,
                    $description,
                    $location_id,
                    $manager_id,
                    $sp_id,
                    $status,
                    $existing_asset['id']
                ]);
            } else {
                // Insert new asset
                $sql = "INSERT INTO assets (list_owner_id, client_id, asset_no, item, description, location_id, manager_id, sp_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $list_owner_id,
                    $client_id,
                    $asset_no,
                    $item,
                    $description,
                    $location_id,
                    $manager_id,
                    $sp_id,
                    $status
                ]);
            }
            $success_count++;
        }

        $pdo->commit();
        fclose($handle);

        if (empty($error_messages)) {
            echo json_encode(['success' => true, 'message' => "Successfully processed {$success_count} assets."]);
        } else {
            http_response_code(207); // Multi-Status
            echo json_encode([
                'success' => true, // Partial success
                'message' => "Processed {$success_count} assets with some errors.",
                'errors' => $error_messages
            ]);
        }

    } catch (PDOException $e) {
        $pdo->rollBack();
        fclose($handle);
        http_response_code(500);
        error_log('CSV Upload PDO Error: ' . $e->getMessage());
        echo json_encode(['error' => 'Database error during CSV processing.']);
    } catch (Exception $e) {
        $pdo->rollBack();
        fclose($handle);
        http_response_code(500);
        error_log('CSV Upload Error: ' . $e->getMessage());
        echo json_encode(['error' => 'An unexpected error occurred during CSV processing.']);
    }

} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>
