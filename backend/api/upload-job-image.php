<?php
ini_set('log_errors', true);
ini_set('error_log', $_SERVER['DOCUMENT_ROOT'].'/all-logs/api.upload-job-image.log');
require_once '../config/database.php';
require_once '../includes/JWT.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Debug: Log the method and basic info
error_log(__FILE__ . ' - Request received: ' . $_SERVER['REQUEST_METHOD'] . ', Content-Type: ' . ($_SERVER['CONTENT_TYPE'] ?? 'none'));

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    error_log(__FILE__ . ' - Handling OPTIONS request');
    exit(0);
}

$requestMethod = $_SERVER['REQUEST_METHOD'];
if ($requestMethod !== 'POST') {
    error_log(__FILE__ . ' - Rejecting non-POST request: ' . $requestMethod);
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Received: ' . $requestMethod]);
    exit;
}

error_log(__FILE__ . ' - POST request accepted, proceeding with authentication');

// JWT Authentication - check both headers and form data for live server compatibility
$token = null;

// Try to get token from Authorization header first
$headers = getallheaders();
$auth_header = isset($headers['Authorization']) ? $headers['Authorization'] : '';
if ($auth_header && preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
    $token = $matches[1];
}

// If no token in header, try form data or query parameter
if (!$token) {
    $token = $_POST['token'] ?? $_GET['token'] ?? null;
}

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
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid token']);
    exit;
}

// Verify user is a client OR service provider
if (!in_array($entity_type, ['client', 'service_provider'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied. Only clients and service providers can upload images.']);
    exit;
}

// Get job_id from form data
$job_id = isset($_POST['job_id']) ? (int)$_POST['job_id'] : null;
error_log(__FILE__.'/'.__LINE__.'/ >>>> '.json_encode($job_id));
if (!$job_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Job ID is required']);
    exit;
}

// Verify that the job belongs to this entity (client or service provider)
if ($entity_type === 'client') {
    // Client: verify job belongs to their organization
    $stmt = $pdo->prepare("
        SELECT j.id FROM jobs j
        WHERE j.id = ? AND j.client_id = ?
    ");
    $stmt->execute([$job_id, $entity_id]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$job) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied. Job not found or does not belong to your organization.']);
        exit;
    }
} else if ($entity_type === 'service_provider') {
    // Service provider: verify job is assigned to them
    $stmt = $pdo->prepare("
        SELECT j.id FROM jobs j
        WHERE j.id = ? AND j.assigned_provider_participant_id = ?
    ");
    $stmt->execute([$job_id, $entity_id]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$job) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied. Job not found or is not assigned to your organization.']);
        exit;
    }
}

// Check if file was uploaded
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'No image file uploaded or upload error occurred']);
    exit;
}

$file = $_FILES['image'];

// Validate file type
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($file['type'], $allowed_types)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid file type. Only JPEG, PNG, GIF, and WebP images are allowed.']);
    exit;
}

// Validate file size (max 10MB)
$max_size = 10 * 1024 * 1024; // 10MB
if ($file['size'] > $max_size) {
    http_response_code(400);
    echo json_encode(['error' => 'File too large. Maximum size is 10MB.']);
    exit;
}

// Create uploads directory if it doesn't exist (use absolute path from document root)
$upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/job_images/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
    error_log(__FILE__ . ' - Created upload directory: ' . realpath($upload_dir));
}
error_log(__FILE__ . ' - Upload directory: ' . realpath($upload_dir) . ', is_writable: ' . (is_writable($upload_dir) ? 'yes' : 'no'));
error_log(__FILE__ . ' - Temp file: ' . $file['tmp_name'] . ', exists: ' . (file_exists($file['tmp_name']) ? 'yes' : 'no'));

// Generate unique filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = uniqid('job_' . $job_id . '_', true) . '.' . $extension;
$file_path = $upload_dir . $filename;

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $file_path)) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save uploaded file']);
    exit;
}

try {
    // Insert image record into database
    $stmt = $pdo->prepare("
        INSERT INTO job_images (job_id, filename, original_filename, file_path, file_size, mime_type, uploaded_by)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $job_id,
        $filename,
        $file['name'],
        $file_path,
        $file['size'],
        $file['type'],
        $user_id
    ]);

    $image_id = $pdo->lastInsertId();

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Image uploaded successfully',
        'image' => [
            'id' => $image_id,
            'filename' => $filename,
            'original_filename' => $file['name'],
            'file_path' => $file_path,
            'file_size' => $file['size'],
            'mime_type' => $file['type'],
            'uploaded_by' => $user_id,
            'uploaded_at' => date('Y-m-d H:i:s')
        ]
    ]);

} catch (Exception $e) {
    // Clean up uploaded file if database insert fails
    if (file_exists($file_path)) {
        unlink($file_path);
    }

    http_response_code(500);
    echo json_encode(['error' => 'Failed to save image record: ' . $e->getMessage()]);
}
?>
