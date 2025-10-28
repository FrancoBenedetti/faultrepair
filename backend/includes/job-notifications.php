<?php
/**
 * Job Notification System - Automated emails for job status changes
 */

require_once __DIR__ . '/email.php';

class JobNotifications {

    /**
     * Send notifications for job status changes
     */
    public static function notifyJobStatusChange($jobId, $oldStatus, $newStatus, $changedByUserId, $notes = null) {
        try {
            $pdo = $GLOBALS['pdo'];
            $job = self::getJobDetails($jobId, $pdo);

            if (!$job) {
                error_log("JobNotifications: Job $jobId not found");
                return false;
            }

            // Notify client users depending on status change
            if ($newStatus === 'Reported') {
                self::notifyNewJob($job);
            } elseif ($newStatus === 'Assigned') {
                self::notifyJobAssigned($job);
            } elseif ($newStatus === 'In Progress') {
                self::notifyJobStarted($job);
            } elseif ($newStatus === 'Completed') {
                self::notifyJobCompleted($job);
            } elseif ($newStatus === 'Confirmed') {
                self::notifyJobConfirmed($job);
            } elseif ($newStatus === 'Cannot repair') {
                self::notifyCannotRepair($job, $notes);
            } elseif ($newStatus === 'Incomplete') {
                self::notifyJobIncomplete($job, $notes);
            } elseif ($newStatus === 'Rejected') {
                self::notifyJobRejected($job, $notes);
            } elseif ($newStatus === 'Quote Requested') {
                self::notifyQuoteRequested($job);
            }

        } catch (Exception $e) {
            error_log("JobNotifications: Error in notifyJobStatusChange: " . $e->getMessage());
            return false;
        }
        return true;
    }

    /**
     * Notify quote response actions
     */
    public static function notifyQuoteResponse($quoteId, $action, $notes = null) {
        try {
            $pdo = $GLOBALS['pdo'];
            $quoteData = self::getQuoteDetails($quoteId, $pdo);

            if (!$quoteData) {
                error_log("JobNotifications: Quote $quoteId not found");
                return false;
            }

            if ($action === 'accepted') {
                self::notifyQuoteAccepted($quoteData);
            } elseif ($action === 'rejected') {
                self::notifyQuoteRejected($quoteData, $notes);
            } elseif ($action === 'request_revision') {
                self::notifyQuoteRevisionRequested($quoteData, $notes);
            }

        } catch (Exception $e) {
            error_log("JobNotifications: Error in notifyQuoteResponse: " . $e->getMessage());
            return false;
        }
        return true;
    }

    /**
     * Notify about overdue jobs (to be called by cron job)
     */
    public static function notifyOverdueJobs() {
        try {
            $pdo = $GLOBALS['pdo'];

            // Find jobs that are overdue (no activity for 7 days on active jobs)
            $stmt = $pdo->prepare("
                SELECT j.id, j.item_identifier, j.job_status, j.created_at, j.updated_at,
                       c.name as client_name, u.first_name, u.last_name,
                       u.email as reporting_user_email
                FROM jobs j
                JOIN participants c ON j.client_id = c.participantId
                LEFT JOIN users u ON j.reporting_user_id = u.userId
                WHERE j.job_status NOT IN ('Confirmed', 'Rejected')
                AND j.updated_at < DATE_SUB(NOW(), INTERVAL 7 DAY)
                AND j.archived_by_client = 0
            ");
            $stmt->execute();
            $overdueJobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($overdueJobs as $job) {
                self::notifyOverdueJob($job);
            }

        } catch (Exception $e) {
            error_log("JobNotifications: Error in notifyOverdueJobs: " . $e->getMessage());
            return false;
        }
        return true;
    }

    private static function getJobDetails($jobId, $pdo) {
        $stmt = $pdo->prepare("
            SELECT j.*, c.name as client_name, c.email as client_email,
                   sp.name as provider_name,
                   u.first_name, u.last_name, u.email as reporting_user_email,
                   p.name as client_org_name
            FROM jobs j
            LEFT JOIN participants c ON j.client_id = c.participantId
            LEFT JOIN participants sp ON j.assigned_provider_participant_id = sp.participantId
            LEFT JOIN users u ON j.reporting_user_id = u.userId
            LEFT JOIN participants p ON j.client_id = p.participantId
            WHERE j.id = ?
        ");
        $stmt->execute([$jobId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private static function getQuoteDetails($quoteId, $pdo) {
        $stmt = $pdo->prepare("
            SELECT jq.*, j.item_identifier, j.fault_description,
                   c.name as client_name, c.email as client_email,
                   sp.name as provider_name
            FROM job_quotations jq
            JOIN jobs j ON jq.job_id = j.id
            JOIN participants c ON j.client_id = c.participantId
            LEFT JOIN participants sp ON jq.provider_participant_id = sp.participantId
            WHERE jq.id = ?
        ");
        $stmt->execute([$quoteId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private static function notifyNewJob($job) {
        // Notify service providers approved for this client (if any)
        $pdo = $GLOBALS['pdo'];
        $stmt = $pdo->prepare("
            SELECT pa.provider_participant_id, p.name, p.email,
                   u.first_name, u.last_name, u.email as contact_email
            FROM participant_approvals pa
            JOIN participants p ON pa.provider_participant_id = p.participantId
            LEFT JOIN users u ON p.contact_user_id = u.userId
            WHERE pa.client_participant_id = ? AND pa.status = 'approved'
        ");
        $stmt->execute([$job['client_id']]);
        $providers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($providers as $provider) {
            $subject = "New Service Request: {$job['item_identifier']}";
            $message = self::getNewJobEmailBody($job, $provider);
            EmailService::sendNotificationEmail($provider['contact_email'] ?: $provider['email'], $subject, $message);
        }
    }

    private static function notifyJobAssigned($job) {
        // Notify assigned technician
        $pdo = $GLOBALS['pdo'];
        if ($job['assigned_technician_user_id']) {
            $stmt = $pdo->prepare("SELECT email, first_name, last_name FROM users WHERE userId = ?");
            $stmt->execute([$job['assigned_technician_user_id']]);
            $technician = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($technician) {
                $subject = "Job Assigned: {$job['item_identifier']}";
                $message = self::getJobAssignedEmailBody($job, $technician);
                EmailService::sendNotificationEmail($technician['email'], $subject, $message);
            }
        }

        // Notify assigned provider (if has contact)
        if ($job['assigned_provider_participant_id']) {
            $stmt = $pdo->prepare("
                SELECT p.email, u.email as contact_email, u.first_name, u.last_name
                FROM participants p
                LEFT JOIN users u ON p.contact_user_id = u.userId
                WHERE p.participantId = ?
            ");
            $stmt->execute([$job['assigned_provider_participant_id']]);
            $provider = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($provider) {
                $subject = "Job Assigned to Your Company: {$job['item_identifier']}";
                $message = self::getProviderJobAssignedEmailBody($job, $provider);
                EmailService::sendNotificationEmail($provider['contact_email'] ?: $provider['email'], $subject, $message);
            }
        }
    }

    private static function notifyJobStarted($job) {
        // Notify client about job starting
        if ($job['reporting_user_email']) {
            $subject = "Service Started: {$job['item_identifier']}";
            $message = self::getJobStartedEmailBody($job);
            EmailService::sendNotificationEmail($job['reporting_user_email'], $subject, $message);
        }
    }

    private static function notifyJobCompleted($job) {
        // Notify client that job is completed and ready for confirmation
        if ($job['reporting_user_email']) {
            $subject = "Service Completed: {$job['item_identifier']}";
            $message = self::getJobCompletedEmailBody($job);
            EmailService::sendNotificationEmail($job['reporting_user_email'], $subject, $message);
        }
    }

    private static function notifyJobConfirmed($job) {
        // Notify service provider that client confirmed the work
        if ($job['assigned_provider_participant_id']) {
            $pdo = $GLOBALS['pdo'];
            $stmt = $pdo->prepare("
                SELECT p.email, u.email as contact_email, u.first_name, u.last_name
                FROM participants p
                LEFT JOIN users u ON p.contact_user_id = u.userId
                WHERE p.participantId = ?
            ");
            $stmt->execute([$job['assigned_provider_participant_id']]);
            $provider = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($provider) {
                $subject = "Job Confirmed: {$job['item_identifier']}";
                $message = self::getJobConfirmedEmailBody($job, $provider);
                EmailService::sendNotificationEmail($provider['contact_email'] ?: $provider['email'], $subject, $message);
            }
        }
    }

    private static function notifyCannotRepair($job, $reason) {
        // Notify client about cannot repair status
        if ($job['reporting_user_email']) {
            $subject = "Cannot Repair: {$job['item_identifier']}";
            $message = self::getCannotRepairEmailBody($job, $reason);
            EmailService::sendNotificationEmail($job['reporting_user_email'], $subject, $message);
        }
    }

    private static function notifyJobIncomplete($job, $reason) {
        // Notify service provider about job being returned for rework
        if ($job['assigned_provider_participant_id']) {
            $pdo = $GLOBALS['pdo'];
            $stmt = $pdo->prepare("
                SELECT p.email, u.email as contact_email, u.first_name, u.last_name
                FROM participants p
                LEFT JOIN users u ON p.contact_user_id = u.userId
                WHERE p.participantId = ?
            ");
            $stmt->execute([$job['assigned_provider_participant_id']]);
            $provider = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($provider) {
                $subject = "Job Returned for Rework: {$job['item_identifier']}";
                $message = self::getJobIncompleteEmailBody($job, $provider, $reason);
                EmailService::sendNotificationEmail($provider['contact_email'] ?: $provider['email'], $subject, $message);
            }
        }
    }

    private static function notifyJobRejected($job, $reason) {
        // Notify service provider about job rejection
        if ($job['assigned_provider_participant_id']) {
            $pdo = $GLOBALS['pdo'];
            $stmt = $pdo->prepare("
                SELECT p.email, u.email as contact_email, u.first_name, u.last_name
                FROM participants p
                LEFT JOIN users u ON p.contact_user_id = u.userId
                WHERE p.participantId = ?
            ");
            $stmt->execute([$job['assigned_provider_participant_id']]);
            $provider = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($provider) {
                $subject = "Job Rejected: {$job['item_identifier']}";
                $message = self::getJobRejectedEmailBody($job, $provider, $reason);
                EmailService::sendNotificationEmail($provider['contact_email'] ?: $provider['email'], $subject, $message);
            }
        }
    }

    private static function notifyQuoteRequested($job) {
        // Notify assigned provider to submit quote
        if ($job['assigned_provider_participant_id']) {
            $pdo = $GLOBALS['pdo'];
            $stmt = $pdo->prepare("
                SELECT p.email, u.email as contact_email, u.first_name, u.last_name
                FROM participants p
                LEFT JOIN users u ON p.contact_user_id = u.userId
                WHERE p.participantId = ?
            ");
            $stmt->execute([$job['assigned_provider_participant_id']]);
            $provider = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($provider) {
                $subject = "Quote Request: {$job['item_identifier']}";
                $message = self::getQuoteRequestedEmailBody($job, $provider);
                EmailService::sendNotificationEmail($provider['contact_email'] ?: $provider['email'], $subject, $message);
            }
        }
    }

    private static function notifyQuoteAccepted($quoteData) {
        // Notify provider that quote was accepted
        if ($quoteData['provider_participant_id']) {
            $pdo = $GLOBALS['pdo'];
            $stmt = $pdo->prepare("
                SELECT p.email, u.email as contact_email, u.first_name, u.last_name
                FROM participants p
                LEFT JOIN users u ON p.contact_user_id = u.userId
                WHERE p.participantId = ?
            ");
            $stmt->execute([$quoteData['provider_participant_id']]);
            $provider = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($provider) {
                $subject = "Quote Accepted: {$quoteData['item_identifier']}";
                $message = self::getQuoteAcceptedEmailBody($quoteData, $provider);
                EmailService::sendNotificationEmail($provider['contact_email'] ?: $provider['email'], $subject, $message);
            }
        }
    }

    private static function notifyQuoteRejected($quoteData, $reason) {
        // Notify provider that quote was rejected
        if ($quoteData['provider_participant_id']) {
            $pdo = $GLOBALS['pdo'];
            $stmt = $pdo->prepare("
                SELECT p.email, u.email as contact_email, u.first_name, u.last_name
                FROM participants p
                LEFT JOIN users u ON p.contact_user_id = u.userId
                WHERE p.participantId = ?
            ");
            $stmt->execute([$quoteData['provider_participant_id']]);
            $provider = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($provider) {
                $subject = "Quote Rejected: {$quoteData['item_identifier']}";
                $message = self::getQuoteRejectedEmailBody($quoteData, $provider, $reason);
                EmailService::sendNotificationEmail($provider['contact_email'] ?: $provider['email'], $subject, $message);
            }
        }
    }

    private static function notifyQuoteRevisionRequested($quoteData, $reason) {
        // Notify provider about revision request
        if ($quoteData['provider_participant_id']) {
            $pdo = $GLOBALS['pdo'];
            $stmt = $pdo->prepare("
                SELECT p.email, u.email as contact_email, u.first_name, u.last_name
                FROM participants p
                LEFT JOIN users u ON p.contact_user_id = u.userId
                WHERE p.participantId = ?
            ");
            $stmt->execute([$quoteData['provider_participant_id']]);
            $provider = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($provider) {
                $subject = "Quote Revision Requested: {$quoteData['item_identifier']}";
                $message = self::getQuoteRevisionEmailBody($quoteData, $provider, $reason);
                EmailService::sendNotificationEmail($provider['contact_email'] ?: $provider['email'], $subject, $message);
            }
        }
    }

    private static function notifyOverdueJob($job) {
        // Send reminder to assigned provider and client
        $daysSinceUpdate = floor((time() - strtotime($job['updated_at'])) / (60 * 60 * 24));

        // Notify client
        if ($job['reporting_user_email']) {
            $subject = "Overdue Job Reminder: {$job['item_identifier']}";
            $message = self::getOverdueJobEmailBody($job, 'client', $daysSinceUpdate);
            EmailService::sendNotificationEmail($job['reporting_user_email'], $subject, $message);
        }

        // Notify provider
        if ($job['assigned_provider_participant_id']) {
            $pdo = $GLOBALS['pdo'];
            $stmt = $pdo->prepare("
                SELECT p.email, u.email as contact_email
                FROM participants p
                LEFT JOIN users u ON p.contact_user_id = u.userId
                WHERE p.participantId = ?
            ");
            $stmt->execute([$job['assigned_provider_participant_id']]);
            $provider = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($provider) {
                $subject = "Overdue Job Reminder: {$job['item_identifier']}";
                $message = self::getOverdueJobEmailBody($job, 'provider', $daysSinceUpdate);
                EmailService::sendNotificationEmail($provider['contact_email'] ?: $provider['email'], $subject, $message);
            }
        }
    }

    // Email body template methods
    private static function getNewJobEmailBody($job, $provider) {
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <h2 style='color: #007bff;'>New Service Request</h2>
            <p>Dear {$provider['name']} Team,</p>
            <p>A new service request has been submitted by <strong>{$job['client_org_name']}</strong>:</p>

            <div style='background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0;'>
                <strong>Item:</strong> {$job['item_identifier']}<br>
                <strong>Description:</strong> {$job['fault_description']}<br>
                <strong>Reported by:</strong> " . trim($job['first_name'] . ' ' . $job['last_name']) . "<br>
                <strong>Reported on:</strong> " . date('M j, Y \a\t g:i A', strtotime($job['created_at'])) . "
            </div>

            <p>Please log into the fault reporting system to view the full details and provide a quote or accept the assignment.</p>

            <div style='background: #e3f2fd; border-left: 4px solid #2196f3; padding: 10px; margin: 15px 0;'>
                <p style='margin: 0;'><strong>Next Steps:</strong></p>
                <ul style='margin: 10px 0 0 20px;'>
                    <li>Review the service request details</li>
                    <li>Upload any additional images if available</li>
                    <li>Accept assignment or provide a quote</li>
                </ul>
            </div>
        </div>
        ";
    }

    private static function getJobAssignedEmailBody($job, $technician) {
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <h2 style='color: #28a745;'>Job Assigned to You</h2>
            <p>Hi " . trim($technician['first_name'] . ' ' . $technician['last_name']) . ",</p>
            <p>You have been assigned to work on this service request:</p>

            <div style='background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0;'>
                <strong>Item:</strong> {$job['item_identifier']}<br>
                <strong>Description:</strong> {$job['fault_description']}<br>
                <strong>Client:</strong> {$job['client_org_name']}<br>
                <strong>Assigned on:</strong> " . date('M j, Y \a\t g:i A') . "
            </div>

            <p>Please log into the fault reporting system to start working on this job and update its status when completed.</p>
        </div>
        ";
    }

    private static function getProviderJobAssignedEmailBody($job, $provider) {
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <h2 style='color: #007bff;'>Job Assigned to Your Company</h2>
            <p>Dear {$provider['first_name']} {$provider['last_name']},</p>
            <p>A job has been assigned to your service provider company:</p>

            <div style='background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0;'>
                <strong>Item:</strong> {$job['item_identifier']}<br>
                <strong>Description:</strong> {$job['fault_description']}<br>
                <strong>Client:</strong> {$job['client_org_name']}<br>
                <strong>Assigned on:</strong> " . date('M j, Y \a\t g:i A') . "
            </div>

            <p>Please assign a technician to this job and update the status as work progresses.</p>
        </div>
        ";
    }

    private static function getJobStartedEmailBody($job) {
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <h2 style='color: #17a2b8;'>Service Work Started</h2>
            <p>Dear " . trim($job['first_name'] . ' ' . $job['last_name']) . ",</p>
            <p>The service provider has started working on your service request:</p>

            <div style='background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0;'>
                <strong>Item:</strong> {$job['item_identifier']}<br>
                <strong>Description:</strong> {$job['fault_description']}<br>
                <strong>Provider:</strong> {$job['provider_name']}<br>
                <strong>Started on:</strong> " . date('M j, Y \a\t g:i A') . "
            </div>

            <p>You will receive another notification when the work is completed for your confirmation.</p>
        </div>
        ";
    }

    private static function getJobCompletedEmailBody($job) {
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <h2 style='color: #28a745;'>Service Work Completed</h2>
            <p>Dear " . trim($job['first_name'] . ' ' . $job['last_name']) . ",</p>
            <p>The service work has been completed for your service request:</p>

            <div style='background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0;'>
                <strong>Item:</strong> {$job['item_identifier']}<br>
                <strong>Description:</strong> {$job['fault_description']}<br>
                <strong>Provider:</strong> {$job['provider_name']}<br>
                <strong>Completed on:</strong> " . date('M j, Y \a\t g:i A') . "
            </div>

            <p>Please log into the fault reporting system to review the work and confirm completion, or reject the work if it doesn't meet your requirements.</p>

            <div style='background: #e3f2fd; border-left: 4px solid #2196f3; padding: 10px; margin: 15px 0;'>
                <p style='margin: 0;'><strong>Action Required:</strong> Log in to confirm or reject this work within 7 days.</p>
            </div>
        </div>
        ";
    }

    private static function getJobConfirmedEmailBody($job, $provider) {
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <h2 style='color: #6f42c1;'>Job Confirmed by Client</h2>
            <p>Dear {$provider['first_name']} {$provider['last_name']},</p>
            <p>The client has confirmed completion of this job:</p>

            <div style='background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0;'>
                <strong>Item:</strong> {$job['item_identifier']}<br>
                <strong>Description:</strong> {$job['fault_description']}<br>
                <strong>Client:</strong> {$job['client_org_name']}<br>
                <strong>Confirmed on:</strong> " . date('M j, Y \a\t g:i A') . "
            </div>

            <p>The job has been completed and closed. Thank you for your excellent service!</p>
        </div>
        ";
    }

    private static function getCannotRepairEmailBody($job, $reason) {
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <h2 style='color: #dc3545;'>Service Provider Unable to Repair</h2>
            <p>Dear " . trim($job['first_name'] . ' ' . $job['last_name']) . ",</p>
            <p>The service provider has indicated they are unable to repair your item:</p>

            <div style='background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0;'>
                <strong>Item:</strong> {$job['item_identifier']}<br>
                <strong>Description:</strong> {$job['fault_description']}<br>
                <strong>Provider:</strong> {$job['provider_name']}<br>
                " . (!empty($reason) ? "<strong>Reason:</strong> {$reason}<br>" : "") . "
                <strong>Reported on:</strong> " . date('M j, Y \a\t g:i A') . "
            </div>

            <p>You may choose to:</p>
            <ul style='margin: 15px 0 15px 20px;'>
                <li>Reassign this job to a different service provider</li>
                <li>Confirm receipt of this analysis and close the job</li>
            </ul>
        </div>
        ";
    }

    private static function getJobIncompleteEmailBody($job, $provider, $reason) {
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <h2 style='color: #ffc107;'>Job Returned for Rework</h2>
            <p>Dear {$provider['first_name']} {$provider['last_name']},</p>
            <p>The client has returned this job for rework:</p>

            <div style='background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0;'>
                <strong>Item:</strong> {$job['item_identifier']}<br>
                <strong>Description:</strong> {$job['fault_description']}<br>
                <strong>Client:</strong> {$job['client_org_name']}<br>
                " . (!empty($reason) ? "<strong>Client Feedback:</strong> {$reason}<br>" : "") . "
                <strong>Returned on:</strong> " . date('M j, Y \a\t g:i A') . "
            </div>

            <p>Please review the client's feedback and continue working on this job.</p>
        </div>
        ";
    }

    private static function getJobRejectedEmailBody($job, $provider, $reason) {
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <h2 style='color: #dc3545;'>Job Rejected by Client</h2>
            <p>Dear {$provider['first_name']} {$provider['last_name']},</p>
            <p>The client has rejected this work:</p>

            <div style='background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0;'>
                <strong>Item:</strong> {$job['item_identifier']}<br>
                <strong>Description:</strong> {$job['fault_description']}<br>
                <strong>Client:</strong> {$job['client_org_name']}<br>
                " . (!empty($reason) ? "<strong>Client Feedback:</strong> {$reason}<br>" : "") . "
                <strong>Rejected on:</strong> " . date('M j, Y \a\t g:i A') . "
            </div>

            <p>This job has been terminated. No further work is required.</p>
        </div>
        ";
    }

    private static function getQuoteRequestedEmailBody($job, $provider) {
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <h2 style='color: #ffc107;'>Quote Requested</h2>
            <p>Dear {$provider['first_name']} {$provider['last_name']},</p>
            <p>The client has requested a quote for this service request:</p>

            <div style='background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0;'>
                <strong>Item:</strong> {$job['item_identifier']}<br>
                <strong>Description:</strong> {$job['fault_description']}<br>
                <strong>Client:</strong> {$job['client_org_name']}<br>
                <strong>Quote due:</strong> " . ($job['quotation_deadline'] ? date('M j, Y', strtotime($job['quotation_deadline'])) : 'ASAP') . "<br>
                <strong>Requested on:</strong> " . date('M j, Y \a\t g:i A') . "
            </div>

            <p>Please log into the fault reporting system to review the details and provide a quotation.</p>
        </div>
        ";
    }

    private static function getQuoteAcceptedEmailBody($quoteData, $provider) {
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <h2 style='color: #28a745;'>Quote Accepted</h2>
            <p>Dear {$provider['first_name']} {$provider['last_name']},</p>
            <p>Your quote has been accepted by the client:</p>

            <div style='background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0;'>
                <strong>Item:</strong> {$quoteData['item_identifier']}<br>
                <strong>Description:</strong> {$quoteData['fault_description']}<br>
                <strong>Client:</strong> {$quoteData['client_name']}<br>
                <strong>Quoted Amount:</strong> R{$quoteData['quotation_amount']}<br>
                <strong>Accepted on:</strong> " . date('M j, Y \a\t g:i A') . "
            </div>

            <p>The job has been assigned to you. Please start working on it and update the status as appropriate.</p>
        </div>
        ";
    }

    private static function getQuoteRejectedEmailBody($quoteData, $provider, $reason) {
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <h2 style='color: #dc3545;'>Quote Rejected</h2>
            <p>Dear {$provider['first_name']} {$provider['last_name']},</p>
            <p>Your quote has been rejected by the client:</p>

            <div style='background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0;'>
                <strong>Item:</strong> {$quoteData['item_identifier']}<br>
                <strong>Description:</strong> {$quoteData['fault_description']}<br>
                <strong>Client:</strong> {$quoteData['client_name']}<br>
                <strong>Quoted Amount:</strong> R{$quoteData['quotation_amount']}<br>
                " . (!empty($reason) ? "<strong>Client Feedback:</strong> {$reason}<br>" : "") . "
                <strong>Rejected on:</strong> " . date('M j, Y \a\t g:i A') . "
            </div>

            <p>The job has not been assigned. You may provide a revised quote if appropriate.</p>
        </div>
        ";
    }

    private static function getQuoteRevisionEmailBody($quoteData, $provider, $reason) {
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <h2 style='color: #ffc107;'>Quote Revision Requested</h2>
            <p>Dear {$provider['first_name']} {$provider['last_name']},</p>
            <p>The client has requested a revised quote for this job:</p>

            <div style='background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0;'>
                <strong>Item:</strong> {$quoteData['item_identifier']}<br>
                <strong>Description:</strong> {$quoteData['fault_description']}<br>
                <strong>Client:</strong> {$quoteData['client_name']}<br>
                <strong>Previous Quote:</strong> R{$quoteData['quotation_amount']}<br>
                " . (!empty($reason) ? "<strong>Client Feedback:</strong> {$reason}<br>" : "") . "
                <strong>Requested on:</strong> " . date('M j, Y \a\t g:i A') . "
            </div>

            <p>Please provide a revised quotation based on the client's feedback.</p>
        </div>
        ";
    }

    private static function getOverdueJobEmailBody($job, $recipientType, $daysOverdue) {
        if ($recipientType === 'client') {
            return "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h2 style='color: #ff6b35;'>Overdue Job Reminder</h2>
                <p>Dear " . trim($job['first_name'] . ' ' . $job['last_name']) . ",</p>
                <p>This job has been inactive for {$daysOverdue} days:</p>

                <div style='background: #fff3cd; border: 2px solid #ff6b35; padding: 15px; border-radius: 8px; margin: 15px 0;'>
                    <strong>Item:</strong> {$job['item_identifier']}<br>
                    <strong>Description:</strong> {$job['fault_description']}<br>
                    <strong>Status:</strong> {$job['job_status']}<br>
                    <strong>Last Activity:</strong> " . date('M j, Y \a\t g:i A', strtotime($job['updated_at'])) . "
                </div>

                <p>Please check on the status of this service request and contact your service provider if needed.</p>
            </div>
            ";
        } else {
            return "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h2 style='color: #ff6b35;'>Overdue Job Reminder</h2>
                <p>Dear Service Provider,</p>
                <p>This assigned job has been inactive for {$daysOverdue} days:</p>

                <div style='background: #fff3cd; border: 2px solid #ff6b35; padding: 15px; border-radius: 8px; margin: 15px 0;'>
                    <strong>Item:</strong> {$job['item_identifier']}<br>
                    <strong>Description:</strong> {$job['fault_description']}<br>
                    <strong>Client:</strong> {$job['client_org_name']}<br>
                    <strong>Status:</strong> {$job['job_status']}<br>
                    <strong>Last Activity:</strong> " . date('M j, Y \a\t g:i A', strtotime($job['updated_at'])) . "
                </div>

                <p>Please update the job status or contact the client for clarification.</p>
            </div>
            ";
        }
    }
}
?>
