-- Test Data Population Script for New Snappy Architecture
-- This script populates comprehensive test data for thorough testing

USE snappy;

-- Load all initial site settings (already pre-populated in schema)

-- Insert South African provinces
INSERT INTO regions (name, code, country, is_active) VALUES
('Gauteng', 'GP', 'South Africa', TRUE),
('Western Cape', 'WC', 'South Africa', TRUE),
('KwaZulu-Natal', 'KZN', 'South Africa', TRUE),
('Eastern Cape', 'EC', 'South Africa', TRUE),
('Limpopo', 'LP', 'South Africa', TRUE),
('Mpumalanga', 'MP', 'South Africa', TRUE),
('North West', 'NW', 'South Africa', TRUE),
('Northern Cape', 'NC', 'South Africa', TRUE),
('Free State', 'FS', 'South Africa', TRUE);

-- Insert common service categories
INSERT INTO services (name, category, description, is_active) VALUES
('Electrical Repairs', 'Electrical', 'General electrical repairs and maintenance', TRUE),
('Plumbing Repairs', 'Plumbing', 'Plumbing repairs and installations', TRUE),
('HVAC Maintenance', 'HVAC', 'Heating, ventilation, and air conditioning services', TRUE),
('IT Support', 'Technology', 'Information technology support and maintenance', TRUE),
('General Cleaning', 'Maintenance', 'General cleaning and maintenance services', TRUE),
('Security Systems', 'Security', 'Security system installation and maintenance', TRUE),
('Painting Services', 'Maintenance', 'Interior and exterior painting', TRUE),
('Carpentry', 'Construction', 'Woodworking and carpentry services', TRUE),
('Roofing Repairs', 'Construction', 'Roof repairs and maintenance', TRUE),
('Flooring', 'Construction', 'Floor installation and repair', TRUE);

-- Insert test participants (business entities)
INSERT INTO participants (name, address, website, manager_name, manager_email, manager_phone, description) VALUES
-- Test Clients
('Acme Corporation', '123 Business Avenue, Johannesburg, 2196', 'https://acme.co.za', 'John Smith', 'john@acme.co.za', '+27-11-123-4567', 'Large manufacturing company with multiple facilities'),
('TechStart SME', '456 Innovation Drive, Cape Town, 8001', 'https://techstart.co.za', 'Sarah Johnson', 'sarah@techstart.co.za', '+27-21-987-6543', 'Technology startup with office space in multiple areas'),

-- Test Service Providers
('QuickFix Services', '789 Service Road, Durban, 4001', 'https://quickfix.co.za', 'Mike Wilson', 'mike@quickfix.co.za', '+27-31-456-7890', 'Fast response maintenance and repair service'),
('ElectroTech Solutions', '321 Tech Park, Pretoria, 0181', 'https://electrotech.co.za', 'Lisa Brown', 'lisa@electrotech.co.za', '+27-12-657-3456', 'Specialized electrical and technical solutions'),
('Cape Plumbing Pros', '101 Water Street, Cape Town, 8001', 'https://capeplumbing.co.za', 'David Miller', 'david@capeplumbing.co.za', '+27-21-765-4321', 'Professional plumbing services');

-- Set participant types (Client vs Service Provider)
INSERT INTO participant_type (participantId, participantType, isActive) VALUES
(1, 'C', 'Y'), -- Acme = Client
(2, 'C', 'Y'), -- TechStart = Client
(3, 'S', 'Y'), -- QuickFix = Service Provider
(4, 'S', 'Y'), -- ElectroTech = Service Provider
(5, 'S', 'Y'); -- Cape Plumbing = Service Provider

-- Create test users and link to participants (common password hash for "password123")
INSERT INTO users (username, password_hash, email, entity_id, role_id, first_name, last_name, phone, is_active, email_verified) VALUES
-- Client Users (using predefined role_id: 1=Client User, 2=Client Admin)
('acme-admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@acme.co.za', 1, 2, 'John', 'Smith', '+27-11-123-4567', TRUE, TRUE),
('acme-user', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user@acme.co.za', 1, 1, 'Jane', 'Doe', '+27-11-234-5678', TRUE, TRUE),
('acme-manager', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager@acme.co.za', 1, 1, 'Bob', 'Johnson', '+27-11-345-6789', TRUE, TRUE),

-- TechStart Users
('techstart-admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@techstart.co.za', 2, 2, 'Sarah', 'Johnson', '+27-21-987-6543', TRUE, TRUE),
('techstart-user', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user@techstart.co.za', 2, 1, 'Mike', 'Davis', '+27-21-876-5432', TRUE, TRUE),

-- Service Provider Users (using predefined: 3=SP Technician, 4=SP Admin)
('quickfix-admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@quickfix.co.za', 3, 4, 'Mike', 'Wilson', '+27-31-456-7890', TRUE, TRUE),
('quickfix-tech', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'tech@quickfix.co.za', 3, 3, 'Bob', 'Technician', '+27-31-567-8901', TRUE, TRUE),
('quickfix-tech2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'tech2@quickfix.co.za', 3, 3, 'Alice', 'Helper', '+27-31-678-9012', TRUE, TRUE),

-- ElectroTech Users
('electrotech-admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@electrotech.co.za', 4, 4, 'Lisa', 'Brown', '+27-12-657-3456', TRUE, TRUE),
('electrotech-tech', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'tech@electrotech.co.za', 4, 3, 'Tom', 'Electrician', '+27-12-767-4567', TRUE, TRUE),

-- Cape Plumbing Users
('capeplumb-admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@capeplumbing.co.za', 5, 4, 'David', 'Miller', '+27-21-765-4321', TRUE, TRUE),
('capeplumb-tech', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'tech@capeplumbing.co.za', 5, 3, 'Steve', 'Plumber', '+27-21-654-3210', TRUE, TRUE);

-- Create admin user for testing admin functionality
INSERT INTO users (username, password_hash, email, role_id, first_name, last_name, phone, is_active, email_verified) VALUES
('siteadmin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@fault-reporter.local', 5, 'Site', 'Administrator', '+27-10-123-4567', TRUE, TRUE);

-- Create subscriptions for participants (mix of free, basic, advanced)
INSERT INTO subscriptions (participantId, subscription_tier, status, monthly_job_limit, current_period_start, current_period_end) VALUES
(1, 'free', 'active', 3, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 MONTH)),      -- Acme on free
(2, 'basic', 'active', 9999, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 MONTH)),   -- TechStart on basic
(3, 'free', 'active', 4, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 MONTH)),      -- QuickFix on free
(4, 'advanced', 'active', 9999, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 MONTH)), -- ElectroTech on advanced
(5, 'basic', 'active', 9999, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 MONTH));   -- Cape Plumbing on basic

-- Link service providers to regions they serve
INSERT INTO service_provider_regions (service_provider_id, region_id) VALUES
(3, 1), -- QuickFix serves Gauteng
(3, 2), -- QuickFix serves Western Cape
(3, 9), -- QuickFix serves Free State
(4, 1), -- ElectroTech serves Gauteng
(4, 3), -- ElectroTech serves KwaZulu-Natal
(4, 5), -- ElectroTech serves Limpopo
(5, 2), -- Cape Plumbing serves Western Cape
(5, 4); -- Cape Plumbing serves Eastern Cape

-- Link service providers to services they offer
INSERT INTO service_provider_services (service_provider_id, service_id) VALUES
(3, 1), -- QuickFix offers Electrical Repairs
(3, 2), -- QuickFix offers Plumbing
(3, 3), -- QuickFix offers HVAC
(3, 5), -- QuickFix offers Cleaning
(4, 1), -- ElectroTech offers Electrical Repairs
(4, 4), -- ElectroTech offers IT Support
(4, 6), -- ElectroTech offers Security Systems
(4, 8), -- ElectroTech offers Carpentry
(5, 2), -- Cape Plumbing offers Plumbing
(5, 3), -- Cape Plumbing offers HVAC
(5, 9); -- Cape Plumbing offers Roofing

-- Enable advanced features for advanced tier service providers
INSERT INTO participant_features (participantId, feature_name, is_enabled, valid_until) VALUES
(4, 'qr_creation', TRUE, DATE_ADD(CURDATE(), INTERVAL 1 YEAR)),
(4, 'job_cost_collection', TRUE, DATE_ADD(CURDATE(), INTERVAL 1 YEAR)),
(4, 'health_safety_assessment', TRUE, DATE_ADD(CURDATE(), INTERVAL 1 YEAR)),
(4, 'technician_routing', TRUE, DATE_ADD(CURDATE(), INTERVAL 1 YEAR));

-- Create client locations for job creation testing
INSERT INTO locations (participant_id, name, address, coordinates, access_instructions) VALUES
(1, 'Head Office', '123 Business Avenue, Johannesburg, 2196', '-26.2041,28.0473', 'Main entrance on Business Avenue'),
(1, 'Warehouse Facility', '456 Industrial Road, Johannesburg, 2196', '-26.1784,27.9731', 'Security clearance required, call ahead'),
(1, 'SME Branch', '789 Commerce Street, Johannesburg, 2196', '-26.1958,28.0399', 'Ring doorbell for access'),
(2, 'Cape Town Office', '456 Innovation Drive, Cape Town, 8001', '-33.9249,18.4241', 'Enter via main reception'),
(2, 'Stellenbosch Campus', '123 Academic Avenue, Stellenbosch, 7600', '-33.9328,18.8657', 'Campus security access required');

-- Establish client-provider approval relationships
INSERT INTO participant_approvals (client_participant_id, provider_participant_id) VALUES
(1, 3), -- Acme approves QuickFix
(1, 4), -- Acme approves ElectroTech
(2, 4), -- TechStart approves ElectroTech
(2, 5); -- TechStart approves Cape Plumbing

-- Create sample jobs to test workflow
INSERT INTO jobs (client_location_id, item_identifier, fault_description, reporting_user_id, job_status, contact_person) VALUES
(1, 'AC-001', 'Air conditioning unit not cooling effectively in executive offices', 3, 'Reported', 'Jane Smith (Office Manager)'),
(1, 'NET-002', 'Network connectivity issues in warehouse area', 2, 'Assigned', 'Bob Johnson (IT Manager)'),
(2, 'ELEC-003', 'Main power panel tripping circuit breakers', 3, 'In Progress', 'Sarah Wilson (Facilities)'),
(4, 'PLUMB-004', 'Leaking pipe under bathroom sink', 5, 'Completed', 'Mike Davis (Building Mgr)'),
(5, 'HVAC-005', 'Heating system not responding to thermostat', 5, 'Reported', 'Lisa Brown (Admin)');

-- Assign jobs to providers (participants, not users)
UPDATE jobs SET assigned_provider_participant_id = 3, assigning_user_id = 1, assigned_technician_user_id = 7 WHERE id = 1; -- AC repair to QuickFix
UPDATE jobs SET assigned_provider_participant_id = 4, assigning_user_id = 1, assigned_technician_user_id = 10 WHERE id = 2; -- Network to ElectroTech
UPDATE jobs SET assigned_provider_participant_id = 3, assigning_user_id = 1, assigned_technician_user_id = 7 WHERE id = 3; -- Electric to QuickFix
UPDATE jobs SET assigned_provider_participant_id = 5, assigning_user_id = 4, assigned_technician_user_id = 12 WHERE id = 4; -- Plumbing to Cape Plumbing
UPDATE jobs SET assigned_provider_participant_id = 3, assigning_user_id = 4, assigned_technician_user_id = 8 WHERE id = 5; -- HVAC to QuickFix

-- Add some job status history
INSERT INTO job_status_history (job_id, status, changed_by_user_id) VALUES
(1, 'Reported', 3),
(1, 'Assigned', 1),
(2, 'Reported', 2),
(2, 'Assigned', 1),
(3, 'Reported', 3),
(3, 'Assigned', 1),
(3, 'In Progress', 7),
(4, 'Reported', 5),
(4, 'Assigned', 4),
(4, 'In Progress', 12),
(4, 'Completed', 12),
(5, 'Reported', 5);

-- Add usage data to test subscription limits (current month only)
INSERT INTO subscription_usage (subscription_id, usage_type, usage_month, count) VALUES
(1, 'jobs_created', DATE_FORMAT(CURDATE(), '%Y-%m'), 2), -- Free client near limit
(3, 'jobs_accepted', DATE_FORMAT(CURDATE(), '%Y-%m'), 3); -- Free provider near limit

COMMIT;

-- Test data population complete
-- Next steps: Run backend rebuilding scripts and test API endpoints
