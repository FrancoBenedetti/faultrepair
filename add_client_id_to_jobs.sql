-- Migration script to add client_id field to jobs table and populate with data
-- Run this script after updating the schema (snappy-dev.sql)

-- Step 1: Add the client_id column (already done in schema)
-- ALTER TABLE jobs ADD COLUMN client_id INT NOT NULL COMMENT 'Direct reference to the client participant' AFTER client_location_id;
-- ALTER TABLE jobs ADD CONSTRAINT fk_jobs_client_id FOREIGN KEY (client_id) REFERENCES participants(participantId);

-- Step 2: Populate client_id for existing records
-- For jobs with client_location_id set, get client from location->participant_id
UPDATE jobs j
INNER JOIN locations l ON j.client_location_id = l.id
SET j.client_id = l.participant_id
WHERE j.client_location_id IS NOT NULL;

-- For jobs with NULL client_location_id (default location jobs),
-- derive client_id from the reporting user's entity_id
UPDATE jobs j
INNER JOIN users u ON j.reporting_user_id = u.userId
SET j.client_id = u.entity_id
WHERE j.client_location_id IS NULL;

-- Step 3: Verify all jobs now have client_id
-- This should return 0 rows if migration is successful
SELECT COUNT(*) as jobs_without_client_id FROM jobs WHERE client_id IS NULL;

-- Step 4: Optional verification query to check data integrity
-- This should show consistent client relationships
SELECT
    j.id,
    j.client_id,
    j.client_location_id,
    CASE
        WHEN j.client_location_id IS NOT NULL THEN l.participant_id
        WHEN j.client_location_id IS NULL THEN u.entity_id
        ELSE NULL
    END as expected_client_id,
    CASE
        WHEN j.client_id = CASE
            WHEN j.client_location_id IS NOT NULL THEN l.participant_id
            WHEN j.client_location_id IS NULL THEN u.entity_id
            ELSE NULL
        END THEN 'CORRECT'
        ELSE 'MISMATCH'
    END as verification_status
FROM jobs j
LEFT JOIN locations l ON j.client_location_id = l.id
LEFT JOIN users u ON j.reporting_user_id = u.userId;

-- Optional: Make client_id NOT NULL after verification (already done in schema update)
-- ALTER TABLE jobs MODIFY COLUMN client_id INT NOT NULL COMMENT 'Direct reference to the client participant';

COMMIT;
