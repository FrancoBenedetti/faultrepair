-- Service Provider Workflow Database Migrations
-- This file contains all database schema changes for the Service Provider functionality

USE faultreporter;

-- Phase 1: Extend service_providers table with business details
ALTER TABLE service_providers ADD COLUMN website VARCHAR(255);
ALTER TABLE service_providers ADD COLUMN manager_name VARCHAR(100);
ALTER TABLE service_providers ADD COLUMN manager_email VARCHAR(100);
ALTER TABLE service_providers ADD COLUMN manager_phone VARCHAR(20);
ALTER TABLE service_providers ADD COLUMN vat_number VARCHAR(50);
ALTER TABLE service_providers ADD COLUMN business_registration_number VARCHAR(50);
ALTER TABLE service_providers ADD COLUMN description TEXT;
ALTER TABLE service_providers ADD COLUMN logo_url VARCHAR(255);
ALTER TABLE service_providers ADD COLUMN is_active BOOLEAN DEFAULT TRUE;
ALTER TABLE service_providers ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Phase 2: Create services table
CREATE TABLE services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    category VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert some default services
INSERT INTO services (name, description, category) VALUES
('Electrical Repairs', 'Electrical system repairs and maintenance', 'Electrical'),
('Plumbing', 'Plumbing repairs and installations', 'Plumbing'),
('HVAC', 'Heating, ventilation, and air conditioning', 'HVAC'),
('General Maintenance', 'General building maintenance', 'Maintenance'),
('Security Systems', 'Security system installation and repair', 'Security'),
('IT Support', 'Information technology support', 'IT'),
('Cleaning Services', 'Professional cleaning services', 'Cleaning'),
('Landscaping', 'Garden and landscaping services', 'Landscaping');

-- Phase 3: Create service_provider_services table (many-to-many)
CREATE TABLE service_provider_services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_provider_id INT NOT NULL,
    service_id INT NOT NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (service_provider_id) REFERENCES service_providers(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
    UNIQUE(service_provider_id, service_id)
);

-- Phase 4: Create regions table
CREATE TABLE regions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(10) UNIQUE,
    country VARCHAR(50) DEFAULT 'South Africa',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert South African provinces/regions
INSERT INTO regions (name, code, country) VALUES
('Gauteng', 'GP', 'South Africa'),
('Western Cape', 'WC', 'South Africa'),
('KwaZulu-Natal', 'KZN', 'South Africa'),
('Eastern Cape', 'EC', 'South Africa'),
('Limpopo', 'LP', 'South Africa'),
('Mpumalanga', 'MP', 'South Africa'),
('North West', 'NW', 'South Africa'),
('Northern Cape', 'NC', 'South Africa'),
('Free State', 'FS', 'South Africa');

-- Phase 5: Create service_provider_regions table (many-to-many)
CREATE TABLE service_provider_regions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    service_provider_id INT NOT NULL,
    region_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (service_provider_id) REFERENCES service_providers(id) ON DELETE CASCADE,
    FOREIGN KEY (region_id) REFERENCES regions(id) ON DELETE CASCADE,
    UNIQUE(service_provider_id, region_id)
);

-- Phase 6: Enhance client_approved_providers table
ALTER TABLE client_approved_providers ADD COLUMN approved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE client_approved_providers ADD COLUMN approved_by_user_id INT;
ALTER TABLE client_approved_providers ADD COLUMN notes TEXT;
ALTER TABLE client_approved_providers ADD FOREIGN KEY (approved_by_user_id) REFERENCES users(id);

-- Phase 7: Create indexes for better performance
CREATE INDEX idx_service_provider_services_provider_id ON service_provider_services(service_provider_id);
CREATE INDEX idx_service_provider_services_service_id ON service_provider_services(service_id);
CREATE INDEX idx_service_provider_regions_provider_id ON service_provider_regions(service_provider_id);
CREATE INDEX idx_service_provider_regions_region_id ON service_provider_regions(region_id);
CREATE INDEX idx_client_approved_providers_client_id ON client_approved_providers(client_id);
CREATE INDEX idx_client_approved_providers_provider_id ON client_approved_providers(service_provider_id);

-- Phase 8: Add some sample data for testing (optional)
-- This will be useful for development and testing
INSERT INTO service_providers (name, address, website, description, is_active) VALUES
('ABC Electrical Services', '123 Main Street, Johannesburg', 'https://abc-electrical.co.za', 'Professional electrical services for residential and commercial properties', TRUE),
('Premium Plumbing Solutions', '456 Oak Avenue, Cape Town', 'https://premiumplumbing.co.za', 'Expert plumbing services with 15 years experience', TRUE),
('TechSupport Pro', '789 Pine Road, Durban', 'https://techsupportpro.co.za', 'Comprehensive IT support and maintenance services', TRUE);

-- Link sample providers to services
INSERT INTO service_provider_services (service_provider_id, service_id, is_primary) VALUES
(1, 1, TRUE), -- ABC Electrical - Electrical Repairs (primary)
(2, 2, TRUE), -- Premium Plumbing - Plumbing (primary)
(3, 6, TRUE); -- TechSupport Pro - IT Support (primary)

-- Link sample providers to regions
INSERT INTO service_provider_regions (service_provider_id, region_id) VALUES
(1, 1), -- ABC Electrical - Gauteng
(2, 2), -- Premium Plumbing - Western Cape
(3, 3); -- TechSupport Pro - KwaZulu-Natal

-- Phase 9: Extend clients table with business profile fields (similar to service_providers)
ALTER TABLE clients ADD COLUMN website VARCHAR(255);
ALTER TABLE clients ADD COLUMN manager_name VARCHAR(100);
ALTER TABLE clients ADD COLUMN manager_email VARCHAR(100);
ALTER TABLE clients ADD COLUMN manager_phone VARCHAR(20);
ALTER TABLE clients ADD COLUMN vat_number VARCHAR(50);
ALTER TABLE clients ADD COLUMN business_registration_number VARCHAR(50);
ALTER TABLE clients ADD COLUMN description TEXT;
ALTER TABLE clients ADD COLUMN logo_url VARCHAR(255);
ALTER TABLE clients ADD COLUMN is_active BOOLEAN DEFAULT TRUE;
ALTER TABLE clients ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Display completion message
SELECT 'Database migration completed successfully!' as status;
