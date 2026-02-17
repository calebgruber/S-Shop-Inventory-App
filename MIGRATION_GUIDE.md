# Application Restructure Migration Guide

## Completed ✅
1. Created all module directories
2. Copied all files to new locations
3. Fixed include paths (require_once '../includes/...')

## Next Steps Required

### 1. Update Navigation (includes/header.php) 🔴 CRITICAL

Current links need updating:
```php
// OLD → NEW
"items" → "items/"
"item_edit" → "items/edit"
"pullsheets" → "pullsheets/"
"pullsheet_create" → "pullsheets/create"
"change_orders" → "change-orders/"
"change_order_create" → "change-orders/create"
"user_management" → "users/"
"user_settings" → "users/settings"
"shows" → "shows/" (already done)
"show_create" → "shows/create" (already done)
"repairs" → "repairs/"
"reports" → "reports/"
"production_calendar" → "calendar/"
"admin_approvals" → "admin/approvals"
"settings" → "admin/settings"
"pick_mode" → "operations/pick"
"return_mode" → "operations/return"
"student_requests" → "student/"
```

### 2. Update Internal Links in Files

**Items Module:**
- items/index.php: Links to items/edit
- items/edit.php: Redirects and cancels

**Pullsheets Module:**
- pullsheets/index.php: Links to pullsheets/create, pullsheets/edit, pullsheets/view
- pullsheets/create.php: Redirects to pullsheets/
- pullsheets/edit.php: Links and redirects
- pullsheets/view.php: Links back

**Change Orders Module:**
- change-orders/index.php: Links to change-orders/create, change-orders/edit, change-orders/view
- change-orders/create.php: Redirects
- change-orders/edit.php: Links and redirects  
- change-orders/view.php: Links back

**Shows Module:**
- shows/index.php: Already updated
- shows/create.php: Already updated
- shows/edit.php: Already updated
- shows/tracker.php: Check links

**Other Modules:**
- All form actions need updating
- All redirect() calls need updating
- All cancel links need updating

### 3. Update Asset Paths

Files in subdirectories need:
```php
// CSS/JS
'assets/css/...' → '../assets/css/...'
'assets/js/...' → '../assets/js/...'

// Uploads
'uploads/...' → '../uploads/...'
```

### 4. Update .htaccess (if needed)

May need rules for:
- change-orders (hyphenated directory)
- Any other special routing

### 5. Test Each Module

- [ ] /items/ - List, create, edit
- [ ] /pullsheets/ - List, create, edit, view
- [ ] /change-orders/ - List, create, edit, view
- [ ] /users/ - Management, settings
- [ ] /shows/ - List, create, edit, tracker
- [ ] /repairs/ - Functionality
- [ ] /reports/ - Reports generation
- [ ] /calendar/ - Calendar view
- [ ] /auth/login - Login flow
- [ ] /admin/settings - Settings page
- [ ] /admin/approvals - Approvals
- [ ] /operations/pick - Pick mode
- [ ] /operations/return - Return mode
- [ ] /api/notifications - API calls
- [ ] /tools/* - Utilities

### 6. Update Keyboard Shortcuts (includes/footer.php)

All keyboard shortcuts need new paths.

### 7. Clean Up

After everything works:
```bash
# Remove old files from root
rm items.php item_edit.php item_barcodes.php
rm pullsheets.php pullsheet_create.php pullsheet_edit.php pullsheet_view.php
rm change_orders.php change_order_create.php change_order_edit.php change_order_view.php
# ... etc
```

## URL Examples

### Before
- http://example.com/items.php
- http://example.com/pullsheet_create
- http://example.com/change_order_edit?id=5

### After
- http://example.com/items/
- http://example.com/pullsheets/create
- http://example.com/change-orders/edit?id=5

## Benefits

- ✨ Cleaner URLs
- ✨ Better organization
- ✨ Easier to maintain
- ✨ Modern structure
- ✨ Scalable architecture

