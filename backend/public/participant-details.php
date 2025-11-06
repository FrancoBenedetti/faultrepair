<?php
// Enable error reporting for this specific script to aid in debugging.
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the database configuration.
require_once '../config/database.php';

// Get participant ID from the URL, ensuring it's an integer.
$participant_id = isset($_GET['participant_id']) ? (int)$_GET['participant_id'] : 0;

$participant_name = '';
$users = [];
$locations = [];
$approved_providers = [];
$all_participants = [];
$error_message = '';

if ($participant_id > 0) {
    if ($pdo) {
        try {
            // 1. Fetch participant name
            $stmt = $pdo->prepare("SELECT name FROM participants WHERE participantId = ?");
            $stmt->execute([$participant_id]);
            $participant = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($participant) {
                $participant_name = $participant['name'];

                // 2. Fetch users for the participant
                $stmt_users = $pdo->prepare("
                    SELECT u.userId, u.first_name, u.last_name, u.email, r.name as role_name
                    FROM users u
                    JOIN user_roles r ON u.role_id = r.roleId
                    WHERE u.entity_id = ?
                    ORDER BY u.first_name ASC, u.last_name ASC
                ");
                $stmt_users->execute([$participant_id]);
                $users = $stmt_users->fetchAll(PDO::FETCH_ASSOC);

                // 3. Fetch locations for the participant
                $stmt_locations = $pdo->prepare("
                    SELECT id, name, address
                    FROM locations
                    WHERE participant_id = ?
                    ORDER BY name ASC
                ");
                $stmt_locations->execute([$participant_id]);
                $locations = $stmt_locations->fetchAll(PDO::FETCH_ASSOC);

                // 4. Fetch approved service providers for the participant
                $stmt_providers = $pdo->prepare("
                    SELECT p.participantId, p.name
                    FROM participants p
                    JOIN participant_approvals pa ON p.participantId = pa.provider_participant_id
                    WHERE pa.client_participant_id = ?
                    ORDER BY p.name ASC
                ");
                $stmt_providers->execute([$participant_id]);
                $approved_providers = $stmt_providers->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $error_message = "No participant found with ID: " . htmlspecialchars($participant_id);
            }
        } catch (Exception $e) {
            $error_message = "Database query failed: " . htmlspecialchars($e->getMessage());
        }
    } else {
        $error_message = "<strong>Database Connection Error:</strong> Could not connect to the database. Please check the server logs for more details.";
    }
}

// Fetch all participants for the dropdown, regardless of whether one is selected.
if ($pdo) {
    try {
        $stmt_all = $pdo->prepare("SELECT participantId, name FROM participants ORDER BY name ASC");
        $stmt_all->execute();
        $all_participants = $stmt_all->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // If this fails, the form will just not have a dropdown, which is a graceful failure.
        $error_message = "Could not load participant list: " . htmlspecialchars($e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Participant Details</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; margin: 2em; background-color: #f8f9fa; color: #212529; }
        .container { max-width: 960px; margin: auto; background: #fff; padding: 2em; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 1.5em; }
        th, td { border: 1px solid #dee2e6; padding: 12px; text-align: left; }
        th { background-color: #e9ecef; }
        h1, h2 { color: #343a40; border-bottom: 2px solid #dee2e6; padding-bottom: 0.5em; margin-bottom: 1em;}
        h2 { margin-top: 2em; font-size: 1.5em; }
        p { color: #6c757d; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; margin-bottom: .5rem; font-weight: 500; }
        .form-group input { width: 100%; padding: .5rem; border: 1px solid #ced4da; border-radius: .25rem; }
        .btn { display: inline-block; padding: .5rem 1rem; background-color: #007bff; color: #fff; border: none; border-radius: .25rem; cursor: pointer; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Participant Details</h1>

        <form method="GET" action="">
            <div class="form-group">
                <label for="participant_id">Enter Participant ID:</label>
                <select id="participant_id" name="participant_id" class="form-group input" style="width: 100%; padding: .5rem; border: 1px solid #ced4da; border-radius: .25rem;" required>
                    <option value="">-- Select a Participant --</option>
                    <?php foreach ($all_participants as $p): ?>
                        <option value="<?php echo htmlspecialchars($p['participantId']); ?>" <?php if ($p['participantId'] == $participant_id) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($p['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn">View Details</button>
        </form>

        <?php if ($error_message): ?>
            <p style="color: #dc3545;"><?php echo $error_message; ?></p>
        <?php elseif ($participant_id > 0): ?>
            <h2><?php echo htmlspecialchars($participant_name); ?> (ID: <?php echo htmlspecialchars($participant_id); ?>)</h2>

            <h3>Users (<?php echo count($users); ?>)</h3>
            <?php if (count($users) > 0): ?>
                <table><thead><tr><th>User ID</th><th>Name</th><th>Email</th><th>Role</th></tr></thead><tbody>
                <?php foreach ($users as $user): ?>
                    <tr><td><?php echo htmlspecialchars($user['userId']); ?></td><td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td><td><?php echo htmlspecialchars($user['email']); ?></td><td><?php echo htmlspecialchars($user['role_name']); ?></td></tr>
                <?php endforeach; ?>
                </tbody></table>
            <?php else: echo '<p>No users found for this participant.</p>'; endif; ?>

            <h3>Locations (<?php echo count($locations); ?>)</h3>
            <?php if (count($locations) > 0): ?>
                <table><thead><tr><th>Location ID</th><th>Name</th><th>Address</th></tr></thead><tbody>
                <?php foreach ($locations as $location): ?>
                    <tr><td><?php echo htmlspecialchars($location['id']); ?></td><td><?php echo htmlspecialchars($location['name']); ?></td><td><?php echo htmlspecialchars($location['address']); ?></td></tr>
                <?php endforeach; ?>
                </tbody></table>
            <?php else: echo '<p>No locations found for this participant.</p>'; endif; ?>

            <h3>Approved Service Providers (<?php echo count($approved_providers); ?>)</h3>
            <?php if (count($approved_providers) > 0): ?>
                <table><thead><tr><th>Provider ID</th><th>Company Name</th></tr></thead><tbody>
                <?php foreach ($approved_providers as $provider): ?>
                    <tr><td><?php echo htmlspecialchars($provider['participantId']); ?></td><td><?php echo htmlspecialchars($provider['name']); ?></td></tr>
                <?php endforeach; ?>
                </tbody></table>
            <?php else: echo '<p>No approved service providers found for this participant.</p>'; endif; ?>

        <?php endif; ?>
    </div>
</body>
</html>