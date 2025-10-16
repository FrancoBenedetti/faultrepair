-- Enhanced Geographic Segmentation for Snappy Fault Reporter
-- MariaDB 10.6 Compatible Schema Extensions

-- Phase 1: New Tables for Geographic Enhancement

-- Clean up any existing tables first
DROP TABLE IF EXISTS geographic_analytics;
DROP TABLE IF EXISTS geographic_search_cache;
DROP TABLE IF EXISTS regional_classifications;
DROP TABLE IF EXISTS region_hierarchy;
DROP TABLE IF EXISTS geographic_boundaries;
DROP TABLE IF EXISTS geographic_types;

-- Define different types of geographic classifications
CREATE TABLE geographic_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type_code VARCHAR(25) NOT NULL UNIQUE COMMENT 'PROVINCE, DISTRICT_MUNICIPALITY, LOCAL_MUNICIPALITY, SUBURB, REGIONAL_AREA, CUSTOM',
    type_name VARCHAR(50) NOT NULL,
    description TEXT,
    hierarchy_level TINYINT NOT NULL COMMENT '1-6, where 1 is highest level',
    is_administrative TINYINT(1) DEFAULT 1 COMMENT 'True for official administrative boundaries',
    is_searchable TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 999,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default geographic types
INSERT INTO geographic_types (type_code, type_name, description, hierarchy_level, is_administrative) VALUES
('PROVINCE', 'Province', 'Top-level administrative division', 1, 1),
('DISTRICT_MUNICIPALITY', 'District Municipality', 'Second-level administrative division', 2, 1),
('LOCAL_MUNICIPALITY', 'Local Municipality', 'Third-level administrative division', 3, 1),
('SUBURB', 'Suburb/Neighborhood', 'Urban area subdivision', 4, 0),
('TOWN', 'Town/City', 'Urban center', 4, 0),
('REGIONAL_AREA', 'Regional Area', 'Commonly used regional designation', 5, 0),
('CUSTOM', 'Custom Area', 'User-defined geographic area', 6, 0);

-- Store coordinate-based boundaries using MariaDB's spatial features
CREATE TABLE geographic_boundaries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    region_id INT NOT NULL,
    boundary_type ENUM('point', 'polygon', 'radius', 'bbox') NOT NULL DEFAULT 'point',
    center_latitude DECIMAL(10,8) NULL,
    center_longitude DECIMAL(10,8) NULL,
    radius_km DECIMAL(8,2) NULL COMMENT 'For radius-based boundaries',
    boundary_coordinates JSON NULL COMMENT 'For polygon/multipoint boundaries',
    north_lat DECIMAL(10,8) NULL COMMENT 'Bounding box north',
    south_lat DECIMAL(10,8) NULL COMMENT 'Bounding box south',
    east_lng DECIMAL(10,8) NULL COMMENT 'Bounding box east',
    west_lng DECIMAL(10,8) NULL COMMENT 'Bounding box west',
    accuracy_meters INT DEFAULT NULL COMMENT 'Accuracy of boundary data',
    source VARCHAR(50) DEFAULT NULL COMMENT 'Source of boundary data',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Explicitly manage hierarchical relationships
CREATE TABLE region_hierarchy (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ancestor_id INT NOT NULL COMMENT 'Higher level region',
    descendant_id INT NOT NULL COMMENT 'Lower level region',
    path_length TINYINT NOT NULL COMMENT 'Number of levels between regions',
    hierarchy_type ENUM('administrative', 'geographic', 'custom') DEFAULT 'administrative',
    is_primary_path TINYINT(1) DEFAULT 1 COMMENT 'True if this is the main hierarchy path',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_hierarchy (ancestor_id, descendant_id, hierarchy_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Handle regional names like Bushveld, Namaqualand, Border, etc.
CREATE TABLE regional_classifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    region_id INT NOT NULL,
    classification_type VARCHAR(50) NOT NULL COMMENT 'bushveld, namaqualand, border, karoo, etc.',
    classification_name VARCHAR(100) NOT NULL,
    description TEXT,
    boundary_definition JSON NULL COMMENT 'How this regional classification is defined',
    is_primary TINYINT(1) DEFAULT 0 COMMENT 'True if this is the main classification for the region',
    confidence_level TINYINT DEFAULT 100 COMMENT 'How confident we are in this classification (0-100)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optimize geographic searches with caching
CREATE TABLE geographic_search_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    search_term VARCHAR(255) NOT NULL,
    region_ids JSON NOT NULL COMMENT 'Matching region IDs',
    search_type ENUM('name', 'code', 'postal', 'keyword', 'proximity') NOT NULL,
    latitude DECIMAL(10,8) NULL COMMENT 'For proximity searches',
    longitude DECIMAL(10,8) NULL COMMENT 'For proximity searches',
    radius_km DECIMAL(8,2) NULL,
    result_count INT NOT NULL,
    cache_expires TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Track geographic usage patterns
CREATE TABLE geographic_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    region_id INT NOT NULL,
    metric_type ENUM('searches', 'service_providers', 'jobs_created', 'jobs_completed') NOT NULL,
    metric_date DATE NOT NULL,
    metric_value INT NOT NULL DEFAULT 0,
    metadata JSON NULL COMMENT 'Additional context data',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_region_metric (region_id, metric_type, metric_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Phase 2: Enhance Existing Tables

-- Check if columns exist and only add if they don't (handle partial previous runs)
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_NAME = 'regions' AND COLUMN_NAME = 'region_type') = 0,
    'ALTER TABLE regions ADD COLUMN (
        region_type ENUM(''province'', ''district_municipality'', ''local_municipality'', ''suburb'', ''town'', ''regional_area'', ''custom'') DEFAULT ''town'',
        parent_region_id INT NULL,
        geographic_level TINYINT DEFAULT 4 COMMENT ''1=Province, 2=District, 3=Local Municipality, 4=Suburb/Town'',
        population INT DEFAULT NULL,
        area_km2 DECIMAL(10,2) DEFAULT NULL,
        latitude DECIMAL(10,8) DEFAULT NULL,
        longitude DECIMAL(10,8) DEFAULT NULL,
        bounding_box POLYGON NULL COMMENT ''Geographic boundary as polygon'',
        is_primary_city TINYINT(1) DEFAULT 0,
        metro_area VARCHAR(100) NULL COMMENT ''For suburbs belonging to metro areas'',
        common_name VARCHAR(100) NULL COMMENT ''Alternative commonly used name'',
        official_name VARCHAR(100) NULL COMMENT ''Official administrative name'',
        district_code VARCHAR(20) NULL COMMENT ''Official district/municipality code'',
        municipality_code VARCHAR(20) NULL COMMENT ''Official municipality code'',
        ward_number VARCHAR(10) NULL COMMENT ''For electoral wards'',
        postal_codes TEXT NULL COMMENT ''Comma-separated postal codes'',
        search_keywords TEXT NULL COMMENT ''Additional search terms'',
        display_order INT DEFAULT 999,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )',
    'SELECT ''Columns already exist, skipping ALTER TABLE regions'''
));
PREPARE stmt FROM @sql;
EXECUTE stmt;

-- Rename the existing coordinates column to avoid conflict
ALTER TABLE locations CHANGE coordinates coordinates_varchar VARCHAR(50) NULL;
ALTER TABLE locations ADD COLUMN (
    primary_region_id INT NULL COMMENT 'Main region for this location',
    region_classifications JSON NULL COMMENT 'Multiple region associations with confidence levels',
    coordinates POINT NULL COMMENT 'Precise GPS coordinates (MariaDB spatial point)',
    address_components JSON NULL COMMENT 'Structured address breakdown',
    suburb VARCHAR(100) NULL,
    city VARCHAR(100) NULL,
    district VARCHAR(100) NULL,
    province VARCHAR(100) NULL,
    postal_code VARCHAR(10) NULL,
    location_type ENUM('business', 'residential', 'industrial', 'rural', 'other') DEFAULT 'business',
    is_verified TINYINT(1) DEFAULT 0,
    verification_method VARCHAR(50) NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Enhanced coverage areas for service providers
ALTER TABLE service_provider_regions ADD COLUMN (
    coverage_type ENUM('primary', 'secondary', 'occasional') DEFAULT 'primary',
    service_radius_km INT DEFAULT NULL COMMENT 'Radius of service coverage from region center',
    coverage_notes TEXT NULL,
    effective_from DATE NULL,
    effective_until DATE NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Phase 3: Add Indexes for Performance

CREATE INDEX idx_region_type ON regions(region_type);
CREATE INDEX idx_parent_region ON regions(parent_region_id);
CREATE INDEX idx_geographic_level ON regions(geographic_level);
CREATE INDEX idx_coordinates ON regions(latitude, longitude);

CREATE INDEX idx_boundary_type ON geographic_boundaries(boundary_type);
CREATE INDEX idx_boundary_coordinates ON geographic_boundaries(center_latitude, center_longitude);
CREATE INDEX idx_bbox ON geographic_boundaries(north_lat, south_lat, east_lng, west_lng);

CREATE INDEX idx_hierarchy_descendant ON region_hierarchy(descendant_id);
CREATE INDEX idx_hierarchy_path_length ON region_hierarchy(path_length);

CREATE INDEX idx_classification_type ON regional_classifications(classification_type);
CREATE INDEX idx_classification_name ON regional_classifications(classification_name);

CREATE INDEX idx_search_term ON geographic_search_cache(search_term);
CREATE INDEX idx_search_type ON geographic_search_cache(search_type);
CREATE INDEX idx_search_expires ON geographic_search_cache(cache_expires);

CREATE INDEX idx_geographic_metric_date ON geographic_analytics(metric_date);
CREATE INDEX idx_geographic_metric_type ON geographic_analytics(metric_type);

CREATE INDEX idx_primary_region ON locations(primary_region_id);

-- Phase 4: Add Foreign Key Constraints

-- geographic_boundaries foreign key
ALTER TABLE geographic_boundaries
ADD CONSTRAINT fk_geographic_boundaries_region
FOREIGN KEY (region_id) REFERENCES regions(id) ON DELETE CASCADE;

-- region_hierarchy foreign keys
ALTER TABLE region_hierarchy
ADD CONSTRAINT fk_region_hierarchy_ancestor
FOREIGN KEY (ancestor_id) REFERENCES regions(id) ON DELETE CASCADE,
ADD CONSTRAINT fk_region_hierarchy_descendant
FOREIGN KEY (descendant_id) REFERENCES regions(id) ON DELETE CASCADE;

-- regional_classifications foreign key
ALTER TABLE regional_classifications
ADD CONSTRAINT fk_regional_classifications_region
FOREIGN KEY (region_id) REFERENCES regions(id) ON DELETE CASCADE;

-- geographic_analytics foreign key
ALTER TABLE geographic_analytics
ADD CONSTRAINT fk_geographic_analytics_region
FOREIGN KEY (region_id) REFERENCES regions(id) ON DELETE CASCADE;

-- locations foreign key
ALTER TABLE locations
ADD CONSTRAINT fk_locations_primary_region
FOREIGN KEY (primary_region_id) REFERENCES regions(id) ON DELETE SET NULL;

-- Phase 5: Migration - Update existing regions with proper hierarchy

UPDATE regions SET
    region_type = CASE
        WHEN name IN ('Gauteng', 'Western Cape', 'KwaZulu-Natal', 'Eastern Cape', 'Free State', 'Limpopo', 'Mpumalanga', 'North West', 'Northern Cape')
        THEN 'province' ELSE 'town'
    END,
    geographic_level = CASE
        WHEN name IN ('Gauteng', 'Western Cape', 'KwaZulu-Natal', 'Eastern Cape', 'Free State', 'Limpopo', 'Mpumalanga', 'North West', 'Northern Cape')
        THEN 1 ELSE 4
    END;

-- Phase 6: South African Geographic Data Population

-- Add South African Provinces (official)
INSERT INTO regions (name, code, country, region_type, geographic_level, is_primary_city, official_name, display_order, is_active) VALUES
('Limpopo', 'LP', 'South Africa', 'province', 1, 0, 'Limpopo Province', 5, 1),
('Mpumalanga', 'MP', 'South Africa', 'province', 1, 0, 'Mpumalanga Province', 6, 1),
('North West', 'NW', 'South Africa', 'province', 1, 0, 'North West Province', 7, 1),
('Northern Cape', 'NC', 'South Africa', 'province', 1, 0, 'Northern Cape Province', 8, 1);

-- Add major South African District Municipalities (subset)
INSERT INTO regions (name, code, country, region_type, geographic_level, parent_region_id, district_code, official_name, is_active) VALUES
('Waterberg', 'DC36', 'South Africa', 'district_municipality', 2, (SELECT id FROM regions WHERE code='LP' AND region_type='province'), 'DC36', 'Waterberg District Municipality', 1),
('Capricorn', 'DC35', 'South Africa', 'district_municipality', 2, (SELECT id FROM regions WHERE code='LP' AND region_type='province'), 'DC35', 'Capricorn District Municipality', 1);

-- Add major South African cities and towns with coordinates
INSERT INTO regions (name, code, country, region_type, geographic_level, latitude, longitude, is_primary_city, population, display_order, is_active) VALUES
('Polokwane', 'PTG', 'South Africa', 'town', 4, -23.8962, 29.4478, 1, 702079, 35, 1),
('Nelspruit', 'NLP', 'South Africa', 'town', 4, -25.4753, 30.9694, 1, 588794, 36, 1),
('Kimberley', 'KIM', 'South Africa', 'town', 4, -28.7282, 24.7499, 1, 312109, 37, 1),
('Upington', 'UTN', 'South Africa', 'town', 4, -28.4478, 21.2561, 1, 71373, 38, 1),
('Rustenburg', 'RUS', 'South Africa', 'town', 4, -25.6667, 27.2421, 1, 549575, 43, 1),
('Vanderbijlpark', 'VDP', 'South Africa', 'town', 4, -26.7117, 27.8414, 1, 95292, 45, 1),
('Beaufort West', 'BFT', 'South Africa', 'town', 4, -32.3567, 22.5880, 0, 51034, 42, 1);

-- Add metro areas as suburbs for major cities
INSERT INTO regions (name, code, country, region_type, geographic_level, parent_region_id, metro_area, display_order, is_active) VALUES
('Sandton', 'JHB-STN', 'South Africa', 'suburb', 4, (SELECT id FROM regions WHERE name='Johannesburg'), 'Johannesburg Metropolitan Area', 28, 1),
('Fourways', 'JHB-FWY', 'South Africa', 'suburb', 4, (SELECT id FROM regions WHERE name='Johannesburg'), 'Johannesburg Metropolitan Area', 28, 1),
('Rosebank', 'JHB-RSB', 'South Africa', 'suburb', 4, (SELECT id FROM regions WHERE name='Johannesburg'), 'Johannesburg Metropolitan Area', 28, 1),
('Parow', 'CPT-PRW', 'South Africa', 'suburb', 4, (SELECT id FROM regions WHERE name='Cape Town'), 'Cape Town Metropolitan Area', 29, 1),
('Plumstead', 'CPT-PLM', 'South Africa', 'suburb', 4, (SELECT id FROM regions WHERE name='Cape Town'), 'Cape Town Metropolitan Area', 29, 1),
('Claremont', 'CPT-CLM', 'South Africa', 'suburb', 4, (SELECT id FROM regions WHERE name='Cape Town'), 'Cape Town Metropolitan Area', 29, 1),
('Sea Point', 'CPT-SPT', 'South Africa', 'suburb', 4, (SELECT id FROM regions WHERE name='Cape Town'), 'Cape Town Metropolitan Area', 29, 1),
('Lotus River', 'CPT-LTR', 'South Africa', 'suburb', 4, (SELECT id FROM regions WHERE name='Cape Town'), 'Cape Town Metropolitan Area', 29, 1);

-- Create region hierarchy relationships
INSERT INTO region_hierarchy (ancestor_id, descendant_id, path_length, hierarchy_type) VALUES
-- Province -> City relationships
((SELECT id FROM regions WHERE name='Limpopo'), (SELECT id FROM regions WHERE name='Polokwane'), 1, 'administrative'),
((SELECT id FROM regions WHERE name='Mpumalanga'), (SELECT id FROM regions WHERE name='Nelspruit'), 1, 'administrative'),
((SELECT id FROM regions WHERE name='Northern Cape'), (SELECT id FROM regions WHERE name='Kimberley'), 1, 'administrative'),
((SELECT id FROM regions WHERE name='Northern Cape'), (SELECT id FROM regions WHERE name='Upington'), 1, 'administrative'),
((SELECT id FROM regions WHERE name='North West'), (SELECT id FROM regions WHERE name='Rustenburg'), 1, 'administrative'),
((SELECT id FROM regions WHERE name='Gauteng'), (SELECT id FROM regions WHERE name='Vanderbijlpark'), 1, 'administrative');

-- Add regional classifications for traditional/geographic regions
INSERT INTO regional_classifications (region_id, classification_type, classification_name, description, is_primary) VALUES
-- Northern Cape
((SELECT id FROM regions WHERE name='Northern Cape'), 'namaqualand', 'Namaqualand', 'Arid region in northwestern South Africa known for its mineral wealth, unique ecosystems, and spring flowers', 1),
((SELECT id FROM regions WHERE name='Northern Cape'), 'karoo', 'Karoo', 'Semi-desert natural region of South Africa characterized by low rainfall and vast plains', 1),
-- Mpumalanga
((SELECT id FROM regions WHERE name='Mpumalanga'), 'lowveld', 'Lowveld', 'Subtropical region of South Africa known for its wildlife, game farms, bananas and citrus production', 1),
((SELECT id FROM regions WHERE name='Mpumalanga'), 'highveld', 'Highveld', 'High-elevation grassland region known for gold mining and cooler climate', 1),
-- Limpopo
((SELECT id FROM regions WHERE name='Limpopo'), 'bushveld', 'Bushveld', 'Wooded savanna region known for wildlife, game reserves, and mining activities', 1),
((SELECT id FROM regions WHERE name='Limpopo'), 'waterberg', 'Waterberg', 'Massif and biosphere reserve known for its unique sandstone formations and biodiversity', 0),
-- KwaZulu-Natal
((SELECT id FROM regions WHERE name='KwaZulu-Natal'), 'natal_midlands', 'Natal Midlands', 'Region in KwaZulu-Natal province, known for its rolling hills, dairy farming and cooler climate', 1),
-- North West
((SELECT id FROM regions WHERE name='North West'), 'platinum_belt', 'Platinum Belt', 'Mining region known for platinum group metals production', 1);

COMMIT;
