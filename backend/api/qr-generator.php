<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/database.php';
require_once '../config/site.php';
require_once '../includes/JWT.php';

header('Content-Type: text/html');

// --- Authentication ---
$auth_header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token = '';
if (preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
    $token = $matches[1];
}
if (!$token) {
    $token = $_GET['token'] ?? '';
}
if (!$token) {
    http_response_code(401);
    echo "<h1>Authorization required.</h1>";
    exit;
}

$payload = JWT::decode($token);
if ($payload === false) {
    http_response_code(401);
    echo "<h1>Invalid or expired token.</h1>";
    exit;
}

$user_id = $payload['user_id'];
$role_id = $payload['role_id'];
$entity_id = $payload['entity_id'];
$entity_type = $payload['entity_type'] ?? 'client'; // Default to client for safety

$dashboard_url = '/'; // Default fallback
if ($entity_type === 'client') {
    $dashboard_url = '/client-dashboard';
} elseif ($entity_type === 'service_provider') {
    $dashboard_url = '/service-provider-dashboard';
}

// --- Input Validation ---
if (!isset($_GET['ids']) || empty($_GET['ids'])) {
    http_response_code(400);
    echo "<h1>No asset IDs provided.</h1>";
    exit;
}

$asset_ids_str = $_GET['ids'];
$asset_ids = explode(',', $asset_ids_str);
$placeholders = implode(',', array_fill(0, count($asset_ids), '?'));

// --- Database Query ---
// Ensure user can only access assets they own (as list_owner_id)
try {
    $sql = "SELECT
                a.id, a.asset_no, a.item, a.description, a.client_id, a.sp_id, a.manager_id, a.location_id,
                l.name as location_name,
                p_sp.name as sp_name,
                u.first_name as manager_first_name,
                u.last_name as manager_last_name
            FROM assets a
            LEFT JOIN locations l ON a.location_id = l.id
            LEFT JOIN participants p_sp ON a.sp_id = p_sp.participantId
            LEFT JOIN users u ON a.manager_id = u.userId
            WHERE a.id IN ($placeholders) AND a.list_owner_id = ?";
    
    $stmt = $pdo->prepare($sql);
    
    // Bind asset IDs and the user's entity_id
    $params = array_merge($asset_ids, [$entity_id]);
    $stmt->execute($params);
    
    $assets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($assets)) {
        http_response_code(404);
        echo "<h1>No assets found or you do not have permission to view them.</h1>";
        exit;
    }

} catch (PDOException $e) {
    http_response_code(500);
    error_log("QR Code Generation DB Error: " . $e->getMessage());
    echo "<h1>Database error occurred.</h1>";
    exit;
}

// --- HTML Generation ---
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asset QR Codes</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap');
        body {
            font-family: 'Inter', sans-serif;
            margin: 20px;
            background-color: #f9fafb;
        }
        .print-container {
            max-width: 8.5in;
            margin: auto;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 10px;
        }
        .header h1 {
            font-size: 24px;
            color: #111827;
            margin: 0;
        }
        .header p {
            font-size: 14px;
            color: #6b7280;
            margin: 5px 0 0;
        }
        .qr-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        .qr-item {
            background-color: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 15px;
            display: flex;
            align-items: center;
            page-break-inside: avoid;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .qr-code {
            flex-shrink: 0;
            margin-right: 15px;
        }
        .qr-code img {
            width: 120px;
            height: 120px;
            display: block;
        }
        .asset-details {
            overflow: hidden;
        }
        .asset-details h2 {
            font-size: 16px;
            font-weight: 600;
            margin: 0 0 5px;
            color: #111827;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .asset-details p {
            margin: 0;
            font-size: 12px;
            color: #4b5563;
            line-height: 1.5;
        }
        .asset-details .label {
            font-weight: 600;
            color: #374151;
        }
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 15px;
            background-color: #2563eb;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .back-button {
            position: fixed;
            top: 20px;
            left: 20px;
            padding: 10px 15px;
            background-color: #4b5563;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        @media print {
            body {
                margin: 0;
                background-color: #fff;
            }
            .print-container {
                max-width: 100%;
                margin: 0;
            }
            .print-button, .back-button {
                display: none;
            }
            .qr-item {
                box-shadow: none;
                border: 1px solid #d1d5db;
            }
        }
    </style>
</head>
<body>
    <a href="<?php echo $dashboard_url; ?>" class="back-button">Back to Dashboard</a>
    <button class="print-button" onclick="window.print()">Print QR Codes</button>
    <div class="print-container">
        <div class="header">
            <h1>Asset QR Codes</h1>
            <p>Generated on: <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>
        <div class="qr-grid">
            <?php foreach ($assets as $asset):
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";

                $params = [
                    'client' => $asset['client_id'],
                    'a' => $asset['asset_no'],
                    'item' => $asset['item'],
                    'location' => $asset['location_id'],
                    'mngr' => $asset['manager_id'],
                ];

                if ($entity_type === 'service_provider') {
                    $params['sp'] = $entity_id;
                } elseif ($asset['sp_id']) {
                    $params['sp'] = $asset['sp_id'];
                }

                $asset_url = $protocol . DOMAIN . "/qr?" . http_build_query($params);
                $qr_api_url = "https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=" . urlencode($asset_url);
                
                $manager_name = trim(($asset['manager_first_name'] ?? '') . ' ' . ($asset['manager_last_name'] ?? ''));
            ?>
                <div class="qr-item">
                    <div class="qr-code">
                        <img src="<?php echo $qr_api_url; ?>" alt="QR Code for Asset <?php echo htmlspecialchars($asset['asset_no']); ?>">
                    </div>
                    <div class="asset-details">
                        <h2><?php echo htmlspecialchars($asset['item']); ?></h2>
                        <p><span class="label">Asset No:</span> <?php echo htmlspecialchars($asset['asset_no']); ?></p>
                        <?php if (!empty($asset['location_name'])): ?>
                            <p><span class="label">Location:</span> <?php echo htmlspecialchars($asset['location_name']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($manager_name)): ?>
                            <p><span class="label">Manager:</span> <?php echo htmlspecialchars($manager_name); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($asset['sp_name'])): ?>
                            <p><span class="label">Provider:</span> <?php echo htmlspecialchars($asset['sp_name']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>