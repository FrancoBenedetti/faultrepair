-- Database Update Script for Technician Functionality
-- Run this script to add missing columns to existing database

USE faultreporter;

-- Add missing columns to users table
ALTER TABLE users ADD COLUMN first_name VARCHAR(50);
ALTER TABLE users ADD COLUMN last_name VARCHAR(50);
ALTER TABLE users ADD COLUMN phone VARCHAR(20);
ALTER TABLE users ADD COLUMN is_active BOOLEAN DEFAULT TRUE;

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
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
);

-- Add indexes for better performance
CREATE INDEX idx_job_images_job_id ON job_images(job_id);
CREATE INDEX idx_job_images_uploaded_by ON job_images(uploaded_by);
CREATE INDEX idx_job_images_uploaded_at ON job_images(uploaded_at);

-- Display success message
SELECT 'Database update completed successfully!' as status;
