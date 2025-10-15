-- Set up comprehensive test data for fault reporting system
USE snappy;

-- Insert a test client (participant type C)
INSERT INTO participants (name, address) VALUES
('Test Client Company', '123 Test Street, Johannesburg, South Africa');

SET @client_id = LAST_INSERT_ID();

INSERT INTO participant_type (participantId, participantType) VALUES
(@client_id, 'C');

-- Insert a test service provider (participant type S)
INSERT INTO participants (name, address) VALUES
('Test Service Provider', '456 Service Avenue, Pretoria, South Africa');

SET @provider_id = LAST_INSERT_ID();

INSERT INTO participant_type (participantId, participantType) VALUES
(@provider_id, 'S');

-- Create subscriptions for both participants
INSERT INTO subscriptions (participantId, subscription_tier, status, monthly_job_limit) VALUES
(@client_id, 'free', 'active', 3),
(@provider_id, 'free', 'active', 4);

-- Create test client users
INSERT INTO users (username, password_hash, email, role_id, entity_id, is_active, email_verified) VALUES
('client_user', '$2y$10$hashedpassword1', 'client@test.com', 1, @client_id, TRUE, TRUE),
('client_admin', '$2y$10$hashedpassword2', 'client_admin@test.com', 2, @client_id, TRUE, TRUE);

-- Create test service provider users
-- admin_user should be role 3 (Service Provider Admin)
INSERT INTO users (username, password_hash, email, role_id, entity_id, is_active, email_verified, first_name, last_name) VALUES
('admin_user', '$2y$10$hashedpassword3', 'admin@testprovider.com', 3, @provider_id, TRUE, TRUE, 'John', 'Admin'),
('tech1_user', '$2y$10$hashedpassword4', 'tech1@testprovider.com', 4, @provider_id, TRUE, TRUE, 'Jane', 'Technician'),
('tech2_user', '$2y$10$hashedpassword5', 'tech2@testprovider.com', 4, @provider_id, TRUE, TRUE, 'Bob', 'Technician');

-- Create client locations
INSERT INTO locations (participant_id, name, address, coordinates, access_rules, access_instructions) VALUES
(@client_id, 'Main Office', '123 Test Street, Johannesburg', '-26.2041,28.0473', 'Contact reception for access', 'Use main entrance, show ID'),
(@client_id, 'Warehouse', '456 Industrial Road, Johannesburg', '-26.2000,28.0500', 'Security gate code: 1234', 'Enter through loading dock');

-- Approve the service provider for the client
INSERT INTO participant_approvals (client_participant_id, provider_participant_id) VALUES
(@client_id, @provider_id);

-- Create sample jobs
INSERT INTO jobs (client_location_id, reporting_user_id, item_identifier, fault_description, assigned_provider_participant_id, assigned_technician_user_id, job_status, created_at) VALUES
-- Job assigned to tech1_user
(1, (SELECT userId FROM users WHERE username = 'client_user'), 'Computer-001', 'Screen flickering intermittently', @provider_id, (SELECT userId FROM users WHERE username = 'tech1_user'), 'Assigned', NOW() - INTERVAL 2 DAY),
-- Job assigned to tech2_user
(2, (SELECT userId FROM users WHERE username = 'client_admin'), 'Printer-ABC', 'Paper jam error persists', @provider_id, (SELECT userId FROM users WHERE username = 'tech2_user'), 'In Progress', NOW() - INTERVAL 1 DAY),
-- Job not assigned (unassigned)
(1, (SELECT userId FROM users WHERE username = 'client_user'), 'Phone-123', 'No dial tone', @provider_id, NULL, 'Reported', NOW() - INTERVAL 6 HOUR);

SELECT 'Test data setup completed!' as status;
SELECT 'Participants:' as info, COUNT(*) as count FROM participants
UNION ALL
SELECT 'Users:', COUNT(*) FROM users
UNION ALL
SELECT 'Jobs:', COUNT(*) FROM jobs
UNION ALL
SELECT 'Locations:', COUNT(*) FROM locations
UNION ALL
SELECT 'Approvals:', COUNT(*) FROM participant_approvals;
