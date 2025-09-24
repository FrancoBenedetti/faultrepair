<?php
// Simple router for development server
$requestUri = $_SERVER['REQUEST_URI'];

// Remove query string
$path = parse_url($requestUri, PHP_URL_PATH);

// Remove leading slash
$path = ltrim($path, '/');

// Handle API routes
if (strpos($path, 'api/') === 0) {
    $apiPath = '../' . $path;
    if (file_exists($apiPath)) {
        include $apiPath;
        exit;
    }
}

// Handle uploads
if (strpos($path, 'uploads/') === 0) {
    $uploadPath = '../' . $path;
    if (file_exists($uploadPath)) {
        // Serve the file
        $mime = mime_content_type($uploadPath);
        header('Content-Type: ' . $mime);
        readfile($uploadPath);
        exit;
    }
}

// Default response
http_response_code(404);
echo json_encode(['error' => 'Not found']);
?>
