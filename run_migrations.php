<?php
/**
 * Unified Database Migration Runner
 * This file runs all pending database migrations
 * Can be executed via CLI or web interface
 */

require_once 'includes/config.php';
require_once 'includes/functions.php';

// Check if running from CLI or web
$isCLI = php_sapi_name() === 'cli';

// If web request, require admin permission
if (!$isCLI) {
    session_start();
    requireRole('admin');
    
    // Return JSON for AJAX requests
    header('Content-Type: application/json');
}

try {
    $db = getDB();
    $output = [];
    $hasErrors = false;
    
    // Create migrations tracking table if it doesn't exist
    $output[] = "Checking migrations tracking table...";
    $db->query("
        CREATE TABLE IF NOT EXISTS migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration_name VARCHAR(255) UNIQUE NOT NULL,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_migration_name (migration_name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $output[] = "✓ Migrations tracking table ready";
    
    // Define all migrations in order
    $migrations = [
        [
            'name' => '001_add_password_reset_fields',
            'description' => 'Add password reset fields to users table',
            'sql' => [
                "ALTER TABLE users ADD COLUMN IF NOT EXISTS must_reset_password BOOLEAN DEFAULT FALSE",
                "ALTER TABLE users ADD COLUMN IF NOT EXISTS temp_password VARCHAR(255) NULL"
            ]
        ],
        [
            'name' => '002_add_approval_status',
            'description' => 'Add approval status for admin approvals',
            'sql' => [
                "ALTER TABLE pullsheets ADD COLUMN IF NOT EXISTS approval_status VARCHAR(20) NULL DEFAULT NULL",
                "ALTER TABLE change_orders ADD COLUMN IF NOT EXISTS approval_status VARCHAR(20) NULL DEFAULT NULL"
            ]
        ],
        [
            'name' => '003_add_notifications_title',
            'description' => 'Add title column to notifications table',
            'sql' => [
                "ALTER TABLE notifications ADD COLUMN IF NOT EXISTS title VARCHAR(255) NULL"
            ]
        ],
        [
            'name' => '004_ensure_app_url_setting',
            'description' => 'Ensure app_url setting exists in settings table',
            'callback' => function($db, &$output) {
                // Check if app_url setting exists
                $result = $db->fetchOne("SELECT setting_value FROM settings WHERE setting_key = 'app_url'");
                
                if ($result === null) {
                    // Insert default app_url setting
                    $db->query(
                        "INSERT INTO settings (setting_key, setting_value) VALUES ('app_url', '')",
                        []
                    );
                    $output[] = "  ✓ Created app_url setting (empty - configure in Settings)";
                } else {
                    $output[] = "  ✓ app_url setting already exists";
                }
            }
        ],
        [
            'name' => '005_create_login_banners_table',
            'description' => 'Create table for rotating login banner images',
            'sql' => [
                "CREATE TABLE IF NOT EXISTS login_banners (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    file_path VARCHAR(255) NOT NULL,
                    is_active BOOLEAN DEFAULT TRUE,
                    display_order INT DEFAULT 0,
                    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    uploaded_by INT,
                    INDEX idx_active (is_active),
                    INDEX idx_order (display_order),
                    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            ]
        ],
        [
            'name' => '006_add_banner_rotation_interval_setting',
            'description' => 'Add setting for login banner rotation interval in milliseconds',
            'callback' => function($db, &$output) {
                // Check if banner_rotation_interval setting exists
                $result = $db->fetchOne("SELECT setting_value FROM settings WHERE setting_key = 'banner_rotation_interval'");
                
                if ($result === null) {
                    // Insert default banner_rotation_interval setting (5000ms = 5 seconds)
                    $db->query(
                        "INSERT INTO settings (setting_key, setting_value) VALUES ('banner_rotation_interval', '5000')",
                        []
                    );
                    $output[] = "  ✓ Created banner_rotation_interval setting (default: 5000ms)";
                } else {
                    $output[] = "  ✓ banner_rotation_interval setting already exists";
                }
            }
        ],
        [
            'name' => '007_remove_hotkeys_table',
            'description' => 'Remove user_hotkeys table as hotkey feature is discontinued',
            'sql' => [
                "DROP TABLE IF EXISTS user_hotkeys"
            ]
        ],
        [
            'name' => '008_add_favicon_setting',
            'description' => 'Add setting for custom favicon',
            'callback' => function($db, &$output) {
                // Check if favicon_path setting exists
                $result = $db->fetchOne("SELECT setting_value FROM settings WHERE setting_key = 'favicon_path'");
                
                if ($result === null) {
                    // Insert default favicon_path setting (empty)
                    $db->query(
                        "INSERT INTO settings (setting_key, setting_value) VALUES ('favicon_path', '')",
                        []
                    );
                    $output[] = "  ✓ Created favicon_path setting";
                } else {
                    $output[] = "  ✓ favicon_path setting already exists";
                }
            }
        ],
        [
            'name' => '009_add_maintenance_mode_setting',
            'description' => 'Add maintenance mode setting',
            'callback' => function($db, &$output) {
                // Check if maintenance_mode setting exists
                $result = $db->fetchOne("SELECT name FROM settings WHERE name = 'maintenance_mode'");
                if (!$result) {
                    $db->query("INSERT INTO settings (name, value) VALUES ('maintenance_mode', '0')");
                    $output[] = "  ✓ Created maintenance_mode setting (disabled by default)";
                } else {
                    $output[] = "  ✓ maintenance_mode setting already exists";
                }
            }
        ]
    ];
    
    $output[] = "\nRunning migrations...";
    $migrationsRun = 0;
    $migrationsSkipped = 0;
    
    foreach ($migrations as $migration) {
        $migrationName = $migration['name'];
        
        // Check if migration has already been run
        $result = $db->fetchOne(
            "SELECT id FROM migrations WHERE migration_name = ?",
            [$migrationName]
        );
        
        if ($result) {
            $output[] = "⊘ Skipping {$migrationName} (already run)";
            $migrationsSkipped++;
            continue;
        }
        
        $output[] = "\n→ Running: {$migrationName}";
        $output[] = "  Description: {$migration['description']}";
        
        try {
            // Run SQL statements if defined
            if (isset($migration['sql'])) {
                foreach ($migration['sql'] as $sql) {
                    $db->query($sql);
                }
                $output[] = "  ✓ SQL executed successfully";
            }
            
            // Run callback if defined
            if (isset($migration['callback']) && is_callable($migration['callback'])) {
                $migration['callback']($db, $output);
            }
            
            // Mark migration as complete
            $db->query(
                "INSERT INTO migrations (migration_name) VALUES (?)",
                [$migrationName]
            );
            
            $output[] = "  ✓ Migration {$migrationName} completed";
            $migrationsRun++;
            
        } catch (Exception $e) {
            $output[] = "  ✗ Error: " . $e->getMessage();
            $hasErrors = true;
            // Continue with other migrations even if one fails
        }
    }
    
    $output[] = "\n" . str_repeat("=", 50);
    $output[] = "Migration Summary:";
    $output[] = "  - Migrations run: {$migrationsRun}";
    $output[] = "  - Migrations skipped: {$migrationsSkipped}";
    $output[] = "  - Total migrations: " . count($migrations);
    
    if ($hasErrors) {
        $output[] = "\n⚠ Some migrations had errors (see above)";
    } else {
        $output[] = "\n✓ All migrations completed successfully!";
    }
    
    // Output results
    if ($isCLI) {
        foreach ($output as $line) {
            echo $line . "\n";
        }
        exit($hasErrors ? 1 : 0);
    } else {
        echo json_encode([
            'success' => !$hasErrors,
            'output' => $output,
            'migrations_run' => $migrationsRun,
            'migrations_skipped' => $migrationsSkipped
        ]);
    }
    
} catch (Exception $e) {
    $errorMsg = "Fatal error: " . $e->getMessage();
    
    if ($isCLI) {
        echo $errorMsg . "\n";
        exit(1);
    } else {
        echo json_encode([
            'success' => false,
            'error' => $errorMsg,
            'output' => [$errorMsg]
        ]);
    }
}
