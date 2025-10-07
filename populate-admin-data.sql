-- Populate admin tables with sample data
USE snappy;

-- Insert user roles
INSERT INTO user_roles (roleId, name) VALUES
(1, 'Client User'),
(2, 'Client Admin'),
(3, 'Service Provider Technician'),
(4, 'Service Provider Admin'),
(5, 'System Administrator'),
(6, 'Budget Controller')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Insert regions
INSERT INTO regions (name, code, country, is_active) VALUES
('Johannesburg', 'JHB', 'South Africa', 1),
('Cape Town', 'CPT', 'South Africa', 1),
('Durban', 'DUR', 'South Africa', 1),
('Pretoria', 'PTA', 'South Africa', 1),
('Port Elizabeth', 'PE', 'South Africa', 1),
('Bloemfontein', 'BFN', 'South Africa', 1),
('East London', 'ELS', 'South Africa', 1),
('Polokwane', 'PTG', 'South Africa', 1),
('Nelspruit', 'NLP', 'South Africa', 1),
('Kimberley', 'KIM', 'South Africa', 1),
('Upington', 'UTN', 'South Africa', 1),
('Oudtshoorn', 'OUD', 'South Africa', 1),
('George', 'GRJ', 'South Africa', 1),
('Mossel Bay', 'MZY', 'South Africa', 1),
('Beaufort West', 'BFT', 'South Africa', 1),
('Rustenburg', 'RUS', 'South Africa', 1),
('Potchefstroom', 'PCF', 'South Africa', 1),
('Vanderbijlpark', 'VDP', 'South Africa', 1),
('Klerksdorp', 'KXF', 'South Africa', 1),
('Welkom', 'WLK', 'South Africa', 1),
('Bethlehem', 'BEH', 'South Africa', 1),
('Harrismith', 'HRS', 'South Africa', 1),
('Pietermaritzburg', 'PLZ', 'South Africa', 1),
('Richards Bay', 'RCB', 'South Africa', 1),
('Newcastle', 'NCL', 'South Africa', 1),
('Ladysmith', 'LAY', 'South Africa', 1),
('Vryheid', 'VRU', 'South Africa', 1)
ON DUPLICATE KEY UPDATE name = VALUES(name), code = VALUES(code), country = VALUES(country), is_active = VALUES(is_active);

-- Insert services by category
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

-- Insert site settings
INSERT INTO site_settings (setting_key, setting_value, setting_type, description) VALUES
('client_free_jobs_per_month', '5', 'int', 'Number of free jobs allowed for client accounts per month'),
('provider_free_jobs_per_month', '10', 'int', 'Number of free jobs allowed for service provider accounts per month'),
('client_basic_subscription_price', '299.00', 'num', 'Monthly subscription price for basic client accounts'),
('provider_basic_subscription_price', '399.00', 'num', 'Monthly subscription price for basic service provider accounts'),
('subscription_grace_period_days', '7', 'int', 'Number of days grace period after failed payment'),
('job_image_retention_free_days', '30', 'int', 'How long job images are retained for free accounts'),
('job_image_retention_basic_days', '90', 'int', 'How long job images are retained for basic accounts'),
('job_image_retention_advanced_days', '365', 'int', 'How long job images are retained for advanced accounts'),
('site_maintenance_mode', 'false', 'bool', 'Enable maintenance mode for the entire site'),
('site_registration_enabled', 'true', 'bool', 'Allow new user registrations'),
('site_name', 'Fault Reporter', 'string', 'Name of the application/site'),
('site_description', 'Professional fault reporting and service provider platform', 'string', 'Site description for SEO'),
('site_contact_email', 'admin@faultreporter.com', 'string', 'Contact email for site administration'),
('site_support_email', 'support@faultreporter.com', 'string', 'Support email for user assistance'),
('site_currency', 'ZAR', 'string', 'Default currency for pricing and transactions'),
('site_timezone', 'Africa/Johannesburg', 'string', 'Default timezone for the application'),
('billing_cycle_day', '1', 'int', 'Day of month when billing cycles begin'),
('invoice_number_prefix', 'INV', 'string', 'Prefix for invoice numbers'),
('tax_rate_percentage', '15.00', 'num', 'Default tax rate for South Africa (VAT)'),
('payment_gateway_enabled', 'true', 'bool', 'Enable payment processing gateway'),
('google_maps_api_key', '', 'string', 'Google Maps API key for location services (leave empty for demo)'),
('smtp_host', '', 'string', 'SMTP host for email notifications'),
('smtp_port', '587', 'int', 'SMTP port number'),
('smtp_username', '', 'string', 'SMTP authentication username'),
('smtp_password', '', 'string', 'SMTP authentication password'),
('email_verification_required', 'true', 'bool', 'Require email verification for new accounts'),
('two_factor_auth_enabled', 'false', 'bool', 'Enable two-factor authentication'),
('max_upload_size_mb', '10', 'int', 'Maximum file upload size in MB'),
('max_images_per_job', '5', 'int', 'Maximum number of images allowed per job'),
('max_jobs_per_day_free', '2', 'int', 'Maximum jobs allowed per day for free accounts'),
('max_jobs_per_day_basic', '10', 'int', 'Maximum jobs allowed per day for basic accounts'),
('max_jobs_per_day_advanced', 'unlimited', 'string', 'Maximum jobs allowed per day for advanced accounts')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), setting_type = VALUES(setting_type), description = VALUES(description);

SELECT 'Admin data population completed successfully!' as status;
SELECT 'Regions:' as info, COUNT(*) as count FROM regions WHERE is_active = 1
UNION ALL
SELECT 'Services:', COUNT(*) FROM services WHERE is_active = 1
UNION ALL
SELECT 'User Roles:', COUNT(*) FROM user_roles
UNION ALL
SELECT 'Site Settings:', COUNT(*) FROM site_settings;
