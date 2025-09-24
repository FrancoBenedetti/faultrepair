<?php
/**
 * API Endpoint Testing Helper
 * This script provides examples of how to test the email verification APIs
 */

echo "<h1>Email Verification API Testing Guide</h1>";
echo "<p>Use these curl commands or browser requests to test the email verification system.</p>";

echo "<h2>1. Test Client Registration</h2>";
echo "<pre>";
echo "curl -X POST http://fault-reporter.local/backend/api/register-client.php \\
  -H 'Content-Type: application/json' \\
  -d '{
    \"clientName\": \"Test Company\",
    \"address\": \"123 Test St\",
    \"username\": \"testuser\",
    \"email\": \"test@example.com\",
    \"password\": \"TestPass123\"
  }'
";
echo "</pre>";

echo "<h2>2. Check Email Logs</h2>";
echo "<p>After registration, check the PHP error log for the verification email:</p>";
echo "<pre>tail -f /var/log/apache2/error.log</pre>";
echo "<p>Or check the application logs in the all-logs directory.</p>";

echo "<h2>3. Test Email Verification</h2>";
echo "<p>Extract the verification token from the logs and use it:</p>";
echo "<pre>";
echo "curl 'http://fault-reporter.local/backend/api/verify-email.php?token=YOUR_TOKEN_HERE'
";
echo "</pre>";

echo "<h2>4. Test Login (Should Fail Before Verification)</h2>";
echo "<pre>";
echo "curl -X POST http://fault-reporter.local/backend/api/auth.php \\
  -H 'Content-Type: application/json' \\
  -d '{
    \"username\": \"testuser\",
    \"password\": \"TestPass123\"
  }'
";
echo "</pre>";

echo "<h2>5. Test Login (Should Succeed After Verification)</h2>";
echo "<p>Same command as above - should now return a JWT token.</p>";

echo "<h2>6. Test Adding Users (Admin Action)</h2>";
echo "<pre>";
echo "curl -X POST http://fault-reporter.local/backend/api/client-users.php \\
  -H 'Content-Type: application/json' \\
  -H 'Authorization: Bearer YOUR_JWT_TOKEN' \\
  -d '{
    \"username\": \"newemployee\",
    \"email\": \"employee@example.com\",
    \"first_name\": \"John\",
    \"last_name\": \"Doe\",
    \"phone\": \"1234567890\",
    \"role_id\": 1
  }'
";
echo "</pre>";

echo "<h2>7. Test Password Setup for New Users</h2>";
echo "<p>Get the token from logs and set password:</p>";
echo "<pre>";
echo "curl -X POST http://fault-reporter.local/backend/api/verify-email.php \\
  -H 'Content-Type: application/json' \\
  -d '{
    \"token\": \"YOUR_TOKEN\",
    \"action\": \"set_password\",
    \"password\": \"NewEmployeePass123\"
  }'
";
echo "</pre>";

echo "<h2>8. Test Password Reset</h2>";
echo "<pre>";
echo "curl -X POST http://fault-reporter.local/backend/api/request-password-reset.php \\
  -H 'Content-Type: application/json' \\
  -d '{
    \"email\": \"test@example.com\"
  }'
";
echo "</pre>";

echo "<h2>9. Test Password Reset Completion</h2>";
echo "<pre>";
echo "curl -X POST http://fault-reporter.local/backend/api/verify-email.php \\
  -H 'Content-Type: application/json' \\
  -d '{
    \"token\": \"RESET_TOKEN_FROM_LOGS\",
    \"action\": \"reset\",
    \"password\": \"NewPassword123\"
  }'
";
echo "</pre>";

echo "<h2>10. Test Promotion to Admin</h2>";
echo "<pre>";
echo "curl -X PUT http://fault-reporter.local/backend/api/client-users.php \\
  -H 'Content-Type: application/json' \\
  -H 'Authorization: Bearer YOUR_JWT_TOKEN' \\
  -d '{
    \"user_id\": 123,
    \"role_id\": 2
  }'
";
echo "</pre>";
echo "<p>If user is not verified, this will fail and send verification email.</p>";

echo "<h2>Database Inspection</h2>";
echo "<p>Check user status in database:</p>";
echo "<pre>";
echo "mysql -u root -p faultreporter -e \\
  'SELECT id, username, email, is_active, email_verified, verification_token FROM users;'
";
echo "</pre>";

echo "<h2>Alternative Testing Tools</h2>";
echo "<ul>";
echo "<li><strong>Postman/Insomnia:</strong> Import the above curl commands</li>";
echo "<li><strong>Browser:</strong> Use developer tools to make requests</li>";
echo "<li><strong>MailHog:</strong> docker run -d -p 1025:1025 -p 8025:8025 mailhog/mailhog</li>";
echo "<li><strong>Local Email Testing:</strong> Configure sendmail or postfix for local delivery</li>";
echo "</ul>";

echo "<h2>Enabling Real Email (Optional)</h2>";
echo "<p>To enable actual email sending:</p>";
echo "<ol>";
echo "<li>Install and configure sendmail or postfix</li>";
echo "<li>Edit backend/includes/email.php</li>";
echo "<li>Uncomment the mail() call and comment out the error_log line</li>";
echo "<li>Update \$fromEmail to a real address</li>";
echo "</ol>";

?>
