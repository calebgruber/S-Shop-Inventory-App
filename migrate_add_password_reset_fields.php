<?php
/**
 * Database Migration: Add password reset fields to users table
 * Run this once to add temp_password and must_reset_password fields
 */

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

try {
    $db = getDB();
    
    echo "Starting migration...\n";
    
    // Check if columns already exist
    $columns = $db->fetchAll("SHOW COLUMNS FROM users LIKE 'must_reset_password'");
    
    if (empty($columns)) {
        echo "Adding must_reset_password column...\n";
        $db->query("ALTER TABLE users ADD COLUMN must_reset_password BOOLEAN DEFAULT FALSE AFTER is_deleted");
        echo "✓ must_reset_password column added\n";
    } else {
        echo "✓ must_reset_password column already exists\n";
    }
    
    $columns = $db->fetchAll("SHOW COLUMNS FROM users LIKE 'temp_password'");
    
    if (empty($columns)) {
        echo "Adding temp_password column...\n";
        $db->query("ALTER TABLE users ADD COLUMN temp_password VARCHAR(255) NULL AFTER must_reset_password");
        echo "✓ temp_password column added\n";
    } else {
        echo "✓ temp_password column already exists\n";
    }
    
    echo "\nMigration completed successfully!\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
