-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               10.6.22-MariaDB-0ubuntu0.22.04.1 - Ubuntu 22.04
-- Server OS:                    debian-linux-gnu
-- HeidiSQL Version:             12.11.0.7065
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- Dumping structure for table snappy.admin_actions
CREATE TABLE IF NOT EXISTS `admin_actions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_user_id` int(11) NOT NULL,
  `action_type` enum('enable_participant','disable_participant','update_setting','reset_usage','feature_management') NOT NULL,
  `target_type` enum('participant','site_setting','subscription','feature') NOT NULL,
  `target_id` int(11) DEFAULT NULL,
  `target_identifier` varchar(255) DEFAULT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `admin_user_id` (`admin_user_id`),
  KEY `idx_action_type` (`action_type`),
  KEY `idx_target` (`target_type`,`target_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `admin_actions_ibfk_1` FOREIGN KEY (`admin_user_id`) REFERENCES `users` (`userId`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table snappy.geographic_analytics
CREATE TABLE IF NOT EXISTS `geographic_analytics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `region_id` int(11) NOT NULL,
  `metric_type` enum('searches','service_providers','jobs_created','jobs_completed') NOT NULL,
  `metric_date` date NOT NULL,
  `metric_value` int(11) NOT NULL DEFAULT 0,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Additional context data' CHECK (json_valid(`metadata`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_region_metric` (`region_id`,`metric_type`,`metric_date`),
  KEY `idx_geographic_metric_date` (`metric_date`),
  KEY `idx_geographic_metric_type` (`metric_type`),
  CONSTRAINT `fk_geographic_analytics_region` FOREIGN KEY (`region_id`) REFERENCES `regions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table snappy.geographic_boundaries
CREATE TABLE IF NOT EXISTS `geographic_boundaries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `region_id` int(11) NOT NULL,
  `boundary_type` enum('point','polygon','radius','bbox') NOT NULL DEFAULT 'point',
  `center_latitude` decimal(10,8) DEFAULT NULL,
  `center_longitude` decimal(10,8) DEFAULT NULL,
  `radius_km` decimal(8,2) DEFAULT NULL COMMENT 'For radius-based boundaries',
  `boundary_coordinates` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'For polygon/multipoint boundaries' CHECK (json_valid(`boundary_coordinates`)),
  `north_lat` decimal(10,8) DEFAULT NULL COMMENT 'Bounding box north',
  `south_lat` decimal(10,8) DEFAULT NULL COMMENT 'Bounding box south',
  `east_lng` decimal(10,8) DEFAULT NULL COMMENT 'Bounding box east',
  `west_lng` decimal(10,8) DEFAULT NULL COMMENT 'Bounding box west',
  `accuracy_meters` int(11) DEFAULT NULL COMMENT 'Accuracy of boundary data',
  `source` varchar(50) DEFAULT NULL COMMENT 'Source of boundary data',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_boundary_type` (`boundary_type`),
  KEY `idx_boundary_coordinates` (`center_latitude`,`center_longitude`),
  KEY `idx_bbox` (`north_lat`,`south_lat`,`east_lng`,`west_lng`),
  KEY `fk_geographic_boundaries_region` (`region_id`),
  CONSTRAINT `fk_geographic_boundaries_region` FOREIGN KEY (`region_id`) REFERENCES `regions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table snappy.geographic_search_cache
CREATE TABLE IF NOT EXISTS `geographic_search_cache` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `search_term` varchar(255) NOT NULL,
  `region_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Matching region IDs' CHECK (json_valid(`region_ids`)),
  `search_type` enum('name','code','postal','keyword','proximity') NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL COMMENT 'For proximity searches',
  `longitude` decimal(10,8) DEFAULT NULL COMMENT 'For proximity searches',
  `radius_km` decimal(8,2) DEFAULT NULL,
  `result_count` int(11) NOT NULL,
  `cache_expires` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_search_term` (`search_term`),
  KEY `idx_search_type` (`search_type`),
  KEY `idx_search_expires` (`cache_expires`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table snappy.geographic_types
CREATE TABLE IF NOT EXISTS `geographic_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type_code` varchar(25) NOT NULL COMMENT 'PROVINCE, DISTRICT_MUNICIPALITY, LOCAL_MUNICIPALITY, SUBURB, REGIONAL_AREA, CUSTOM',
  `type_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `hierarchy_level` tinyint(4) NOT NULL COMMENT '1-6, where 1 is highest level',
  `is_administrative` tinyint(1) DEFAULT 1 COMMENT 'True for official administrative boundaries',
  `is_searchable` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 999,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `type_code` (`type_code`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table snappy.invitations
CREATE TABLE IF NOT EXISTS `invitations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invitation_token` varchar(64) NOT NULL,
  `inviter_user_id` int(11) NOT NULL,
  `inviter_entity_type` varchar(20) NOT NULL,
  `inviter_entity_id` int(11) NOT NULL,
  `invitee_first_name` varchar(50) NOT NULL,
  `invitee_last_name` varchar(50) NOT NULL,
  `invitee_email` varchar(100) DEFAULT NULL,
  `invitee_phone` varchar(20) DEFAULT NULL,
  `invitee_user_id` int(11) DEFAULT NULL,
  `invitee_entity_type` varchar(20) DEFAULT NULL,
  `invitee_entity_id` int(11) DEFAULT NULL,
  `auto_approval_applied` tinyint(1) DEFAULT 0,
  `access_message` text DEFAULT NULL,
  `auto_approval_available_for_invitee` tinyint(1) DEFAULT 0 COMMENT 'Auto-approval granted to invitee during registration',
  `communication_method` varchar(20) NOT NULL,
  `invitation_status` varchar(30) DEFAULT 'pending',
  `initial_expiry_days` int(11) DEFAULT 7,
  `expires_at` datetime NOT NULL,
  `registration_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`registration_data`)),
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `accessed_at` timestamp NULL DEFAULT NULL,
  `last_activity_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invitation_token` (`invitation_token`),
  KEY `inviter_user_id` (`inviter_user_id`),
  KEY `invitee_user_id` (`invitee_user_id`),
  CONSTRAINT `invitations_ibfk_1` FOREIGN KEY (`inviter_user_id`) REFERENCES `users` (`userId`),
  CONSTRAINT `invitations_ibfk_2` FOREIGN KEY (`invitee_user_id`) REFERENCES `users` (`userId`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table snappy.invitation_access_log
CREATE TABLE IF NOT EXISTS `invitation_access_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invitation_id` int(11) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `invitation_id` (`invitation_id`),
  CONSTRAINT `invitation_access_log_ibfk_1` FOREIGN KEY (`invitation_id`) REFERENCES `invitations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table snappy.jobs
CREATE TABLE IF NOT EXISTS `jobs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_location_id` int(11) DEFAULT NULL,
  `item_identifier` varchar(100) DEFAULT NULL,
  `fault_description` text DEFAULT NULL,
  `technician_notes` text DEFAULT NULL,
  `assigned_provider_participant_id` int(11) DEFAULT NULL,
  `reporting_user_id` int(11) NOT NULL,
  `assigning_user_id` int(11) DEFAULT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `assigned_technician_user_id` int(11) DEFAULT NULL,
  `job_status` varchar(50) DEFAULT 'Reported',
  `archived_by_client` tinyint(1) DEFAULT 0,
  `archived_by_service_provider` tinyint(1) DEFAULT 0,
  `quotation_required` tinyint(1) DEFAULT 0 COMMENT 'Whether this job requires a quotation before work',
  `current_quotation_id` int(11) DEFAULT NULL COMMENT 'Reference to active quotation for this job',
  `quotation_deadline` date DEFAULT NULL COMMENT 'Deadline for quotation submission',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `client_location_id` (`client_location_id`),
  KEY `assigned_provider_participant_id` (`assigned_provider_participant_id`),
  KEY `reporting_user_id` (`reporting_user_id`),
  KEY `assigning_user_id` (`assigning_user_id`),
  KEY `assigned_technician_user_id` (`assigned_technician_user_id`),
  CONSTRAINT `jobs_ibfk_1` FOREIGN KEY (`client_location_id`) REFERENCES `locations` (`id`),
  CONSTRAINT `jobs_ibfk_2` FOREIGN KEY (`assigned_provider_participant_id`) REFERENCES `participants` (`participantId`),
  CONSTRAINT `jobs_ibfk_3` FOREIGN KEY (`reporting_user_id`) REFERENCES `users` (`userId`),
  CONSTRAINT `jobs_ibfk_4` FOREIGN KEY (`assigning_user_id`) REFERENCES `users` (`userId`),
  CONSTRAINT `jobs_ibfk_5` FOREIGN KEY (`assigned_technician_user_id`) REFERENCES `users` (`userId`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table snappy.job_images
CREATE TABLE IF NOT EXISTS `job_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `job_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_job_images_job_id` (`job_id`),
  KEY `idx_job_images_uploaded_by` (`uploaded_by`),
  KEY `idx_job_images_uploaded_at` (`uploaded_at`),
  CONSTRAINT `job_images_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `job_images_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`userId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table snappy.job_notes
CREATE TABLE IF NOT EXISTS `job_notes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `job_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `job_id` (`job_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `job_notes_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`),
  CONSTRAINT `job_notes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`userId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table snappy.job_status_history
CREATE TABLE IF NOT EXISTS `job_status_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `job_id` int(11) NOT NULL,
  `status` varchar(50) NOT NULL,
  `changed_by_user_id` int(11) NOT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `job_id` (`job_id`),
  KEY `changed_by_user_id` (`changed_by_user_id`),
  CONSTRAINT `job_status_history_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`),
  CONSTRAINT `job_status_history_ibfk_2` FOREIGN KEY (`changed_by_user_id`) REFERENCES `users` (`userId`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table snappy.locations
CREATE TABLE IF NOT EXISTS `locations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `participant_id` int(11) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `coordinates_varchar` varchar(50) DEFAULT NULL,
  `access_rules` varchar(500) DEFAULT NULL,
  `access_instructions` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `primary_region_id` int(11) DEFAULT NULL COMMENT 'Main region for this location',
  `region_classifications` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Multiple region associations with confidence levels' CHECK (json_valid(`region_classifications`)),
  `coordinates` point DEFAULT NULL COMMENT 'Precise GPS coordinates (MariaDB spatial point)',
  `address_components` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Structured address breakdown' CHECK (json_valid(`address_components`)),
  `suburb` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `postal_code` varchar(10) DEFAULT NULL,
  `location_type` enum('business','residential','industrial','rural','other') DEFAULT 'business',
  `is_verified` tinyint(1) DEFAULT 0,
  `verification_method` varchar(50) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `locations_ibfk_1` (`participant_id`),
  KEY `idx_primary_region` (`primary_region_id`),
  CONSTRAINT `fk_locations_primary_region` FOREIGN KEY (`primary_region_id`) REFERENCES `regions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `locations_ibfk_1` FOREIGN KEY (`participant_id`) REFERENCES `participants` (`participantId`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table snappy.participants
CREATE TABLE IF NOT EXISTS `participants` (
  `participantId` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `website` varchar(255) DEFAULT NULL,
  `manager_name` varchar(100) DEFAULT NULL,
  `manager_email` varchar(100) DEFAULT NULL,
  `manager_phone` varchar(20) DEFAULT NULL,
  `vat_number` varchar(50) DEFAULT NULL,
  `business_registration_number` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `logo_url` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `is_enabled` tinyint(1) DEFAULT 1,
  `disabled_reason` text DEFAULT NULL,
  `disabled_at` timestamp NULL DEFAULT NULL,
  `disabled_by_user_id` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`participantId`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table snappy.participant_approvals
CREATE TABLE IF NOT EXISTS `participant_approvals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_participant_id` int(11) NOT NULL,
  `provider_participant_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_client_provider` (`client_participant_id`,`provider_participant_id`),
  KEY `provider_participant_id` (`provider_participant_id`),
  CONSTRAINT `participant_approvals_ibfk_1` FOREIGN KEY (`client_participant_id`) REFERENCES `participants` (`participantId`),
  CONSTRAINT `participant_approvals_ibfk_2` FOREIGN KEY (`provider_participant_id`) REFERENCES `participants` (`participantId`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table snappy.participant_features
CREATE TABLE IF NOT EXISTS `participant_features` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `participantId` int(11) NOT NULL,
  `feature_name` varchar(100) NOT NULL,
  `is_enabled` tinyint(1) DEFAULT 1,
  `valid_until` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_participant_feature` (`participantId`,`feature_name`),
  KEY `idx_participant_features_participant` (`participantId`),
  CONSTRAINT `participant_features_ibfk_1` FOREIGN KEY (`participantId`) REFERENCES `participants` (`participantId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table snappy.participant_type
CREATE TABLE IF NOT EXISTS `participant_type` (
  `participantId` int(11) NOT NULL,
  `participantType` enum('C','S') NOT NULL DEFAULT 'S',
  `isActive` enum('Y','N') DEFAULT 'Y',
  PRIMARY KEY (`participantId`,`participantType`),
  CONSTRAINT `participant_type_ibfk_1` FOREIGN KEY (`participantId`) REFERENCES `participants` (`participantId`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table snappy.regional_classifications
CREATE TABLE IF NOT EXISTS `regional_classifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `region_id` int(11) NOT NULL,
  `classification_type` varchar(50) NOT NULL COMMENT 'bushveld, namaqualand, border, karoo, etc.',
  `classification_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `boundary_definition` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'How this regional classification is defined' CHECK (json_valid(`boundary_definition`)),
  `is_primary` tinyint(1) DEFAULT 0 COMMENT 'True if this is the main classification for the region',
  `confidence_level` tinyint(4) DEFAULT 100 COMMENT 'How confident we are in this classification (0-100)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_classification_type` (`classification_type`),
  KEY `idx_classification_name` (`classification_name`),
  KEY `fk_regional_classifications_region` (`region_id`),
  CONSTRAINT `fk_regional_classifications_region` FOREIGN KEY (`region_id`) REFERENCES `regions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table snappy.regions
CREATE TABLE IF NOT EXISTS `regions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(10) NOT NULL,
  `country` varchar(50) DEFAULT 'South Africa',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `region_type` enum('province','district_municipality','local_municipality','suburb','town','regional_area','custom') DEFAULT 'town',
  `parent_region_id` int(11) DEFAULT NULL,
  `geographic_level` tinyint(4) DEFAULT 4 COMMENT '1=Province, 2=District, 3=Local Municipality, 4=Suburb/Town',
  `population` int(11) DEFAULT NULL,
  `area_km2` decimal(10,2) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(10,8) DEFAULT NULL,
  `bounding_box` polygon DEFAULT NULL COMMENT 'Geographic boundary as polygon',
  `is_primary_city` tinyint(1) DEFAULT 0,
  `metro_area` varchar(100) DEFAULT NULL COMMENT 'For suburbs belonging to metro areas',
  `common_name` varchar(100) DEFAULT NULL COMMENT 'Alternative commonly used name',
  `official_name` varchar(100) DEFAULT NULL COMMENT 'Official administrative name',
  `district_code` varchar(20) DEFAULT NULL COMMENT 'Official district/municipality code',
  `municipality_code` varchar(20) DEFAULT NULL COMMENT 'Official municipality code',
  `ward_number` varchar(10) DEFAULT NULL COMMENT 'For electoral wards',
  `postal_codes` text DEFAULT NULL COMMENT 'Comma-separated postal codes',
  `search_keywords` text DEFAULT NULL COMMENT 'Additional search terms',
  `display_order` int(11) DEFAULT 999,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_region_type` (`region_type`),
  KEY `idx_parent_region` (`parent_region_id`),
  KEY `idx_geographic_level` (`geographic_level`),
  KEY `idx_coordinates` (`latitude`,`longitude`)
) ENGINE=InnoDB AUTO_INCREMENT=149 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table snappy.region_hierarchy
CREATE TABLE IF NOT EXISTS `region_hierarchy` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ancestor_id` int(11) NOT NULL COMMENT 'Higher level region',
  `descendant_id` int(11) NOT NULL COMMENT 'Lower level region',
  `path_length` tinyint(4) NOT NULL COMMENT 'Number of levels between regions',
  `hierarchy_type` enum('administrative','geographic','custom') DEFAULT 'administrative',
  `is_primary_path` tinyint(1) DEFAULT 1 COMMENT 'True if this is the main hierarchy path',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_hierarchy` (`ancestor_id`,`descendant_id`,`hierarchy_type`),
  KEY `idx_hierarchy_descendant` (`descendant_id`),
  KEY `idx_hierarchy_path_length` (`path_length`),
  CONSTRAINT `fk_region_hierarchy_ancestor` FOREIGN KEY (`ancestor_id`) REFERENCES `regions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_region_hierarchy_descendant` FOREIGN KEY (`descendant_id`) REFERENCES `regions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data exporting was unselected.

-- Dumping structure for table snappy.services
CREATE TABLE IF NOT EXISTS `services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=231 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table snappy.service_provider_regions
CREATE TABLE IF NOT EXISTS `service_provider_regions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_provider_id` int(11) NOT NULL,
  `region_id` int(11) NOT NULL,
  `coverage_type` enum('primary','secondary','occasional') DEFAULT 'primary',
  `service_radius_km` int(11) DEFAULT NULL COMMENT 'Radius of service coverage from region center',
  `coverage_notes` text DEFAULT NULL,
  `effective_from` date DEFAULT NULL,
  `effective_until` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_sp_region` (`service_provider_id`,`region_id`),
  KEY `region_id` (`region_id`),
  CONSTRAINT `service_provider_regions_ibfk_1` FOREIGN KEY (`service_provider_id`) REFERENCES `participants` (`participantId`),
  CONSTRAINT `service_provider_regions_ibfk_2` FOREIGN KEY (`region_id`) REFERENCES `regions` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table snappy.service_provider_services
CREATE TABLE IF NOT EXISTS `service_provider_services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_provider_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_sp_service` (`service_provider_id`,`service_id`),
  KEY `service_id` (`service_id`),
  CONSTRAINT `service_provider_services_ibfk_1` FOREIGN KEY (`service_provider_id`) REFERENCES `participants` (`participantId`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table snappy.site_settings
CREATE TABLE IF NOT EXISTS `site_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `setting_type` enum('string','int','bool','json') DEFAULT 'string',
  `description` text DEFAULT NULL,
  `updated_by_user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`),
  KEY `idx_site_settings_key` (`setting_key`),
  KEY `FK_site_settings_users` (`updated_by_user_id`),
  CONSTRAINT `FK_site_settings_users` FOREIGN KEY (`updated_by_user_id`) REFERENCES `users` (`userId`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=148 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table snappy.state
CREATE TABLE IF NOT EXISTS `state` (
  `state_id` int(11) NOT NULL AUTO_INCREMENT,
  `client_label` varchar(50) DEFAULT NULL,
  `sp_label` varchar(50) DEFAULT NULL,
  `description` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`state_id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Data exporting was unselected.

-- Dumping structure for table snappy.state_precedence
CREATE TABLE IF NOT EXISTS `state_precedence` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `applicableTo` enum('Client','Service Provider') NOT NULL,
  `state_id` int(11) NOT NULL,
  `prec_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `applicableTo_state_id_prec_id` (`applicableTo`,`state_id`,`prec_id`),
  KEY `FK_state_precedence_state` (`state_id`),
  KEY `FK_state_precedence_state_2` (`prec_id`),
  CONSTRAINT `FK_state_precedence_state` FOREIGN KEY (`state_id`) REFERENCES `state` (`state_id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `FK_state_precedence_state_2` FOREIGN KEY (`prec_id`) REFERENCES `state` (`state_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Data exporting was unselected.

-- Dumping structure for table snappy.subscriptions
CREATE TABLE IF NOT EXISTS `subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `participantId` int(11) NOT NULL,
  `subscription_tier` enum('free','basic','advanced') DEFAULT 'free',
  `status` enum('active','cancelled','expired','suspended') DEFAULT 'active',
  `stripe_customer_id` varchar(100) DEFAULT NULL,
  `monthly_job_limit` int(11) DEFAULT 3,
  `subscription_enabled` tinyint(1) DEFAULT 1,
  `current_period_start` date DEFAULT NULL,
  `current_period_end` date DEFAULT NULL,
  `cancel_at_period_end` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_subscriptions_participant` (`participantId`),
  CONSTRAINT `subscriptions_ibfk_1` FOREIGN KEY (`participantId`) REFERENCES `participants` (`participantId`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table snappy.subscription_usage
CREATE TABLE IF NOT EXISTS `subscription_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subscription_id` int(11) NOT NULL,
  `usage_type` enum('jobs_created','jobs_accepted') NOT NULL,
  `usage_month` varchar(7) NOT NULL COMMENT 'Format: YYYY-MM',
  `count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_subscription_month_type` (`subscription_id`,`usage_month`,`usage_type`),
  KEY `idx_usage_tracking_subscription_month` (`subscription_id`,`usage_month`),
  CONSTRAINT `subscription_usage_ibfk_1` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table snappy.users
CREATE TABLE IF NOT EXISTS `users` (
  `userId` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `entity_id` int(11) NOT NULL,
  `role_id` int(11) DEFAULT 1,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `entity_type` varchar(20) NOT NULL DEFAULT 'client',
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `email_verified` tinyint(1) DEFAULT 0,
  `verification_token` varchar(255) DEFAULT NULL,
  `token_expires` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`userId`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_users_entity` (`entity_id`),
  KEY `idx_users_role` (`role_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`entity_id`) REFERENCES `participants` (`participantId`),
  CONSTRAINT `users_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `user_roles` (`roleId`)
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

-- Dumping structure for table snappy.user_roles
CREATE TABLE IF NOT EXISTS `user_roles` (
  `roleId` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`roleId`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Data exporting was unselected.

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
