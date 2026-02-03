-- Migration to add assigned_by column to user_show_assignments table
-- Run this if you already created the user_show_assignments table without the assigned_by column

-- Add the assigned_by column if it doesn't exist
ALTER TABLE `user_show_assignments` 
ADD COLUMN IF NOT EXISTS `assigned_by` int(11) DEFAULT NULL AFTER `show_id`,
ADD INDEX IF NOT EXISTS `assigned_by` (`assigned_by`);

-- Add foreign key constraint if it doesn't exist
-- Note: MySQL doesn't support IF NOT EXISTS for foreign keys, so we check first
SET @fk_exists = (
    SELECT COUNT(*) 
    FROM information_schema.TABLE_CONSTRAINTS 
    WHERE CONSTRAINT_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'user_show_assignments' 
    AND CONSTRAINT_NAME = 'user_show_assignments_ibfk_3'
);

SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE `user_show_assignments` ADD CONSTRAINT `user_show_assignments_ibfk_3` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL',
    'SELECT "Foreign key already exists" AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
