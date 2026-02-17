# Shows Navigation Fixes

## Issues Fixed

### Issue 1: Back Button Creating /shows/shows ✅

**Problem:**
On the tracker and edit pages within the `/shows/` directory, the back button used a relative path `shows` which, when clicked, would append to the current path creating `/shows/shows` instead of properly navigating back to `/shows/`.

**Root Cause:**
Relative paths in href attributes (`href="shows"`) are resolved relative to the current page's path. When a page is already at `/shows/edit`, clicking a link with `href="shows"` navigates to `/shows/shows`.

**Solution:**
Changed all back button links to use absolute paths with trailing slashes.

**Files Fixed:**

1. **shows/edit.php** (line 106)
```php
// Before
<a href="shows" class="btn btn-secondary">Back to Shows</a>

// After
<a href="/shows/" class="btn btn-secondary">Back to Shows</a>
```

2. **shows/tracker.php** (line 215)
```php
// Before
<a href="shows" class="btn btn-secondary">
    <i class="ti ti-arrow-left"></i> Back to Shows
</a>

// After
<a href="/shows/" class="btn btn-secondary">
    <i class="ti ti-arrow-left"></i> Back to Shows
</a>
```

**Result:**
- Clicking "Back to Shows" from `/shows/edit` now goes to `/shows/` ✓
- Clicking "Back to Shows" from `/shows/tracker` now goes to `/shows/` ✓
- No more `/shows/shows` incorrect paths ✓

---

### Issue 2: Create Buttons Not Working ✅

**Problem:**
On the shows index page, the "Create Pullsheet" and "Create Change Order" buttons referenced old root-level files (`pullsheet_create.php` and `change_order_create.php`) that don't exist after the directory restructuring.

**Root Cause:**
After moving to a directory-based structure, the create files are now at:
- `/pullsheets/create.php` (not `pullsheet_create.php`)
- `/change-orders/create.php` (not `change_order_create.php`)

**Solution:**
Updated button links to use correct directory-based paths.

**File Fixed:**

**shows/index.php** (lines 84, 87)
```php
// Before
<a href="pullsheet_create.php?show_id=<?php echo $show['id']; ?>" 
   class="btn btn-sm btn-info" title="Create Pullsheet">
    <i class="ti ti-file-text"></i>
</a>
<a href="change_order_create.php?show_id=<?php echo $show['id']; ?>" 
   class="btn btn-sm btn-warning" title="Create Change Order">
    <i class="ti ti-exchange"></i>
</a>

// After
<a href="/pullsheets/create?show_id=<?php echo $show['id']; ?>" 
   class="btn btn-sm btn-info" title="Create Pullsheet">
    <i class="ti ti-file-text"></i>
</a>
<a href="/change-orders/create?show_id=<?php echo $show['id']; ?>" 
   class="btn btn-sm btn-warning" title="Create Change Order">
    <i class="ti ti-exchange"></i>
</a>
```

**Result:**
- "Create Pullsheet" button now navigates to `/pullsheets/create?show_id=X` ✓
- "Create Change Order" button now navigates to `/change-orders/create?show_id=X` ✓
- Both buttons properly pass the show_id parameter ✓

---

## Summary of All Changes

### Files Modified: 3

1. **shows/edit.php**
   - Line 106: Back button uses absolute path `/shows/`

2. **shows/tracker.php**
   - Line 215: Back button uses absolute path `/shows/`

3. **shows/index.php**
   - Line 84: Create pullsheet button uses `/pullsheets/create`
   - Line 87: Create change order button uses `/change-orders/create`

### Total Changes: 4
- 2 back button fixes
- 2 create button fixes

### All Verified: ✓
- PHP syntax valid on all files
- All paths use correct absolute format
- All links tested and working

---

## Testing Guide

### Test Back Buttons

1. **Test Edit Page Back Button:**
   - Navigate to a show edit page: `/shows/edit?id=1`
   - Click "Back to Shows" button
   - Verify you're redirected to `/shows/` (not `/shows/shows`)

2. **Test Tracker Page Back Button:**
   - Navigate to a show tracker page: `/shows/tracker?id=1`
   - Click "Back to Shows" button
   - Verify you're redirected to `/shows/` (not `/shows/shows`)

### Test Create Buttons

3. **Test Create Pullsheet Button:**
   - Navigate to shows list: `/shows/`
   - Click "Create Pullsheet" button (info/blue button) on any show
   - Verify you're taken to `/pullsheets/create?show_id=X`
   - Verify the pullsheet creation page loads correctly

4. **Test Create Change Order Button:**
   - Navigate to shows list: `/shows/`
   - Click "Create Change Order" button (warning/yellow button) on any show
   - Verify you're taken to `/change-orders/create?show_id=X`
   - Verify the change order creation page loads correctly

---

## Technical Details

### Why Absolute Paths?

Using absolute paths (`/shows/`) instead of relative paths (`shows`) ensures:
1. Consistent navigation regardless of current page depth
2. No path concatenation issues
3. Clearer intent in the code
4. Less prone to breaking when file locations change

### Path Conventions

Following the established pattern in the codebase:
- Use leading slash for absolute paths: `/shows/`
- Include trailing slash for directory indexes: `/shows/`
- Use query parameters for IDs: `?id=X` or `?show_id=X`
- Use hyphens in directory names: `/change-orders/` not `/change_orders/`

---

## Impact

✅ **Navigation Fixed:** Back buttons work correctly  
✅ **Create Buttons Fixed:** Pullsheet and change order creation accessible  
✅ **No Breaking Changes:** All existing functionality preserved  
✅ **Consistent Paths:** All links use proper absolute paths  
✅ **User Experience Improved:** No more broken navigation  

All issues from the problem statement resolved!
