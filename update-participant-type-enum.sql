-- Migration script to add XS (External Service Provider) to participant_type enum
-- Run this after deploying the updated snappy-dev.sql

-- Update the enum in participant_type table
ALTER TABLE participant_type MODIFY COLUMN participantType ENUM('C','S','XS') NOT NULL DEFAULT 'S';

-- Optional: Update any default values if needed (though this should not be necessary)

-- This migration only changes the enum definition and does not affect existing data
-- All existing 'C' and 'S' records will remain unchanged
