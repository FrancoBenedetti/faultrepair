-- Reset database for testing: Clear all dynamic data but keep static system info
USE snappy;

-- Clear dynamic tables (preserve static system data)
TRUNCATE TABLE job_images;
TRUNCATE TABLE jobs;
TRUNCATE TABLE participant_approvals;
TRUNCATE TABLE invitations;
TRUNCATE TABLE technicians;
TRUNCATE TABLE subscriptions;
TRUNCATE TABLE users;
TRUNCATE TABLE participants;
TRUNCATE TABLE participant_type;

SELECT 'Database reset completed - all dynamic data cleared!' as status;
