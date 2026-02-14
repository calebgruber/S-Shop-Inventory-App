<?php
/**
 * Database Migration: Add approval_status field for admin approvals
 * Run this script once to add the approval_status columns
 */

require_once 'includes/config.php';
require_once 'includes/functions.php';

try {
    $db = getDB();
    
    echo "Adding approval_status to pullsheets table...\n";
    $db->query("ALTER TABLE pullsheets ADD COLUMN approval_status VARCHAR(20) NULL DEFAULT NULL");
    echo "✓ Added approval_status to pullsheets\n";
    
    echo "Adding approval_status to change_orders table...\n";
    $db->query("ALTER TABLE change_orders ADD COLUMN approval_status VARCHAR(20) NULL DEFAULT NULL");
    echo "✓ Added approval_status to change_orders\n";
    
    echo "\nMigration completed successfully!\n";
    echo "Production audio operations will now require admin approval.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
