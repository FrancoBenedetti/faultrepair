-- Create default free subscriptions for all participants without subscriptions
USE snappy;

-- Insert free subscriptions for participants with participant type C (Client)
INSERT IGNORE INTO subscriptions (participantId, subscription_tier, status, monthly_job_limit, subscription_enabled, created_at, updated_at)
SELECT p.participantId, 'free', 'active', 3, TRUE, NOW(), NOW()
FROM participants p
JOIN participant_type pt ON p.participantId = pt.participantId AND pt.participantType = 'C'
LEFT JOIN subscriptions s ON p.participantId = s.participantId
WHERE s.participantId IS NULL;

-- Insert free subscriptions for participants with participant type S (Service Provider)
INSERT IGNORE INTO subscriptions (participantId, subscription_tier, status, monthly_job_limit, subscription_enabled, created_at, updated_at)
SELECT p.participantId, 'free', 'active', 4, TRUE, NOW(), NOW()
FROM participants p
JOIN participant_type pt ON p.participantId = pt.participantId AND pt.participantType = 'S'
LEFT JOIN subscriptions s ON p.participantId = s.participantId
WHERE s.participantId IS NULL;

SELECT 'Default subscriptions created for participants!' as status;
