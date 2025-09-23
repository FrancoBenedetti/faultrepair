<?php
require_once '../config/database.php';
require_once '../includes/JWT.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

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
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid token']);
    exit;
}

// Verify user is a client or service provider
if (!in_array($entity_type, ['client', 'service_provider'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied. Client or service provider access required.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        // Get images for a specific job
        $job_id = isset($_GET['job_id']) ? (int)$_GET['job_id'] : null;

        if (!$job_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Job ID is required']);
            exit;
        }

        // Verify job belongs to this user (client or service provider)
        if ($entity_type === 'client') {
            // Client: verify job belongs to their organization
            $stmt = $pdo->prepare("
                SELECT j.id FROM jobs j
                JOIN locations l ON j.client_location_id = l.id
                WHERE j.id = ? AND l.client_id = ?
            ");
            $stmt->execute([$job_id, $entity_id]);
        } else {
            // Service Provider: verify job is assigned to their company
            // First, let's check what the assigned_provider_id is for this job
            $stmt = $pdo->prepare("SELECT assigned_provider_id FROM jobs WHERE id = ?");
            $stmt->execute([$job_id]);
            $job_info = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$job_info) {
                http_response_code(404);
                echo json_encode(['error' => 'Job not found']);
                exit;
            }

            // Check if this service provider is assigned to the job
            if ($job_info['assigned_provider_id'] !== $entity_id) {
                error_log(__FILE__.'/'.__LINE__.'/ >>>> Job '.$job_id.' assigned to provider '.($job_info['assigned_provider_id'] ?? 'NULL').', but user belongs to provider '.$entity_id);
                http_response_code(403);
                echo json_encode(['error' => 'Access denied. Job not assigned to your service provider.']);
                exit;
            }
        }

        // Get all images for this job
        $stmt = $pdo->prepare("
            SELECT
                id,
                filename,
                original_filename,
                file_path,
                file_size,
                mime_type,
                uploaded_by,
                uploaded_at
            FROM job_images
            WHERE job_id = ?
            ORDER BY uploaded_at ASC
        ");
        $stmt->execute([$job_id]);
        $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Convert file paths to web-accessible URLs
        foreach ($images as &$image) {
            // Assuming images are served from /backend/uploads/job_images/
            $image['url'] = '/backend/uploads/job_images/' . $image['filename'];
        }

        header('Content-Type: application/json');
        echo json_encode(['images' => $images]);

    } elseif ($method === 'DELETE') {
        // Delete a specific image
        $image_id = isset($_GET['image_id']) ? (int)$_GET['image_id'] : null;

        if (!$image_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Image ID is required']);
            exit;
        }

        // Get image details and verify ownership
        if ($entity_type === 'client') {
            // Client: verify image belongs to their organization
            $stmt = $pdo->prepare("
                SELECT ji.file_path, ji.job_id FROM job_images ji
                JOIN jobs j ON ji.job_id = j.id
                JOIN locations l ON j.client_location_id = l.id
                WHERE ji.id = ? AND l.client_id = ?
            ");
            $stmt->execute([$image_id, $entity_id]);
        } else {
            // Service Provider: verify image belongs to a job assigned to their company
            $stmt = $pdo->prepare("
                SELECT ji.file_path, ji.job_id FROM job_images ji
                JOIN jobs j ON ji.job_id = j.id
                WHERE ji.id = ? AND j.assigned_provider_id = ?
            ");
            $stmt->execute([$image_id, $entity_id]);
        }

        $image = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$image) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied. Image not found or does not belong to your organization.']);
            exit;
        }

        // Delete the physical file
        if (file_exists($image['file_path'])) {
            unlink($image['file_path']);
        }

        // Delete the database record
        $stmt = $pdo->prepare("DELETE FROM job_images WHERE id = ?");
        $stmt->execute([$image_id]);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Image deleted successfully'
        ]);

    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
