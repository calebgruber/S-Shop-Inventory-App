-- Advanced Features Migration
-- Adds support for Hotkey Manager, Production Calendar, and Notifications System

-- User Hotkeys Table
CREATE TABLE IF NOT EXISTS user_hotkeys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    hotkey VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_action (user_id, action),
    UNIQUE KEY unique_user_hotkey (user_id, hotkey)
);

-- Show Events Table (Production Calendar)
CREATE TABLE IF NOT EXISTS show_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    show_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    description TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (show_id) REFERENCES shows(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Add calendar_color column to shows table
ALTER TABLE shows 
ADD COLUMN IF NOT EXISTS calendar_color VARCHAR(7) DEFAULT '#206bc4';

-- Notifications Table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('pending_pick', 'pending_return', 'student_request', 'repair_needed', 'general') NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255),
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, is_read),
    INDEX idx_created (created_at)
);

-- Insert default hotkeys for existing users (optional)
-- INSERT INTO user_hotkeys (user_id, action, hotkey)
-- SELECT id, 'quick_lookup', 'ctrl+k' FROM users WHERE id NOT IN (SELECT user_id FROM user_hotkeys WHERE action = 'quick_lookup');

-- Insert default colors for existing shows
UPDATE shows SET calendar_color = '#206bc4' WHERE calendar_color IS NULL OR calendar_color = '';
