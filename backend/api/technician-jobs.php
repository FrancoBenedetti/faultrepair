<?php
require_once '../config/database.php';
require_once '../includes/JWT.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
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

// Verify user is a technician or service provider admin
if ($entity_type !== 'service_provider' || !in_array($role_id, [3, 4])) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied. Technician or admin access required.']);
    exit;
}

// Determine technician ID
$technician_id = null;
if ($role_id === 4) {
    // Technician accessing their own jobs
    $technician_id = $user_id;
} elseif ($role_id === 3) {
    // Admin accessing technician jobs
    if (!isset($_GET['technician_id']) || !is_numeric($_GET['technician_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Technician ID is required for admin access.']);
        exit;
    }
    $technician_id = (int)$_GET['technician_id'];

    // Verify the technician belongs to the same service provider
    $stmt = $pdo->prepare("
        SELECT u.id
        FROM users u
        WHERE u.id = ? AND u.role_id = 4 AND u.entity_type = 'service_provider' AND u.entity_id = ?
    ");
    $stmt->execute([$technician_id, $entity_id]);
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['error' => 'Access denied. Technician does not belong to your service provider.']);
        exit;
    }
}

try {
    // Get technician information
    $stmt = $pdo->prepare("
        SELECT
            u.id,
            u.username,
            u.email,
            u.first_name,
            u.last_name,
            u.phone,
            u.is_active,
            CONCAT(u.first_name, ' ', u.last_name) as full_name
        FROM users u
        WHERE u.id = ? AND u.role_id = 4 AND u.entity_type = 'service_provider'
    ");
    $stmt->execute([$technician_id]);
    $technician = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$technician) {
        http_response_code(404);
        echo json_encode(['error' => 'Technician not found']);
        exit;
    }

    // Get jobs assigned to this technician
    $stmt = $pdo->prepare("
        SELECT
            j.id,
            j.item_identifier,
            j.fault_description,
            j.technician_notes,
            j.job_status,
            j.created_at,
            j.updated_at,
            l.name as location_name,
            l.address as location_address,
            c.name as client_name,
            u.username as reporting_user,
            sp.name as service_provider_name
        FROM jobs j
        JOIN locations l ON j.client_location_id = l.id
        JOIN clients c ON l.client_id = c.id
        JOIN service_providers sp ON j.assigned_provider_id = sp.id
        LEFT JOIN users u ON j.reporting_user_id = u.id
        WHERE j.assigned_technician_id = ?
        ORDER BY j.created_at DESC
    ");

    $stmt->execute([$technician_id]);
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get job status history for each job
    foreach ($jobs as &$job) {
        $stmt = $pdo->prepare("
            SELECT
                jsh.status,
                jsh.changed_at,
                u.username as changed_by
            FROM job_status_history jsh
            LEFT JOIN users u ON jsh.changed_by_user_id = u.id
            WHERE jsh.job_id = ?
            ORDER BY jsh.changed_at DESC
        ");
        $stmt->execute([$job['id']]);
        $job['status_history'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get technician statistics
    $stmt = $pdo->prepare("
        SELECT
            COUNT(*) as total_jobs,
            SUM(CASE WHEN job_status IN ('Reported', 'Assigned', 'In Progress') THEN 1 ELSE 0 END) as active_jobs,
            SUM(CASE WHEN job_status = 'Completed' THEN 1 ELSE 0 END) as completed_jobs
        FROM jobs
        WHERE assigned_technician_id = ?
    ");
    $stmt->execute([$technician_id]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'technician' => $technician,
        'jobs' => $jobs,
        'statistics' => $stats
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to retrieve technician jobs: ' . $e->getMessage()]);
}
?>
