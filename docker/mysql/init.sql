CREATE DATABASE IF NOT EXISTS abet_tools;
USE abet_tools; 

CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'faculty') NOT NULL DEFAULT 'faculty',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    last_login TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS user_profiles (
    profile_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    display_name VARCHAR(255),
    department VARCHAR(255),
    phone VARCHAR(50),
    office_location VARCHAR(255),
    bio VARCHAR(512),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);






-- Logging tables for audit and login events
CREATE TABLE IF NOT EXISTS audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    actor_user_id INT NOT NULL,
    action VARCHAR(255) NOT NULL,
    target_type VARCHAR(255) NOT NULL,
    target_id VARCHAR(255) NOT NULL,
    metadata JSON,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (actor_user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS login_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    email_attempted VARCHAR(255),
    result ENUM('success', 'failed_password', 'failed_mfa', 'locked') NOT NULL,
    reason VARCHAR(255),
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
);