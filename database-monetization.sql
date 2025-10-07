-- Monetization Migration: Standalone monetization setup
-- This can be run independently of the invitations system
-- Prerequisites: Basic schema.sql structure must exist

USE faultreporter;

-- Extend existing site_settings table (created by database-invitations.sql)
-- If site_settings doesn't exist, it will be created by the invitations migration
ALTER TABLE site_settings
ADD COLUMN IF NOT EXISTS description TEXT,
ADD COLUMN IF NOT EXISTS updated_by_user_id INT;

ALTER TABLE site_settings
ADD CONSTRAINT IF NOT EXISTS fk_updated_by_user FOREIGN KEY (updated_by_user_id) REFERENCES users(id);

-- Insert monetization settings
-- These are configurable parameters that can be changed through the admin interface
INSERT INTO site_settings (setting_key, setting_value, setting_type, description) VALUES
('client_free_jobs_per_month', '3', 'int', 'Number of free jobs allowed per month for clients'),
('sp_free_jobs_per_month', '4', 'int', 'Number of free jobs allowed per month for service providers'),
('client_basic_subscription_price', '150.00', 'string', 'Monthly price for basic client subscription (Rands)'),
('sp_basic_subscription_price', '300.00', 'string', 'Monthly price for basic service provider subscription (Rands)'),
('client_advanced_asset_management_price', '99.00', 'string', 'Monthly extra fee for client advanced asset management'),
('client_advanced_maintenance_cost_price', '149.00', 'string', 'Monthly extra fee for client advanced maintenance cost recording'),
('sp_advanced_qr_creation_price', '79.00', 'string', 'Monthly extra fee for service provider QR code creation'),
('sp_advanced_job_cost_collection_price', '99.00', 'string', 'Monthly extra fee for service provider job cost collection'),
('sp_advanced_health_safety_price', '129.00', 'string', 'Monthly extra fee for service provider health and safety assessment'),
('sp_advanced_technician_routing_price', '149.00', 'string', 'Monthly extra fee for service provider technician efficiency routing'),
('subscription_grace_period_days', '7', 'int', 'Grace period in days for failed subscription payments'),
('job_image_retention_free_days', '30', 'int', 'Image retention period for free tier users'),
('job_image_retention_basic_days', '365', 'int', 'Image retention period for basic tier users'),
('job_image_retention_advanced_days', '730', 'int', 'Image retention period for advanced tier users')
ON DUPLICATE KEY UPDATE
    setting_value = VALUES(setting_value),
    setting_type = VALUES(setting_type),
    description = VALUES(description);

-- Add subscription-related columns to users table
-- These track each user's subscription status and limits
ALTER TABLE users
ADD COLUMN IF NOT EXISTS subscription_tier ENUM('free', 'basic', 'advanced') DEFAULT 'free',
ADD COLUMN IF NOT EXISTS subscription_expires DATE NULL,
ADD COLUMN IF NOT EXISTS stripe_customer_id VARCHAR(100) NULL,
ADD COLUMN IF NOT EXISTS monthly_job_limit INT DEFAULT 3,
ADD COLUMN IF NOT EXISTS subscription_enabled BOOLEAN DEFAULT TRUE;

-- Create usage tracking table
-- Tracks monthly job creation and acceptance limits per user
CREATE TABLE IF NOT EXISTS usage_tracking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    usage_type ENUM('jobs_created', 'jobs_accepted') NOT NULL,
    usage_month VARCHAR(7) NOT NULL COMMENT 'Format: YYYY-MM',
    count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_month_type (user_id, usage_month, usage_type),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Create user features table
-- Tracks which advanced features each user has enabled
CREATE TABLE IF NOT EXISTS user_features (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    feature_name VARCHAR(100) NOT NULL,
    is_enabled BOOLEAN DEFAULT TRUE,
    valid_until DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_feature (user_id, feature_name),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Pre-populate user_features for existing users
-- This enables the feature system for users who already exist
-- Features are disabled by default and can be enabled through the subscription API
INSERT IGNORE INTO user_features (user_id, feature_name, is_enabled)
SELECT DISTINCT u.id, feature_name, FALSE
FROM users u
CROSS JOIN (
    SELECT 'asset_management_qr' as feature_name UNION ALL
    SELECT 'maintenance_cost_analysis' UNION ALL
    SELECT 'sp_qr_codes' UNION ALL
    SELECT 'job_cost_collection' UNION ALL
    SELECT 'health_safety_assessment' UNION ALL
    SELECT 'technician_routing'
) features
WHERE u.role_id IN (1, 2) -- Reporting Employee, Site Budget Controller (client-side features)
   OR u.role_id = 3;       -- Service Provider Admin (SP-side features)

-- Create indexes for performance
CREATE INDEX IF NOT EXISTS idx_usage_tracking_user_month ON usage_tracking(user_id, usage_month);
CREATE INDEX IF NOT EXISTS idx_user_features_user ON user_features(user_id, is_enabled);
CREATE INDEX IF NOT EXISTS idx_site_settings_key ON site_settings(setting_key);

COMMIT;
