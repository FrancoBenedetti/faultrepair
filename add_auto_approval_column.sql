-- Add the missing auto_approval_available_for_invitee column to invitations table
USE snappy;

ALTER TABLE invitations
ADD COLUMN auto_approval_available_for_invitee TINYINT(1) DEFAULT 0 COMMENT 'Auto-approval granted to invitee during registration' AFTER access_message;

COMMIT;
