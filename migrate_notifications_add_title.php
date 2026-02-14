<?php
/**
 * Migration Script: Add 'title' column to notifications table
 * 
 * This script adds the missing 'title' column to the notifications table
 * if it doesn't already exist.
 * 
 * Run this script once via CLI: php migrate_notifications_add_title.php
 */

require_once __DIR__ . '/includes/functions.php';

try {
    $db = getDB();
    
    // Check if title column exists
    $columns = $db->fetchAll("SHOW COLUMNS FROM notifications LIKE 'title'");
    
    if (empty($columns)) {
        echo "Adding 'title' column to notifications table...\n";
        $db->query("ALTER TABLE notifications ADD COLUMN title VARCHAR(255) NOT NULL DEFAULT '' AFTER type");
        echo "✓ Successfully added 'title' column to notifications table\n";
    } else {
        echo "✓ 'title' column already exists in notifications table\n";
    }
    
    echo "\nMigration completed successfully!\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
