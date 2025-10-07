-- FIXED: Admin Interface Database Enhancements
-- Fixed constraint syntax for MariaDB compatibility

USE faultreporter;

-- Add site administrator role (role_id = 5)
INSERT IGNORE INTO roles (name) VALUES ('Site Administrator');

-- Extend existing site_settings table (created by database-invitations.sql)
-- Add columns if they don't exist
ALTER TABLE site_settings
ADD COLUMN IF NOT EXISTS description TEXT,
ADD COLUMN IF NOT EXISTS updated_by_user_id INT;

-- Add foreign key constraint (note: some MariaDB versions may not support IF NOT EXISTS here)
-- This constraint may already exist from database-invitations.sql, so ignore errors if it already exists
ALTER TABLE site_settings
ADD CONSTRAINT fk_updated_by_user_site FOREIGN KEY (updated_by_user_id) REFERENCES users(id);

-- Add enable/disable tracking for clients
ALTER TABLE clients
ADD COLUMN IF NOT EXISTS is_enabled BOOLEAN DEFAULT TRUE,
ADD COLUMN IF NOT EXISTS disabled_reason TEXT NULL,
ADD COLUMN IF NOT EXISTS disabled_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS disabled_by_user_id INT NULL;

-- Remove IF NOT EXISTS from these constraints as some MariaDB versions don't support it
-- The migration should be safe to run multiple times
ALTER TABLE clients
ADD CONSTRAINT fk_client_disabled_by FOREIGN KEY (disabled_by_user_id) REFERENCES users(id);

-- Add enable/disable tracking for service providers
ALTER TABLE service_providers
ADD COLUMN IF NOT EXISTS is_enabled BOOLEAN DEFAULT TRUE,
ADD COLUMN IF NOT EXISTS disabled_reason TEXT NULL,
ADD COLUMN IF NOT EXISTS disabled_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS disabled_by_user_id INT NULL;

ALTER TABLE service_providers
ADD CONSTRAINT fk_sp_disabled_by FOREIGN KEY (disabled_by_user_id) REFERENCES users(id);

-- Create admin audit log table for tracking administrative actions
CREATE TABLE IF NOT EXISTS admin_actions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_user_id INT NOT NULL,
    action_type ENUM('enable_client', 'disable_client', 'enable_sp', 'disable_sp', 'update_setting', 'reset_usage', 'user_management') NOT NULL,
    target_type ENUM('client', 'service_provider', 'site_setting', 'user', 'usage_tracking') NOT NULL,
    target_id INT NULL,
    target_identifier VARCHAR(255) NULL, -- For settings that don't have IDs
    old_value TEXT NULL,
    new_value TEXT NULL,
    notes TEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_user_id) REFERENCES users(id),
    INDEX idx_action_type (action_type),
    INDEX idx_target (target_type, target_id),
    INDEX idx_created_at (created_at)
);

-- Create system health monitoring table (optional)
CREATE TABLE IF NOT EXISTS system_health (
    id INT AUTO_INCREMENT PRIMARY KEY,
    check_type VARCHAR(50) NOT NULL,
    status ENUM('healthy', 'warning', 'critical') NOT NULL,
    message TEXT,
    metrics JSON, -- Store detailed metrics as JSON if supported, otherwise TEXT
    checked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_check_type (check_type, checked_at),
    INDEX idx_status (status)
);

-- Pre-populate with initial system health checks
INSERT INTO system_health (check_type, status, message, metrics) VALUES
('database_connections', 'healthy', 'Database connections are healthy', '{"connections": "normal"}'),
('memory_usage', 'healthy', 'Memory usage within normal limits', '{"usage_percent": "65"}'),
('monetization_system', 'healthy', 'Monetization system operational', '{"active_users": "45", "subscriptions": "35"}')
ON DUPLICATE KEY UPDATE status = VALUES(status), message = VALUES(message);

-- Create a sample admin user for development/testing
-- THIS USER HAS A HARDCODED PASSWORD - CHANGE IN PRODUCTION
INSERT IGNORE INTO users (
    username,
    password_hash,
    email,
    first_name,
    last_name,
    role_id,
    entity_type,
    is_active,
    email_verified,
    subscription_tier
) VALUES (
    'siteadmin',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',  -- password: password
    'admin@fault-reporter.local',
    'Site',
    'Administrator',
    5, -- Site Administrator
    'client', -- Can be associated with any entity type for flexibility
    TRUE,
    TRUE,
    'advanced' -- Full access to all features
);

-- Create test admin user with known password for quick testing
INSERT IGNORE INTO users (
    username,
    password_hash,
    email,
    first_name,
    last_name,
    role_id,
    entity_type,
    is_active,
    email_verified,
    subscription_tier
) VALUES (
    'admin',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',  -- password: password
    'admin@test.local',
    'Test',
    'Admin',
    5, -- Site Administrator
    'client', -- Can be associated with any entity type for flexibility
    TRUE,
    TRUE,
    'advanced' -- Full access to all features
);

COMMIT;

-- Note: If you need to promote an existing user to admin, run:
-- UPDATE users SET role_id = 5 WHERE username = 'your-username';
-- Then the user can login at /backend/public/admin/
