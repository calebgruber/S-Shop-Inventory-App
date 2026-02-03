-- Theatre Sound Shop Inventory Database Schema
-- Complete schema with all features included

-- Settings table
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Subcategories table  
CREATE TABLE IF NOT EXISTS subcategories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- Theatre spaces table
CREATE TABLE IF NOT EXISTS theatre_spaces (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    role ENUM('admin', 'designer', 'student', 'production_audio') NOT NULL DEFAULT 'student',
    is_active BOOLEAN DEFAULT TRUE,
    is_deleted BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- User permissions table
CREATE TABLE IF NOT EXISTS user_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    permission_key VARCHAR(100) NOT NULL,
    can_access BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_permission (user_id, permission_key)
);

-- Items table (with all fields from problem statement)
CREATE TABLE IF NOT EXISTS items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    barcode VARCHAR(100) UNIQUE NOT NULL,
    category_id INT,
    subcategory_id INT,
    tracking_type ENUM('quantity', 'serial') DEFAULT 'quantity',
    total_quantity INT DEFAULT 0,
    in_stock_quantity INT DEFAULT 0,
    serial_numbers TEXT,
    location VARCHAR(255),
    photo_path VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (subcategory_id) REFERENCES subcategories(id) ON DELETE SET NULL
);

-- Shows table
CREATE TABLE IF NOT EXISTS shows (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    shop_lead VARCHAR(255),
    designer VARCHAR(255),
    production_audio_id INT,
    designer_id INT,
    theatre_space_id INT,
    status ENUM('active', 'completed', 'cancelled', 'archived') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (theatre_space_id) REFERENCES theatre_spaces(id) ON DELETE SET NULL,
    FOREIGN KEY (production_audio_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (designer_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Show assignments table
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

-- Pullsheets table
CREATE TABLE IF NOT EXISTS pullsheets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    show_id INT,
    barcode VARCHAR(100) UNIQUE NOT NULL,
    created_by VARCHAR(255),
    created_by_id INT,
    status ENUM('draft', 'finalized', 'picked', 'completed') DEFAULT 'draft',
    is_partial BOOLEAN DEFAULT FALSE,
    partial_saved_at TIMESTAMP NULL,
    requires_approval BOOLEAN DEFAULT FALSE,
    approved_by INT,
    approved_at TIMESTAMP NULL,
    approval_status ENUM('pending', 'approved', 'rejected') DEFAULT NULL,
    finalized_at TIMESTAMP NULL,
    picked_at TIMESTAMP NULL,
    picked_by VARCHAR(255),
    signature_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (show_id) REFERENCES shows(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Pullsheet items table
CREATE TABLE IF NOT EXISTS pullsheet_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pullsheet_id INT NOT NULL,
    item_id INT NOT NULL,
    quantity_needed INT NOT NULL,
    quantity_picked INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pullsheet_id) REFERENCES pullsheets(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
);

-- Change orders table
CREATE TABLE IF NOT EXISTS change_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    show_id INT,
    barcode VARCHAR(100) UNIQUE NOT NULL,
    created_by VARCHAR(255),
    created_by_id INT,
    status ENUM('draft', 'finalized', 'processed', 'completed') DEFAULT 'draft',
    is_partial BOOLEAN DEFAULT FALSE,
    partial_saved_at TIMESTAMP NULL,
    requires_approval BOOLEAN DEFAULT FALSE,
    approved_by INT,
    approved_at TIMESTAMP NULL,
    approval_status ENUM('pending', 'approved', 'rejected') DEFAULT NULL,
    finalized_at TIMESTAMP NULL,
    processed_at TIMESTAMP NULL,
    processed_by VARCHAR(255),
    signature_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (show_id) REFERENCES shows(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Change order items table
CREATE TABLE IF NOT EXISTS change_order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    change_order_id INT NOT NULL,
    item_id INT NOT NULL,
    quantity_change INT NOT NULL,
    quantity_processed INT DEFAULT 0,
    type ENUM('add', 'remove') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (change_order_id) REFERENCES change_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
);

-- Item allocations table
CREATE TABLE IF NOT EXISTS item_allocations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    show_id INT,
    theatre_space_id INT,
    pullsheet_id INT,
    change_order_id INT,
    quantity INT NOT NULL,
    status ENUM('reserved', 'checked_out', 'in_shop') DEFAULT 'in_shop',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
    FOREIGN KEY (show_id) REFERENCES shows(id) ON DELETE CASCADE,
    FOREIGN KEY (theatre_space_id) REFERENCES theatre_spaces(id) ON DELETE SET NULL,
    FOREIGN KEY (pullsheet_id) REFERENCES pullsheets(id) ON DELETE SET NULL,
    FOREIGN KEY (change_order_id) REFERENCES change_orders(id) ON DELETE SET NULL
);

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
    denial_reason TEXT,
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

-- Signatures table
CREATE TABLE IF NOT EXISTS signatures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    pullsheet_id INT,
    change_order_id INT,
    signature_data TEXT NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (pullsheet_id) REFERENCES pullsheets(id) ON DELETE CASCADE,
    FOREIGN KEY (change_order_id) REFERENCES change_orders(id) ON DELETE CASCADE
);

-- Notifications table
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

-- User hotkeys table
CREATE TABLE IF NOT EXISTS user_hotkeys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    hotkey VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_action (user_id, action)
);

-- Show events table (for calendar)
CREATE TABLE IF NOT EXISTS show_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    show_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    start_date DATETIME NOT NULL,
    end_date DATETIME,
    color VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (show_id) REFERENCES shows(id) ON DELETE CASCADE
);

-- Insert default settings
INSERT INTO settings (setting_key, setting_value) VALUES 
    ('app_name', 'CMFT Sound Shop Inventory'),
    ('logo_path', ''),
    ('login_cover_image', ''),
    ('dark_mode', '0')
ON DUPLICATE KEY UPDATE setting_key=setting_key;

-- Insert default admin user (password: admin123 - CHANGE THIS!)
INSERT INTO users (email, password_hash, full_name, role, is_active) VALUES 
    ('admin@example.com', '$2y$10$/9whGthAUmjludjbiqJexe6PKsp6qL6L.K9S7Wzfk8JZsMMQ.PC7m', 'System Administrator', 'admin', TRUE)
ON DUPLICATE KEY UPDATE email=email;

-- Create indexes for better performance
CREATE INDEX idx_items_barcode ON items(barcode);
CREATE INDEX idx_items_category ON items(category_id);
CREATE INDEX idx_items_subcategory ON items(subcategory_id);
CREATE INDEX idx_pullsheets_barcode ON pullsheets(barcode);
CREATE INDEX idx_pullsheets_show ON pullsheets(show_id);
CREATE INDEX idx_pullsheets_approval_status ON pullsheets(approval_status);
CREATE INDEX idx_change_orders_barcode ON change_orders(barcode);
CREATE INDEX idx_change_orders_show ON change_orders(show_id);
CREATE INDEX idx_change_orders_approval_status ON change_orders(approval_status);
CREATE INDEX idx_item_allocations_item ON item_allocations(item_id);
CREATE INDEX idx_item_allocations_show ON item_allocations(show_id);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_repairs_item ON repairs(item_id);
CREATE INDEX idx_repairs_status ON repairs(status);
CREATE INDEX idx_student_requests_student ON student_requests(student_id);
CREATE INDEX idx_student_requests_status ON student_requests(status);
CREATE INDEX idx_notifications_user ON notifications(user_id);
CREATE INDEX idx_notifications_read ON notifications(is_read);
CREATE INDEX idx_signatures_user ON signatures(user_id);
CREATE INDEX idx_signatures_pullsheet ON signatures(pullsheet_id);
CREATE INDEX idx_signatures_change_order ON signatures(change_order_id);
CREATE INDEX idx_show_assignments_show ON show_assignments(show_id);
CREATE INDEX idx_show_assignments_user ON show_assignments(user_id);
CREATE INDEX idx_show_events_show ON show_events(show_id);
