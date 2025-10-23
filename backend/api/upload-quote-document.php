<?php
require_once '../config/database.php';
require_once '../includes/JWT.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// JWT Authentication - Read from query parameter for live server compatibility
$token = $_GET['token'] ?? $_POST['token'] ?? '';

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

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    exit(0);
}

if ($method === 'POST') {
    // Handle quote document upload

    // Debug: Log all incoming data for troubleshooting
    error_log('PDF Upload Debug - REQUEST_METHOD: ' . $_SERVER['REQUEST_METHOD']);
    error_log('PDF Upload Debug - FILES: ' . json_encode($_FILES));
    error_log('PDF Upload Debug - POST: ' . json_encode($_POST));
    error_log('PDF Upload Debug - entity_type: ' . $entity_type . ', role_id: ' . $role_id);

    // Validate user permissions (service provider admins only for quote uploads)
    if ($entity_type !== 'service_provider' || $role_id !== 3) {
        error_log('PDF Upload Debug - Authorization failed: entity_type=' . $entity_type . ', role_id=' . $role_id);
        http_response_code(403);
        echo json_encode([
            'error' => 'Access denied. Service provider admin access required.',
            'debug_authorization' => ['entity_type' => $entity_type, 'role_id' => $role_id]
        ]);
        exit;
    }
    error_log('PDF Upload Debug - Authorization passed for user: ' . $user_id);

    // Check if file was uploaded
    if (!isset($_FILES['quote_document']) || !is_uploaded_file($_FILES['quote_document']['tmp_name'])) {
        error_log('PDF Upload Debug - File validation failed: isset=' . (isset($_FILES['quote_document']) ? 'yes' : 'no') . ', is_uploaded=' . (is_uploaded_file($_FILES['quote_document']['tmp_name'] ?? '') ? 'yes' : 'no'));
        http_response_code(400);
        echo json_encode([
            'error' => 'No quote document file provided',
            'debug_files' => $_FILES
        ]);
        exit;
    }
    error_log('PDF Upload Debug - File validation passed');

    // Validate quote_id parameter
    $quote_id = isset($_POST['quote_id']) ? (int)$_POST['quote_id'] : null;
    if (!$quote_id) {
        error_log('PDF Upload Debug - Quote ID validation failed: POST_quote_id=' . ($_POST['quote_id'] ?? 'null'));
        http_response_code(400);
        echo json_encode([
            'error' => 'Quote ID is required',
            'debug_quote_id' => ['post_quote_id' => $_POST['quote_id'] ?? null, 'parsed' => $quote_id]
        ]);
        exit;
    }
    error_log('PDF Upload Debug - Quote ID validation passed: ' . $quote_id);

    // Verify user owns the quote
    $stmt = $pdo->prepare("
        SELECT jq.id FROM job_quotations jq
        WHERE jq.id = ? AND jq.provider_participant_id = ?
    ");
    $stmt->execute([$quote_id, $entity_id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Quote not found or access denied']);
        exit;
    }

    $file = $_FILES['quote_document'];
    $file_error = $file['error'];
    $file_size = $file['size'];
    $file_tmp = $file['tmp_name'];
    $file_name = $file['name'];
    $file_type = $file['type'];

    // File upload error checks
    if ($file_error !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['error' => 'File upload error: ' . $file_error]);
        exit;
    }

    // File size validation (5MB max)
    $max_size = 5 * 1024 * 1024; // 5MB
    if ($file_size > $max_size) {
        http_response_code(400);
        echo json_encode(['error' => 'File size exceeds 5MB limit']);
        exit;
    }

    // File type validation (PDF only)
    $allowed_types = ['application/pdf'];
    if (!in_array($file_type, $allowed_types)) {
        http_response_code(400);
        echo json_encode(['error' => 'Only PDF files are allowed']);
        exit;
    }

    // Validate file extension
    $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    if ($file_extension !== 'pdf') {
        http_response_code(400);
        echo json_encode(['error' => 'File must have .pdf extension']);
        exit;
    }

    // Ensure secure filename
    $secure_filename = 'quote_' . $quote_id . '_' . time() . '_' . uniqid() . '.pdf';
    $upload_dir = __DIR__ . '/../../uploads/quotes/';
    $upload_path = $upload_dir . $secure_filename;

    // Ensure upload directory exists
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create upload directory']);
            exit;
        }
    }

    // Verify the upload directory is writable
    if (!is_writable($upload_dir)) {
        http_response_code(500);
        echo json_encode(['error' => 'Upload directory is not writable']);
        exit;
    }

    // Check database connection
    if (!$pdo) {
        error_log('PDF Upload Debug - Database connection is null');
        http_response_code(500);
        echo json_encode(['error' => 'Database connection error']);
        exit;
    }

    // Debug information
    $debug_info = [
        'upload_dir' => $upload_dir,
        'upload_path' => $upload_path,
        'tmp_file_exists' => file_exists($file_tmp),
        'upload_dir_writable' => is_writable($upload_dir),
        'upload_dir_exists' => is_dir($upload_dir)
    ];

    // Move uploaded file
    if (!move_uploaded_file($file_tmp, $upload_path)) {
        $move_error = error_get_last();
        error_log('PDF Upload Debug - move_uploaded_file failed: ' . json_encode($move_error));
        http_response_code(500);
        echo json_encode([
            'error' => 'Failed to save uploaded file',
            'debug_info' => $debug_info,
            'move_error' => $move_error,
            'target_exists_after_fail' => file_exists($upload_path)
        ]);
        exit;
    }

    // Verify file was actually saved
    if (!file_exists($upload_path)) {
        http_response_code(500);
        echo json_encode([
            'error' => 'File was not saved to target location',
            'debug_info' => $debug_info
        ]);
        exit;
    }

    // Store relative path in database (without full directory path for security)
    $relative_path = 'quotes/' . $secure_filename;

    try {
        // Update quote with document path
        $stmt = $pdo->prepare("
            UPDATE job_quotations
            SET quotation_document_url = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $result = $stmt->execute([$relative_path, $quote_id]);
        error_log('PDF Upload Debug - Quote update result: ' . ($result ? 'success' : 'failed'));

        // Insert quotation history (using valid enum value)
        $stmt = $pdo->prepare("
            INSERT INTO job_quotation_history (quotation_id, action, changed_by_user_id, notes, created_at)
            VALUES (?, 'updated', ?, ?, NOW())
        ");
        $history_result = $stmt->execute([$quote_id, $user_id, 'PDF document uploaded: ' . $file_name]);
        error_log('PDF Upload Debug - History insert result: ' . ($history_result ? 'success' : 'failed'));

        $response_data = [
            'success' => true,
            'message' => 'Quote document uploaded successfully',
            'document_url' => $relative_path,
            'filename' => $file_name,
            'filesize' => $file_size,
            'quote_id' => $quote_id
        ];

        error_log('PDF Upload Debug - About to return success response');
        echo json_encode($response_data);

    } catch (Exception $e) {
        error_log('PDF Upload Debug - Database error: ' . $e->getMessage());
        error_log('PDF Upload Debug - Stack trace: ' . $e->getTraceAsString());
        http_response_code(500);
        echo json_encode([
            'error' => 'Database error during file processing',
            'debug_error' => $e->getMessage()
        ]);
    } catch (PDOException $e) {
        error_log('PDF Upload Debug - PDO error: ' . $e->getMessage());
        error_log('PDF Upload Debug - PDO Stack trace: ' . $e->getTraceAsString());
        http_response_code(500);
        echo json_encode([
            'error' => 'Database connection error during file processing',
            'debug_error' => $e->getMessage()
        ]);
    }

} elseif ($method === 'GET') {
    // Handle serving quote documents

    // Validate document path parameter
    $document_path = isset($_GET['path']) ? $_GET['path'] : null;
    if (!$document_path) {
        http_response_code(400);
        echo json_encode(['error' => 'Document path is required']);
        exit;
    }

    // Sanitize path to prevent directory traversal
    $document_path = str_replace(['../', '..\\', '..'], '', $document_path);

    // Ensure path starts with quotes/ for security
    if (!str_starts_with($document_path, 'quotes/') || !str_ends_with($document_path, '.pdf')) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid document path']);
        exit;
    }

    $file_path = __DIR__ . '/../../uploads/' . $document_path;

    // Check if file exists
    if (!file_exists($file_path)) {
        http_response_code(404);
        echo json_encode(['error' => 'Document not found']);
        exit;
    }

    // Verify user has access to this quote document
    // Extract quote_id from filename (quote_{id}_{timestamp}_{uniqid}.pdf)
    $path_parts = explode('/', $document_path);
    $filename = end($path_parts);
    $filename_parts = explode('_', $filename);

    if (count($filename_parts) >= 2) {
        $quote_id = (int)$filename_parts[1];

        // Check if user has access to this quote
        $access_check = false;

        if ($entity_type === 'service_provider') {
            // Service provider can access their own quotes
            $stmt = $pdo->prepare("
                SELECT 1 FROM job_quotations
                WHERE id = ? AND provider_participant_id = ?
            ");
            $stmt->execute([$quote_id, $entity_id]);
            $access_check = $stmt->fetch() !== false;
        } elseif ($entity_type === 'client') {
            // Client can access quotes for their jobs
            $stmt = $pdo->prepare("
                SELECT 1 FROM job_quotations jq
                JOIN jobs j ON jq.job_id = j.id
                JOIN locations l ON j.client_location_id = l.id
                WHERE jq.id = ? AND l.participant_id = ?
            ");
            $stmt->execute([$quote_id, $entity_id]);
            $access_check = $stmt->fetch() !== false;
        }

        if (!$access_check) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied to this document']);
            exit;
        }
    } else {
        // Fallback: deny access if filename format is unexpected
        http_response_code(403);
        echo json_encode(['error' => 'Invalid document format']);
        exit;
    }

    // Serve the PDF file
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . basename($document_path) . '"');
    header('Content-Length: ' . filesize($file_path));
    header('Cache-Control: private, max-age=3600'); // Cache for 1 hour

    // Security headers to prevent iframe embedding etc.
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');

    readfile($file_path);

} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}

?>
