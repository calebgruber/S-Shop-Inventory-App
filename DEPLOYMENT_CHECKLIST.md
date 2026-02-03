# Deployment Checklist

## Pre-Deployment

### 1. Database Setup
- [ ] Import `database/schema_complete.sql` OR run migrations in order
- [ ] Verify all 21 tables created successfully
- [ ] Check default admin user exists (admin@example.com)
- [ ] **IMPORTANT**: Change default admin password immediately!

### 2. Configuration
- [ ] Update `includes/config.php` with production database credentials
- [ ] Set correct timezone in config.php
- [ ] Verify upload directories exist and are writable
- [ ] Check error logging is configured correctly

### 3. Web Server
- [ ] Apache mod_rewrite enabled (`sudo a2enmod rewrite`)
- [ ] .htaccess file in place
- [ ] Test clean URLs working (no .php extension)
- [ ] Verify 301 redirects from .php URLs work

### 4. File Permissions
```bash
chmod 755 uploads/
chmod 755 assets/
chmod 755 logs/
chmod 644 .htaccess
chmod 644 includes/config.php
```

### 5. Sound Files (Optional but Recommended)
- [ ] Replace placeholder MP3s in `assets/sounds/` with real audio
- [ ] Test each sound file plays correctly
- [ ] Recommended: Download from freesound.org or similar

## Post-Deployment Testing

### Critical Path Tests

#### 1. Authentication
- [ ] Login with default admin credentials works
- [ ] Change admin password
- [ ] Logout works
- [ ] Login with new password works
- [ ] Failed login shows error message

#### 2. User Management
- [ ] Create new user (each role: Designer, Student, Production Audio)
- [ ] Edit user details
- [ ] Change user role
- [ ] Deactivate user
- [ ] Verify deactivated user cannot login

#### 3. Inventory
- [ ] Create new category
- [ ] Create new subcategory
- [ ] Add new item with quantity tracking
- [ ] Add new item with serial number tracking
- [ ] Upload item photo
- [ ] Generate item barcode
- [ ] Print item barcode (Avery 8195 template)

#### 4. Shows
- [ ] Create new show
- [ ] Assign Designer to show
- [ ] Assign Production Audio to show
- [ ] Add event to production calendar
- [ ] Edit show details
- [ ] Archive show

#### 5. Pull Sheets
- [ ] Create pullsheet as Admin (auto-approved)
- [ ] Create pullsheet as Designer (requires approval)
- [ ] Admin approves Designer's pullsheet
- [ ] Finalize pullsheet (generates PDF417 barcode)
- [ ] Download pullsheet PDF
- [ ] Verify logo appears in PDF

#### 6. Pick Mode
- [ ] Enter Pick Mode
- [ ] Scan pullsheet barcode (not search!)
- [ ] Scan item barcodes
- [ ] Verify color coding (red → green)
- [ ] Test success sound plays
- [ ] Test error sound on wrong item
- [ ] Complete pick
- [ ] Verify items marked as checked out

#### 7. Change Orders
- [ ] Create change order as Admin
- [ ] Create change order as Production Audio (requires approval)
- [ ] Admin approves Production Audio's change order
- [ ] Add items to change order
- [ ] Remove items from change order
- [ ] Finalize change order
- [ ] Process in Pick Mode

#### 8. Return Mode
- [ ] Enter Return Mode
- [ ] Scan pullsheet/change order barcode
- [ ] Scan items to return
- [ ] Complete return
- [ ] Verify items back in stock
- [ ] Generate return paperwork

#### 9. Student Requests
- [ ] Student creates equipment request
- [ ] Admin sees request in dashboard
- [ ] Admin approves request
- [ ] Student receives notification
- [ ] Convert approved request to pullsheet
- [ ] Test Admin rejects request with reason
- [ ] Student sees denial reason

#### 10. Repairs
- [ ] Create new repair ticket
- [ ] Assign tech to repair
- [ ] Update repair status
- [ ] Mark repair as completed
- [ ] Verify item quantity adjusted

#### 11. Reports
- [ ] Generate inventory report
- [ ] Generate show report
- [ ] Generate location report
- [ ] Export report as PDF
- [ ] Export barcodes for specific show

#### 12. Settings
- [ ] Upload new logo
- [ ] Verify logo appears in header
- [ ] Upload login cover image
- [ ] Verify cover appears on login page
- [ ] Create new theatre space
- [ ] Regenerate barcodes (test API call)

#### 13. Notifications
- [ ] Verify notification icon shows count
- [ ] Click notification dropdown
- [ ] Mark notification as read
- [ ] Mark all as read
- [ ] Click notification link (navigates correctly)

#### 14. User Hotkeys
- [ ] User configures custom hotkey
- [ ] Test hotkey activates action
- [ ] Change hotkey
- [ ] Test new hotkey works

#### 15. Production Calendar
- [ ] View calendar in different modes (month, week, day)
- [ ] Create new event
- [ ] Edit event by clicking on calendar
- [ ] Delete event
- [ ] Verify show colors display correctly

### Easter Eggs Testing

#### 1. Pink Mode
- [ ] Triple-click logo (3 fast clicks)
- [ ] Verify UI turns pink
- [ ] See floating hearts and cats
- [ ] Wait for meow sound (may take up to 5 minutes)
- [ ] Click cat icon next to logo
- [ ] Verify pink mode deactivates

#### 2. Random Guy Background
- [ ] Reload page multiple times (~20 times)
- [ ] Should see guy background ~5% of time
- [ ] Check console for "👨 Random guy appeared!"
- [ ] Verify UI still functional

#### 3. Cheeseburger Lookup
- [ ] Open Quick Lookup (dashboard)
- [ ] Type or scan: CHZ-BGR
- [ ] Should hear loud whopper sound
- [ ] Works in Pick Mode too
- [ ] Works in Return Mode too

#### 4. Cable Color Bonk
- [ ] View dashboard
- [ ] Find Cable Color Key section
- [ ] Click RED color block
- [ ] Should hear bonk sound

### Clean URL Verification

Test these URLs (should work WITHOUT .php):
- [ ] `/login` - Login page
- [ ] `/index` or `/` - Dashboard
- [ ] `/items` - Inventory list
- [ ] `/item_edit?id=1` - Edit item
- [ ] `/shows` - Shows list
- [ ] `/pullsheets` - Pullsheets list
- [ ] `/change_orders` - Change orders list
- [ ] `/pick_mode` - Pick mode
- [ ] `/return_mode` - Return mode
- [ ] `/student_requests` - Student requests
- [ ] `/repairs` - Repairs
- [ ] `/reports` - Reports
- [ ] `/settings` - Settings
- [ ] `/user_management` - User management
- [ ] `/production_calendar` - Calendar

Test OLD URLs redirect:
- [ ] `/login.php` → redirects to `/login`
- [ ] `/items.php` → redirects to `/items`

### Role-Based Access Testing

#### As Student:
- [ ] Cannot access Settings
- [ ] Cannot access User Management
- [ ] Cannot access Pick Mode directly
- [ ] Cannot access Return Mode directly
- [ ] Can only see own student requests
- [ ] Can view inventory (read-only)
- [ ] Can create student requests only

#### As Designer:
- [ ] Can view inventory (read-only)
- [ ] Can create pullsheets (requires approval)
- [ ] Can create change orders (requires approval)
- [ ] Can create student requests
- [ ] Cannot access Settings
- [ ] Cannot access User Management
- [ ] Can see all student requests

#### As Production Audio:
- [ ] Can view inventory (read-only)
- [ ] Can create pullsheets (requires approval)
- [ ] Can create change orders (requires approval)
- [ ] Can access Pick Mode (requires signature)
- [ ] Can access Return Mode (requires signature)
- [ ] Cannot access Settings
- [ ] Cannot access User Management

#### As Admin:
- [ ] Full access to everything
- [ ] Can approve/reject orders
- [ ] Can manage users
- [ ] Can pick/return without signature
- [ ] Auto-approval for own orders

### Approval Workflow Testing

#### Designer Creates Pullsheet:
- [ ] Designer creates and finalizes pullsheet
- [ ] Status shows "Pending Approval"
- [ ] Admin receives notification
- [ ] Pick Mode blocks picking (not approved)
- [ ] Admin approves pullsheet
- [ ] Designer receives approval notification
- [ ] Pick Mode now allows picking

#### Designer Creates Change Order:
- [ ] Designer creates and finalizes change order
- [ ] Status shows "Pending Approval"
- [ ] Admin receives notification
- [ ] Pick Mode blocks processing (not approved)
- [ ] Admin approves change order
- [ ] Designer receives approval notification
- [ ] Can now be processed

#### Admin Creates Orders:
- [ ] Admin creates pullsheet
- [ ] Auto-approved (no approval needed)
- [ ] Can immediately be picked

### Performance Testing

- [ ] Dashboard loads in < 2 seconds
- [ ] Inventory list with 100+ items loads quickly
- [ ] Search/filter responds instantly
- [ ] Barcode scanning is responsive
- [ ] PDF generation completes in < 5 seconds
- [ ] No JavaScript errors in console
- [ ] No PHP errors in logs

### Security Verification

- [ ] Cannot access admin pages as non-admin
- [ ] Cannot SQL inject in any form
- [ ] Cannot XSS via item names/descriptions
- [ ] Cannot upload non-image files as photos
- [ ] Cannot access other users' data
- [ ] Session expires after logout
- [ ] Password is hashed in database (not plain text)
- [ ] Sensitive files blocked (.sql, .md, .log)

### Browser Compatibility

Test in:
- [ ] Chrome/Edge (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Mobile browser (responsive design)

### Dark/Light Mode

- [ ] Toggle theme in header
- [ ] Theme persists across page loads
- [ ] All pages render correctly in both modes
- [ ] Easter eggs work in both modes

## Known Issues to Document

Document any issues found during testing:

1. Issue: _______________
   Impact: _______________
   Workaround: _______________

2. Issue: _______________
   Impact: _______________
   Workaround: _______________

## Production Readiness Checklist

- [ ] All critical path tests passed
- [ ] All user roles tested
- [ ] Approval workflow works
- [ ] Clean URLs working
- [ ] Security tests passed
- [ ] Performance acceptable
- [ ] Documentation complete
- [ ] Admin password changed
- [ ] Database backed up
- [ ] Error logging working
- [ ] Sound files replaced (optional)

## Post-Launch Monitoring

### Week 1:
- [ ] Monitor error logs daily
- [ ] Check for failed logins
- [ ] Verify barcode API quota
- [ ] Monitor database size
- [ ] Check user feedback

### Week 2-4:
- [ ] Review error logs weekly
- [ ] Check performance metrics
- [ ] Gather user feedback
- [ ] Plan enhancements

## Support Contact

For deployment support:
- Check logs: `/logs/app_YYYY-MM-DD.log`
- Check PHP errors: `/logs/php_errors_YYYY-MM-DD.log`
- Review: `REWRITE_SUMMARY.md`
- Review: `README.md`

---

**Deployment Status**: [ ] Ready [ ] In Progress [ ] Complete
**Deployed By**: _______________
**Deployment Date**: _______________
**Go-Live Date**: _______________
