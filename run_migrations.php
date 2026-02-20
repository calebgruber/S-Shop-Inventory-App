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
                $result = $db->fetchOne("SELECT setting_value FROM settings WHERE setting_key = 'maintenance_mode'");
                if ($result === null) {
                    $db->query("INSERT INTO settings (setting_key, setting_value) VALUES ('maintenance_mode', '0')");
                    $output[] = "  ✓ Created maintenance_mode setting (disabled by default)";
                } else {
                    $output[] = "  ✓ maintenance_mode setting already exists";
                }
            }
        ],
        [
            'name' => '010_make_pullsheet_show_unique',
            'description' => 'Enforce one shop order per show - add unique constraint',
            'callback' => function($db, &$output) {
                // First check for duplicate pullsheets
                $duplicates = $db->query("
                    SELECT show_id, COUNT(*) as count 
                    FROM pullsheets 
                    GROUP BY show_id 
                    HAVING count > 1
                ");
                
                if ($duplicates && $duplicates->num_rows > 0) {
                    $output[] = "  ⚠ Warning: Found shows with multiple pullsheets";
                    while ($dup = $duplicates->fetch_assoc()) {
                        $output[] = "    Show ID {$dup['show_id']} has {$dup['count']} pullsheets";
                        
                        // Keep the most recent pullsheet, delete others
                        $db->query("
                            DELETE FROM pullsheets 
                            WHERE show_id = ? 
                            AND id NOT IN (
                                SELECT * FROM (
                                    SELECT id FROM pullsheets 
                                    WHERE show_id = ? 
                                    ORDER BY created_at DESC 
                                    LIMIT 1
                                ) AS temp
                            )
                        ", [$dup['show_id'], $dup['show_id']]);
                        $output[] = "    Kept most recent pullsheet for show {$dup['show_id']}";
                    }
                }
                
                // Add unique constraint
                try {
                    $db->query("ALTER TABLE pullsheets ADD UNIQUE KEY unique_show_id (show_id)");
                    $output[] = "  ✓ Added unique constraint to pullsheets.show_id";
                } catch (Exception $e) {
                    if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                        $output[] = "  ✓ Unique constraint already exists";
                    } else {
                        throw $e;
                    }
                }
            }
        ],
        [
            'name' => '011_link_change_orders_to_pullsheets',
            'description' => 'Add pullsheet_id to change_orders and change_order_id to pullsheet_items',
            'callback' => function($db, &$output) {
                // Add pullsheet_id to change_orders
                $columns = $db->query("SHOW COLUMNS FROM change_orders LIKE 'pullsheet_id'");
                if (!$columns || $columns->num_rows == 0) {
                    $db->query("ALTER TABLE change_orders ADD COLUMN pullsheet_id INT NULL AFTER show_id");
                    $output[] = "  ✓ Added pullsheet_id column to change_orders";
                } else {
                    $output[] = "  ✓ pullsheet_id column already exists in change_orders";
                }
                
                // Add foreign key for pullsheet_id
                try {
                    $db->query("ALTER TABLE change_orders ADD CONSTRAINT fk_change_orders_pullsheet FOREIGN KEY (pullsheet_id) REFERENCES pullsheets(id) ON DELETE SET NULL");
                    $output[] = "  ✓ Added foreign key fk_change_orders_pullsheet";
                } catch (Exception $e) {
                    if (strpos($e->getMessage(), 'Duplicate key name') !== false || strpos($e->getMessage(), 'already exists') !== false) {
                        $output[] = "  ✓ Foreign key fk_change_orders_pullsheet already exists";
                    } else {
                        $output[] = "  ⚠ Foreign key warning (may already exist): " . substr($e->getMessage(), 0, 100);
                    }
                }
                
                // Add change_order_id to pullsheet_items
                $columns = $db->query("SHOW COLUMNS FROM pullsheet_items LIKE 'change_order_id'");
                if (!$columns || $columns->num_rows == 0) {
                    $db->query("ALTER TABLE pullsheet_items ADD COLUMN change_order_id INT NULL AFTER pullsheet_id");
                    $output[] = "  ✓ Added change_order_id column to pullsheet_items";
                } else {
                    $output[] = "  ✓ change_order_id column already exists in pullsheet_items";
                }
                
                // Add foreign key for change_order_id
                try {
                    $db->query("ALTER TABLE pullsheet_items ADD CONSTRAINT fk_pullsheet_items_change_order FOREIGN KEY (change_order_id) REFERENCES change_orders(id) ON DELETE SET NULL");
                    $output[] = "  ✓ Added foreign key fk_pullsheet_items_change_order";
                } catch (Exception $e) {
                    if (strpos($e->getMessage(), 'Duplicate key name') !== false || strpos($e->getMessage(), 'already exists') !== false) {
                        $output[] = "  ✓ Foreign key fk_pullsheet_items_change_order already exists";
                    } else {
                        $output[] = "  ⚠ Foreign key warning (may already exist): " . substr($e->getMessage(), 0, 100);
                    }
                }
                
                // Add indexes
                try {
                    $db->query("CREATE INDEX idx_change_orders_pullsheet ON change_orders(pullsheet_id)");
                    $output[] = "  ✓ Created index idx_change_orders_pullsheet";
                } catch (Exception $e) {
                    if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                        $output[] = "  ✓ Index idx_change_orders_pullsheet already exists";
                    } else {
                        $output[] = "  ⚠ Index warning: " . substr($e->getMessage(), 0, 100);
                    }
                }
                
                try {
                    $db->query("CREATE INDEX idx_pullsheet_items_change_order ON pullsheet_items(change_order_id)");
                    $output[] = "  ✓ Created index idx_pullsheet_items_change_order";
                } catch (Exception $e) {
                    if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                        $output[] = "  ✓ Index idx_pullsheet_items_change_order already exists";
                    } else {
                        $output[] = "  ⚠ Index warning: " . substr($e->getMessage(), 0, 100);
                    }
                }
            }
        ],
        [
            'name' => '012_add_partial_return_fields',
            'description' => 'Add fields for partial return system',
            'callback' => function($db, &$output) {
                // Add is_partial_return column
                $columns = $db->query("SHOW COLUMNS FROM change_orders LIKE 'is_partial_return'");
                if (!$columns || $columns->num_rows == 0) {
                    $db->query("ALTER TABLE change_orders ADD COLUMN is_partial_return BOOLEAN DEFAULT FALSE AFTER pullsheet_id");
                    $output[] = "  ✓ Added is_partial_return column to change_orders";
                } else {
                    $output[] = "  ✓ is_partial_return column already exists in change_orders";
                }
                
                // Add source_pullsheet_id column
                $columns = $db->query("SHOW COLUMNS FROM change_orders LIKE 'source_pullsheet_id'");
                if (!$columns || $columns->num_rows == 0) {
                    $db->query("ALTER TABLE change_orders ADD COLUMN source_pullsheet_id INT NULL AFTER is_partial_return");
                    $output[] = "  ✓ Added source_pullsheet_id column to change_orders";
                } else {
                    $output[] = "  ✓ source_pullsheet_id column already exists in change_orders";
                }
                
                // Add foreign key for source_pullsheet_id
                try {
                    $db->query("ALTER TABLE change_orders ADD CONSTRAINT fk_change_orders_source_pullsheet FOREIGN KEY (source_pullsheet_id) REFERENCES pullsheets(id) ON DELETE SET NULL");
                    $output[] = "  ✓ Added foreign key fk_change_orders_source_pullsheet";
                } catch (Exception $e) {
                    if (strpos($e->getMessage(), 'Duplicate key name') !== false || strpos($e->getMessage(), 'already exists') !== false) {
                        $output[] = "  ✓ Foreign key fk_change_orders_source_pullsheet already exists";
                    } else {
                        $output[] = "  ⚠ Foreign key warning (may already exist): " . substr($e->getMessage(), 0, 100);
                    }
                }
                
                // Add index for is_partial_return
                try {
                    $db->query("CREATE INDEX idx_change_orders_partial_return ON change_orders(is_partial_return)");
                    $output[] = "  ✓ Created index idx_change_orders_partial_return";
                } catch (Exception $e) {
                    if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                        $output[] = "  ✓ Index idx_change_orders_partial_return already exists";
                    } else {
                        $output[] = "  ⚠ Index warning: " . substr($e->getMessage(), 0, 100);
                    }
                }
                
                // Add index for source_pullsheet_id
                try {
                    $db->query("CREATE INDEX idx_change_orders_source_pullsheet ON change_orders(source_pullsheet_id)");
                    $output[] = "  ✓ Created index idx_change_orders_source_pullsheet";
                } catch (Exception $e) {
                    if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                        $output[] = "  ✓ Index idx_change_orders_source_pullsheet already exists";
                    } else {
                        $output[] = "  ⚠ Index warning: " . substr($e->getMessage(), 0, 100);
                    }
                }
            }
        ],
        [
            'name' => '013_create_pdf_templates_system',
            'description' => 'Create PDF template system with visual editor support',
            'sql' => [
                "CREATE TABLE IF NOT EXISTS pdf_templates (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    description TEXT NULL,
                    template_data LONGTEXT NOT NULL COMMENT 'JSON template definition',
                    preview_image VARCHAR(255) NULL,
                    is_active BOOLEAN DEFAULT TRUE,
                    created_by INT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
                    INDEX idx_name (name),
                    INDEX idx_active (is_active)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
                "CREATE TABLE IF NOT EXISTS pdf_template_assignments (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    template_id INT NOT NULL,
                    document_type ENUM(
                        'pullsheet_created',
                        'change_order_created',
                        'order_picked',
                        'order_returned',
                        'partial_return',
                        'out_of_stock',
                        'repair_request',
                        'student_request'
                    ) NOT NULL,
                    is_default BOOLEAN DEFAULT TRUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (template_id) REFERENCES pdf_templates(id) ON DELETE CASCADE,
                    UNIQUE KEY unique_default_per_type (document_type, is_default),
                    INDEX idx_document_type (document_type)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            ]
        ],
        [
            'name' => '014_fix_notifications_type_column',
            'description' => 'Increase notifications type column size to prevent truncation errors',
            'sql' => [
                "ALTER TABLE notifications MODIFY COLUMN type VARCHAR(100) NOT NULL",
                "CREATE INDEX IF NOT EXISTS idx_notifications_type ON notifications(type)",
                "CREATE INDEX IF NOT EXISTS idx_notifications_user_read ON notifications(user_id, is_read)"
            ]
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
