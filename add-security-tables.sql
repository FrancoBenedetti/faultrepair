-- Security & Audit System Database Schema
-- Run these SQL statements to add security features to the database

USE `snappy`;

-- Authentication logging table
CREATE TABLE IF NOT EXISTS `authentication_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL COMMENT 'login_attempt, password_change, session_start, etc.',
  `success` tinyint(1) NOT NULL DEFAULT 0,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_auth_user` (`user_id`),
  KEY `idx_auth_action` (`action`),
  KEY `idx_auth_success` (`success`),
  KEY `idx_auth_created` (`created_at`),
  CONSTRAINT `fk_auth_log_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`userId`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Security events logging table
CREATE TABLE IF NOT EXISTS `security_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `event_type` varchar(50) NOT NULL COMMENT 'rate_limit_exceeded, suspicious_activity, session_created, etc.',
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `metadata` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_security_event_type` (`event_type`),
  KEY `idx_security_user` (`user_id`),
  KEY `idx_security_created` (`created_at`),
  CONSTRAINT `fk_security_event_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`userId`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data access logging table
CREATE TABLE IF NOT EXISTS `data_access_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `table_name` varchar(100) NOT NULL,
  `operation` enum('SELECT','INSERT','UPDATE','DELETE') NOT NULL,
  `record_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_data_access_user` (`user_id`),
  KEY `idx_data_access_table` (`table_name`),
  KEY `idx_data_access_operation` (`operation`),
  KEY `idx_data_access_record` (`record_id`),
  KEY `idx_data_access_created` (`created_at`),
  CONSTRAINT `fk_data_access_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`userId`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- API rate limiting table
CREATE TABLE IF NOT EXISTS `api_rate_limits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `endpoint` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_rate_limit_user` (`user_id`),
  KEY `idx_rate_limit_endpoint` (`endpoint`),
  KEY `idx_rate_limit_created` (`created_at`),
  CONSTRAINT `fk_rate_limit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`userId`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- User security settings table
CREATE TABLE IF NOT EXISTS `user_security` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `totp_secret` varchar(100) DEFAULT NULL COMMENT 'TOTP secret key for 2FA',
  `totp_enabled` tinyint(1) DEFAULT 0 COMMENT 'Whether 2FA is enabled',
  `backup_codes` json DEFAULT NULL COMMENT 'Backup authentication codes',
  `password_last_changed` timestamp NULL DEFAULT NULL,
  `failed_login_attempts` int(11) DEFAULT 0,
  `account_locked_until` timestamp NULL DEFAULT NULL,
  `login_notifications` tinyint(1) DEFAULT 1 COMMENT 'Send email on new login',
  `suspicious_activity_alerts` tinyint(1) DEFAULT 1 COMMENT 'Alert on suspicious activity',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_security` (`user_id`),
  CONSTRAINT `fk_user_security_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`userId`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Password history table (for preventing reuse)
CREATE TABLE IF NOT EXISTS `password_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `old_password_hash` varchar(255) NOT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_password_history_user` (`user_id`),
  KEY `idx_password_history_changed` (`changed_at`),
  CONSTRAINT `fk_password_history_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`userId`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- User sessions table (for concurrent session management)
CREATE TABLE IF NOT EXISTS `user_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `session_id` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `expires_at` timestamp NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_session` (`user_id`,`session_id`),
  KEY `idx_sessions_user` (`user_id`),
  KEY `idx_sessions_expires` (`expires_at`),
  KEY `idx_sessions_activity` (`last_activity`),
  CONSTRAINT `fk_user_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`userId`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add security-related columns to existing users table
ALTER TABLE `users`
  ADD COLUMN `password_last_changed` timestamp NULL DEFAULT NULL AFTER `token_expires`,
  ADD COLUMN `failed_login_count` int(11) DEFAULT 0 AFTER `password_last_changed`,
  ADD COLUMN `locked_until` timestamp NULL DEFAULT NULL AFTER `failed_login_count`,
  ADD COLUMN `last_login_at` timestamp NULL DEFAULT NULL AFTER `locked_until`,
  ADD COLUMN `last_login_ip` varchar(45) DEFAULT NULL AFTER `last_login_at`;

-- Add indexes for performance
CREATE INDEX `idx_users_password_changed` ON `users` (`password_last_changed`);
CREATE INDEX `idx_users_locked_until` ON `users` (`locked_until`);
CREATE INDEX `idx_users_last_login` ON `users` (`last_login_at`);
