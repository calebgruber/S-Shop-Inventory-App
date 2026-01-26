-- Migration script for new features
-- Run this after schema.sql to add new features

-- Add new fields to items table
ALTER TABLE items 
ADD COLUMN location VARCHAR(255) DEFAULT NULL AFTER serial_numbers,
ADD COLUMN photo_path VARCHAR(500) DEFAULT NULL AFTER location;

-- Add new fields to pullsheets for partial completion
ALTER TABLE pullsheets 
ADD COLUMN is_partial BOOLEAN DEFAULT FALSE AFTER status,
ADD COLUMN partial_saved_at TIMESTAMP NULL AFTER is_partial;

-- Add new fields to change_orders for partial completion  
ALTER TABLE change_orders 
ADD COLUMN is_partial BOOLEAN DEFAULT FALSE AFTER status,
ADD COLUMN partial_saved_at TIMESTAMP NULL AFTER is_partial;

-- Users table for authentication and permissions
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    role ENUM('admin', 'designer', 'student') NOT NULL DEFAULT 'student',
    is_active BOOLEAN DEFAULT TRUE,
    is_deleted BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- User permissions table for granular access control
CREATE TABLE IF NOT EXISTS user_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    permission_key VARCHAR(100) NOT NULL,
    can_access BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_permission (user_id, permission_key)
);

-- Subcategories table for better organization
CREATE TABLE IF NOT EXISTS subcategories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- Add subcategory to items
ALTER TABLE items 
ADD COLUMN subcategory_id INT DEFAULT NULL AFTER category_id,
ADD FOREIGN KEY (subcategory_id) REFERENCES subcategories(id) ON DELETE SET NULL;

-- Repairs table
CREATE TABLE IF NOT EXISTS repairs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    repair_id VARCHAR(50) UNIQUE NOT NULL,
    item_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    category_id INT,
    subcategory_id INT,
    description TEXT,
    status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    reported_by INT,
    assigned_to INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (subcategory_id) REFERENCES subcategories(id) ON DELETE SET NULL,
    FOREIGN KEY (reported_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
);

-- Student requests table
CREATE TABLE IF NOT EXISTS student_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id VARCHAR(50) UNIQUE NOT NULL,
    student_id INT NOT NULL,
    item_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    reason TEXT,
    status ENUM('pending', 'approved', 'rejected', 'fulfilled') DEFAULT 'pending',
    approved_by INT,
    approved_at TIMESTAMP NULL,
    pullsheet_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (pullsheet_id) REFERENCES pullsheets(id) ON DELETE SET NULL
);

-- Pullsheets allow NULL show_id for non-show pullsheets (student requests, etc.)
ALTER TABLE pullsheets 
MODIFY COLUMN show_id INT DEFAULT NULL;

-- Change orders allow NULL show_id for non-show change orders
ALTER TABLE change_orders 
MODIFY COLUMN show_id INT DEFAULT NULL;

-- Create indexes for better performance
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_repairs_item ON repairs(item_id);
CREATE INDEX idx_repairs_status ON repairs(status);
CREATE INDEX idx_student_requests_student ON student_requests(student_id);
CREATE INDEX idx_student_requests_status ON student_requests(status);

-- Insert default admin user (password: admin123 - CHANGE THIS!)
-- Password hash for 'admin123'
INSERT INTO users (email, password_hash, full_name, role, is_active) VALUES 
    ('admin@example.com', '$2y$10$/9whGthAUmjludjbiqJexe6PKsp6qL6L.K9S7Wzfk8JZsMMQ.PC7m', 'System Administrator', 'admin', TRUE)
ON DUPLICATE KEY UPDATE email=email;

-- Permission keys that can be assigned:
-- 'dashboard', 'inventory', 'shows', 'pullsheets', 'change_orders', 
-- 'pick_mode', 'return_mode', 'reports', 'settings', 'repairs', 
-- 'student_requests', 'user_management', 'paperwork'
