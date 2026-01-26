# cPanel Installation Guide

## Quick Setup for Theatre Sound Shop Inventory System

This guide will help you install the application on a cPanel hosting environment.

### Prerequisites

- cPanel hosting account
- MySQL database access (via cPanel)
- PHP 7.4 or higher with GD extension
- File Manager or FTP access

### Step-by-Step Installation

#### 1. Upload Files to cPanel

**Option A: Using File Manager**
1. Log into your cPanel
2. Open "File Manager"
3. Navigate to `public_html` (or your domain's directory)
4. Create a new folder called `sound-shop` (optional, for subdirectory installation)
5. Upload all files from this repository to that folder
6. Extract if you uploaded a ZIP file

**Option B: Using FTP**
1. Connect to your hosting via FTP
2. Navigate to `public_html`
3. Upload all repository files

#### 2. Database is Already Configured

The database credentials are already set in `includes/config.php`:
- Database: `voxelnodes_sshop`
- User: `voxelnodes_sshop`
- Password: ').sBi.*B=}rp'

**If these credentials change**, update `includes/config.php`.

#### 3. Import Database Schema

1. In cPanel, go to **phpMyAdmin**
2. Select the database `voxelnodes_sshop` from the left sidebar
3. Click the **Import** tab
4. Click **Choose File** and select `database/schema.sql` from your computer
5. Scroll down and click **Go**
6. Wait for the success message

#### 4. Set Directory Permissions

Using cPanel File Manager:

1. Right-click on the `uploads` folder
2. Select **Change Permissions**
3. Set permissions to **755** (or 777 if 755 doesn't work)
   - Owner: Read, Write, Execute
   - Group: Read, Execute
   - World: Read, Execute
4. Check "Recurse into subdirectories"
5. Click **Change Permissions**

#### 5. Access the Application

**If installed in root (`public_html`):**
- Main app: `https://yourdomain.com/public/`
- Test page: `https://yourdomain.com/public/test_barcode.php`

**If installed in subdirectory (`public_html/sound-shop`):**
- Main app: `https://yourdomain.com/sound-shop/public/`
- Test page: `https://yourdomain.com/sound-shop/public/test_barcode.php`

### First Steps After Installation

#### 1. Test Barcode Generation

Visit the test page first to ensure barcodes are working:
- `https://yourdomain.com/public/test_barcode.php`

You should see:
- ✓ Code128 barcode image
- ✓ PDF417 barcode image
- ✓ All system requirements met

#### 2. Configure Settings

1. Go to **Settings** in the navigation menu
2. Upload your logo (will appear on PDFs)
3. Add **Categories** for inventory items (e.g., Microphones, Speakers, Cables)
4. Add **Theatre Spaces** (e.g., Main Stage, Black Box, Studio)

#### 3. Add Your First Items

1. Go to **Items**
2. Click **Add New Item**
3. Fill in item details:
   - Name (e.g., "Shure SM58 Microphone")
   - Category
   - Tracking Type (Quantity or Serial Number)
   - Total Quantity
   - In Stock Quantity
4. The barcode will be auto-generated
5. Click **Save**

#### 4. Create a Show

1. Go to **Shows**
2. Click **Create New Show**
3. Enter:
   - Show Name
   - Shop Lead
   - Designer
   - Theatre Space
4. Click **Create Show**

### Troubleshooting

#### Database Connection Errors

If you see "Connection failed" errors:
1. Verify database credentials in `includes/config.php`
2. Check that the database exists in cPanel → MySQL Databases
3. Ensure the user has privileges on the database

#### Barcodes Not Showing

If barcodes don't generate:
1. Check PHP version (must be 7.4+)
2. Verify GD extension is installed:
   - cPanel → Select PHP Version → Check "gd"
3. Visit `test_barcode.php` to see specific errors

#### Permission Errors

If you get "permission denied" errors:
1. Set `uploads` folder to 755 or 777
2. Ensure all PHP files are readable (644)

#### Page Not Found (404)

If pages show 404 errors:
1. Check that `.htaccess` file was uploaded
2. Verify mod_rewrite is enabled in cPanel
3. Try accessing via: `yourdomain.com/public/index.php` directly

### Important Notes

1. **Database is Pre-Configured**: The credentials are already set for the voxelnodes_sshop database
2. **No Dependencies**: This application requires NO composer or external libraries
3. **Pure PHP**: Everything works with standard PHP and MySQL
4. **Backup**: Regular database backups are recommended (use cPanel backup tools)

### Security Recommendations

1. Use HTTPS (enable SSL in cPanel)
2. Keep PHP updated to latest stable version
3. Regular database backups via cPanel
4. Change database password periodically
5. Restrict file permissions (don't use 777 unless absolutely necessary)

### Getting Help

If you encounter issues:
1. Check `test_barcode.php` for system requirements
2. Check PHP error logs in cPanel
3. Verify all files uploaded correctly
4. Ensure database imported successfully

### What's Next?

After installation:
1. ✅ Configure settings (logo, categories, spaces)
2. ✅ Add inventory items
3. ✅ Create shows
4. ✅ Create pullsheets for shows
5. ✅ Use Pick Mode to pick items
6. ✅ Use Return Mode to return items
7. ✅ Generate reports

Enjoy your Theatre Sound Shop Inventory System!
