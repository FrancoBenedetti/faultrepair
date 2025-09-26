<?php
ini_set('log_errors', true);
ini_set('error_log', $_SERVER['DOCUMENT_ROOT'].'/all-logs/mail.log');
/**
 * Email utility functions for the fault reporting system
 */

require_once __DIR__ . '/../config/site.php';

class EmailService {

    private static $fromEmail = 'noreply@' . DOMAIN;
    private static $fromName = SITE_NAME . ' System';

    /**
     * Send an email verification link
     */
    public static function sendVerificationEmail($toEmail, $username, $verificationToken, $isPasswordReset = false) {
        $subject = $isPasswordReset ? 'Password Reset Verification' : 'Email Verification Required';

        $baseUrl = (DOMAIN === 'localhost' ? 'http' : 'https') . '://' . DOMAIN;

        if ($isPasswordReset) {
            $resetUrl = $baseUrl . "/reset-password?token=" . urlencode($verificationToken);
            $body = self::getPasswordResetEmailBody($username, $resetUrl);
        } else {
            $verificationUrl = $baseUrl . "/verify-email?token=" . urlencode($verificationToken);
            $body = self::getVerificationEmailBody($username, $verificationUrl);
        }
        error_log(__FILE__.'/'.__LINE__.'/ >>>> '.$body);
        
        return self::sendEmail($toEmail, $subject, $body);
    }

    /**
     * Send an email for new user to set password
     */
    public static function sendSetPasswordEmail($toEmail, $username, $verificationToken) {
        $subject = 'Account Setup - Set Your Password';

        $baseUrl = (DOMAIN === 'localhost' ? 'http' : 'https') . '://' . DOMAIN;
        $setPasswordUrl = $baseUrl . "/verify-email?token=" . urlencode($verificationToken) . "&action=set_password";

        $body = self::getSetPasswordEmailBody($username, $setPasswordUrl);
        error_log(__FILE__.'/'.__LINE__.'/ >>>> '.$body);
        return self::sendEmail($toEmail, $subject, $body);
    }

    /**
     * Send a notification email to verified users
     */
    public static function sendNotificationEmail($toEmail, $subject, $message) {
        $body = self::getNotificationEmailBody($subject, $message);
        error_log(__FILE__.'/'.__LINE__.'/ >>>> '.$body);
        return self::sendEmail($toEmail, $subject, $body);
    }

    /**
     * Send an email using PHP mail function
     */
    private static function sendEmail($to, $subject, $body) {
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . self::$fromName . ' <' . self::$fromEmail . '>',
            'Reply-To: ' . self::$fromEmail,
            'X-Mailer: PHP/' . phpversion()
        ];

        $headersString = implode("\r\n", $headers);

        // For development/testing, log emails instead of sending
        error_log("EMAIL TO: $to\nSUBJECT: $subject\nHEADERS:\n$headersString\nBODY:\n$body\n" . str_repeat("=", 50) . "\n", 3, __DIR__ . '/../../public/all-logs/mail.log');

        // For development, return true to simulate successful sending
        // Uncomment the mail() call below for production
        // $result = mail($to, $subject, $body, $headersString);
        // error_log(__FILE__.'/'.__LINE__.'/ >>>> '.json_encode($result));
        // return $result;

        return true;
    }

    /**
     * Generate a secure verification token
     */
    public static function generateVerificationToken() {
        return bin2hex(random_bytes(32));
    }

    /**
     * Get the email verification body
     */
    private static function getVerificationEmailBody($username, $verificationUrl) {
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #007bff; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background-color: #f9f9f9; }
                .button { display: inline-block; padding: 10px 20px; background-color: #28a745; color: white; text-decoration: none; border-radius: 5px; }
                .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Welcome to " . SITE_NAME . "</h1>
                </div>
                <div class='content'>
                    <h2>Hello $username,</h2>
                    <p>Thank you for registering with the " . SITE_NAME . " system. To complete your registration and activate your account, please verify your email address by clicking the button below:</p>
                    <p style='text-align: center;'>
                        <a href='$verificationUrl' class='button'>Verify Email Address</a>
                    </p>
                    <p>If the button doesn't work, you can copy and paste this link into your browser:</p>
                    <p><a href='$verificationUrl'>$verificationUrl</a></p>
                    <p>This link will expire in 24 hours for security reasons.</p>
                    <p>If you didn't create this account, please ignore this email.</p>
                </div>
                <div class='footer'>
                    <p>This is an automated message from the " . SITE_NAME . " system. Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    /**
     * Get the password reset email body
     */
    private static function getPasswordResetEmailBody($username, $resetUrl) {
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #dc3545; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background-color: #f9f9f9; }
                .button { display: inline-block; padding: 10px 20px; background-color: #dc3545; color: white; text-decoration: none; border-radius: 5px; }
                .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Password Reset Request</h1>
                </div>
                <div class='content'>
                    <h2>Hello $username,</h2>
                    <p>You have requested to reset your password for the " . SITE_NAME . " system. Click the button below to proceed with the password reset:</p>
                    <p style='text-align: center;'>
                        <a href='$resetUrl' class='button'>Reset Password</a>
                    </p>
                    <p>If the button doesn't work, you can copy and paste this link into your browser:</p>
                    <p><a href='$resetUrl'>$resetUrl</a></p>
                    <p>This link will expire in 1 hour for security reasons.</p>
                    <p>If you didn't request this password reset, please ignore this email. Your password will remain unchanged.</p>
                </div>
                <div class='footer'>
                    <p>This is an automated message from the " . SITE_NAME . " system. Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    /**
     * Get the set password email body
     */
    private static function getSetPasswordEmailBody($username, $setPasswordUrl) {
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #28a745; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background-color: #f9f9f9; }
                .button { display: inline-block; padding: 10px 20px; background-color: #28a745; color: white; text-decoration: none; border-radius: 5px; }
                .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Welcome to " . SITE_NAME . "</h1>
                </div>
                <div class='content'>
                    <h2>Hello $username,</h2>
                    <p>You have been added to the " . SITE_NAME . " system. To activate your account and set your password, please click the button below:</p>
                    <p style='text-align: center;'>
                        <a href='$setPasswordUrl' class='button'>Set Your Password</a>
                    </p>
                    <p>If the button doesn't work, you can copy and paste this link into your browser:</p>
                    <p><a href='$setPasswordUrl'>$setPasswordUrl</a></p>
                    <p>This link will expire in 24 hours for security reasons.</p>
                    <p>If you didn't expect this invitation, please ignore this email.</p>
                </div>
                <div class='footer'>
                    <p>This is an automated message from the " . SITE_NAME . " system. Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    /**
     * Get the notification email body
     */
    private static function getNotificationEmailBody($subject, $message) {
        return "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #17a2b8; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background-color: #f9f9f9; }
                .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>$subject</h1>
                </div>
                <div class='content'>
                    $message
                </div>
                <div class='footer'>
                    <p>This is an automated message from the " . SITE_NAME . " system. Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}
?>
