-- Theatre Sound Shop Inventory Database Schema
-- MySQL Database Schema

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

-- Theatre spaces table
CREATE TABLE IF NOT EXISTS theatre_spaces (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Items table
CREATE TABLE IF NOT EXISTS items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    barcode VARCHAR(100) UNIQUE NOT NULL,
    category_id INT,
    tracking_type ENUM('quantity', 'serial') DEFAULT 'quantity',
    total_quantity INT DEFAULT 0,
    in_stock_quantity INT DEFAULT 0,
    serial_numbers TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Shows table
CREATE TABLE IF NOT EXISTS shows (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    shop_lead VARCHAR(255),
    designer VARCHAR(255),
    theatre_space_id INT,
    status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (theatre_space_id) REFERENCES theatre_spaces(id) ON DELETE SET NULL
);

-- Pull sheets table
CREATE TABLE IF NOT EXISTS pullsheets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    show_id INT NOT NULL,
    barcode VARCHAR(100) UNIQUE NOT NULL,
    created_by VARCHAR(255),
    status ENUM('draft', 'finalized', 'picked', 'completed') DEFAULT 'draft',
    finalized_at TIMESTAMP NULL,
    picked_at TIMESTAMP NULL,
    picked_by VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (show_id) REFERENCES shows(id) ON DELETE CASCADE
);

-- Pull sheet items table
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
    show_id INT NOT NULL,
    barcode VARCHAR(100) UNIQUE NOT NULL,
    created_by VARCHAR(255),
    status ENUM('draft', 'finalized', 'processed', 'completed') DEFAULT 'draft',
    finalized_at TIMESTAMP NULL,
    processed_at TIMESTAMP NULL,
    processed_by VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (show_id) REFERENCES shows(id) ON DELETE CASCADE
);

-- Change order items table
CREATE TABLE IF NOT EXISTS change_order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    change_order_id INT NOT NULL,
    item_id INT NOT NULL,
    quantity_change INT NOT NULL, -- positive for adding items, negative for returning
    quantity_processed INT DEFAULT 0,
    type ENUM('add', 'remove') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (change_order_id) REFERENCES change_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
);

-- Item allocations table (tracks where items are currently located)
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

-- Insert default settings
INSERT INTO settings (setting_key, setting_value) VALUES 
    ('app_name', 'CMFT Sound Shop Inventory'),
    ('logo_path', ''),
    ('dark_mode', '0')
ON DUPLICATE KEY UPDATE setting_key=setting_key;

-- Create indexes for better performance
CREATE INDEX idx_items_barcode ON items(barcode);
CREATE INDEX idx_pullsheets_barcode ON pullsheets(barcode);
CREATE INDEX idx_change_orders_barcode ON change_orders(barcode);
CREATE INDEX idx_pullsheets_show ON pullsheets(show_id);
CREATE INDEX idx_change_orders_show ON change_orders(show_id);
CREATE INDEX idx_item_allocations_item ON item_allocations(item_id);
CREATE INDEX idx_item_allocations_show ON item_allocations(show_id);
