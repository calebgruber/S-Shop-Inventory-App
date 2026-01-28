-- Migration to add Production Audio role and approval/signature features
-- Run this after feature_additions_migration.sql

-- Update users table to include 'production_audio' role
ALTER TABLE users 
MODIFY COLUMN role ENUM('admin', 'designer', 'student', 'production_audio') NOT NULL DEFAULT 'student';

-- Add approval fields to pullsheets
ALTER TABLE pullsheets 
ADD COLUMN requires_approval BOOLEAN DEFAULT FALSE AFTER created_by,
ADD COLUMN approved_by INT DEFAULT NULL AFTER requires_approval,
ADD COLUMN approved_at TIMESTAMP NULL AFTER approved_by,
ADD COLUMN approval_status ENUM('pending', 'approved', 'rejected') DEFAULT NULL AFTER approved_at,
ADD FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL;

-- Add approval fields to change_orders
ALTER TABLE change_orders 
ADD COLUMN requires_approval BOOLEAN DEFAULT FALSE AFTER created_by,
ADD COLUMN approved_by INT DEFAULT NULL AFTER requires_approval,
ADD COLUMN approved_at TIMESTAMP NULL AFTER approved_by,
ADD COLUMN approval_status ENUM('pending', 'approved', 'rejected') DEFAULT NULL AFTER approved_at,
ADD FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL;

-- Create signatures table for Production Audio users
CREATE TABLE IF NOT EXISTS signatures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    pullsheet_id INT DEFAULT NULL,
    change_order_id INT DEFAULT NULL,
    signature_data TEXT NOT NULL, -- Base64 encoded signature image
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (pullsheet_id) REFERENCES pullsheets(id) ON DELETE CASCADE,
    FOREIGN KEY (change_order_id) REFERENCES change_orders(id) ON DELETE CASCADE
);

-- Add signature field to pullsheets for final checkout
ALTER TABLE pullsheets 
ADD COLUMN signature_id INT DEFAULT NULL AFTER picked_by,
ADD FOREIGN KEY (signature_id) REFERENCES signatures(id) ON DELETE SET NULL;

-- Add signature field to change_orders for final checkout
ALTER TABLE change_orders 
ADD COLUMN signature_id INT DEFAULT NULL AFTER processed_by,
ADD FOREIGN KEY (signature_id) REFERENCES signatures(id) ON DELETE SET NULL;

-- Add indices for performance
CREATE INDEX idx_signatures_user ON signatures(user_id);
CREATE INDEX idx_signatures_pullsheet ON signatures(pullsheet_id);
CREATE INDEX idx_signatures_change_order ON signatures(change_order_id);
CREATE INDEX idx_pullsheets_approval_status ON pullsheets(approval_status);
CREATE INDEX idx_change_orders_approval_status ON change_orders(approval_status);

-- Add show assignment fields for designers and production audio
CREATE TABLE IF NOT EXISTS show_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    show_id INT NOT NULL,
    user_id INT NOT NULL,
    role ENUM('designer', 'production_audio') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (show_id) REFERENCES shows(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_show_user (show_id, user_id)
);

CREATE INDEX idx_show_assignments_show ON show_assignments(show_id);
CREATE INDEX idx_show_assignments_user ON show_assignments(user_id);

-- Update shows table to store assigned users properly
ALTER TABLE shows
ADD COLUMN production_audio_id INT DEFAULT NULL AFTER designer,
ADD COLUMN designer_id INT DEFAULT NULL AFTER production_audio_id,
ADD FOREIGN KEY (production_audio_id) REFERENCES users(id) ON DELETE SET NULL,
ADD FOREIGN KEY (designer_id) REFERENCES users(id) ON DELETE SET NULL;

-- Add cover image for login page to settings
INSERT INTO settings (setting_key, setting_value) VALUES 
    ('login_cover_image', '')
ON DUPLICATE KEY UPDATE setting_key=setting_key;

-- Add denial reason to student_requests
ALTER TABLE student_requests
ADD COLUMN denial_reason TEXT DEFAULT NULL AFTER reason;

-- Create notifications table (if not exists from advanced_features_migration)
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT,
    link VARCHAR(255),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_notifications_user ON notifications(user_id);
CREATE INDEX idx_notifications_read ON notifications(is_read);
