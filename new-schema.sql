-- Improved Fault Reporter Database Schema (Snappy Architecture)
-- Clean, participant-based design with proper separation of concerns

CREATE DATABASE IF NOT EXISTS snappy;
USE snappy;

-- User roles (authentication roles, separate from business logic)
CREATE TABLE user_roles (
    roleId INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
);

INSERT INTO user_roles (name) VALUES
('Client User'),
('Client Admin'),
('Service Provider Technician'),
('Service Provider Admin'),
('System Administrator');

-- Participants (unified entity table replacing separate clients/service_providers)
CREATE TABLE participants (
    participantId INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    address TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    website VARCHAR(255),
    manager_name VARCHAR(100),
    manager_email VARCHAR(100),
    manager_phone VARCHAR(20),
    vat_number VARCHAR(50),
    business_registration_number VARCHAR(50),
    description TEXT,
    logo_url VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    is_enabled BOOLEAN DEFAULT TRUE,  -- For admin disabling
    disabled_reason TEXT,
    disabled_at TIMESTAMP NULL,
    disabled_by_user_id INT,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Participant types (defines whether a participant is a client or service provider)
CREATE TABLE participant_type (
    participantId INT NOT NULL,
    participantType ENUM('C','S') NOT NULL DEFAULT 'S',  -- C=Client, S=Service Provider
    isActive ENUM('Y','N') DEFAULT 'Y',
    PRIMARY KEY (participantId, participantType),
    FOREIGN KEY (participantId) REFERENCES participants(participantId) ON DELETE CASCADE
);

-- Users (cleaned up - only authentication, linked to participants)
CREATE TABLE users (
    userId INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    entity_id INT NOT NULL,  -- Links to participants.participantId
    role_id INT DEFAULT 1,   -- Links to user_roles.roleId
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    phone VARCHAR(20),
    is_active BOOLEAN DEFAULT TRUE,
    email_verified BOOLEAN DEFAULT FALSE,
    verification_token VARCHAR(255),
    token_expires TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (entity_id) REFERENCES participants(participantId),
    FOREIGN KEY (role_id) REFERENCES user_roles(roleId)
);

-- Subscriptions (directly associated with participants, not users)
CREATE TABLE subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    participantId INT NOT NULL,  -- Participants own subscriptions
    subscription_tier ENUM('free', 'basic', 'advanced') DEFAULT 'free',
    status ENUM('active', 'cancelled', 'expired', 'suspended') DEFAULT 'active',
    stripe_customer_id VARCHAR(100),
    monthly_job_limit INT DEFAULT 3,
    subscription_enabled BOOLEAN DEFAULT TRUE,
    current_period_start DATE,
    current_period_end DATE,
    cancel_at_period_end BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (participantId) REFERENCES participants(participantId)
);

-- Participant features (associated with participants, particularly service providers)
CREATE TABLE participant_features (
    id INT AUTO_INCREMENT PRIMARY KEY,
    participantId INT NOT NULL,
    feature_name VARCHAR(100) NOT NULL,
    is_enabled BOOLEAN DEFAULT TRUE,
    valid_until DATE NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_participant_feature (participantId, feature_name),
    FOREIGN KEY (participantId) REFERENCES participants(participantId)
);

-- Usage tracking (per subscription, not per user)
CREATE TABLE subscription_usage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subscription_id INT NOT NULL,
    usage_type ENUM('jobs_created', 'jobs_accepted') NOT NULL,
    usage_month VARCHAR(7) NOT NULL COMMENT 'Format: YYYY-MM',
    count INT DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_subscription_month_type (subscription_id, usage_month, usage_type),
    FOREIGN KEY (subscription_id) REFERENCES subscriptions(id)
);

-- Site settings (configurable parameters)
CREATE TABLE site_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    setting_type ENUM('string', 'int', 'bool', 'json') DEFAULT 'string',
    description TEXT,
    updated_by_user_id INT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (updated_by_user_id) REFERENCES users(userId)
);

-- Locations (associated with participants)
CREATE TABLE locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    participant_id INT NOT NULL,
    name VARCHAR(100),
    address TEXT,
    coordinates VARCHAR(50),
    access_rules VARCHAR(500),
    access_instructions TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (participant_id) REFERENCES participants(participantId)
);

-- Jobs (connects participants through locations)
CREATE TABLE jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_location_id INT NOT NULL,  -- References locations which belong to participants
    item_identifier VARCHAR(100),
    fault_description TEXT,
    technician_notes TEXT,
    assigned_provider_participant_id INT,  -- References participants via participant_type
    reporting_user_id INT NOT NULL,
    assigning_user_id INT,
    contact_person VARCHAR(100),
    assigned_technician_user_id INT,
    job_status VARCHAR(50) DEFAULT 'Reported',
    archived_by_client BOOLEAN DEFAULT FALSE,
    archived_by_service_provider BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_location_id) REFERENCES locations(id),
    FOREIGN KEY (assigned_provider_participant_id) REFERENCES participants(participantId),
    FOREIGN KEY (reporting_user_id) REFERENCES users(userId),
    FOREIGN KEY (assigning_user_id) REFERENCES users(userId),
    FOREIGN KEY (assigned_technician_user_id) REFERENCES users(userId)
);

-- Job status history
CREATE TABLE job_status_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    status VARCHAR(50) NOT NULL,
    changed_by_user_id INT NOT NULL,
    changed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES jobs(id),
    FOREIGN KEY (changed_by_user_id) REFERENCES users(userId)
);

-- Client-Provider approvals (participants to participants)
CREATE TABLE participant_approvals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_participant_id INT NOT NULL,
    provider_participant_id INT NOT NULL,
    UNIQUE KEY unique_client_provider (client_participant_id, provider_participant_id),
    FOREIGN KEY (client_participant_id) REFERENCES participants(participantId),
    FOREIGN KEY (provider_participant_id) REFERENCES participants(participantId)
);

-- Job notes
CREATE TABLE job_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    user_id INT NOT NULL,
    note TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES jobs(id),
    FOREIGN KEY (user_id) REFERENCES users(userId)
);

-- Admin actions audit log
CREATE TABLE admin_actions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_user_id INT NOT NULL,
    action_type ENUM('enable_participant', 'disable_participant', 'update_setting', 'reset_usage', 'feature_management') NOT NULL,
    target_type ENUM('participant', 'site_setting', 'subscription', 'feature') NOT NULL,
    target_id INT NULL,
    target_identifier VARCHAR(255) NULL,
    old_value TEXT NULL,
    new_value TEXT NULL,
    notes TEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_user_id) REFERENCES users(userId),
    INDEX idx_action_type (action_type),
    INDEX idx_target (target_type, target_id),
    INDEX idx_created_at (created_at)
);

-- Regions for service provider geographic coverage
CREATE TABLE regions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(10) NOT NULL,
    country VARCHAR(50) DEFAULT 'South Africa',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Services offered by service providers
CREATE TABLE services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    category VARCHAR(50) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Service provider service offerings (many-to-many relationship)
CREATE TABLE service_provider_services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_provider_id INT NOT NULL,
    service_id INT NOT NULL,
    FOREIGN KEY (service_provider_id) REFERENCES participants(participantId),
    FOREIGN KEY (service_id) REFERENCES services(id),
    UNIQUE KEY unique_sp_service (service_provider_id, service_id)
);

-- Service provider geographic coverage areas (many-to-many relationship)
CREATE TABLE service_provider_regions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_provider_id INT NOT NULL,
    region_id INT NOT NULL,
    FOREIGN KEY (service_provider_id) REFERENCES participants(participantId),
    FOREIGN KEY (region_id) REFERENCES regions(id),
    UNIQUE KEY unique_sp_region (service_provider_id, region_id)
);

-- Indexes for performance
CREATE INDEX idx_users_entity ON users(entity_id);
CREATE INDEX idx_users_role ON users(role_id);
CREATE INDEX idx_subscriptions_participant ON subscriptions(participantId);
CREATE INDEX idx_participant_features_participant ON participant_features(participantId);
CREATE INDEX idx_usage_tracking_subscription_month ON subscription_usage(subscription_id, usage_month);
CREATE INDEX idx_site_settings_key ON site_settings(setting_key);

-- Insert default site settings for monetization
INSERT INTO site_settings (setting_key, setting_value, setting_type, description) VALUES
('client_free_jobs_per_month', '3', 'int', 'Number of free jobs allowed per month for clients'),
('provider_free_jobs_per_month', '4', 'int', 'Number of free jobs allowed per month for service providers'),
('client_basic_subscription_price', '150.00', 'string', 'Monthly price for basic client subscription (Rands)'),
('provider_basic_subscription_price', '300.00', 'string', 'Monthly price for basic service provider subscription (Rands)'),
('client_advanced_asset_management_price', '99.00', 'string', 'Monthly extra fee for client advanced asset management'),
('client_advanced_maintenance_cost_price', '149.00', 'string', 'Monthly extra fee for client advanced maintenance cost recording'),
('client_advanced_qr_creation_price', '79.00', 'string', 'Monthly extra fee for client QR code creation'),
('provider_advanced_job_cost_collection_price', '99.00', 'string', 'Monthly extra fee for service provider job cost collection'),
('provider_advanced_health_safety_price', '129.00', 'string', 'Monthly extra fee for service provider health and safety assessment'),
('provider_advanced_routing_price', '149.00', 'string', 'Monthly extra fee for service provider technician routing'),
('subscription_grace_period_days', '7', 'int', 'Grace period in days for failed subscription payments'),
('job_image_retention_free_days', '30', 'int', 'Image retention period for free tier users'),
('job_image_retention_basic_days', '365', 'int', 'Image retention period for basic tier users');

COMMIT;
