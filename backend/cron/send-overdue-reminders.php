<?php
/**
 * Cron Job: Send overdue job reminders
 * Run daily (e.g., every morning at 9 AM)
 * Command: php /path/to/backend/cron/send-overdue-reminders.php
 */

ini_set('log_errors', true);
ini_set('error_log', $_SERVER['DOCUMENT_ROOT'].'/all-logs/cron-overdue-reminders.log');

// Include required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/job-notifications.php';

try {
    // Log the start of the cron job
    error_log("[" . date('Y-m-d H:i:s') . "] Starting overdue job reminder cron job...");

    // Call the notification function
    $result = JobNotifications::notifyOverdueJobs();

    if ($result === true) {
        error_log("[" . date('Y-m-d H:i:s') . "] Overdue job reminders sent successfully");
        echo "SUCCESS: Overdue job reminders processed\n";
    } else {
        error_log("[" . date('Y-m-d H:i:s') . "] Error sending overdue job reminders");
        echo "ERROR: Failed to send overdue job reminders\n";
        exit(1);
    }

} catch (Exception $e) {
    error_log("[" . date('Y-m-d H:i:s') . "] Exception in overdue reminders cron job: " . $e->getMessage());
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>
