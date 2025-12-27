-- database.sql
-- Script de création de la base de données e-FME

CREATE DATABASE IF NOT EXISTS efme_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE efme_db;

-- Table users
CREATE TABLE users (
    id VARCHAR(50) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'manager', 'technician', 'user') DEFAULT 'user',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB;

-- Table sites
CREATE TABLE sites (
    id VARCHAR(50) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code_site VARCHAR(50) UNIQUE NOT NULL,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    radius_meters INT DEFAULT 100,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_code_site (code_site)
) ENGINE=InnoDB;

-- Table equipment
CREATE TABLE equipment (
    id VARCHAR(50) PRIMARY KEY,
    site_id VARCHAR(50) NOT NULL,
    name VARCHAR(100) NOT NULL,
    reference VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
    INDEX idx_site_id (site_id)
) ENGINE=InnoDB;

-- Table pm_tasks
CREATE TABLE pm_tasks (
    id VARCHAR(50) PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    site_id VARCHAR(50) NOT NULL,
    equipment_id VARCHAR(50),
    assigned_to VARCHAR(50),
    planned_date DATE NOT NULL,
    status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    created_by VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE,
    FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_planned_date (planned_date),
    INDEX idx_assigned_to (assigned_to)
) ENGINE=InnoDB;

-- Table task_execution
CREATE TABLE task_execution (
    id VARCHAR(50) PRIMARY KEY,
    pm_task_id VARCHAR(50) NOT NULL,
    executed_by VARCHAR(50) NOT NULL,
    execution_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    comment TEXT,
    synced BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pm_task_id) REFERENCES pm_tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (executed_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_pm_task_id (pm_task_id),
    INDEX idx_execution_date (execution_date)
) ENGINE=InnoDB;

-- Table task_postponement
CREATE TABLE task_postponement (
    id VARCHAR(50) PRIMARY KEY,
    pm_task_id VARCHAR(50) NOT NULL,
    requested_by VARCHAR(50) NOT NULL,
    requested_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    new_planned_date DATE NOT NULL,
    justification TEXT NOT NULL,
    approved_by VARCHAR(50),
    approval_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    approved_at TIMESTAMP NULL,
    FOREIGN KEY (pm_task_id) REFERENCES pm_tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_approval_status (approval_status)
) ENGINE=InnoDB;

-- Table alerts
CREATE TABLE alerts (
    id VARCHAR(50) PRIMARY KEY,
    user_id VARCHAR(50) NOT NULL,
    pm_task_id VARCHAR(50),
    type ENUM('task_assigned', 'task_due', 'task_overdue', 'postponement_request', 'postponement_approved') NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (pm_task_id) REFERENCES pm_tasks(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_is_read (is_read)
) ENGINE=InnoDB;

-- Table reports
CREATE TABLE reports (
    id VARCHAR(50) PRIMARY KEY,
    type ENUM('daily', 'weekly', 'monthly', 'custom') NOT NULL,
    generated_by VARCHAR(50) NOT NULL,
    file_path VARCHAR(255),
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_generated_at (generated_at)
) ENGINE=InnoDB;

-- Table audit_logs
CREATE TABLE audit_logs (
    id VARCHAR(50) PRIMARY KEY,
    user_id VARCHAR(50) NOT NULL,
    action VARCHAR(100) NOT NULL,
    entity VARCHAR(50) NOT NULL,
    entity_id VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_entity (entity),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- Données de test
INSERT INTO users (id, name, email, password_hash, role, is_active) VALUES
('user_admin', 'Admin User', 'admin@efme.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', TRUE),
('user_tech1', 'Technicien 1', 'tech1@efme.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'technician', TRUE);

INSERT INTO sites (id, name, code_site, latitude, longitude, radius_meters) VALUES
('site_001', 'Site Principal', 'SITE-001', 48.8566, 2.3522, 150),
('site_002', 'Site Secondaire', 'SITE-002', 45.7640, 4.8357, 100);

-- Mot de passe par défaut pour les utilisateurs de test : "password"