CREATE DATABASE IF NOT EXISTS magerwa_vehicle_tracking
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE magerwa_vehicle_tracking;

CREATE TABLE IF NOT EXISTS admins (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    names VARCHAR(120) NOT NULL,
    email VARCHAR(160) NOT NULL UNIQUE,
    phone VARCHAR(30) NOT NULL,
    national_id VARCHAR(30) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_admin_phone_format
        CHECK (phone REGEXP '^\\+?[0-9][0-9 -]{5,18}[0-9]$'),
    CONSTRAINT chk_admin_national_id_format
        CHECK (national_id REGEXP '^[0-9]{8,30}$')
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS clients (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    names VARCHAR(120) NOT NULL,
    national_id VARCHAR(30) NOT NULL UNIQUE,
    telephone VARCHAR(30) NOT NULL,
    address VARCHAR(255) NOT NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_clients_national_id_format
        CHECK (national_id REGEXP '^[0-9]{8,30}$'),
    CONSTRAINT chk_clients_phone_format
        CHECK (telephone REGEXP '^\\+?[0-9][0-9 -]{5,18}[0-9]$'),
    CONSTRAINT fk_clients_admin
        FOREIGN KEY (created_by) REFERENCES admins(id)
        ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS vehicles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    chassis_number VARCHAR(80) NOT NULL UNIQUE,
    manufacture_company VARCHAR(120) NOT NULL,
    manufacture_year YEAR NOT NULL,
    price DECIMAL(15, 2) NOT NULL,
    model_name VARCHAR(120) NOT NULL,
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_vehicles_chassis_format
        CHECK (chassis_number REGEXP '^[A-HJ-NPR-Z0-9]{17}$'),
    CONSTRAINT chk_vehicles_year_min
        CHECK (manufacture_year >= 1901),
    CONSTRAINT chk_vehicles_price_positive
        CHECK (price > 0),
    CONSTRAINT fk_vehicles_admin
        FOREIGN KEY (created_by) REFERENCES admins(id)
        ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS vehicle_client_links (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    client_id INT UNSIGNED NOT NULL,
    vehicle_id INT UNSIGNED NOT NULL UNIQUE,
    plate_number VARCHAR(30) NOT NULL UNIQUE,
    linked_by INT UNSIGNED NOT NULL,
    linked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_links_plate_format
        CHECK (plate_number REGEXP '^R[A-Z]{2} [0-9]{3} [A-Z]$'),
    CONSTRAINT fk_links_client
        FOREIGN KEY (client_id) REFERENCES clients(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_links_vehicle
        FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_links_admin
        FOREIGN KEY (linked_by) REFERENCES admins(id)
        ON DELETE RESTRICT
) ENGINE=InnoDB;

