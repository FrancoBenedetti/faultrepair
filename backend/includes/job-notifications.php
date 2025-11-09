<?php

class JobNotifications {

    /**
     * Sends notifications when a job's status changes.
     *
     * @param int $job_id The ID of the job that was updated.
     * @param string $old_status The previous status of the job.
     * @param string $new_status The new status of the job.
     * @param int $changed_by_user_id The ID of the user who made the change.
     * @param string|null $notes Optional notes included with the status change.
     */
    public static function notifyJobStatusChange($job_id, $old_status, $new_status, $changed_by_user_id, $notes = null) {
        global $pdo;

        // Notify Service Provider Admins on new assignment or quote request
        if (($new_status === 'Assigned' || $new_status === 'Quote Requested') && $old_status !== $new_status) {
            try {
                // 1. Get Job, Client, and Provider details
                $stmt = $pdo->prepare("
                    SELECT
                        j.item_identifier,
                        j.fault_description,
                        j.assigned_provider_participant_id,
                        c.name as client_name
                    FROM jobs j
                    JOIN participants c ON j.client_id = c.participantId
                    WHERE j.id = ?
                ");
                $stmt->execute([$job_id]);
                $job_details = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$job_details || !$job_details['assigned_provider_participant_id']) {
                    error_log("JobNotifications: Could not find job details or provider for job ID: $job_id");
                    return;
                }

                $provider_id = $job_details['assigned_provider_participant_id'];
                $client_name = $job_details['client_name'];
                $item_identifier = $job_details['item_identifier'] ?: 'N/A';

                // 2. Get all admin users (role_id = 3) for the service provider
                $stmt = $pdo->prepare("
                    SELECT u.email, u.first_name
                    FROM users u
                    WHERE u.entity_id = ? AND u.role_id = 3 AND u.is_active = 1
                ");
                $stmt->execute([$provider_id]);
                $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (empty($admins)) {
                    error_log("JobNotifications: No active admin users found for provider ID: $provider_id");
                    return;
                }

                // 3. Construct email subject and body
                $subject_action = ($new_status === 'Assigned') ? 'New Job Assigned' : 'New Quote Request';
                $subject = "Snappy: $subject_action - Job #$job_id for $item_identifier";

                // Define the base URL for the frontend application
                // In a real application, this should come from a config file.
                $base_url = 'http://' . ($_SERVER['HTTP_HOST'] ?? 'fault-reporter.local');
                $job_link = "$base_url/jobs/$job_id/edit?from=service-provider";

                $email_body = self::buildEmailTemplate($subject_action, $job_id, $client_name, $item_identifier, $job_details['fault_description'], $job_link);

                // 4. Send email to each admin
                $headers = "MIME-Version: 1.0" . "\r\n";
                $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                $headers .= 'From: Snappy Notifier <noreply@snappy.local>' . "\r\n";

                foreach ($admins as $admin) {
                    $to = $admin['email'];
                    if (!mail($to, $subject, $email_body, $headers)) {
                        error_log("JobNotifications: Failed to send email to $to for job ID: $job_id");
                    } else {
                        error_log("JobNotifications: Email sent to $to for job ID: $job_id");
                    }
                }

            } catch (Exception $e) {
                error_log("JobNotifications: Error sending notification for job ID $job_id: " . $e->getMessage());
            }
        }

        // ... other notification logic for different status changes can be added here ...
    }

    /**
     * Builds a professional HTML email template.
     */
    private static function buildEmailTemplate($action_title, $job_id, $client_name, $item, $description, $link) {
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Snappy Job Notification</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; color: #333; margin: 0; padding: 20px; }
        .container { background-color: #ffffff; border-radius: 8px; padding: 30px; max-width: 600px; margin: auto; border: 1px solid #ddd; }
        .header { font-size: 24px; font-weight: bold; color: #007bff; margin-bottom: 20px; }
        .job-details { margin-bottom: 25px; }
        .job-details p { margin: 5px 0; line-height: 1.6; }
        .job-details strong { color: #555; }
        .action-button { display: inline-block; background-color: #007bff; color: #ffffff; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; margin-top: 20px; }
        .footer { margin-top: 30px; font-size: 12px; color: #888; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">{$action_title}</div>
        <p>You have received a new service request from <strong>{$client_name}</strong>.</p>
        <div class="job-details">
            <p><strong>Job ID:</strong> #{$job_id}</p>
            <p><strong>Item:</strong> {$item}</p>
            <p><strong>Description:</strong></p>
            <p>{$description}</p>
        </div>
        <p>Please review the details and take the necessary action.</p>
        <a href="{$link}" class="action-button">View Job Details</a>
    </div>
    <div class="footer">
        This is an automated notification from the Snappy platform.
    </div>
</body>
</html>
HTML;
        return $html;
    }
}

?>