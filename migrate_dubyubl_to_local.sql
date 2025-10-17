-- Migration script to update snappy-dubyubl database schema to match snappy-local schema
-- This script contains ALTER TABLE statements for existing tables and CREATE TABLE statements for new tables

-- ------------------------------------------------------------
-- First, handle column renames to avoid conflicts
-- ------------------------------------------------------------

-- Rename coordinates column to coordinates_varchar in locations table to match local schema
ALTER TABLE `locations` CHANGE COLUMN `coordinates` `coordinates_varchar` VARCHAR(50) DEFAULT NULL;

-- ------------------------------------------------------------
-- Add new columns and indexes to existing tables
-- ------------------------------------------------------------

-- Update locations table with new columns from local schema
ALTER TABLE `locations`
ADD COLUMN `primary_region_id` INT(11) DEFAULT NULL COMMENT 'Main region for this location' AFTER `created_at`,
ADD COLUMN `region_classifications` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Multiple region associations with confidence levels' CHECK (json_valid(`region_classifications`)) AFTER `primary_region_id`,
ADD COLUMN `coordinates` POINT DEFAULT NULL COMMENT 'Precise GPS coordinates (MariaDB spatial point)' AFTER `region_classifications`,
ADD COLUMN `address_components` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Structured address breakdown' CHECK (json_valid(`address_components`)) AFTER `coordinates`,
ADD COLUMN `suburb` VARCHAR(100) DEFAULT NULL AFTER `address_components`,
ADD COLUMN `city` VARCHAR(100) DEFAULT NULL AFTER `suburb`,
ADD COLUMN `district` VARCHAR(100) DEFAULT NULL AFTER `city`,
ADD COLUMN `province` VARCHAR(100) DEFAULT NULL AFTER `district`,
ADD COLUMN `postal_code` VARCHAR(10) DEFAULT NULL AFTER `province`,
ADD COLUMN `location_type` ENUM('business','residential','industrial','rural','other') DEFAULT 'business' AFTER `postal_code`,
ADD COLUMN `is_verified` TINYINT(1) DEFAULT 0 AFTER `location_type`,
ADD COLUMN `verification_method` VARCHAR(50) DEFAULT NULL AFTER `is_verified`,
ADD COLUMN `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP() AFTER `verification_method`;

-- Add new indexes to locations table
ALTER TABLE `locations`
ADD KEY `idx_primary_region` (`primary_region_id`);

-- Add new foreign key constraint to locations table
ALTER TABLE `locations`
ADD CONSTRAINT `fk_locations_primary_region` FOREIGN KEY (`primary_region_id`) REFERENCES `regions` (`id`) ON DELETE SET NULL;

-- Update regions table with new columns from local schema
ALTER TABLE `regions`
ADD COLUMN `region_type` ENUM('province','district_municipality','local_municipality','suburb','town','regional_area','custom') DEFAULT 'town' AFTER `created_at`,
ADD COLUMN `parent_region_id` INT(11) DEFAULT NULL AFTER `region_type`,
ADD COLUMN `geographic_level` TINYINT(4) DEFAULT 4 COMMENT '1=Province, 2=District, 3=Local Municipality, 4=Suburb/Town' AFTER `parent_region_id`,
ADD COLUMN `population` INT(11) DEFAULT NULL AFTER `geographic_level`,
ADD COLUMN `area_km2` DECIMAL(10,2) DEFAULT NULL AFTER `population`,
ADD COLUMN `latitude` DECIMAL(10,8) DEFAULT NULL AFTER `area_km2`,
ADD COLUMN `longitude` DECIMAL(10,8) DEFAULT NULL AFTER `latitude`,
ADD COLUMN `bounding_box` POLYGON DEFAULT NULL COMMENT 'Geographic boundary as polygon' AFTER `longitude`,
ADD COLUMN `is_primary_city` TINYINT(1) DEFAULT 0 AFTER `bounding_box`,
ADD COLUMN `metro_area` VARCHAR(100) DEFAULT NULL COMMENT 'For suburbs belonging to metro areas' AFTER `is_primary_city`,
ADD COLUMN `common_name` VARCHAR(100) DEFAULT NULL COMMENT 'Alternative commonly used name' AFTER `metro_area`,
ADD COLUMN `official_name` VARCHAR(100) DEFAULT NULL COMMENT 'Official administrative name' AFTER `common_name`,
ADD COLUMN `district_code` VARCHAR(20) DEFAULT NULL COMMENT 'Official district/municipality code' AFTER `official_name`,
ADD COLUMN `municipality_code` VARCHAR(20) DEFAULT NULL COMMENT 'Official municipality code' AFTER `district_code`,
ADD COLUMN `ward_number` VARCHAR(10) DEFAULT NULL COMMENT 'For electoral wards' AFTER `municipality_code`,
ADD COLUMN `postal_codes` TEXT DEFAULT NULL COMMENT 'Comma-separated postal codes' AFTER `ward_number`,
ADD COLUMN `search_keywords` TEXT DEFAULT NULL COMMENT 'Additional search terms' AFTER `postal_codes`,
ADD COLUMN `display_order` INT(11) DEFAULT 999 AFTER `search_keywords`,
ADD COLUMN `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP() AFTER `display_order`;

-- Add new indexes to regions table
ALTER TABLE `regions`
ADD KEY `idx_region_type` (`region_type`),
ADD KEY `idx_parent_region` (`parent_region_id`),
ADD KEY `idx_geographic_level` (`geographic_level`),
ADD KEY `idx_coordinates` (`latitude`,`longitude`);

-- Update service_provider_regions table with new columns from local schema
ALTER TABLE `service_provider_regions`
ADD COLUMN `coverage_type` ENUM('primary','secondary','occasional') DEFAULT 'primary' AFTER `region_id`,
ADD COLUMN `service_radius_km` INT(11) DEFAULT NULL COMMENT 'Radius of service coverage from region center' AFTER `coverage_type`,
ADD COLUMN `coverage_notes` TEXT DEFAULT NULL AFTER `service_radius_km`,
ADD COLUMN `effective_from` DATE DEFAULT NULL AFTER `coverage_notes`,
ADD COLUMN `effective_until` DATE DEFAULT NULL AFTER `effective_from`,
ADD COLUMN `is_active` TINYINT(1) DEFAULT 1 AFTER `effective_until`,
ADD COLUMN `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() AFTER `is_active`,
ADD COLUMN `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP() AFTER `created_at`;

-- Update site_settings foreign key constraint name to match local schema
ALTER TABLE `site_settings` DROP FOREIGN KEY `site_settings_ibfk_1`;
ALTER TABLE `site_settings` ADD CONSTRAINT `FK_site_settings_users` FOREIGN KEY (`updated_by_user_id`) REFERENCES `users` (`userId`) ON DELETE SET NULL;

-- ------------------------------------------------------------
-- Create new tables that exist in local schema but not in dubyubl
-- ------------------------------------------------------------

-- Create geographic_analytics table
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

-- Create geographic_boundaries table
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

-- Create geographic_search_cache table
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

-- Create geographic_types table
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create regional_classifications table
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create region_hierarchy table
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Completion message
-- ------------------------------------------------------------
-- Migration completed: dubyubl schema has been updated to match local schema
-- Note: This migration adds new tables and columns, but does not remove any existing data or columns
-- Always backup your database before running this migration
