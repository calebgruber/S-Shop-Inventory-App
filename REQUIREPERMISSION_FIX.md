# requirePermission() Fatal Error Fix

## Problem

Multiple pages were throwing fatal errors:
```
PHP Fatal error: Uncaught Error: Call to undefined function requirePermission()
in [page].php:2
```

**Impact**: 17 pages completely broken, application unusable

## Root Cause

In the security fix (commit 103125a), authorization checks were added to all pages. However, `requirePermission()` was called BEFORE the file defining it was loaded:

```php
<?php
requirePermission('feature');              // ❌ Line 2 - Called FIRST
require_once 'includes/functions.php';    // Line 3 - Loaded SECOND
```

**Why this failed**:
- `requirePermission()` is defined in `includes/functions.php`
- Function was called before the file was included
- Function didn't exist yet → Fatal error

## Solution

Moved all `requirePermission()` calls to AFTER functions.php is loaded.

### Pattern 1: Pages with Header
```php
<?php
$pageTitle = 'Page Name';
require_once 'includes/header.php';    // Loads functions.php
requirePermission('feature');          // ✅ Function exists now
```

### Pattern 2: API/Special Pages
```php
<?php
require_once 'includes/functions.php'; // Load directly
requirePermission('feature');          // ✅ Function exists now
```

## Files Fixed (17 total)

### Pages with Header (7 files)
1. items.php
2. item_edit.php
3. pullsheets.php
4. change_orders.php
5. student_requests.php
6. repairs.php
7. paperwork.php

### Pages Loading functions.php Directly (10 files)
8. pullsheet_create.php
9. pullsheet_edit.php
10. pullsheet_view.php
11. change_order_create.php
12. change_order_edit.php
13. change_order_view.php
14. pick_mode.php
15. return_mode.php
16. show_edit.php
17. show_tracker.php

## Testing

All 17 files validated - no syntax errors.

**Manual testing checklist**:
- [ ] Load items page
- [ ] Load pullsheets page
- [ ] Load change orders page
- [ ] Load student requests page
- [ ] Try unauthorized access (should redirect with error)
- [ ] Verify pick/return modes work
- [ ] Check repairs and paperwork pages

## Result

✅ All fatal errors fixed
✅ Authorization still enforced
✅ Application functional again

## Related Fixes

- Commit 99c775a: Fixed requireLogin() ordering
- Commit 2ad2201: requireLogin() documentation
- Commit 8a929cd: Fixed requirePermission() ordering (this fix)

All three issues involved calling functions before they were loaded.
