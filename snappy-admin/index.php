<?php
ini_set('log_errors', true);
ini_set('error_log', $_SERVER['DOCUMENT_ROOT'].'/all-logs/snapy-admin.index.log');
/**
 * Site Manager Dashboard
 * Admin interface for managing the fault reporter platform
 */
error_log(__FILE__.'/'.__LINE__.'/ >>>>  LOGGER START <<<<');
require_once '../backend/config/database.php';
require_once '../backend/includes/JWT.php';
require_once '../backend/includes/subscription.php';

// Authentication check
$token = $_GET['token'] ?? '';
$admin_user = null;

error_log(__FILE__.'/'.__LINE__.'/ Authentication check - Token present: ' . (!empty($token) ? 'YES' : 'NO'));
error_log(__FILE__.'/'.__LINE__.'/ Token length: ' . strlen($token ?? ''));

if ($token) {
    try {
        error_log(__FILE__.'/'.__LINE__.'/ Attempting JWT decode...');
        $payload = JWT::decode($token);
        error_log(__FILE__.'/'.__LINE__.'/ JWT decoded successfully. Payload: ' . json_encode($payload));

        if ($payload['role_id'] === 5 || $payload['role_id'] === 2) { // Site Administrator or Budget Controller
            $admin_user = $payload;
            error_log(__FILE__.'/'.__LINE__.'/ Admin access granted. Role ID: ' . $payload['role_id']);
        } else {
            error_log(__FILE__.'/'.__LINE__.'/ Admin access denied. Insufficient role privileges. Role ID: ' . $payload['role_id']);
        }
    } catch (Exception $e) {
        error_log(__FILE__.'/'.__LINE__.'/ JWT decode failed: ' . $e->getMessage());
        // Invalid token
    }
} else {
    error_log(__FILE__.'/'.__LINE__.'/ No token provided, showing login form');
}

if (!$admin_user) {
    // Show login form for admin
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Fault Reporter - Site Manager</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
            .login-container {
                max-width: 400px;
                margin: 50px auto;
                background: white;
                padding: 30px;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            .login-container h1 { text-align: center; color: #333; margin-bottom: 30px; }
            .form-group { margin-bottom: 20px; }
            .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
            .form-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px; }
            .btn { width: 100%; padding: 12px; background: #007bff; color: white; border: none; border-radius: 4px; font-size: 16px; cursor: pointer; }
            .btn:hover { background: #0056b3; }
            .alert { padding: 15px; margin-bottom: 20px; border: 1px solid transparent; border-radius: 4px; }
            .alert-error { color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; }
        </style>
    </head>
    <body>
        <div class="login-container">
            <h1>🔧 Site Manager</h1>
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-error">Invalid credentials or insufficient permissions.</div>
            <?php endif; ?>
            <div class="alert alert-error" id="error-message" style="display: none;"></div>
            <form id="login-form">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" required placeholder="admin">
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required placeholder="admin password">
                </div>
                <button type="submit" class="btn">Login</button>
            </form>
        </div>

        <script>
            document.getElementById('login-form').addEventListener('submit', async (e) => {
                e.preventDefault();

                const username = document.getElementById('username').value;
                const password = document.getElementById('password').value;

                // Authenticate with the main auth endpoint
                try {
                const response = await fetch('../backend/api/auth.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ username, password })
                });

                    const data = await response.json();

                    if (data.token) {
                        // Redirect with the token
                        window.location.href = `?token=${data.token}`;
                    } else {
                        document.getElementById('error-message').textContent = 'Login failed. Check credentials and ensure you have admin access.';
                        document.getElementById('error-message').style.display = 'block';
                    }
                } catch (error) {
                    document.getElementById('error-message').textContent = 'Network error. Please try again.';
                    document.getElementById('error-message').style.display = 'block';
                }
            });
        </script>
    </body>
    </html>
    <?php
    exit;
}

$admin_user_name = 'Administrator';

// Try to get the actual user name from database
try {
    $stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE userId = ?");
    $stmt->execute([$admin_user['user_id']]);
    $user_info = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user_info) {
        $admin_user_name = trim($user_info['first_name'] . ' ' . $user_info['last_name']);
    }

    error_log(__FILE__.'/'.__LINE__.'/ Retrieved admin user name: ' . $admin_user_name);

} catch (Exception $e) {
    error_log(__FILE__.'/'.__LINE__.'/ Failed to get admin user name: ' . $e->getMessage());
}

// If authenticated, show the admin dashboard
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fault Reporter - Site Manager</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f8f9fa; color: #333; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header h1 { font-size: 24px; margin-bottom: 5px; }
        .header p { opacity: 0.9; }
        .nav { background: white; padding: 15px 20px; border-bottom: 1px solid #e9ecef; box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow-x: auto; }
        .nav-tabs { display: flex; gap: 8px; flex-wrap: wrap; }
        .nav-tab { padding: 8px 14px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 6px; cursor: pointer; transition: all 0.2s; white-space: nowrap; font-size: 13px; }
        .nav-tab:hover { background: #e9ecef; }
        .nav-tab.active { background: #007bff; color: white; border-color: #007bff; }
        .assignment-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 20px; }
        .assignment-item { background: white; border: 1px solid #dee2e6; border-radius: 6px; padding: 15px; }
        .assignment-item h4 { margin: 0 0 10px 0; color: #495057; font-size: 14px; }
        .checkbox-group { display: flex; flex-wrap: wrap; gap: 8px; }
        .checkbox-item { display: flex; align-items: center; gap: 5px; font-size: 12px; }
        .assignment-actions { margin-top: 15px; text-align: right; }
        .action-summary { margin-bottom: 10px; font-size: 12px; color: #6c757d; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .card { background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .card h3 { color: #495057; margin-bottom: 10px; font-size: 18px; }
        .metric { font-size: 32px; font-weight: bold; color: #007bff; }
        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .table th, .table td { padding: 12px; text-align: left; border-bottom: 1px solid #dee2e6; }
        .table th { background: #f8f9fa; font-weight: 600; }
        .btn { padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; transition: all 0.2s; }
        .btn-primary { background: #007bff; color: white; }
        .btn-primary:hover { background: #0056b3; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-danger:hover { background: #c82333; }
        .btn-success { background: #28a745; color: white; }
        .btn-success:hover { background: #218838; }
        .status-enabled { color: #28a745; font-weight: bold; }
        .status-disabled { color: #dc3545; font-weight: bold; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; }
        .modal-content { background: white; margin: 10% auto; padding: 20px; border-radius: 8px; width: 90%; max-width: 500px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 8px; border: 1px solid #dee2e6; border-radius: 4px; }
        .form-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; }
        .loading { opacity: 0.6; pointer-events: none; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🔧 Site Manager Dashboard</h1>
        <p>Welcome, <?php echo htmlspecialchars($admin_user_name); ?> • Last login: <?php echo date('Y-m-d H:i:s'); ?></p>
    </div>

    <div class="nav">
        <div class="nav-tabs">
            <div class="nav-tab active" data-tab="dashboard">Dashboard</div>
            <div class="nav-tab" data-tab="usage">Usage</div>
            <div class="nav-tab" data-tab="users">Users</div>
            <div class="nav-tab" data-tab="regions">Regions</div>
            <div class="nav-tab" data-tab="services">Services</div>
            <div class="nav-tab" data-tab="participants">Participants</div>
            <div class="nav-tab" data-tab="settings">Settings</div>
            <div class="nav-tab" data-tab="audit">Audit Log</div>
        </div>
    </div>

    <div class="container">
        <!-- Dashboard Tab -->
        <div id="dashboard-tab" class="tab-content">
            <div class="cards" id="dashboard-cards">
                <!-- Stats will be loaded here -->
            </div>
        </div>

        <!-- Usage Tab -->
        <div id="usage-tab" class="tab-content" style="display: none;">
            <div class="card">
                <h3>📊 Usage Monitoring</h3>
                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Filter by:</label>
                    <select id="usage-filter" style="width: auto; margin-right: 10px;">
                        <option value="all">All Users</option>
                        <option value="clients">Clients Only</option>
                        <option value="service_providers">Service Providers Only</option>
                    </select>
                    <select id="usage-month" style="width: auto; margin-right: 10px;">
                        <?php
                        for ($i = 0; $i < 6; $i++) {
                            $date = date('Y-m', strtotime("-$i months"));
                            $label = date('F Y', strtotime("-$i months"));
                            echo "<option value=\"$date\">$label</option>";
                        }
                        ?>
                    </select>
                    <button class="btn btn-primary" onclick="loadUsageStats()">Refresh</button>
                </div>
                <div id="usage-table-container">
                    <!-- Usage table will be loaded here -->
                </div>
            </div>
        </div>

        <!-- Users Tab -->
        <div id="users-tab" class="tab-content" style="display: none;">
            <div class="card">
                <h3>👥 User Management</h3>
                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Search:</label>
                    <input type="text" id="user-search" placeholder="Search by name, email, or company..." style="width: 300px; margin-right: 10px;">
                    <select id="user-filter" style="width: auto; margin-right: 10px;">
                        <option value="all">All Users</option>
                        <option value="clients">Clients Only</option>
                        <option value="sps">Service Providers Only</option>
                        <option value="disabled">Disabled Only</option>
                    </select>
                    <button class="btn btn-primary" onclick="loadUserData()">Search</button>
                </div>
                <div id="users-table-container">
                    <!-- User table will be loaded here -->
                </div>
            </div>
        </div>

        <!-- Regions Tab -->
        <div id="regions-tab" class="tab-content" style="display: none;">
            <div class="card">
                <h3>🗺️ Region Management</h3>
                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Search:</label>
                    <input type="text" id="region-search" placeholder="Search regions..." style="width: 300px; margin-right: 10px;">
                    <select id="region-active-filter" style="width: auto; margin-right: 10px;">
                        <option value="">All Regions</option>
                        <option value="true">Active Only</option>
                        <option value="false">Inactive Only</option>
                    </select>
                    <button class="btn btn-primary" onclick="loadRegions()">Search</button>
                    <button class="btn btn-success" style="margin-left: 10px;" onclick="showCreateRegionModal()">Add Region</button>
                </div>
                <div id="regions-table-container">
                    <!-- Regions table will be loaded here -->
                </div>
            </div>
        </div>

        <!-- Services Tab -->
        <div id="services-tab" class="tab-content" style="display: none;">
            <div class="card">
                <h3>🛠️ Service Management</h3>
                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Search:</label>
                    <input type="text" id="service-search" placeholder="Search services..." style="width: 300px; margin-right: 10px;">
                    <select id="service-category-filter" style="width: auto; margin-right: 10px;">
                        <option value="">All Categories</option>
                    </select>
                    <select id="service-active-filter" style="width: auto; margin-right: 10px;">
                        <option value="">All Services</option>
                        <option value="true">Active Only</option>
                        <option value="false">Inactive Only</option>
                    </select>
                    <button class="btn btn-primary" onclick="loadServices()">Search</button>
                    <button class="btn btn-success" style="margin-left: 10px;" onclick="showCreateServiceModal()">Add Service</button>
                </div>
                <div id="services-table-container">
                    <!-- Services table will be loaded here -->
                </div>
            </div>
        </div>

        <!-- Participants Tab -->
        <div id="participants-tab" class="tab-content" style="display: none;">
            <div class="card">
                <h3>👥 Participant Management</h3>
                <div class="form-group" style="margin-bottom: 20px;">
                    <label>Search:</label>
                    <input type="text" id="participant-search" placeholder="Search by name, company, or email..." style="width: 300px; margin-right: 10px;">
                    <select id="participant-type-filter" style="width: auto; margin-right: 10px;">
                        <option value="all">All Participants</option>
                        <option value="client">Clients Only</option>
                        <option value="service_provider">Service Providers Only</option>
                    </select>
                    <select id="participant-status-filter" style="width: auto; margin-right: 10px;">
                        <option value="all">All Status</option>
                        <option value="enabled">Enabled Only</option>
                        <option value="disabled">Disabled Only</option>
                    </select>
                    <button class="btn btn-primary" onclick="loadParticipants()">Search</button>
                </div>
                <div id="participants-table-container">
                    <!-- Participants table will be loaded here -->
                </div>
            </div>
        </div>

        <!-- Settings Tab -->
        <div id="settings-tab" class="tab-content" style="display: none;">
            <div class="card">
                <h3>⚙️ Site Settings</h3>
                <div id="settings-container">
                    <!-- Settings table will be loaded here -->
                </div>
            </div>
        </div>

        <!-- Audit Tab -->
        <div id="audit-tab" class="tab-content" style="display: none;">
            <div class="card">
                <h3>📋 Audit Log</h3>
                <div id="audit-container">
                    <!-- Audit log will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <div id="settings-modal" class="modal">
        <div class="modal-content">
            <h3 id="settings-modal-title">Edit Setting</h3>
            <form id="settings-form">
                <input type="hidden" id="setting-id">
                <div class="form-group">
                    <label>Setting Key:</label>
                    <input type="text" id="setting-key" readonly>
                </div>
                <div class="form-group">
                    <label>Value:</label>
                    <input type="text" id="setting-value" required>
                </div>
                <div class="form-group">
                    <label>Description:</label>
                    <textarea id="setting-description" readonly></textarea>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <div id="region-modal" class="modal">
        <div class="modal-content">
            <h3 id="region-modal-title">Edit Region</h3>
            <form id="region-form">
                <input type="hidden" id="region-id">
                <div class="form-group">
                    <label>Name:</label>
                    <input type="text" id="region-name" required placeholder="e.g. Johannesburg">
                </div>
                <div class="form-group">
                    <label>Code:</label>
                    <input type="text" id="region-code" required placeholder="e.g. JHB" maxlength="3" pattern="[A-Z]{3}">
                </div>
                <div class="form-group">
                    <label>Country:</label>
                    <input type="text" id="region-country" required placeholder="e.g. South Africa">
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="region-active"> Active
                    </label>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <div id="user-modal" class="modal">
        <div class="modal-content">
            <h3 id="user-modal-title">Manage User</h3>
            <form id="user-form">
                <input type="hidden" id="entity-type">
                <input type="hidden" id="entity-id">
                <div class="form-group">
                    <label>User:</label>
                    <input type="text" id="entity-name" readonly>
                </div>
                <div class="form-group">
                    <label>Action:</label>
                    <select id="user-action" required>
                        <option value="enable">Enable Account</option>
                        <option value="disable">Disable Account</option>
                    </select>
                </div>
                <div class="form-group" id="reason-group" style="display: none;">
                    <label>Reason:</label>
                    <textarea id="disable-reason" placeholder="Reason for disabling account..."></textarea>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Apply Changes</button>
                </div>
            </form>
        </div>
    </div>

    <div id="participant-details-modal" class="modal">
        <div class="modal-content" style="max-width: 800px;">
            <h3 id="participant-details-title">Participant Details</h3>
            <div id="participant-details-content">
                <!-- Participant details will be loaded here -->
            </div>
            <div class="form-actions">
                <button type="button" class="btn" onclick="closeModal()">Close</button>
            </div>
        </div>
    </div>

    <script>
        const token = '<?php echo htmlspecialchars($token); ?>';

        // Tab switching
        document.querySelectorAll('.nav-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.nav-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');

                tab.classList.add('active');
                document.getElementById(tab.dataset.tab + '-tab').style.display = 'block';

                // Load tab content
                loadTabData(tab.dataset.tab);
            });
        });

        // Load initial data
        console.log('Loading initial dashboard data...');
        loadDashboardStats();

        function loadTabData(tab) {
            console.log('Loading tab data for:', tab);
            switch(tab) {
                case 'dashboard':
                    loadDashboardStats();
                    break;
                case 'usage':
                    loadUsageStats();
                    break;
                case 'users':
                    loadUserData();
                    break;
                case 'regions':
                    loadRegions();
                    break;
                case 'services':
                    loadServices();
                    break;
                case 'participants':
                    loadParticipants();
                    break;
                case 'settings':
                    loadSettings();
                    break;
                case 'audit':
                    loadAuditLog();
                    break;
            }
        }

        async function loadDashboardStats() {
            console.log('JS: Starting loadDashboardStats call...');
            console.log('JS: Token available:', token ? 'YES (' + token.length + ' chars)' : 'NO');

            try {
                const apiUrl = `../backend/api/admin.php?action=dashboard&token=${token}`;
                console.log('JS: API URL:', apiUrl);

                console.log('JS: Making fetch request...');
                const response = await fetch(apiUrl);
                console.log('JS: Response status:', response.status);
                console.log('JS: Response headers:', [...response.headers.entries()]);

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const responseText = await response.text();
                console.log('JS: Raw response text:', responseText);

                const data = JSON.parse(responseText);
                console.log('JS: Parsed JSON data:', data);

                const cardsContainer = document.getElementById('dashboard-cards');
                console.log('JS: Cards container found:', cardsContainer ? 'YES' : 'NO');

                cardsContainer.innerHTML = `
                    <div class="card">
                        <h3>🏢 Active Clients</h3>
                        <div class="metric">${data.stats.active_clients || 0}</div>
                    </div>
                    <div class="card">
                        <h3>🔧 Active Service Providers</h3>
                        <div class="metric">${data.stats.active_sps || 0}</div>
                    </div>
                    <div class="card">
                        <h3>👥 Total Users</h3>
                        <div class="metric">${data.stats.active_users || 0}</div>
                    </div>
                    <div class="card">
                        <h3>💳 Paying Subscribers</h3>
                        <div class="metric">${data.stats.paying_users || 0}</div>
                    </div>
                    <div class="card">
                        <h3>📋 Jobs Created (This Month)</h3>
                        <div class="metric">${data.stats.total_jobs_created || 0}</div>
                    </div>
                    <div class="card">
                        <h3>✅ Jobs Accepted (This Month)</h3>
                        <div class="metric">${data.stats.total_jobs_accepted || 0}</div>
                    </div>
                `;

                console.log('JS: Dashboard HTML updated successfully');

            } catch (error) {
                console.error('JS: Error loading dashboard stats:', error);

                // Display error in the UI for debugging
                const cardsContainer = document.getElementById('dashboard-cards');
                cardsContainer.innerHTML = `
                    <div class="card">
                        <h3 style="color: red;">❌ Error Loading Dashboard</h3>
                        <p style="font-size: 12px; color: #666;">Check browser console for details</p>
                        <p style="font-size: 10px; color: #999; margin-top: 10px;">Error: ${error.message}</p>
                    </div>
                `;
            }
        }

        async function loadUsageStats() {
            const filter = document.getElementById('usage-filter').value;
            const month = document.getElementById('usage-month').value;

            try {
                const response = await fetch(`../backend/api/admin.php?action=usage&user_type=${filter === 'all' ? '' : filter}&month=${month}&token=${token}`);
                const data = await response.json();

                const container = document.getElementById('usage-table-container');
                if (data.usage && data.usage.length > 0) {
                    let html = '<table class="table"><thead><tr>';
                    html += '<th>User</th><th>Company</th><th>Tier</th><th>Jobs Created</th><th>Jobs Accepted</th><th>Limit</th><th>Actions</th>';
                    html += '</tr></thead><tbody>';

                    data.usage.forEach(user => {
                        const jobsCreated = user.jobs_created || 0;
                        const jobsAccepted = user.jobs_accepted || 0;
                        const limit = user.monthly_job_limit;
                        const isNearLimit = (user.entity_type === 'client' && jobsCreated >= limit * 0.8) ||
                                          (user.entity_type === 'service_provider' && jobsAccepted >= limit * 0.8);

                        html += `<tr class="${isNearLimit ? 'near-limit' : ''}">`;
                        html += `<td>${user.first_name} ${user.last_name}</td>`;
                        html += `<td>${user.entity_name || 'N/A'}</td>`;
                        html += `<td>${user.subscription_tier || 'free'}</td>`;
                        html += `<td>${jobsCreated}/${user.entity_type === 'client' ? limit : '∞'}</td>`;
                        html += `<td>${jobsAccepted}/${user.entity_type === 'service_provider' ? limit : '∞'}</td>`;
                        html += `<td>${limit}</td>`;
                        html += `<td><button class="btn btn-primary" onclick="resetUserUsage(${user.id})">Reset</button></td>`;
                        html += '</tr>';
                    });

                    html += '</tbody></table>';
                    container.innerHTML = html;
                } else {
                    container.innerHTML = '<p>No usage data found for selected filters.</p>';
                }
            } catch (error) {
                console.error('Error loading usage stats:', error);
            }
        }

        async function loadUserData() {
            const filter = document.getElementById('user-filter').value;
            const search = document.getElementById('user-search').value;

            try {
                const response = await fetch(`../backend/api/admin.php?action=users&filter=${filter}&search=${encodeURIComponent(search)}&token=${token}`);
                const data = await response.json();

                console.log('User data received:', data); // Debug log

                const container = document.getElementById('users-table-container');
                if (data.users && data.users.length > 0) {
                    let html = '<table class="table"><thead><tr>';
                    html += '<th>User</th><th>Email</th><th>Type</th><th>Company</th><th>Status</th><th>Monthly Usage</th><th>Actions</th>';
                    html += '</tr></thead><tbody>';

                    data.users.forEach(user => {
                        // Properly convert MySQL boolean values for participant enabled status
                        const enabledValue = user.participant_enabled;
                        console.log(`User ${user.first_name} ${user.last_name}: enabledValue = "${enabledValue}" (${typeof enabledValue}), participant_type: ${user.participantType}`);
                        const isEnabled = enabledValue !== null && enabledValue !== undefined && enabledValue !== 0 && enabledValue !== "0" && enabledValue !== false;
                        console.log(`isEnabled = ${isEnabled}`);

                        html += `<tr>`;
                        html += `<td>${user.first_name} ${user.last_name}</td>`;
                        html += `<td>${user.email}</td>`;
                        html += `<td>${user.entity_type}</td>`;
                        html += `<td>${user.participant_name || 'N/A'}</td>`;
                        html += `<td class="${isEnabled ? 'status-enabled' : 'status-disabled'}">${isEnabled ? 'Enabled' : 'Disabled'}</td>`;
                        html += `<td>${user.monthly_jobs_created || 0} created, ${user.monthly_jobs_accepted || 0} accepted</td>`;
                        html += `<td><button class="btn ${isEnabled ? 'btn-danger' : 'btn-success'}" onclick="manageUser('${user.entity_type}', '${user.participantId}', '${user.participant_name}', ${isEnabled})">${isEnabled ? 'Disable' : 'Enable'}</button></td>`;
                        html += '</tr>';
                    });

                    html += '</tbody></table>';
                    container.innerHTML = html;
                } else {
                    container.innerHTML = '<p>No users found matching the criteria.</p>';
                }
            } catch (error) {
                console.error('Error loading user data:', error);
            }
        }

        async function loadSettings() {
            try {
                const response = await fetch(`../backend/api/admin.php?action=settings&token=${token}`);
                const data = await response.json();

                console.log('Settings data:', data); // Debug log

                const container = document.getElementById('settings-container');
                if (data.settings && data.settings.length > 0) {
                    let html = '<table class="table"><thead><tr>';
                    html += '<th>Setting Key</th><th>Current Value</th><th>Description</th><th>Last Updated</th><th>Actions</th>';
                    html += '</tr></thead><tbody>';

                    data.settings.forEach(setting => {
                        html += `<tr>`;
                        html += `<td>${setting.setting_key}</td>`;
                        html += `<td>${setting.setting_value}</td>`;
                        html += `<td>${setting.description || 'N/A'}</td>`;
                        html += `<td>${setting.updated_by || 'System'}<br><small>${new Date(setting.updated_at).toLocaleDateString()}</small></td>`;
                        html += `<td><button class="btn btn-primary" onclick="editSetting(${setting.id}, '${setting.setting_key}', '${setting.setting_value}', '${setting.description || ''}')">Edit</button></td>`;
                        html += '</tr>';
                    });

                    html += '</tbody></table>';
                    container.innerHTML = html;
                } else {
                    container.innerHTML = '<p>No settings found. Response: ' + JSON.stringify(data) + '</p>';
                }
            } catch (error) {
                console.error('Error loading settings:', error);
                document.getElementById('settings-container').innerHTML = '<p>Error loading settings: ' + error.message + '</p>';
            }
        }

        async function loadRegions() {
            const search = document.getElementById('region-search').value;
            const activeOnly = document.getElementById('region-active-filter').value;

            try {
                let url = `../backend/api/admin.php?action=regions&token=${token}`;
                if (search) url += `&search=${encodeURIComponent(search)}`;
                if (activeOnly) url += `&active_only=${activeOnly}`;

                const response = await fetch(url);
                const data = await response.json();

                const container = document.getElementById('regions-table-container');
                if (data.regions && data.regions.length > 0) {
                    let html = '<table class="table"><thead><tr>';
                    html += '<th>Name</th><th>Code</th><th>Country</th><th>Status</th><th>Created</th><th>Actions</th>';
                    html += '</tr></thead><tbody>';

                    data.regions.forEach(function(region) {
                        const statusClass = region.is_active ? 'status-enabled' : 'status-disabled';
                        const statusText = region.is_active ? 'Active' : 'Inactive';

                        // Escape single quotes in string values
                        const escapedName = region.name.replace(/'/g, "\\'");
                        const escapedCountry = region.country.replace(/'/g, "\\'");

                        html += `<tr>`;
                        html += `<td>${region.name}</td>`;
                        html += `<td>${region.code}</td>`;
                        html += `<td>${region.country}</td>`;
                        html += `<td class="${statusClass}">${statusText}</td>`;
                        html += `<td><small>${new Date(region.created_at).toLocaleDateString()}</small></td>`;
                        html += `<td>`;
                        html += `<button class="btn btn-primary" onclick="editRegion(${region.id}, '${escapedName}', '${region.code}', '${escapedCountry}', ${region.is_active})">Edit</button>`;
                        html += ` <button class="btn btn-danger" onclick="deleteRegion(${region.id}, '${escapedName}')">Delete</button>`;
                        html += `</td>`;
                        html += '</tr>';
                    });

                    html += '</tbody></table>';
                    container.innerHTML = html;
                } else {
                    container.innerHTML = '<p>No regions found matching the criteria.</p>';
                }
            } catch (error) {
                console.error('Error loading regions:', error);
                document.getElementById('regions-table-container').innerHTML = '<p>Error loading regions: ' + error.message + '</p>';
            }
        }

        async function loadServices() {
            const search = document.getElementById('service-search').value;
            const category = document.getElementById('service-category-filter').value;
            const activeOnly = document.getElementById('service-active-filter').value;

            try {
                let url = `../backend/api/admin.php?action=services&token=${token}`;
                if (search) url += `&search=${encodeURIComponent(search)}`;
                if (category) url += `&category=${encodeURIComponent(category)}`;
                if (activeOnly) url += `&active_only=${activeOnly}`;

                const response = await fetch(url);
                const data = await response.json();

                const container = document.getElementById('services-table-container');

                // Update category filter options
                if (data.categories && data.categories.length > 0) {
                    const select = document.getElementById('service-category-filter');
                    select.innerHTML = '<option value="">All Categories</option>';
                    data.categories.forEach(cat => {
                        select.innerHTML += `<option value="${cat}">${cat}</option>`;
                    });
                    // Restore selected value if it exists
                    const currentCategory = document.getElementById('service-category-filter').dataset.selected || '';
                    if (currentCategory) select.value = currentCategory;
                }

                if (data.services && data.services.length > 0) {
                    let html = '<table class="table"><thead><tr>';
                    html += '<th>Name</th><th>Category</th><th>Description</th><th>Status</th><th>Created</th><th>Actions</th>';
                    html += '</tr></thead><tbody>';

                    data.services.forEach(service => {
                        const statusClass = service.is_active ? 'status-enabled' : 'status-disabled';
                        const statusText = service.is_active ? 'Active' : 'Inactive';

                        html += `<tr>`;
                        html += `<td>${service.name}</td>`;
                        html += `<td>${service.category}</td>`;
                        html += `<td>${service.description || 'N/A'}</td>`;
                        html += `<td class="${statusClass}">${statusText}</td>`;
                        html += `<td><small>${new Date(service.created_at).toLocaleDateString()}</small></td>`;
                        html += `<td>`;
                        html += `<button class="btn btn-primary" onclick="editService(${service.id}, '${service.name}', '${service.category}', '${service.description || ''}', ${service.is_active})">Edit</button>`;
                        html += ` <button class="btn btn-danger" onclick="deleteService(${service.id}, '${service.name}')">Delete</button>`;
                        html += `</td>`;
                        html += '</tr>';
                    });

                    html += '</tbody></table>';
                    container.innerHTML = html;
                } else {
                    container.innerHTML = '<p>No services found matching the criteria.</p>';
                }
            } catch (error) {
                console.error('Error loading services:', error);
                document.getElementById('services-table-container').innerHTML = '<p>Error loading services: ' + error.message + '</p>';
            }
        }

        async function loadParticipants() {
            const search = document.getElementById('participant-search').value;
            const typeFilter = document.getElementById('participant-type-filter').value;
            const statusFilter = document.getElementById('participant-status-filter').value;

            try {
                let url = `../backend/api/admin.php?action=participants&token=${token}`;
                if (search) url += `&search=${encodeURIComponent(search)}`;
                if (typeFilter && typeFilter !== 'all') url += `&type=${typeFilter}`;
                if (statusFilter && statusFilter !== 'all') url += `&status=${statusFilter}`;

                const response = await fetch(url);
                const data = await response.json();

                const container = document.getElementById('participants-table-container');
                if (data.participants && data.participants.length > 0) {
                    let html = '<table class="table"><thead><tr>';
                    html += '<th>Type</th><th>Company</th><th>Manager</th><th>Email</th><th>Subscription Tier</th><th>Status</th><th>Usage</th><th>Actions</th>';
                    html += '</tr></thead><tbody>';

                    data.participants.forEach(participant => {
                        const statusClass = participant.is_enabled ? 'status-enabled' : 'status-disabled';
                        const statusText = participant.is_enabled ? 'Enabled' : 'Disabled';
                        const participantType = participant.entity_type === 'client' ? 'Client' :
                                              participant.entity_type === 'service_provider' ? 'Service Provider' :
                                              'Both';

                        html += `<tr>`;
                        html += `<td><strong>${participantType}</strong></td>`;
                        html += `<td>${participant.name || 'N/A'}</td>`;
                        html += `<td>${participant.manager_name || participant.first_name + ' ' + participant.last_name}</td>`;
                        html += `<td>${participant.manager_email || participant.email}</td>`;
                        html += `<td>${participant.subscription_tier || 'Free'}</td>`;
                        html += `<td class="${statusClass}">${statusText}</td>`;
                        html += `<td><small>${participant.monthly_usage || 0} jobs this month</small></td>`;
                        html += `<td>`;
                        html += `<button class="btn ${participant.is_enabled ? 'btn-danger' : 'btn-success'}" onclick="toggleParticipantStatus('${participant.entity_type}', '${participant.entity_id}', '${participant.name}', ${participant.is_enabled})">${participant.is_enabled ? 'Disable' : 'Enable'}</button> `;
                        html += `<button class="btn btn-primary" onclick="viewParticipantDetails(${participant.entity_id}, '${participant.name}', '${participant.entity_type}')">View Details</button>`;
                        html += `</td>`;
                        html += '</tr>';
                    });

                    html += '</tbody></table>';
                    container.innerHTML = html;
                } else {
                    container.innerHTML = '<p>No participants found matching the criteria.</p>';
                }
            } catch (error) {
                console.error('Error loading participants:', error);
                document.getElementById('participants-table-container').innerHTML = '<p>Error loading participants: ' + error.message + '</p>';
            }
        }

        async function loadAuditLog() {
            try {
                const response = await fetch(`../backend/api/admin.php?action=audit&token=${token}`);
                const data = await response.json();

                const container = document.getElementById('audit-container');
                if (data.audit_log && data.audit_log.length > 0) {
                    let html = '<table class="table"><thead><tr>';
                    html += '<th>Time</th><th>Admin</th><th>Action</th><th>Target</th><th>Details</th>';
                    html += '</tr></thead><tbody>';

                    data.audit_log.forEach(entry => {
                        html += `<tr>`;
                        html += `<td><small>${new Date(entry.created_at).toLocaleString()}</small></td>`;
                        html += `<td>${entry.admin_name}</td>`;
                        html += `<td>${entry.action_type}</td>`;
                        html += `<td>${entry.target_type} ${entry.target_id || entry.target_identifier || ''}</td>`;
                        html += `<td><small>${entry.notes || ''}</small></td>`;
                        html += '</tr>';
                    });

                    html += '</tbody></table>';
                    container.innerHTML = html;
                } else {
                    container.innerHTML = '<p>No audit log entries found.</p>';
                }
            } catch (error) {
                console.error('Error loading audit log:', error);
            }
        }

        function editSetting(id, key, value, description) {
            document.getElementById('setting-id').value = id;
            document.getElementById('setting-key').value = key;
            document.getElementById('setting-value').value = value;
            document.getElementById('setting-description').value = description;
            document.getElementById('settings-modal').style.display = 'block';
        }

        function manageUser(entityType, entityId, entityName, isEnabled) {
            document.getElementById('entity-type').value = entityType;
            document.getElementById('entity-id').value = entityId;
            document.getElementById('entity-name').value = entityName;
            document.getElementById('user-action').value = isEnabled ? 'disable' : 'enable';
            document.getElementById('user-modal').style.display = 'block';
            toggleReasonField();
        }

        function toggleReasonField() {
            const action = document.getElementById('user-action').value;
            document.getElementById('reason-group').style.display = action === 'disable' ? 'block' : 'none';
        }

        document.getElementById('user-action').addEventListener('change', toggleReasonField);

        function closeModal() {
            document.getElementById('settings-modal').style.display = 'none';
            document.getElementById('region-modal').style.display = 'none';
            document.getElementById('user-modal').style.display = 'none';
            document.getElementById('participant-details-modal').style.display = 'none';
        }

        // Form handlers
        document.getElementById('settings-form').addEventListener('submit', async (e) => {
            e.preventDefault();

            const settingKey = document.getElementById('setting-key').value;
            const settingValue = document.getElementById('setting-value').value;

            try {
                    const response = await fetch('../backend/api/admin.php?action=update_setting&token=' + token, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ setting_key: settingKey, setting_value: settingValue })
                });

                const data = await response.json();
                if (data.success) {
                    alert('Setting updated successfully!');
                    closeModal();
                    loadSettings();
                } else {
                    alert('Error updating setting: ' + (data.error || 'Unknown error'));
                }
            } catch (error) {
                alert('Network error occurred');
            }
        });

        document.getElementById('user-form').addEventListener('submit', async (e) => {
            e.preventDefault();

            const entityType = document.getElementById('entity-type').value;
            const entityId = document.getElementById('entity-id').value;
            const action = document.getElementById('user-action').value;
            const reason = document.getElementById('disable-reason').value;

            const endpoint = action === 'enable' ? 'enable_' + entityType : 'disable_' + entityType;

            try {
                const response = await fetch(`../backend/api/admin.php?action=${endpoint}&token=${token}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ entity_id: entityId, reason: reason })
                });

                const data = await response.json();
                if (data.success) {
                    alert('User ' + action + 'd successfully!');
                    closeModal();
                    loadUserData();
                    loadDashboardStats();
                } else {
                    alert('Error: ' + (data.error || 'Unknown error'));
                }
            } catch (error) {
                alert('Network error occurred');
            }
        });

        document.getElementById('region-form').addEventListener('submit', async (e) => {
            e.preventDefault();

            const regionId = document.getElementById('region-id').value;
            const name = document.getElementById('region-name').value;
            const code = document.getElementById('region-code').value;
            const country = document.getElementById('region-country').value;
            const isActive = document.getElementById('region-active').checked;

            const action = regionId ? 'update_region' : 'create_region';
            const method = regionId ? 'PUT' : 'POST';

            try {
                const response = await fetch(`../backend/api/admin.php?action=${action}&token=${token}`, {
                    method: method,
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id: regionId || undefined,
                        name,
                        code,
                        country,
                        is_active: isActive
                    })
                });

                const data = await response.json();
                if (data.success) {
                    alert(`Region ${regionId ? 'updated' : 'created'} successfully!`);
                    closeModal();
                    loadRegions(); // Refresh the regions list
                } else {
                    alert('Error saving region: ' + (data.error || 'Unknown error'));
                }
            } catch (error) {
                alert('Network error occurred while saving region');
                console.error('Save region error:', error);
            }
        });

        // Service management functions
        function editService(id, name, category, description, isActive) {
            console.log('Edit service called:', { id, name, category, description, isActive });
            alert('Edit Service: ' + name + '\n\nFeature coming soon! ID: ' + id);
        }

        function deleteService(id, name) {
            console.log('Delete service called:', { id, name });
            alert('Delete Service: ' + name + '\n\nFeature coming soon! ID: ' + id);
        }

        function editRegion(id, name, code, country, isActive) {
            console.log('Edit region called:', { id, name, code, country, isActive });

            // Populate modal with region data
            document.getElementById('region-id').value = id;
            document.getElementById('region-name').value = name;
            document.getElementById('region-code').value = code;
            document.getElementById('region-country').value = country;
            document.getElementById('region-active').checked = isActive;

            document.getElementById('region-modal').style.display = 'block';
        }

        async function deleteRegion(id, name) {
            console.log('Delete region called:', { id, name });

            if (!confirm(`Are you sure you want to delete the region "${name}"?\n\nThis action cannot be undone.`)) {
                return;
            }

            try {
                const response = await fetch(`../backend/api/admin.php?id=${id}&token=${token}`, {
                    method: 'DELETE'
                });

                const data = await response.json();
                if (data.success) {
                    alert('Region deleted successfully!');
                    loadRegions(); // Refresh the regions list
                } else {
                    alert('Error deleting region: ' + (data.error || 'Unknown error'));
                }
            } catch (error) {
                alert('Network error occurred while deleting region');
                console.error('Delete region error:', error);
            }
        }

        function toggleParticipantStatus(entityType, entityId, name, isEnabled) {
            console.log('Toggle participant status called:', { entityType, entityId, name, isEnabled });
            if (!confirm(`Are you sure you want to ${isEnabled ? 'disable' : 'enable'} participant ${name}?`)) return;

            // Use existing manageUser function to show modal
            manageUser(entityType, entityId, name, isEnabled);
        }

        async function viewParticipantDetails(entityId, name, entityType) {
            console.log('View participant details called:', { entityId, name, entityType });

            try {
                const response = await fetch(`../backend/api/admin.php?action=participant_details&id=${entityId}&token=${token}`);
                const data = await response.json();

                if (data.success && data.participant) {
                    const participant = data.participant;
                    const participantType = participant.entity_type === 'client' ? 'Client' : 'Service Provider';

                    // Set modal title
                    document.getElementById('participant-details-title').textContent = `${participantType} Details: ${participant.name}`;

                    // Build detailed content
                    let html = '<div style="line-height: 1.6;">';

                    // Basic Information Section
                    html += '<div style="margin-bottom: 20px;">';
                    html += '<h4 style="margin-bottom: 10px; color: #495057;">📋 Basic Information</h4>';
                    html += '<table style="width: 100%; border-collapse: collapse;">';
                    html += `<tr><th style="text-align: left; padding: 8px; background: #f8f9fa; border: 1px solid #dee2e6;">Name</th><td style="padding: 8px; border: 1px solid #dee2e6;">${participant.name || 'N/A'}</td></tr>`;
                    html += `<tr><th style="text-align: left; padding: 8px; background: #f8f9fa; border: 1px solid #dee2e6;">Type</th><td style="padding: 8px; border: 1px solid #dee2e6;">${participantType}</td></tr>`;
                    html += `<tr><th style="text-align: left; padding: 8px; background: #f8f9fa; border: 1px solid #dee2e6;">Manager</th><td style="padding: 8px; border: 1px solid #dee2e6;">${participant.manager_name || 'N/A'}</td></tr>`;
                    html += `<tr><th style="text-align: left; padding: 8px; background: #f8f9fa; border: 1px solid #dee2e6;">Email</th><td style="padding: 8px; border: 1px solid #dee2e6;">${participant.manager_email || 'N/A'}</td></tr>`;
                    html += `<tr><th style="text-align: left; padding: 8px; background: #f8f9fa; border: 1px solid #dee2e6;">Status</th><td style="padding: 8px; border: 1px solid #dee2e6;"><span class="${participant.is_enabled ? 'status-enabled' : 'status-disabled'}">${participant.is_enabled ? 'Enabled' : 'Disabled'}</span></td></tr>`;
                    html += `<tr><th style="text-align: left; padding: 8px; background: #f8f9fa; border: 1px solid #dee2e6;">Created</th><td style="padding: 8px; border: 1px solid #dee2e6;">${new Date(participant.created_at).toLocaleDateString()}</td></tr>`;
                    html += '</table>';
                    html += '</div>';

                    // Subscription Information Section
                    html += '<div style="margin-bottom: 20px;">';
                    html += '<h4 style="margin-bottom: 10px; color: #495057;">💳 Subscription</h4>';
                    html += '<table style="width: 100%; border-collapse: collapse;">';
                    html += `<tr><th style="text-align: left; padding: 8px; background: #f8f9fa; border: 1px solid #dee2e6;">Tier</th><td style="padding: 8px; border: 1px solid #dee2e6;">${participant.subscription_tier || 'Free'}</td></tr>`;
                    if (participant.subscription_tier !== 'free') {
                        html += `<tr><th style="text-align: left; padding: 8px; background: #f8f9fa; border: 1px solid #dee2e6;">Monthly Limit</th><td style="padding: 8px; border: 1px solid #dee2e6;">${participant.monthly_job_limit || 'Unlimited'}</td></tr>`;
                        html += `<tr><th style="text-align: left; padding: 8px; background: #f8f9fa; border: 1px solid #dee2e6;">Subscription Enabled</th><td style="padding: 8px; border: 1px solid #dee2e6;"><span class="${participant.subscription_enabled ? 'status-enabled' : 'status-disabled'}">${participant.subscription_enabled ? 'Yes' : 'No'}</span></td></tr>`;
                    }
                    html += `<tr><th style="text-align: left; padding: 8px; background: #f8f9fa; border: 1px solid #dee2e6;">Monthly Usage</th><td style="padding: 8px; border: 1px solid #dee2e6;">${data.monthly_usage || 0} jobs this month</td></tr>`;
                    html += '</table>';
                    html += '</div>';

                    // Associated Users Section
                    if (data.users && data.users.length > 0) {
                        html += '<div style="margin-bottom: 20px;">';
                        html += '<h4 style="margin-bottom: 10px; color: #495057;">👥 Associated Users</h4>';
                        html += '<table style="width: 100%; border-collapse: collapse;">';
                        html += '<tr><th style="background: #f8f9fa; border: 1px solid #dee2e6; padding: 8px;">Name</th><th style="background: #f8f9fa; border: 1px solid #dee2e6; padding: 8px;">Email</th><th style="background: #f8f9fa; border: 1px solid #dee2e6; padding: 8px;">Role</th><th style="background: #f8f9fa; border: 1px solid #dee2e6; padding: 8px;">Status</th><th style="background: #f8f9fa; border: 1px solid #dee2e6; padding: 8px;">Created</th></tr>';

                        data.users.forEach(user => {
                            const userStatus = user.user_active ? 'Active' : 'Inactive';
                            const statusClass = user.user_active ? 'status-enabled' : 'status-disabled';
                            html += `<tr>`;
                            html += `<td style="border: 1px solid #dee2e6; padding: 8px;">${user.first_name} ${user.last_name}</td>`;
                            html += `<td style="border: 1px solid #dee2e6; padding: 8px;">${user.email}</td>`;
                            html += `<td style="border: 1px solid #dee2e6; padding: 8px;">${user.role_name || 'User'}</td>`;
                            html += `<td style="border: 1px solid #dee2e6; padding: 8px;"><span class="${statusClass}">${userStatus}</span></td>`;
                            html += `<td style="border: 1px solid #dee2e6; padding: 8px;">${new Date(user.user_created).toLocaleDateString()}</td>`;
                            html += '</tr>';
                        });
                        html += '</table>';
                        html += '</div>';
                    }

                    // Locations Section (for clients)
                    if (data.locations && data.locations.length > 0 && participant.participantType === 'C') {
                        html += '<div style="margin-bottom: 20px;">';
                        html += '<h4 style="margin-bottom: 10px; color: #495057;">📍 Client Locations</h4>';
                        html += '<div style="max-height: 200px; overflow-y: auto;">';
                        data.locations.forEach(location => {
                            html += '<div style="border: 1px solid #dee2e6; padding: 10px; margin-bottom: 8px; border-radius: 4px;">';
                            html += `<strong>${location.name || location.address}</strong><br>`;
                            html += `<small>Address: ${location.address}</small><br>`;
                            if (location.access_instructions) html += `<small>Instructions: ${location.access_instructions}</small><br>`;
                            html += `<small>Region: ${location.region_name} (${location.region_code})</small>`;
                            html += '</div>';
                        });
                        html += '</div>';
                        html += '</div>';
                    }

                    // Assigned Regions Section (for service providers)
                    if (data.assigned_regions && data.assigned_regions.length > 0 && participant.participantType === 'S') {
                        html += '<div style="margin-bottom: 20px;">';
                        html += '<h4 style="margin-bottom: 10px; color: #495057;">🗺️ Service Areas</h4>';
                        const regions = data.assigned_regions.map(r => `${r.name} (${r.code})`).join(', ');
                        html += `<p>${regions}</p>`;
                        html += '</div>';
                    }

                    // Assigned Services Section (for service providers)
                    if (data.assigned_services && data.assigned_services.length > 0 && participant.participantType === 'S') {
                        html += '<div style="margin-bottom: 20px;">';
                        html += '<h4 style="margin-bottom: 10px; color: #495057;">🛠️ Offered Services</h4>';
                        const servicesByCategory = {};
                        data.assigned_services.forEach(service => {
                            if (!servicesByCategory[service.category]) {
                                servicesByCategory[service.category] = [];
                            }
                            servicesByCategory[service.category].push(service.name);
                        });

                        Object.keys(servicesByCategory).forEach(category => {
                            html += `<strong>${category}:</strong> ${servicesByCategory[category].join(', ')}<br>`;
                        });
                        html += '</div>';
                    }

                    // Recent Jobs Section
                    if (data.recent_jobs && data.recent_jobs.length > 0) {
                        html += '<div style="margin-bottom: 20px;">';
                        html += '<h4 style="margin-bottom: 10px; color: #495057;">📋 Recent Activity</h4>';
                        html += '<table style="width: 100%; border-collapse: collapse;">';
                        html += '<tr><th style="background: #f8f9fa; border: 1px solid #dee2e6; padding: 8px;">Job Title</th><th style="background: #f8f9fa; border: 1px solid #dee2e6; padding: 8px;">Status</th><th style="background: #f8f9fa; border: 1px solid #dee2e6; padding: 8px;">Created</th></tr>';

                        data.recent_jobs.slice(0, 5).forEach(job => {
                            html += `<tr>`;
                            html += `<td style="border: 1px solid #dee2e6; padding: 8px;">${job.title}</td>`;
                            html += `<td style="border: 1px solid #dee2e6; padding: 8px;">${job.status}</td>`;
                            html += `<td style="border: 1px solid #dee2e6; padding: 8px;"><small>${new Date(job.created_at).toLocaleDateString()}</small></td>`;
                            html += '</tr>';
                        });
                        html += '</table>';
                        if (data.recent_jobs.length > 5) {
                            html += `<p><small>Showing first 5 of ${data.recent_jobs.length} recent jobs</small></p>`;
                        }
                        html += '</div>';
                    }

                    html += '</div>';

                    // Set content and show modal
                    document.getElementById('participant-details-content').innerHTML = html;
                    document.getElementById('participant-details-modal').style.display = 'block';

                } else {
                    alert('Error loading participant details: ' + (data.error || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error loading participant details:', error);
                alert('Network error occurred while loading participant details');
            }
        }

        function manageProviderAssignments(id, name) {
            console.log('Legacy function called:', { id, name });
            alert('Legacy function - participant management now in Participants tab');
        }

        // Modal placeholders
        function showCreateRegionModal() {
            // Clear form for creating new region
            document.getElementById('region-id').value = '';
            document.getElementById('region-name').value = '';
            document.getElementById('region-code').value = '';
            document.getElementById('region-country').value = 'South Africa';
            document.getElementById('region-active').checked = true;
            document.getElementById('region-modal-title').textContent = 'Create New Region';

            document.getElementById('region-modal').style.display = 'block';

            // Focus on first input
            document.getElementById('region-name').focus();
        }

        function showCreateServiceModal() {
            alert('Add Service - Feature coming soon!');
        }

        // Utility functions
        async function resetUserUsage(userId) {
            if (!confirm('Are you sure you want to reset this user\'s monthly usage counters?')) return;

            try {
                const response = await fetch('../backend/api/admin.php?action=reset_usage&token=' + token, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: userId })
                });

                const data = await response.json();
                if (data.success) {
                    alert('Usage reset successfully!');
                    loadUsageStats();
                } else {
                    alert('Error resetting usage: ' + (data.error || 'Unknown error'));
                }
            } catch (error) {
                alert('Network error occurred');
            }
        }
    </script>
</body>
</html>
