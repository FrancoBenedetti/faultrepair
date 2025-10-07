-- Admin Interface Database Enhancements
-- Adds site administrator role and admin functionality

USE faultreporter;

-- Add site administrator role (role_id = 5)
INSERT INTO roles (name) VALUES ('Site Administrator');

-- Add enable/disable tracking for clients and service providers
ALTER TABLE clients
ADD COLUMN IF NOT EXISTS is_enabled BOOLEAN DEFAULT TRUE,
ADD COLUMN IF NOT EXISTS disabled_reason TEXT NULL,
ADD COLUMN IF NOT EXISTS disabled_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS disabled_by_user_id INT NULL,
ADD CONSTRAINT IF NOT EXISTS fk_client_disabled_by FOREIGN KEY (disabled_by_user_id) REFERENCES users(id);

ALTER TABLE service_providers
ADD COLUMN IF NOT EXISTS is_enabled BOOLEAN DEFAULT TRUE,
ADD COLUMN IF NOT EXISTS disabled_reason TEXT NULL,
ADD COLUMN IF NOT EXISTS disabled_at TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS disabled_by_user_id INT NULL,
ADD CONSTRAINT IF NOT EXISTS fk_sp_disabled_by FOREIGN KEY (disabled_by_user_id) REFERENCES users(id);

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

-- Create system health monitoring table
CREATE TABLE IF NOT EXISTS system_health (
    id INT AUTO_INCREMENT PRIMARY KEY,
    check_type VARCHAR(50) NOT NULL,
    status ENUM('healthy', 'warning', 'critical') NOT NULL,
    message TEXT,
    metrics JSON, -- Store detailed metrics as JSON
    checked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_check_type (check_type, checked_at),
    INDEX idx_status (status)
);

-- Pre-populate with a sample admin user for development (remove in production)
-- This creates a site administrator account with known credentials
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
    '$2y$10$8zU8RnKVflQXKvQYJQ4zLeGnJcjnIpYWQkEjNuZ6pJcYPZ9QHhF6i',
    'admin@fault-reporter.local',
    'Site',
    'Administrator',
    5, -- Site Administrator
    'client', -- Can be associated with any entity type
    TRUE,
    TRUE,
    'advanced' -- Full access
);

COMMIT;

-- Insert some initial system health checks
INSERT INTO system_health (check_type, status, message, metrics) VALUES
('database_connections', 'healthy', 'Database connections are healthy', '{"connections": "normal"}'),
('disk_space', 'healthy', 'Disk space usage within normal limits', '{"free_space_gb": "50", "usage_percent": "45"}'),
('memory_usage', 'healthy', 'Memory usage within normal limits', '{"usage_percent": "65"}'),
('subscription_system', 'healthy', 'Monetization system operational', '{"active_users": "45", "subscriptions": "35"}')
ON DUPLICATE KEY UPDATE status = VALUES(status), message = VALUES(message);

Commit;
