<?php
// Enable error reporting for this specific script to aid in debugging.
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the database configuration. The path is relative to the current file's location.
require_once '../config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Participants</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; margin: 2em; background-color: #f8f9fa; color: #212529; }
        .container { max-width: 960px; margin: auto; background: #fff; padding: 2em; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 1.5em; }
        th, td { border: 1px solid #dee2e6; padding: 12px; text-align: left; }
        th { background-color: #e9ecef; }
        h1 { color: #343a40; border-bottom: 2px solid #dee2e6; padding-bottom: 0.5em; margin-bottom: 1em;}
        p { color: #6c757d; }
    </style>
</head>
<body>

    <div class="container">
        <h1>Client Participants</h1>

        <?php
        // Check if the database connection was successful
        if ($pdo === null) {
            echo '<p style="color: #dc3545;"><strong>Database Connection Error:</strong> Could not connect to the database. Please check the server logs for more details.</p>';
            // You can check your PHP error log (e.g., /var/log/apache2/error.log) or the log file specified in your php.ini
            exit;
        }

        try {
            // Prepare the SQL query to select all participants of type 'Client'
            $stmt = $pdo->prepare("
                SELECT p.participantId, p.name, p.address, p.created_at
                FROM participants p
                JOIN participant_type pt ON p.participantId = pt.participantId
                WHERE pt.participantType = 'C'
                ORDER BY p.name ASC
            ");

            $stmt->execute();
            $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (count($clients) > 0) {
                echo '<table><thead><tr><th>ID</th><th>Company Name</th><th>Address</th><th>Date Registered</th></tr></thead><tbody>';
                foreach ($clients as $client) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($client['participantId']) . '</td>';
                    echo '<td>' . htmlspecialchars($client['name']) . '</td>';
                    echo '<td>' . htmlspecialchars($client['address']) . '</td>';
                    echo '<td>' . htmlspecialchars(date('F j, Y', strtotime($client['created_at']))) . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
            } else {
                echo '<p>No client participants found in the database.</p>';
            }
        } catch (Exception $e) {
            echo '<p style="color: #dc3545;">Error fetching clients: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
        ?>
    </div>

</body>
</html>