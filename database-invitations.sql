-- Migration script for invitation system
-- Add this to your existing database

USE faultreporter;

-- Create invitations table
CREATE TABLE invitations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invitation_token VARCHAR(64) NOT NULL UNIQUE,
    inviter_user_id INT NOT NULL,
    inviter_entity_type ENUM('client', 'service_provider') NOT NULL,
    inviter_entity_id INT NOT NULL,
    invitee_first_name VARCHAR(50) NOT NULL,
    invitee_last_name VARCHAR(50) NOT NULL,
    invitee_email VARCHAR(100),
    invitee_phone VARCHAR(20),
    communication_method ENUM('whatsapp', 'telegram', 'sms', 'email') NOT NULL DEFAULT 'whatsapp',
    invitation_status ENUM('pending', 'sent', 'accessed', 'expired', 'completed', 'cancelled') DEFAULT 'pending',
    initial_expiry_days INT DEFAULT 7,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sent_at TIMESTAMP NULL,
    accessed_at TIMESTAMP NULL,
    last_activity_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    whatsapp_message_id VARCHAR(100),
    telegram_message_id VARCHAR(100),
    sms_message_id VARCHAR(100),
    email_message_id VARCHAR(100),

    -- Pre-populated registration data (JSON format for flexibility)
    registration_data JSON,

    -- Additional metadata
    notes TEXT,

    FOREIGN KEY (inviter_user_id) REFERENCES users(id),
    INDEX idx_invitation_token (invitation_token),
    INDEX idx_inviter_entity (inviter_entity_type, inviter_entity_id),
    INDEX idx_status (invitation_status),
    INDEX idx_expires_at (expires_at)
);

-- Create invitation_access_log table to track when invitations are accessed
CREATE TABLE invitation_access_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invitation_id INT NOT NULL,
    accessed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent TEXT,
    session_duration_minutes INT DEFAULT 0,
    FOREIGN KEY (invitation_id) REFERENCES invitations(id),
    INDEX idx_invitation_access (invitation_id, accessed_at)
);

-- Add columns for handling existing user scenarios
ALTER TABLE invitations ADD COLUMN invitee_user_id INT NULL AFTER invitee_phone;
ALTER TABLE invitations ADD COLUMN invitee_entity_type ENUM('client', 'service_provider') NULL AFTER invitee_user_id;
ALTER TABLE invitations ADD COLUMN invitee_entity_id INT NULL AFTER invitee_entity_type;
ALTER TABLE invitations ADD COLUMN auto_approval_applied BOOLEAN DEFAULT FALSE AFTER invitee_entity_id;
ALTER TABLE invitations ADD COLUMN access_message TEXT NULL AFTER auto_approval_applied;

-- Add foreign key for existing user detection
ALTER TABLE invitations ADD CONSTRAINT fk_invitee_user FOREIGN KEY (invitee_user_id) REFERENCES users(id);

-- Create site_settings table for configurable values (if it doesn't exist)
CREATE TABLE IF NOT EXISTS site_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_type ENUM('string', 'int', 'bool', 'json') DEFAULT 'string',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default invitation expiry setting (7 days)
INSERT INTO site_settings (setting_key, setting_value, setting_type)
VALUES ('invitation_expiry_days', '7', 'int')
ON DUPLICATE KEY UPDATE setting_value = '7';

-- Create a view for easy invitation management
CREATE VIEW invitation_summary AS
SELECT
    i.id,
    i.invitation_token,
    CONCAT(u.first_name, ' ', u.last_name) as inviter_name,
    u.email as inviter_email,
    i.inviter_entity_type,
    CASE
        WHEN i.inviter_entity_type = 'client' THEN c.name
        WHEN i.inviter_entity_type = 'service_provider' THEN sp.name
    END as inviter_entity_name,
    CONCAT(i.invitee_first_name, ' ', i.invitee_last_name) as invitee_name,
    i.invitee_email,
    i.invitee_phone,
    i.communication_method,
    i.invitation_status,
    i.created_at,
    i.sent_at,
    i.accessed_at,
    i.expires_at,
    i.completed_at,
    CASE
        WHEN i.invitation_status = 'completed' THEN 'Completed'
        WHEN i.expires_at < NOW() THEN 'Expired'
        WHEN i.invitation_status = 'accessed' AND i.last_activity_at < DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN 'Expired (Inactive)'
        ELSE 'Active'
    END as current_status
FROM invitations i
JOIN users u ON i.inviter_user_id = u.id
LEFT JOIN clients c ON i.inviter_entity_type = 'client' AND i.inviter_entity_id = c.id
LEFT JOIN service_providers sp ON i.inviter_entity_type = 'service_provider' AND i.inviter_entity_id = sp.id;
