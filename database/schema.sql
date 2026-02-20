-- PAPER Framework - EAV + Hybrid Schema
-- Roles and Users (standard tables for auth performance)
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id)
);

-- EAV Core Tables (install only - attributes are registered at runtime from models)
CREATE TABLE IF NOT EXISTS eav_entities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entity_type VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_entity_type (entity_type)
);

CREATE TABLE IF NOT EXISTS eav_attributes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entity_type VARCHAR(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    data_type VARCHAR(20) DEFAULT 'string',
    UNIQUE KEY uk_attr (entity_type, name),
    INDEX idx_entity_type (entity_type)
);

CREATE TABLE IF NOT EXISTS eav_values (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entity_id INT NOT NULL,
    attribute_id INT NOT NULL,
    value TEXT,
    FOREIGN KEY (entity_id) REFERENCES eav_entities(id) ON DELETE CASCADE,
    FOREIGN KEY (attribute_id) REFERENCES eav_attributes(id) ON DELETE CASCADE,
    UNIQUE KEY uk_entity_attr (entity_id, attribute_id),
    INDEX idx_entity (entity_id),
    INDEX idx_attr (attribute_id)
);

-- Insert default roles
INSERT INTO roles (name) VALUES ('Administrator'), ('Standard User'), ('Coordinator')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Default admin user (password: admin123)
INSERT INTO users (username, password_hash, role_id) 
SELECT 'admin', '$2y$10$rBrZEQ7IKxJRkXZ2fzRdpO4MHdYqOd.wERK/VbgIFLxfx6JEowMGS', id FROM roles WHERE name = 'Administrator' LIMIT 1
ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash);

-- Fix admin password (admin123)
-- Run this if you cannot login with admin/admin123
UPDATE users SET password_hash = '$2y$10$rBrZEQ7IKxJRkXZ2fzRdpO4MHdYqOd.wERK/VbgIFLxfx6JEowMGS' WHERE username = 'admin';
