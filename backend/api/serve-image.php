<?php
require_once '../config/database.php';
require_once '../includes/JWT.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

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

// Get image filename from URL
$image_filename = isset($_GET['filename']) ? $_GET['filename'] : null;

if (!$image_filename) {
    http_response_code(400);
    echo json_encode(['error' => 'Image filename is required']);
    exit;
}

// Extract job_id from filename (format: job_{job_id}_{timestamp}_{uniqid}.{ext})
$filename_parts = explode('_', $image_filename);
$job_id = isset($filename_parts[1]) ? (int)$filename_parts[1] : null;

if (!$job_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid image filename format']);
    exit;
}

// Verify that the job belongs to this entity (client or service provider)
if ($entity_type === 'client') {
    // Client: verify job belongs to their organization
    $stmt = $pdo->prepare("
        SELECT j.id FROM jobs j
        JOIN locations l ON j.client_location_id = l.id
        WHERE j.id = ? AND l.participant_id = ?
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

// Verify the image exists in the database and belongs to this job
$stmt = $pdo->prepare("
    SELECT filename, mime_type, file_path
    FROM job_images
    WHERE filename = ? AND job_id = ?
");
$stmt->execute([$image_filename, $job_id]);
$image = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$image) {
    http_response_code(404);
    echo json_encode(['error' => 'Image not found or does not belong to the specified job']);
    exit;
}

// Check if the file exists on disk
$file_path = $image['file_path'];
if (!file_exists($file_path)) {
    http_response_code(404);
    echo json_encode(['error' => 'Image file not found on disk']);
    exit;
}

// Set appropriate headers
header('Content-Type: ' . $image['mime_type']);
header('Content-Length: ' . filesize($file_path));
header('Cache-Control: private, max-age=3600'); // Cache for 1 hour
header('Pragma: private');

// Output the image file
readfile($file_path);
?>
