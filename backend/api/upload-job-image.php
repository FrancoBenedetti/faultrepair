<?php
require_once '../config/database.php';
require_once '../includes/JWT.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
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

// Verify user is a client
if ($entity_type !== 'client') {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied. Client access required.']);
    exit;
}

// Get job_id from form data
$job_id = isset($_POST['job_id']) ? (int)$_POST['job_id'] : null;

if (!$job_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Job ID is required']);
    exit;
}

// Verify that the job belongs to this client
$stmt = $pdo->prepare("
    SELECT j.id FROM jobs j
    JOIN locations l ON j.client_location_id = l.id
    WHERE j.id = ? AND l.client_id = ?
");
$stmt->execute([$job_id, $entity_id]);
$job = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$job) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied. Job not found or does not belong to your organization.']);
    exit;
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

// Create uploads directory if it doesn't exist
$upload_dir = '../uploads/job_images/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

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
