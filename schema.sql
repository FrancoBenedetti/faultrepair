CREATE DATABASE IF NOT EXISTS faultreporter;
USE faultreporter;

CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
);

INSERT INTO roles (name) VALUES
('Reporting Employee'),
('Site Budget Controller'),
('Service Provider Admin'),
('Technician');

CREATE TABLE clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE service_providers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    name VARCHAR(100),
    address TEXT,
    FOREIGN KEY (client_id) REFERENCES clients(id)
);

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    phone VARCHAR(20),
    role_id INT NOT NULL,
    entity_type ENUM('client', 'service_provider') NOT NULL,
    entity_id INT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id)
);

CREATE TABLE jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_location_id INT NOT NULL,
    item_identifier VARCHAR(100),
    fault_description TEXT,
    technician_notes TEXT,
    assigned_provider_id INT,
    reporting_user_id INT NOT NULL,
    assigning_user_id INT,
    contact_person VARCHAR(100),
    assigned_technician_id INT,
    job_status VARCHAR(50) DEFAULT 'Reported',
    archived_by_client BOOLEAN DEFAULT FALSE,
    archived_by_service_provider BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_location_id) REFERENCES locations(id),
    FOREIGN KEY (assigned_provider_id) REFERENCES service_providers(id),
    FOREIGN KEY (reporting_user_id) REFERENCES users(id),
    FOREIGN KEY (assigning_user_id) REFERENCES users(id),
    FOREIGN KEY (assigned_technician_id) REFERENCES users(id)
);

CREATE TABLE job_status_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    status VARCHAR(50) NOT NULL,
    changed_by_user_id INT NOT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES jobs(id),
    FOREIGN KEY (changed_by_user_id) REFERENCES users(id)
);

CREATE TABLE client_approved_providers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    service_provider_id INT NOT NULL,
    UNIQUE(client_id, service_provider_id),
    FOREIGN KEY (client_id) REFERENCES clients(id),
    FOREIGN KEY (service_provider_id) REFERENCES service_providers(id)
);

CREATE TABLE job_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    user_id INT NOT NULL,
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES jobs(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);
