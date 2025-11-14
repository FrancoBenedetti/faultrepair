--
-- Table structure for table `assets`
--

CREATE TABLE `assets` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
	`list_owner_id` INT(11) NOT NULL COMMENT 'Participant ID of the list owner (either the client or a service provider). Determines who can edit the record.',
	`client_id` INT(11) NOT NULL COMMENT 'Participant ID of the client that owns the asset.',
	`asset_no` VARCHAR(255) NOT NULL COLLATE 'utf8mb4_general_ci',
	`item` VARCHAR(255) NOT NULL COLLATE 'utf8mb4_general_ci',
	`description` TEXT NULL DEFAULT NULL COLLATE 'utf8mb4_general_ci',
	`location_id` INT(11) NULL DEFAULT NULL COMMENT 'Site ID where the item is located',
	`manager_id` INT(11) NULL DEFAULT NULL COMMENT 'User ID of the manager responsible for approvals',
	`sp_id` INT(11) NULL DEFAULT NULL COMMENT 'Optional: A default Service Provider ID to associate with the asset',
	`star` TINYINT(1) NULL DEFAULT '0',
	`status` VARCHAR(50) NULL DEFAULT 'active' COMMENT 'e.g., active, inactive, unavailable, decommissioned' COLLATE 'utf8mb4_general_ci',
	`created_at` DATETIME NULL DEFAULT current_timestamp(),
	`updated_at` DATETIME NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
	PRIMARY KEY (`id`) USING BTREE,
	UNIQUE INDEX `unique_asset_in_list` (`list_owner_id`, `client_id`, `asset_no`) USING BTREE,
	INDEX `FK_assets_participants_2` (`client_id`) USING BTREE,
	INDEX `FK_assets_locations` (`location_id`) USING BTREE,
	INDEX `FK_assets_users` (`manager_id`) USING BTREE,
	CONSTRAINT `FK_assets_locations` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON UPDATE NO ACTION ON DELETE SET NULL,
	CONSTRAINT `FK_assets_participants` FOREIGN KEY (`list_owner_id`) REFERENCES `participants` (`participantId`) ON UPDATE NO ACTION ON DELETE CASCADE,
	CONSTRAINT `FK_assets_participants_2` FOREIGN KEY (`client_id`) REFERENCES `participants` (`participantId`) ON UPDATE NO ACTION ON DELETE CASCADE,
	CONSTRAINT `FK_assets_users` FOREIGN KEY (`manager_id`) REFERENCES `users` (`userId`) ON UPDATE NO ACTION ON DELETE SET NULL
)
COMMENT='Stores asset information. Editing is controlled by list_owner_id.'
COLLATE='utf8mb4_general_ci'
ENGINE=InnoDB
;