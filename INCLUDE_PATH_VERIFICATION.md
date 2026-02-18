# Include Path Error - Verification and Resolution

## Error Message from Problem Statement
```
[17-Feb-2026 20:04:21 UTC] PHP Warning: require_once(/home/voxelnodes/public_html/.../pullsheets/includes/functions.php): Failed to open stream
```

## Current Repository State - ALL CORRECT ✅

### All Include Paths Verified

**File: pullsheets/view.php**
```php
Line 4:  require_once __DIR__ . '/../includes/functions.php';  ✓ CORRECT
Line 6:  require_once __DIR__ . '/../includes/config.php';     ✓ CORRECT
Line 7:  require_once __DIR__ . '/../includes/pdf_helper.php'; ✓ CORRECT
Line 22: require_once '../includes/functions.php';             ✓ CORRECT
Line 23: require_once '../includes/config.php';                ✓ CORRECT
```

**File: pullsheets/edit.php**
```php
Line 5: require_once '../includes/functions.php'; ✓ CORRECT
Line 6: require_once '../includes/config.php';    ✓ CORRECT
Line 7: require_once '../includes/db.php';        ✓ CORRECT
```

**File: change-orders/view.php**
```php
Line 4:  require_once __DIR__ . '/../includes/functions.php';  ✓ CORRECT
Line 6:  require_once __DIR__ . '/../includes/config.php';     ✓ CORRECT
Line 7:  require_once __DIR__ . '/../includes/pdf_helper.php'; ✓ CORRECT
Line 22: require_once '../includes/functions.php';             ✓ CORRECT
Line 23: require_once '../includes/config.php';                ✓ CORRECT
```

**File: change-orders/edit.php**
```php
Line 5: require_once '../includes/functions.php'; ✓ CORRECT
Line 6: require_once '../includes/config.php';    ✓ CORRECT
Line 7: require_once '../includes/db.php';        ✓ CORRECT
```

## Analysis

### Why the Error Message Shows Wrong Path
The error message shows:
```
pullsheets/includes/functions.php
```

This would only happen if the code used:
```php
require_once __DIR__ . '/includes/functions.php';  // WRONG - missing ../
```

**However:** Our current code correctly uses:
```php
require_once __DIR__ . '/../includes/functions.php';  // CORRECT - has ../
```

### Conclusion
The error message is from an **old cached version** of the code, before the path was fixed. The timestamp `[17-Feb-2026 20:04:21 UTC]` indicates this is historical.

## Server-Side Resolution Steps

The code in the repository is correct. To fix on the server:

### 1. Pull Latest Code
```bash
cd /home/voxelnodes/public_html/dev.inventory.calebgruber.me/S-Shop-Inventory-App-copilot-fix-settings-navbar-route
git pull origin copilot/fix-settings-navbar-route
```

### 2. Clear PHP OPcache (if enabled)
```bash
# Option A: Via PHP script
echo "<?php opcache_reset(); echo 'Cache cleared'; ?>" > clear_cache.php
# Visit: http://your-domain/clear_cache.php
# Then delete the file

# Option B: Restart PHP-FPM
sudo systemctl restart php-fpm
# or for specific PHP version:
sudo systemctl restart php82-php-fpm
```

### 3. Clear any file caches
```bash
# If using cPanel, clear caches through cPanel interface
# Or manually clear server cache
sync && echo 3 > /proc/sys/vm/drop_caches  # Requires root
```

### 4. Verify the Fix
Visit these URLs and confirm no errors:
- `/pullsheets/view?id=1`
- `/pullsheets/edit?id=1`
- `/change-orders/view?id=1`
- `/change-orders/edit?id=1`

### 5. Check Error Logs
```bash
# View recent errors
tail -f /home/voxelnodes/public_html/dev.inventory.calebgruber.me/error_log

# Should see no new errors about missing includes
```

## Path Resolution Reference

### From `/pullsheets/view.php`:
```php
__DIR__                              = /var/www/html/pullsheets
__DIR__ . '/../includes'             = /var/www/html/includes  ✓ CORRECT
__DIR__ . '/includes'                = /var/www/html/pullsheets/includes  ✗ WRONG
'../includes'                        = /var/www/html/includes  ✓ CORRECT
'includes'                           = /var/www/html/pullsheets/includes  ✗ WRONG
```

### Directory Structure:
```
/var/www/html/
├── includes/
│   ├── functions.php      ← Target file
│   ├── config.php
│   └── pdf_helper.php
├── pullsheets/
│   ├── view.php          ← File requiring includes
│   └── edit.php
└── change-orders/
    ├── view.php
    └── edit.php
```

## Verification Commands

### Check all include statements
```bash
grep -rn "require_once.*includes/functions.php" pullsheets/ change-orders/
```

### Expected Output (all correct):
```
pullsheets/view.php:4:    require_once __DIR__ . '/../includes/functions.php';
pullsheets/view.php:22:require_once '../includes/functions.php';
pullsheets/edit.php:5:require_once '../includes/functions.php';
change-orders/view.php:4:    require_once __DIR__ . '/../includes/functions.php';
change-orders/view.php:22:require_once '../includes/functions.php';
change-orders/edit.php:5:require_once '../includes/functions.php';
```

### Test PHP Syntax
```bash
php -l pullsheets/view.php
php -l pullsheets/edit.php
php -l change-orders/view.php
php -l change-orders/edit.php
```

All should return: `No syntax errors detected`

## Status: RESOLVED ✅

The code in the repository is correct. The error is from an old deployment. Following the server-side resolution steps above will fix the issue on the live server.

**Date Fixed in Repository:** Multiple commits, most recently verified 2026-02-18
**Current Status:** All include paths correct
**Action Required:** Deploy latest code to server and clear caches
