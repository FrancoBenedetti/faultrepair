-- Database Update Script for Snappy Schema Fixes
-- Run this script to add missing columns and tables to snappy database

USE snappy;

-- Add missing columns to users table
ALTER TABLE users ADD COLUMN first_name VARCHAR(50);
ALTER TABLE users ADD COLUMN last_name VARCHAR(50);
ALTER TABLE users ADD COLUMN phone VARCHAR(20);
ALTER TABLE users MODIFY COLUMN password_hash VARCHAR(255) NULL;
ALTER TABLE users ADD COLUMN is_active BOOLEAN DEFAULT FALSE;
ALTER TABLE users ADD COLUMN email_verified BOOLEAN DEFAULT FALSE;
ALTER TABLE users ADD COLUMN verification_token VARCHAR(255);
ALTER TABLE users ADD COLUMN token_expires TIMESTAMP NULL;

-- Verify the changes
DESCRIBE users;

-- Add job_images table for storing job-related images
CREATE TABLE IF NOT EXISTS job_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    uploaded_by INT NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(userId)
);

-- Add is_primary column to service_provider_services table
ALTER TABLE service_provider_services ADD COLUMN is_primary TINYINT(1) DEFAULT 0;

-- Add indexes for better performance
CREATE INDEX idx_job_images_job_id ON job_images(job_id);
CREATE INDEX idx_job_images_uploaded_by ON job_images(uploaded_by);
CREATE INDEX idx_job_images_uploaded_at ON job_images(uploaded_at);

-- Add new columns to locations table for GPS coordinates and access information
ALTER TABLE locations ADD COLUMN coordinates VARCHAR(50);
ALTER TABLE locations ADD COLUMN access_rules VARCHAR(500);
ALTER TABLE locations ADD COLUMN access_instructions TEXT;

-- Add created_at column to participant_approvals table (for approved_at in query)
ALTER TABLE participant_approvals ADD COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP;

-- Fix foreign key reference for job_images (userId instead of id)
-- First drop existing foreign key if it exists (to handle corrections)
-- Note: If foreign key exists, this will fail if there's bad data, but assuming it's new table

-- Display success message
SELECT 'Database update completed successfully!' as status;
