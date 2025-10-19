-- Job Quotations System
-- This table stores quotation data for jobs that require quotes before work begins

CREATE TABLE IF NOT EXISTS `job_quotations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `job_id` int(11) NOT NULL,
  `provider_participant_id` int(11) NOT NULL,
  `quotation_amount` decimal(10,2) DEFAULT NULL COMMENT 'Quoted amount (indicative only)',
  `quotation_description` text DEFAULT NULL COMMENT 'Detailed quotation description and scope of work',
  `quotation_document_url` varchar(500) DEFAULT NULL COMMENT 'URL to actual quotation document (PDF, etc.)',
  `valid_until` date DEFAULT NULL COMMENT 'Date when quotation expires',
  `status` enum('draft','submitted','accepted','rejected','expired') DEFAULT 'draft',
  `submitted_at` timestamp NULL DEFAULT NULL COMMENT 'When quote was submitted to client',
  `responded_at` timestamp NULL DEFAULT NULL COMMENT 'When client responded to quote',
  `response_notes` text DEFAULT NULL COMMENT 'Client notes on quote decision',
  `internal_notes` text DEFAULT NULL COMMENT 'Service provider internal notes',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_job_provider` (`job_id`,`provider_participant_id`) COMMENT 'One quote per provider per job',
  KEY `idx_job_quotations_job_id` (`job_id`),
  KEY `idx_job_quotations_provider` (`provider_participant_id`),
  KEY `idx_job_quotations_status` (`status`),
  KEY `idx_job_quotations_valid_until` (`valid_until`),
  CONSTRAINT `job_quotations_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `job_quotations_ibfk_2` FOREIGN KEY (`provider_participant_id`) REFERENCES `participants` (`participantId`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add new status to existing state table for consistency
INSERT IGNORE INTO `state` (`state_id`, `client_label`, `sp_label`, `description`) VALUES
(16, 'Quote Submitted', 'Quote Provided', 'Service provider has submitted a quotation for client review'),
(17, 'Quote Accepted', 'Quote Accepted', 'Client has accepted the quotation and work can proceed'),
(18, 'Quote Rejected', 'Quote Rejected', 'Client has rejected the quotation'),
(19, 'Quote Expired', 'Quote Expired', 'Quotation has expired without response');

-- Update state precedence for new quote states
INSERT IGNORE INTO `state_precedence` (`applicableTo`, `state_id`, `prec_id`) VALUES
('Client', 16, 6),  -- Quote Submitted comes after Quote Requested
('Service Provider', 16, 6),
('Client', 17, 16), -- Quote Accepted comes after Quote Submitted
('Service Provider', 17, 16),
('Client', 18, 16), -- Quote Rejected comes after Quote Submitted
('Service Provider', 18, 16),
('Client', 19, 16), -- Quote Expired comes after Quote Submitted
('Service Provider', 19, 16);

-- Add quotation fields to jobs table for quote workflow integration
ALTER TABLE `jobs`
ADD COLUMN `quotation_required` tinyint(1) DEFAULT 0 COMMENT 'Whether this job requires a quotation before work',
ADD COLUMN `current_quotation_id` int(11) DEFAULT NULL COMMENT 'Reference to active quotation for this job',
ADD COLUMN `quotation_deadline` date DEFAULT NULL COMMENT 'Deadline for quotation submission',
ADD KEY `idx_jobs_quotation_required` (`quotation_required`),
ADD KEY `idx_jobs_current_quotation` (`current_quotation_id`),
ADD KEY `idx_jobs_quotation_deadline` (`quotation_deadline`),
ADD CONSTRAINT `jobs_ibfk_quotation` FOREIGN KEY (`current_quotation_id`) REFERENCES `job_quotations` (`id`) ON DELETE SET NULL;

-- Create quotation response history table
CREATE TABLE IF NOT EXISTS `job_quotation_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quotation_id` int(11) NOT NULL,
  `action` enum('created','submitted','accepted','rejected','expired','updated') NOT NULL,
  `changed_by_user_id` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_quotation_history_quotation` (`quotation_id`),
  KEY `idx_quotation_history_user` (`changed_by_user_id`),
  KEY `idx_quotation_history_action` (`action`),
  CONSTRAINT `job_quotation_history_ibfk_1` FOREIGN KEY (`quotation_id`) REFERENCES `job_quotations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `job_quotation_history_ibfk_2` FOREIGN KEY (`changed_by_user_id`) REFERENCES `users` (`userId`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create indexes for better performance
CREATE INDEX `idx_job_quotations_status_job` ON `job_quotations` (`status`, `job_id`);
CREATE INDEX `idx_job_quotations_provider_status` ON `job_quotations` (`provider_participant_id`, `status`);
CREATE INDEX `idx_jobs_quotation_workflow` ON `jobs` (`job_status`, `quotation_required`, `current_quotation_id`);
