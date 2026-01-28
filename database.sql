-- S-Shop Inventory System Database Schema
-- Complete schema for theatre sound shop inventory and workflow management

CREATE DATABASE IF NOT EXISTS sshop_inventory CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sshop_inventory;

-- Categories table
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Subcategories table
CREATE TABLE IF NOT EXISTS `subcategories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `subcategories_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Theatre spaces table
CREATE TABLE IF NOT EXISTS `theatre_spaces` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Items table
CREATE TABLE IF NOT EXISTS `items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `barcode` varchar(100) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `subcategory_id` int(11) DEFAULT NULL,
  `tracking_type` enum('quantity','serial') DEFAULT 'quantity',
  `total_quantity` int(11) DEFAULT 0,
  `in_stock_quantity` int(11) DEFAULT 0,
  `serial_numbers` text DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `photo_path` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `barcode` (`barcode`),
  KEY `category_id` (`category_id`),
  KEY `subcategory_id` (`subcategory_id`),
  CONSTRAINT `items_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `items_ibfk_2` FOREIGN KEY (`subcategory_id`) REFERENCES `subcategories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Users table
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `role` enum('admin','designer','student','production_audio') DEFAULT 'student',
  `avatar_seed` varchar(50) DEFAULT NULL,
  `hotkeys` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Shows table
CREATE TABLE IF NOT EXISTS `shows` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `theatre_space_id` int(11) DEFAULT NULL,
  `designer_id` int(11) DEFAULT NULL,
  `production_audio_id` int(11) DEFAULT NULL,
  `color` varchar(7) DEFAULT '#3b82f6',
  `archived` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `theatre_space_id` (`theatre_space_id`),
  KEY `designer_id` (`designer_id`),
  KEY `production_audio_id` (`production_audio_id`),
  CONSTRAINT `shows_ibfk_1` FOREIGN KEY (`theatre_space_id`) REFERENCES `theatre_spaces` (`id`) ON DELETE SET NULL,
  CONSTRAINT `shows_ibfk_2` FOREIGN KEY (`designer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `shows_ibfk_3` FOREIGN KEY (`production_audio_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pull sheets (shop orders) table
CREATE TABLE IF NOT EXISTS `pullsheets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `show_id` int(11) NOT NULL,
  `barcode` varchar(100) NOT NULL,
  `created_by` int(11) NOT NULL,
  `status` enum('draft','pending_approval','approved','picked','returned','cancelled') DEFAULT 'draft',
  `approved_by` int(11) DEFAULT NULL,
  `picked_by` int(11) DEFAULT NULL,
  `returned_by` int(11) DEFAULT NULL,
  `signature_data` text DEFAULT NULL,
  `signature_name` varchar(100) DEFAULT NULL,
  `pdf_path` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `barcode` (`barcode`),
  KEY `show_id` (`show_id`),
  KEY `created_by` (`created_by`),
  KEY `approved_by` (`approved_by`),
  KEY `picked_by` (`picked_by`),
  KEY `returned_by` (`returned_by`),
  CONSTRAINT `pullsheets_ibfk_1` FOREIGN KEY (`show_id`) REFERENCES `shows` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pullsheets_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `pullsheets_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`),
  CONSTRAINT `pullsheets_ibfk_4` FOREIGN KEY (`picked_by`) REFERENCES `users` (`id`),
  CONSTRAINT `pullsheets_ibfk_5` FOREIGN KEY (`returned_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pull sheet items table
CREATE TABLE IF NOT EXISTS `pullsheet_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pullsheet_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity_needed` int(11) NOT NULL,
  `quantity_picked` int(11) DEFAULT 0,
  `serial_numbers_picked` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `pullsheet_id` (`pullsheet_id`),
  KEY `item_id` (`item_id`),
  CONSTRAINT `pullsheet_items_ibfk_1` FOREIGN KEY (`pullsheet_id`) REFERENCES `pullsheets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pullsheet_items_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Change orders table
CREATE TABLE IF NOT EXISTS `change_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `show_id` int(11) NOT NULL,
  `barcode` varchar(100) NOT NULL,
  `created_by` int(11) NOT NULL,
  `status` enum('draft','pending_approval','approved','picked','returned','cancelled') DEFAULT 'draft',
  `approved_by` int(11) DEFAULT NULL,
  `picked_by` int(11) DEFAULT NULL,
  `returned_by` int(11) DEFAULT NULL,
  `signature_data` text DEFAULT NULL,
  `signature_name` varchar(100) DEFAULT NULL,
  `pdf_path` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `barcode` (`barcode`),
  KEY `show_id` (`show_id`),
  KEY `created_by` (`created_by`),
  KEY `approved_by` (`approved_by`),
  KEY `picked_by` (`picked_by`),
  KEY `returned_by` (`returned_by`),
  CONSTRAINT `change_orders_ibfk_1` FOREIGN KEY (`show_id`) REFERENCES `shows` (`id`) ON DELETE CASCADE,
  CONSTRAINT `change_orders_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `change_orders_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`),
  CONSTRAINT `change_orders_ibfk_4` FOREIGN KEY (`picked_by`) REFERENCES `users` (`id`),
  CONSTRAINT `change_orders_ibfk_5` FOREIGN KEY (`returned_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Change order items table
CREATE TABLE IF NOT EXISTS `change_order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `change_order_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `action` enum('add','remove') NOT NULL,
  `quantity` int(11) NOT NULL,
  `quantity_picked` int(11) DEFAULT 0,
  `serial_numbers_picked` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `change_order_id` (`change_order_id`),
  KEY `item_id` (`item_id`),
  CONSTRAINT `change_order_items_ibfk_1` FOREIGN KEY (`change_order_id`) REFERENCES `change_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `change_order_items_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Student equipment requests table
CREATE TABLE IF NOT EXISTS `student_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','approved','denied','fulfilled') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `denial_reason` text DEFAULT NULL,
  `pullsheet_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `approved_by` (`approved_by`),
  KEY `pullsheet_id` (`pullsheet_id`),
  CONSTRAINT `student_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_requests_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`),
  CONSTRAINT `student_requests_ibfk_3` FOREIGN KEY (`pullsheet_id`) REFERENCES `pullsheets` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Student request items table
CREATE TABLE IF NOT EXISTS `student_request_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `request_id` (`request_id`),
  KEY `item_id` (`item_id`),
  CONSTRAINT `student_request_items_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `student_requests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_request_items_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Repairs table
CREATE TABLE IF NOT EXISTS `repairs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `description` text NOT NULL,
  `status` enum('pending','in_progress','completed','cancelled') DEFAULT 'pending',
  `assigned_tech` int(11) DEFAULT NULL,
  `completed_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `item_id` (`item_id`),
  KEY `assigned_tech` (`assigned_tech`),
  CONSTRAINT `repairs_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `repairs_ibfk_2` FOREIGN KEY (`assigned_tech`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Calendar events table
CREATE TABLE IF NOT EXISTS `calendar_events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `show_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `all_day` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `show_id` (`show_id`),
  CONSTRAINT `calendar_events_ibfk_1` FOREIGN KEY (`show_id`) REFERENCES `shows` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications table
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` enum('order_approved','order_picked','change_order_picked','request_approved','request_denied') NOT NULL,
  `message` text NOT NULL,
  `related_id` int(11) DEFAULT NULL,
  `read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Settings table
CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default settings
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('site_logo', NULL),
('login_cover', NULL),
('site_name', 'S-Shop Inventory System'),
('default_theme', 'light');

-- Insert default admin user (password: admin123)
INSERT INTO `users` (`username`, `email`, `password`, `first_name`, `last_name`, `role`, `avatar_seed`) VALUES
('admin', 'admin@sshop.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User', 'admin', 'admin');

-- Insert sample theatre spaces
INSERT INTO `theatre_spaces` (`name`, `description`) VALUES
('Main Stage', 'Primary performance space'),
('Black Box', 'Flexible experimental theatre'),
('Studio Theatre', 'Intimate performance space');

-- Insert sample categories
INSERT INTO `categories` (`name`, `description`) VALUES
('Microphones', 'All microphone types'),
('Cables', 'Audio cables and adapters'),
('Speakers', 'Loudspeakers and monitors'),
('Wireless Systems', 'Wireless microphone systems'),
('Headphones', 'Headphones and earpieces'),
('Accessories', 'Miscellaneous audio accessories');
