-- Create missing admin tables for MariaDB 10.11.13 compatibility
USE snappy;

-- 1. Create site_settings table (if not exists)
CREATE TABLE IF NOT EXISTS site_settings (
    id INT NOT NULL AUTO_INCREMENT,
    setting_key VARCHAR(255) NOT NULL,
    setting_value TEXT NOT NULL,
    setting_type VARCHAR(20) NOT NULL DEFAULT 'string',
    description TEXT,
    updated_by_user_id INT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key),
    INDEX idx_updated_by (updated_by_user_id),
    PRIMARY KEY (id)
);

-- 2. Create user_roles table (if not exists)
CREATE TABLE IF NOT EXISTS user_roles (
    roleId INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    PRIMARY KEY (roleId)
);

-- 3. Create admin_actions table (if not exists) - no ENUM fields to avoid issues
CREATE TABLE IF NOT EXISTS admin_actions (
    id INT NOT NULL AUTO_INCREMENT,
    admin_user_id INT NOT NULL,
    action_type VARCHAR(50) NOT NULL,
    target_type VARCHAR(50) NOT NULL,
    target_id INT NULL,
    target_identifier VARCHAR(255) NULL,
    old_value TEXT NULL,
    new_value TEXT NULL,
    notes TEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_admin_user (admin_user_id),
    INDEX idx_action_type (action_type),
    INDEX idx_target_type (target_type, target_id),
    INDEX idx_created_at (created_at),
    PRIMARY KEY (id)
);

-- 4. Create regions table (if not exists)
CREATE TABLE IF NOT EXISTS regions (
    id INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(20) NOT NULL,
    country VARCHAR(100) NOT NULL DEFAULT 'South Africa',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_code (code),
    INDEX idx_active (is_active),
    PRIMARY KEY (id)
);

-- 5. Create services table (if not exists)
CREATE TABLE IF NOT EXISTS services (
    id INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL,
    description TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_category (category),
    INDEX idx_active (is_active),
    PRIMARY KEY (id)
);

-- 6. Create service_provider_services table (if not exists)
CREATE TABLE IF NOT EXISTS service_provider_services (
    id INT NOT NULL AUTO_INCREMENT,
    service_provider_id INT NOT NULL,
    service_id INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_provider_service (service_provider_id, service_id),
    INDEX idx_service_provider (service_provider_id),
    INDEX idx_service (service_id),
    UNIQUE KEY uk_provider_service (service_provider_id, service_id),
    PRIMARY KEY (id)
);

-- 7. Create service_provider_regions table (if not exists)
CREATE TABLE IF NOT EXISTS service_provider_regions (
    id INT NOT NULL AUTO_INCREMENT,
    service_provider_id INT NOT NULL,
    region_id INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_provider_region (service_provider_id, region_id),
    INDEX idx_service_provider_region (service_provider_id),
    INDEX idx_region (region_id),
    UNIQUE KEY uk_provider_region (service_provider_id, region_id),
    PRIMARY KEY (id)
);

SELECT 'Admin tables creation completed successfully!' as status;

-- Show what was created
SHOW TABLES LIKE '%';

SELECT 'Total tables created' as info;
