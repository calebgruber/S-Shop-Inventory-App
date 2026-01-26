# S-Shop Inventory App - New Features Documentation

## Overview
This document describes the major features added to the CMFT Sound Shop Inventory Management System.

---

## 🔐 User Authentication & Management

### Login System
- Email and password authentication
- Session-based security
- Default credentials: `admin@example.com` / `admin123` (CHANGE IMMEDIATELY)

### User Roles
1. **Admin** - Full system access
2. **Designer** - Most features except user management
3. **Student** - View inventory and make requests only

### User Management (Admin Only)
- Create, edit, and deactivate users
- Reset user passwords
- Manage granular permissions per user
- User profile photos via DiceBear API

**Access:** Dashboard → User dropdown → User Management

---

## 🏠 Enhanced Dashboard

### Quick Lookup
- Search items by name or scan barcode
- Shows item photo, location, stock levels
- Modal-based interface
- **Access:** Click "Quick Lookup" button on dashboard

### Cable Color Key
- Visual reference for cable lengths:
  - **Red** - 5 feet
  - **Grey** - 10 feet
  - **Purple** - 15 feet
  - **Yellow** - 25 feet
  - **Blue** - 50 feet
  - **White** - 100 feet

### Quick Action Buttons
- One-click access to common tasks
- Permission-based visibility
- Includes: Quick Lookup, Inventory, Show Reports, Create Pullsheet, Pick Mode, Return Mode, Shows, Change Orders

---

## 📦 Inventory Enhancements

### Item Photos
- Upload photos for each item
- Supported formats: JPG, PNG, GIF
- Photos displayed in quick lookup and item details

### Location Tracking
- Add shop location for each item (e.g., "Shelf A-3")
- Helps staff quickly find items
- **Future:** Will appear on paperwork

**Access:** Inventory → Edit Item → Location/Photo fields

---

## 📄 Pullsheets & Change Orders

### Organization by Show
- Pullsheets and change orders grouped by show
- Collapsible accordion interface
- Separate section for items not attached to shows
- Easy navigation with item counts per show

### Draft/Partial Completion
- **Save as Draft** during pick or return operations
- Resume from where you left off
- Quantities are saved and restored
- Shows "Resuming partial pick from [date]" alert

**How to Use:**
1. Start pick/return mode and scan pullsheet
2. Scan some items
3. Click "Save as Draft" (yellow button)
4. Later, scan same pullsheet to resume
5. Complete when finished (green button)

---

## 📊 Reports

### Collapsible Sections
- Reports by show: Accordion interface for each show
- Reports by space: Accordion interface for each theatre space
- Expand/collapse individual sections
- Print button for each report type

**Access:** Navigation → Reports → Select Report Type

---

## 📋 Paperwork Management

### View All Paperwork by Show
- Select a show to see all associated paperwork
- View pullsheets and change orders together
- Filter by:
  - Type (pullsheets, change orders, or both)
  - Category
- Quick links to view/edit paperwork

**Access:** Navigation → Paperwork → Select Show

---

## 🔧 Repairs System

### Create Repairs
- Report items needing repair
- Auto-generated repair IDs (e.g., RPR-XXXXX)
- Items removed from stock automatically
- Track repair status:
  - Pending
  - In Progress
  - Completed
  - Cancelled

### Manage Repairs
- Filter by status or category
- Update repair status
- Items return to stock when marked complete
- View repair details and history

**Access:** Navigation → Repairs

---

## 🎓 Student Request System

### For Students
- Browse all inventory items
- Submit requests for non-show items
- View request status
- Delete pending requests

### For Admins/Designers
- View all student requests
- Approve or reject requests
- Generate pullsheets from approved requests
- Mark requests as fulfilled

**How to Use:**
1. Student navigates to Requests
2. Clicks "Browse Items" to see inventory
3. Selects item and submits request with reason
4. Admin approves request
5. Admin creates pullsheet from approved requests
6. Items are picked and request marked fulfilled

**Access:** Navigation → Requests

---

## 🎨 UI/UX Improvements

### Permission-Based Navigation
- Menu items only show for authorized users
- Students see limited menu
- Designers see most features
- Admins see everything

### User Profile Menu
- User avatar in top-right corner
- Quick access to theme toggle
- Logout option
- User management (admins only)

### Footer
- Made with ❤ by Caleb Gruber
- Link to developer website

---

## 🔒 Permissions System

### Default Permissions by Role

**Admin:**
- Full access to all features
- Cannot be restricted

**Designer:**
- Dashboard, Inventory, Shows, Pullsheets, Change Orders
- Pick Mode, Return Mode, Reports, Paperwork
- Repairs

**Student:**
- Dashboard (read-only stats)
- Inventory (read-only)
- Student Requests

### Custom Permissions
Admins can override default permissions for individual users.

**Available Permissions:**
- dashboard, inventory, shows, pullsheets, change_orders
- pick_mode, return_mode, reports, paperwork
- repairs, student_requests, settings, user_management

---

## 📱 Responsive Design
- Works on desktop, tablet, and mobile
- Touch-friendly interface
- Optimized for barcode scanners
- Dark mode support (toggle in user menu)

---

## 🚀 Getting Started

### Initial Setup
1. Install database using `database/schema.sql`
2. Run migration: `database/feature_additions_migration.sql`
3. Log in with default admin credentials
4. **IMMEDIATELY** change admin password
5. Create additional users as needed
6. Configure settings and categories
7. Add inventory items with photos and locations

### First Day Workflow
1. **Login** with your credentials
2. **Quick Lookup** - Familiarize yourself with items
3. **Create Show** - Set up your production
4. **Create Pullsheet** - Add items needed for the show
5. **Pick Mode** - Scan and pick items (save drafts if needed)
6. **Reports** - Review what's out and what's available

---

## 🔐 Security Notes

### Important Security Reminders
- Change default admin password immediately
- Use strong passwords for all users
- Regularly review user access
- Deactivate users who no longer need access
- Keep PHP and MySQL updated
- Use HTTPS in production
- Regularly backup database

### Password Policy
- Passwords are hashed using PHP's password_hash()
- Minimum recommended length: 10 characters
- Include mix of letters, numbers, symbols

---

## 📖 Additional Resources

### Database Schema
- Users: `users`, `user_permissions`
- Items: `items` (with location, photo_path)
- Repairs: `repairs`
- Requests: `student_requests`
- Shows: `shows`, `pullsheets`, `change_orders`
- Subcategories: `subcategories`

### File Upload Directories
- `uploads/items/` - Item photos
- `uploads/logos/` - Shop logo

### API Endpoints
- `api_quick_lookup.php` - Quick item search

---

## 🐛 Troubleshooting

### Common Issues

**Cannot log in:**
- Verify database migration has been run
- Check database connection in `includes/config.php`
- Ensure `users` table exists

**Photos not uploading:**
- Check `uploads/items/` directory exists
- Verify directory permissions (755 or 777)
- Confirm file size is under PHP upload limit

**Permissions not working:**
- Verify user has correct role
- Check user_permissions table
- Admins always have all permissions

**Draft picks not saving:**
- Ensure migration added `is_partial` and `partial_saved_at` columns
- Check browser console for JavaScript errors

---

## 🎯 Best Practices

### Workflow Recommendations

1. **Start of Semester:**
   - Create student user accounts
   - Review and update inventory
   - Take photos of new items

2. **Per Show:**
   - Create show in system
   - Build pullsheet
   - Finalize and print
   - Pick items (use draft feature for large shows)
   - Generate change orders as needed

3. **End of Show:**
   - Return all items
   - Update show status to completed
   - Run reports to verify all items returned

4. **Ongoing:**
   - Process student requests weekly
   - Review repair status regularly
   - Generate inventory reports monthly
   - Backup database weekly

---

## 📞 Support

For issues and questions, please open an issue on GitHub or contact the developer.

**Developer:** Caleb Gruber
**Website:** https://www.calebgruber.me

---

## 📝 Version History

### v2.0 (2026-01-26)
- Added user authentication and management
- Implemented role-based permissions
- Added repairs system
- Added student request system
- Added paperwork management page
- Enhanced dashboard with quick lookup
- Added item photos and locations
- Implemented draft/partial completion for pick/return
- Organized pullsheets and change orders by show
- Made reports collapsible
- Added cable color key
- Integrated DiceBear for user avatars
- Added custom footer

### v1.0 (Previous)
- Initial inventory management system
- Basic pullsheets and change orders
- Pick and return modes
- Reports
- Barcode generation
