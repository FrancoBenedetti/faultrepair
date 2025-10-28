-- Add performance_logs table for monitoring and optimization
-- Run this SQL to create the performance_logs table

USE `snappy`;

CREATE TABLE IF NOT EXISTS `performance_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `operation` varchar(100) NOT NULL COMMENT 'Operation being monitored (e.g., api_service_provider_jobs)',
  `duration_ms` decimal(12,2) NOT NULL COMMENT 'Duration in milliseconds',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_performance_operation` (`operation`),
  KEY `idx_performance_duration` (`duration_ms`),
  KEY `idx_performance_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Performance monitoring logs for API operations and system metrics';
