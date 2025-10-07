-- Essential data for admin functionality
USE snappy;

-- Insert user roles (if not already inserted)
INSERT IGNORE INTO user_roles (name) VALUES
('Client User'),
('Client Admin'),
('Service Provider Technician'),
('Service Provider Admin'),
('System Administrator');

-- Insert essential site settings
INSERT IGNORE INTO site_settings (setting_key, setting_value, setting_type, description) VALUES
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

-- Insert sample regions
INSERT IGNORE INTO regions (name, code, country) VALUES
('Johannesburg', 'JHB', 'South Africa'),
('Cape Town', 'CPT', 'South Africa'),
('Durban', 'DBN', 'South Africa'),
('Pretoria', 'PRE', 'South Africa'),
('Port Elizabeth', 'PE', 'South Africa'),
('Bloemfontein', 'BFN', 'South Africa');

-- Insert sample service categories
INSERT IGNORE INTO services (name, category, description) VALUES
-- Electrical
('Electrical Installation', 'Electrical', 'Complete electrical installation and wiring'),
('Electrical Repairs', 'Electrical', 'Electrical fault repairs and maintenance'),
('Generator Installation', 'Electrical', 'Generator setup and electrical connections'),
('UPS Systems', 'Electrical', 'Uninterruptible power supply installation'),

-- Plumbing
('Pipe Repairs', 'Plumbing', 'Pipe leak repairs and replacement'),
('Water Heater Installation', 'Plumbing', 'Hot water heater installation'),
('Drainage Systems', 'Plumbing', 'Drainage and sewer system repairs'),
('Bathroom Renovations', 'Plumbing', 'Complete bathroom plumbing setups'),

-- HVAC
('HVAC Installation', 'HVAC', 'Heating, ventilation, and air conditioning installation'),
('HVAC Repairs', 'HVAC', 'HVAC system repairs and maintenance'),
('Air Conditioning', 'HVAC', 'Air conditioning installation and servicing'),
('Ventilation Systems', 'HVAC', 'Industrial and commercial ventilation'),

-- General Engineering
('Maintenance Services', 'General Engineering', 'General maintenance and repair services'),
('Emergency Repairs', 'General Engineering', '24/7 emergency repair services'),
('Building Maintenance', 'General Engineering', 'General building maintenance'),
('Equipment Servicing', 'General Engineering', 'Equipment maintenance and servicing');

COMMIT;
