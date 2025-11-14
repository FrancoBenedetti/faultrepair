<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

try {
    // Attempt to include the database configuration
    require_once '../config/database.php';

    // The $pdo variable should now be available from database.php
    if (isset($pdo)) {
        // To verify the connection is live, perform a simple query
        $stmt = $pdo->query("SELECT 1");
        if ($stmt) {
            echo json_encode(['status' => 'success', 'message' => 'Database connection successful.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Connection object exists, but query failed.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'database.php was included, but $pdo variable is not set.']);
    }
} catch (Throwable $e) {
    // Catch any error (including parse errors or exceptions)
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'An exception occurred.',
        'error_message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>
