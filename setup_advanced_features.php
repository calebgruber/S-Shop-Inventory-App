<?php
/**
 * Advanced Features Setup & Test Script
 * Run this after applying the migration to verify everything works
 */

require_once 'includes/functions.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db = getDB();

echo "=== Advanced Features Setup & Test ===\n\n";

// Check if tables exist
echo "1. Checking database tables...\n";

$tables = ['user_hotkeys', 'show_events', 'notifications'];
foreach ($tables as $table) {
    $result = $db->fetchOne("SHOW TABLES LIKE ?", [$table]);
    if ($result) {
        echo "   ✓ Table '$table' exists\n";
    } else {
        echo "   ✗ Table '$table' NOT FOUND - run migration first!\n";
        echo "\nMigration required: mysql -u [user] -p [database] < database/advanced_features_migration.sql\n";
        return;
    }
}

// Check shows.calendar_color column
$result = $db->fetchOne("SHOW COLUMNS FROM shows LIKE 'calendar_color'");
if ($result) {
    echo "   ✓ Column 'shows.calendar_color' exists\n";
} else {
    echo "   ✗ Column 'shows.calendar_color' NOT FOUND - run migration first!\n";
    echo "\nMigration required: mysql -u [user] -p [database] < database/advanced_features_migration.sql\n";
    return;
}

echo "\n2. Checking for sample data...\n";

// Check for users
$userCount = $db->fetchOne("SELECT COUNT(*) as count FROM users");
echo "   - Users in database: " . $userCount['count'] . "\n";

// Check for shows
$showCount = $db->fetchOne("SELECT COUNT(*) as count FROM shows");
echo "   - Shows in database: " . $showCount['count'] . "\n";

echo "\n3. Testing notification functions...\n";

// Get first user for testing
$testUser = $db->fetchOne("SELECT id, email FROM users WHERE role = 'admin' LIMIT 1");

if ($testUser) {
    echo "   - Testing with user: " . $testUser['email'] . "\n";
    
    // Create test notification
    createNotification(
        $testUser['id'],
        'general',
        'Test notification - Advanced features installed successfully!',
        'index.php'
    );
    echo "   ✓ Created test notification\n";
    
    // Check unread count
    $unreadCount = getUnreadNotificationCount($testUser['id']);
    echo "   ✓ Unread notifications: " . $unreadCount . "\n";
    
    // Get notifications
    $notifications = getUserNotifications($testUser['id'], true);
    echo "   ✓ Retrieved " . count($notifications) . " unread notification(s)\n";
} else {
    echo "   ⚠ No admin user found - create a user first\n";
}

echo "\n4. Testing hotkey functions...\n";

if ($testUser) {
    // Set default hotkeys for test user
    $defaultHotkeys = [
        'quick_lookup' => 'Ctrl+K',
        'pick_mode' => 'Ctrl+Shift+P',
        'inventory' => 'Ctrl+I'
    ];
    
    foreach ($defaultHotkeys as $action => $hotkey) {
        try {
            $db->query(
                "INSERT IGNORE INTO user_hotkeys (user_id, action, hotkey) VALUES (?, ?, ?)",
                [$testUser['id'], $action, $hotkey]
            );
        } catch (Exception $e) {
            // Ignore duplicates
        }
    }
    
    $userHotkeys = getUserHotkeys($testUser['id']);
    echo "   ✓ Set " . count($userHotkeys) . " default hotkey(s)\n";
    foreach ($userHotkeys as $action => $hotkey) {
        echo "     - $action: $hotkey\n";
    }
} else {
    echo "   ⚠ Skipping - no user found\n";
}

echo "\n5. Checking show colors...\n";

$shows = $db->fetchAll("SELECT id, name, calendar_color FROM shows LIMIT 5");
if (empty($shows)) {
    echo "   ⚠ No shows found - create a show to test calendar\n";
} else {
    foreach ($shows as $show) {
        if (empty($show['calendar_color']) || $show['calendar_color'] == '') {
            // Set default color
            $db->query("UPDATE shows SET calendar_color = '#206bc4' WHERE id = ?", [$show['id']]);
            echo "   ✓ Set default color for: " . $show['name'] . "\n";
        } else {
            echo "   ✓ Show '" . $show['name'] . "' has color: " . $show['calendar_color'] . "\n";
        }
    }
}

echo "\n=== Setup Complete ===\n";
echo "\nNext steps:\n";
echo "1. Visit user_settings.php to configure hotkeys\n";
echo "2. Visit production_calendar.php to create events\n";
echo "3. Check the notifications bell icon in the header\n";
echo "\nAll features are ready to use!\n";
