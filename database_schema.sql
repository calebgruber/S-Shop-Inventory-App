-- S-Shop Inventory App Database Schema
-- MySQL Database Schema for Theatre Sound Shop Inventory Management

CREATE DATABASE IF NOT EXISTS voxelnodes_sshop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE voxelnodes_sshop;

-- Settings table
CREATE TABLE IF NOT EXISTS settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default settings
INSERT INTO settings (setting_key, setting_value) VALUES 
('logo_path', 'assets/logo.png'),
('app_name', 'CMFT Sound Shop Inventory'),
('theme_mode', 'light')
ON DUPLICATE KEY UPDATE setting_key=setting_key;

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Theatre spaces table
CREATE TABLE IF NOT EXISTS theatre_spaces (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Items table (inventory)
CREATE TABLE IF NOT EXISTS items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    barcode VARCHAR(100) UNIQUE NOT NULL,
    category_id INT,
    tracking_type ENUM('quantity', 'serial') DEFAULT 'quantity',
    total_quantity INT DEFAULT 0,
    available_quantity INT DEFAULT 0,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_barcode (barcode),
    INDEX idx_name (name)
);

-- Serial numbers for items tracked by serial
CREATE TABLE IF NOT EXISTS item_serials (
    id INT PRIMARY KEY AUTO_INCREMENT,
    item_id INT NOT NULL,
    serial_number VARCHAR(100) NOT NULL,
    status ENUM('available', 'reserved', 'checked_out') DEFAULT 'available',
    current_location VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
    UNIQUE KEY unique_serial (item_id, serial_number),
    INDEX idx_serial (serial_number)
);

-- Shows table
CREATE TABLE IF NOT EXISTS shows (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    designer VARCHAR(255),
    shop_lead VARCHAR(255),
    theatre_space_id INT,
    status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (theatre_space_id) REFERENCES theatre_spaces(id) ON DELETE SET NULL,
    INDEX idx_status (status)
);

-- Pull sheets table
CREATE TABLE IF NOT EXISTS pull_sheets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    show_id INT NOT NULL,
    barcode VARCHAR(100) UNIQUE NOT NULL,
    created_by VARCHAR(255),
    status ENUM('draft', 'finalized', 'picked', 'completed') DEFAULT 'draft',
    is_main BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    finalized_at TIMESTAMP NULL,
    picked_at TIMESTAMP NULL,
    FOREIGN KEY (show_id) REFERENCES shows(id) ON DELETE CASCADE,
    INDEX idx_barcode (barcode),
    INDEX idx_status (status)
);

-- Pull sheet items
CREATE TABLE IF NOT EXISTS pull_sheet_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    pull_sheet_id INT NOT NULL,
    item_id INT NOT NULL,
    quantity_needed INT NOT NULL,
    quantity_picked INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pull_sheet_id) REFERENCES pull_sheets(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
);

-- Change orders table
CREATE TABLE IF NOT EXISTS change_orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    show_id INT NOT NULL,
    barcode VARCHAR(100) UNIQUE NOT NULL,
    created_by VARCHAR(255),
    status ENUM('draft', 'finalized', 'picked', 'returned', 'completed') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    finalized_at TIMESTAMP NULL,
    picked_at TIMESTAMP NULL,
    returned_at TIMESTAMP NULL,
    FOREIGN KEY (show_id) REFERENCES shows(id) ON DELETE CASCADE,
    INDEX idx_barcode (barcode),
    INDEX idx_status (status)
);

-- Change order items
CREATE TABLE IF NOT EXISTS change_order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    change_order_id INT NOT NULL,
    item_id INT NOT NULL,
    action_type ENUM('add', 'remove') NOT NULL,
    quantity INT NOT NULL,
    quantity_processed INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (change_order_id) REFERENCES change_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
);

-- Item transactions/audit log
CREATE TABLE IF NOT EXISTS item_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    item_id INT NOT NULL,
    transaction_type ENUM('reserve', 'pick', 'return', 'adjust') NOT NULL,
    quantity INT NOT NULL,
    pull_sheet_id INT NULL,
    change_order_id INT NULL,
    show_id INT NULL,
    theatre_space_id INT NULL,
    performed_by VARCHAR(255),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
    FOREIGN KEY (pull_sheet_id) REFERENCES pull_sheets(id) ON DELETE SET NULL,
    FOREIGN KEY (change_order_id) REFERENCES change_orders(id) ON DELETE SET NULL,
    FOREIGN KEY (show_id) REFERENCES shows(id) ON DELETE SET NULL,
    FOREIGN KEY (theatre_space_id) REFERENCES theatre_spaces(id) ON DELETE SET NULL,
    INDEX idx_transaction_type (transaction_type),
    INDEX idx_created_at (created_at)
);

-- Item locations (current location tracking)
CREATE TABLE IF NOT EXISTS item_locations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    item_id INT NOT NULL,
    quantity INT NOT NULL,
    location_type ENUM('shop', 'theatre', 'reserved') NOT NULL,
    theatre_space_id INT NULL,
    show_id INT NULL,
    pull_sheet_id INT NULL,
    change_order_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
    FOREIGN KEY (theatre_space_id) REFERENCES theatre_spaces(id) ON DELETE SET NULL,
    FOREIGN KEY (show_id) REFERENCES shows(id) ON DELETE CASCADE,
    INDEX idx_location_type (location_type),
    INDEX idx_item_location (item_id, location_type)
);

-- Insert sample categories
INSERT INTO categories (name, description) VALUES 
('Microphones', 'Wireless and wired microphones'),
('Speakers', 'PA speakers and monitors'),
('Cables', 'Audio cables and adapters'),
('Mixers', 'Audio mixing consoles'),
('Effects', 'Signal processors and effects units'),
('Accessories', 'Stands, cases, and other accessories')
ON DUPLICATE KEY UPDATE name=name;

-- Insert sample theatre spaces
INSERT INTO theatre_spaces (name, description) VALUES 
('Main Stage', 'Main theatre performance space'),
('Black Box', 'Experimental black box theatre'),
('Studio Theatre', 'Small studio performance space'),
('Rehearsal Room', 'Rehearsal and practice space')
ON DUPLICATE KEY UPDATE name=name;
