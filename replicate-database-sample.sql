-- Replicate database without data, with sample data for specified tables
-- Based on new-schema.sql and populate-admin-data.sql
-- Includes sample data as requested:
-- - locations: comprehensive list of towns and regions in South Africa
-- - services: categorized set of services for South Africa
-- - site_settings: replicated data
-- - user_roles: replicated data
-- - System administrator user with roleId=5, password=admin123

CREATE DATABASE IF NOT EXISTS snappy;
USE snappy;

-- Temporarily disable foreign key checks for clean drops
SET FOREIGN_KEY_CHECKS = 0;

-- Drop tables if they exist (in reverse dependency order)
DROP TABLE IF EXISTS service_provider_regions;
DROP TABLE IF EXISTS service_provider_services;
DROP TABLE IF EXISTS admin_actions;
DROP TABLE IF EXISTS job_notes;
DROP TABLE IF EXISTS participant_approvals;
DROP TABLE IF EXISTS job_status_history;
DROP TABLE IF EXISTS jobs;
DROP TABLE IF EXISTS locations;
DROP TABLE IF EXISTS site_settings;
DROP TABLE IF EXISTS subscription_usage;
DROP TABLE IF EXISTS participant_features;
DROP TABLE IF EXISTS subscriptions;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS participant_type;
DROP TABLE IF EXISTS participants;
DROP TABLE IF EXISTS regions;
DROP TABLE IF EXISTS services;
DROP TABLE IF EXISTS user_roles;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- User roles (authentication roles, separate from business logic)
CREATE TABLE user_roles (
    roleId INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
);

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

-- Insert sample data for user_roles
INSERT INTO user_roles (roleId, name) VALUES
(1, 'Client User'),
(2, 'Client Admin'),
(3, 'Service Provider Technician'),
(4, 'Service Provider Admin'),
(5, 'System Administrator'),
(6, 'Budget Controller')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Insert sample data for site_settings
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

-- Insert sample data for services (categorized for South Africa)
INSERT INTO services (name, category, description, is_active) VALUES
-- Electrical Services
('Electrical Wiring', 'Electrical', 'Installation and repair of electrical wiring and circuits', 1),
('Electrical Panel Upgrade', 'Electrical', 'Upgrade and repair of electrical distribution panels', 1),
('Lighting Installation', 'Electrical', 'Installation of residential and commercial lighting systems', 1),
('Generator Installation', 'Electrical', 'Installation and maintenance of backup generators', 1),
('Electrical Safety Testing', 'Electrical', 'Electrical safety inspections and testing', 1),

-- Plumbing Services
('Pipe Installation', 'Plumbing', 'Installation of water and drainage pipes', 1),
('Leak Repair', 'Plumbing', 'Detection and repair of water leaks', 1),
('Drain Cleaning', 'Plumbing', 'Professional drain cleaning and unclogging', 1),
('Water Heater Installation', 'Plumbing', 'Installation and repair of water heaters', 1),
('Pipe Burst Repair', 'Plumbing', 'Emergency pipe burst repairs', 1),

-- HVAC Services
('HVAC System Installation', 'HVAC', 'Installation of heating, ventilation, and air conditioning systems', 1),
('HVAC Maintenance', 'HVAC', 'Regular maintenance and servicing of HVAC systems', 1),
('Air Duct Cleaning', 'HVAC', 'Professional air duct cleaning and repair', 1),
('Thermostat Installation', 'HVAC', 'Smart thermostat installation and programming', 1),
('Air Filter Replacement', 'HVAC', 'HVAC air filter replacement and maintenance', 1),

-- General Maintenance
('Appliance Installation', 'General', 'Installation of major household appliances', 1),
('Drywall Repair', 'General', 'Drywall installation and repair', 1),
('Door and Window Repair', 'General', 'Repair and replacement of doors and windows', 1),
('Flooring Installation', 'General', 'Installation of flooring materials', 1),
('Pressure Washing', 'General', 'Exterior cleaning and pressure washing', 1),

-- Carpentry
('Cabinet Installation', 'Carpentry', 'Kitchen and bathroom cabinet installation', 1),
('Deck Construction', 'Carpentry', 'Outdoor deck construction and repair', 1),
('Fence Installation', 'Carpentry', 'Fence building and repair services', 1),
('Trim and Molding', 'Carpentry', 'Interior trim and molding installation', 1),
('Shelving Installation', 'Carpentry', 'Custom shelving and storage solutions', 1),

-- Painting
('Interior Painting', 'Painting', 'Interior wall and ceiling painting', 1),
('Exterior Painting', 'Painting', 'House exterior painting services', 1),
('Deck Staining', 'Painting', 'Deck and fence staining services', 1),
('Wallpaper Installation', 'Painting', 'Wallpaper hanging and removal', 1),
('Paint Consulting', 'Painting', 'Color consultation and paint recommendations', 1),

-- Roofing
('Roof Repair', 'Roofing', 'Emergency roof leak repairs', 1),
('Roof Replacement', 'Roofing', 'Complete roof replacement services', 1),
('Gutter Installation', 'Roofing', 'Gutter installation and cleaning', 1),
('Roof Inspection', 'Roofing', 'Professional roof inspection services', 1),
('Snow Removal', 'Roofing', 'Roof snow and debris removal', 1),

-- Landscaping
('Lawn Care', 'Landscaping', 'Weekly lawn mowing and maintenance', 1),
('Tree Trimming', 'Landscaping', 'Tree pruning and trimming services', 1),
('Garden Design', 'Landscaping', 'Landscape design and installation', 1),
('Irrigation System', 'Landscaping', 'Sprinkler system installation and repair', 1),
('Weed Control', 'Landscaping', 'Professional weed removal services', 1)
ON DUPLICATE KEY UPDATE name = VALUES(name), category = VALUES(category), description = VALUES(description), is_active = VALUES(is_active);

-- Insert sample data for regions (South African cities, towns and regions served by service providers)
INSERT INTO regions (name, code, country, is_active) VALUES
('Johannesburg', 'JHB', 'South Africa', 1),
('Pretoria', 'PTA', 'South Africa', 1),
('Cape Town', 'CPT', 'South Africa', 1),
('Durban', 'DUR', 'South Africa', 1),
('Bloemfontein', 'BFN', 'South Africa', 1),
('Port Elizabeth', 'PE', 'South Africa', 1),
('East London', 'ELS', 'South Africa', 1),
('Kimberley', 'KIM', 'South Africa', 1),
('Polokwane', 'PTG', 'South Africa', 1),
('Nelspruit', 'NLP', 'South Africa', 1),
('Rustenburg', 'RUS', 'South Africa', 1),
('Potchefstroom', 'PCF', 'South Africa', 1),
('Newcastle', 'NCL', 'South Africa', 1),
('Richards Bay', 'RCB', 'South Africa', 1),
('Pietermaritzburg', 'PLZ', 'South Africa', 1),
('Mossel Bay', 'MZY', 'South Africa', 1),
('Oudtshoorn', 'OUD', 'South Africa', 1),
('George', 'GRJ', 'South Africa', 1),
('Upington', 'UTN', 'South Africa', 1),
('Vredenburg', 'VRE', 'South Africa', 1),
('Stellenbosch', 'STB', 'South Africa', 1),
('Paarl', 'PRL', 'South Africa', 1),
('Wellington', 'WLG', 'South Africa', 1),
('Hermanus', 'HMS', 'South Africa', 1)
ON DUPLICATE KEY UPDATE name = VALUES(name), code = VALUES(code), country = VALUES(country), is_active = VALUES(is_active);

-- Create a sample participant for demonstration (system-level)
INSERT INTO participants (name, address) VALUES
('System Administration', 'Virtual');

SET @system_participant_id = LAST_INSERT_ID();

INSERT INTO participant_type (participantId, participantType) VALUES
(@system_participant_id, 'S');

-- Insert sample data for locations (comprehensive list of towns and regions in South Africa)
-- Note: Using the system participant ID for demonstration purposes
INSERT INTO locations (participant_id, name, address, coordinates, access_rules, access_instructions) VALUES
-- Gauteng Region
(@system_participant_id, 'Johannesburg Central', 'Johannesburg Central Business District, Gauteng, South Africa', '-26.2041,28.0473', 'Open to public access', 'Main CBD area'),
(@system_participant_id, 'Pretoria Central', 'Pretoria City Centre, Gauteng, South Africa', '-25.7479,28.2293', 'Open to public access', 'Administrative capital'),
(@system_participant_id, 'Sandton', 'Sandton, Johannesburg, Gauteng, South Africa', '-26.1076,28.0567', 'Private estates may require permits', 'High-end business district'),
(@system_participant_id, 'Midrand', 'Midrand, Johannesburg, Gauteng, South Africa', '-25.9630,28.1378', 'Some areas require access permissions', 'Technology hub'),

-- Western Cape Region
(@system_participant_id, 'Cape Town Central', 'Cape Town Central Business District, Western Cape, South Africa', '-33.9189,18.4233', 'Open to public access', 'Tourist area with Table Mountain views'),
(@system_participant_id, 'Stellenbosch', 'Stellenbosch, Western Cape, South Africa', '-33.9321,18.8602', 'University area, open access', 'Winelands town'),
(@system_participant_id, 'Paarl', 'Paarl, Western Cape, South Africa', '-33.7357,18.9621', 'Agricultural area, access generally open', 'Wine and fruit farming district'),
(@system_participant_id, 'Worcester', 'Worcester, Western Cape, South Africa', '-33.6465,19.4489', 'Open agricultural district', 'Breede River Valley farming area'),

-- KwaZulu-Natal Region
(@system_participant_id, 'Durban Central', 'Durban Central Business District, KwaZulu-Natal, South Africa', '-29.8587,31.0218', 'Port area with security checkpoints', 'Harbor city port'),
(@system_participant_id, 'Pietermaritzburg', 'Pietermaritzburg, KwaZulu-Natal, South Africa', '-29.6168,30.3928', 'Provincial capital, open access', 'Cultural and administrative center'),
(@system_participant_id, 'Richards Bay', 'Richards Bay, KwaZulu-Natal, South Africa', '-28.7830,32.0380', 'Industrial port area with security', 'Major industrial harbor'),

-- Eastern Cape Region
(@system_participant_id, 'Port Elizabeth', 'Port Elizabeth Central, Eastern Cape, South Africa', '-33.7241,25.5305', 'Port area with security measures', 'Coastal industrial city'),
(@system_participant_id, 'East London', 'East London, Eastern Cape, South Africa', '-33.0292,27.8546', 'Port city with security protocols', 'Indian Ocean port'),
(@system_participant_id, 'Grahamstown', 'Grahamstown (Makhanda), Eastern Cape, South Africa', '-33.3042,26.5329', 'University town, open access', 'Historical university town'),

-- Free State Region
(@system_participant_id, 'Bloemfontein', 'Bloemfontein, Free State, South Africa', '-29.0852,26.1597', 'Judicial capital, public access', 'Administrative and judicial center'),
(@system_participant_id, 'Welkom', 'Welkom, Free State, South Africa', '-27.9768,26.7354', 'Mining town with some restrictions', 'Gold mining area'),
(@system_participant_id, 'Bethlehem', 'Bethlehem, Free State, South Africa', '-28.2018,28.3087', 'Agricultural town, open access', 'Farming community'),

-- Mpumalanga Region
(@system_participant_id, 'Nelspruit (Mbombela)', 'Nelspruit, Mpumalanga, South Africa', '-25.4750,30.9694', 'Gateway to Kruger National Park', 'Ecotourism hub'),
(@system_participant_id, 'Witbank', 'Witbank (eMalahleni), Mpumalanga, South Africa', '-25.8778,29.1920', 'Coal mining area', 'Energy district'),

-- North West Region
(@system_participant_id, 'Rustenburg', 'Rustenburg, North West, South Africa', '-25.6667,27.2421', 'Mining city, some security areas', 'Platinum mining capital'),
(@system_participant_id, 'Potchefstroom', 'Potchefstroom, North West, South Africa', '-26.7167,27.1000', 'University town, open access', 'Academic and agricultural center'),

-- Limpopo Region
(@system_participant_id, 'Polokwane', 'Polokwane (Pietersburg), Limpopo, South Africa', '-23.8962,29.4486', 'Provincial capital, open access', 'Agricultural center'),
(@system_participant_id, 'Tzaneen', 'Tzaneen, Limpopo, South Africa', '-23.8332,30.1667', 'Gateway to malaria-free game reserves', 'Tropical fruit growing area'),

-- Northern Cape Region
(@system_participant_id, 'Kimberley', 'Kimberley, Northern Cape, South Africa', '-28.7400,24.7719', 'Diamond mining city', 'Mining heritage town'),
(@system_participant_id, 'Upington', 'Upington, Northern Cape, South Africa', '-28.4500,21.2500', 'Orange River town', 'Irrigation farming district');

-- Create system administrator user
-- Note: Password hash generated for 'admin123' using password_hash() in PHP or equivalent
INSERT INTO users (username, password_hash, email, entity_id, role_id, is_active, email_verified, first_name, last_name) VALUES
('admin', '$2y$10$eDbjKQ5RK8fkJWzKHqJGoeMhMsRxPEvdAxOmN5mQyLxjvE4yYB.Ce', 'admin@system.local', @system_participant_id, 5, TRUE, TRUE, 'System', 'Administrator');

COMMIT;
