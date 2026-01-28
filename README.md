# Theatre Sound Shop Inventory Management System

A complete inventory management application for theatre sound shops built with pure PHP, MySQL, and Tabler UI.

## Features

### Core Features
- **Item Management**: Track items by quantity or serial number with barcode generation
- **Show Management**: Create and manage theatre shows with shop leads and designers
- **Pullsheets**: Create pullsheets for shows with PDF417 barcodes
- **Pick Mode**: Fullscreen barcode scanning interface with sound effects
- **Return Mode**: Return items to inventory with barcode scanning and sound feedback
- **Change Orders**: Multiple change orders per show for adding/removing items
- **Reports**: Comprehensive inventory reports by show, space, or full inventory
- **Dark/Light Mode**: User-selectable theme that persists
- **Barcode Printing**: Print Code128 barcodes on Avery 8195 labels

### User Roles & Permissions
- **Admin**: Full access to all features, approves orders, manages users
- **Designer**: Read-only inventory, create orders (requires approval), assigned to shows
- **Student**: Read-only inventory, create equipment requests only
- **Production Audio**: Read-only inventory, create orders (requires approval), can pick/return items

See [PRODUCTION_AUDIO_GUIDE.md](PRODUCTION_AUDIO_GUIDE.md) for complete role details and approval workflow.

### Advanced Features
- **Approval Workflow**: Pullsheets and change orders from Designers and Production Audio require admin approval
  - Visual approval status indicators
  - Email notifications for approval requests
  - Prevent picking unapproved orders
  
- **Hotkey Manager**: Customize keyboard shortcuts for quick access to common actions
  - Configure personal hotkeys for 7+ common operations
  - Access via user settings page
  - Persistent per-user preferences
  
- **Production Calendar**: Visual calendar system for managing show events
  - FullCalendar integration with multiple view modes
  - Color-coded shows for easy identification
  - Create/edit/delete events linked to shows
  - Admin-controlled color customization
  
- **Notifications System**: Real-time alerts for important events
  - Unread notification badge in header
  - Dropdown with recent notifications
  - Auto-generated for: pullsheet finalization, student requests, repairs needed, approvals
  - Mark as read/mark all read functionality

- **Easter Eggs**: Fun hidden features for entertainment
  - Pink Mode with floating cats and hearts
  - Random guy background
  - Special sound effects for certain actions
  - See [EASTER_EGGS.md](EASTER_EGGS.md) for details

See [ADVANCED_FEATURES.md](ADVANCED_FEATURES.md) for detailed documentation.

## Requirements

- PHP 7.4 or higher with GD extension (for barcode generation)
- MySQL 5.7 or higher
- Web server (Apache or Nginx)
- No external dependencies required
- MP3 sound files for scanning feedback (success.mp3, error.mp3) - see assets/sounds/README.md

## Installation on cPanel

### 1. Upload Files

Upload all files to your public_html directory (or a subdirectory if desired).

### 2. Database Setup

1. In cPanel, go to MySQL Databases
2. Create a new database (e.g., `youruser_sound_shop`)
3. Create a database user with a strong password
4. Add the user to the database with ALL PRIVILEGES
5. Note down the database name, username, and password

### 3. Import Database Schema

1. In cPanel, go to phpMyAdmin
2. Select your newly created database
3. Click on the "Import" tab
4. Import the following SQL files in order:
   - First: `database/schema.sql` (core schema)
   - Second: `database/advanced_features_migration.sql` (hotkeys, calendar, notifications)
   - Third: `database/feature_additions_migration.sql` (repairs, student requests, users)
   - Fourth: `database/production_audio_migration.sql` (Production Audio role, approval system)
5. Click "Go" to import each file

### 4. Configure Database Connection

The database credentials are already configured in `includes/config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'voxelnodes_sshop');
define('DB_PASS', ').sBi.*B=}rp');
define('DB_NAME', 'voxelnodes_sshop');
```

If you need to change these, edit the `includes/config.php` file.

### 5. Set Permissions

The uploads directory needs to be writable:
- In cPanel File Manager, right-click on `uploads` folder
- Select "Change Permissions"
- Set to 755 or 777 (if 755 doesn't work)

### 6. Access the Application

Navigate to your domain (e.g., `https://yourdomain.com/`) in your web browser.

## Installation on VPS/Dedicated Server

### 1. Clone the repository

```bash
git clone <repository-url>
cd S-Shop-Inventory-App
```

### 2. Database Setup

Create a MySQL database and import the schema:

```bash
mysql -u root -p -e "CREATE DATABASE sound_shop_inventory CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p sound_shop_inventory < database/schema.sql
mysql -u root -p sound_shop_inventory < database/advanced_features_migration.sql
```

### 3. Configure Database Connection

Edit `includes/config.php` and update the credentials.

### 4. Set Permissions

```bash
chmod 755 uploads
chmod 755 includes
```

### 5. Web Server Configuration

#### Apache

Make sure mod_rewrite is enabled. The included `.htaccess` files will handle routing.

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

Point your virtual host to the repository root directory.

#### Nginx

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/S-Shop-Inventory-App/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 6. Access the Application

Navigate to `http://your-domain.com/` in your web browser.

## Usage

### Initial Setup

1. **Configure Settings**: Go to Settings and upload your logo, add categories, and theatre spaces
2. **Add Items**: Navigate to Items and add your inventory items with barcodes
3. **Create Shows**: Create shows and assign them to theatre spaces

### Workflow

1. **Create a Pullsheet**: 
   - Go to a show and click "Create Pullsheet"
   - Add items by scanning or searching
   - Finalize the pullsheet to generate a PDF417 barcode

2. **Pick Items (Pick Mode)**:
   - Enter Pick Mode from the dashboard
   - Scan the pullsheet barcode to start
   - Scan each item to mark it as picked
   - Complete when all items are scanned

3. **Return Items (Return Mode)**:
   - Enter Return Mode from the dashboard
   - Scan the pullsheet barcode
   - Scan items to return them to inventory

4. **Change Orders**:
   - Create change orders to add or remove items from a show
   - Multiple change orders can be created per show
   - Process change orders through Pick Mode

## File Structure

```
S-Shop-Inventory-App/
├── database/
│   ├── schema.sql                    # Database schema
│   └── advanced_features_migration.sql # Advanced features tables
├── includes/
│   ├── barcode/
│   │   ├── Code128.php               # Pure PHP Code128 generator
│   │   └── PDF417.php                # Pure PHP PDF417 generator
│   ├── pdf/
│   │   └── SimplePDF.php             # Pure PHP PDF generator
│   ├── config.php                    # Configuration
│   ├── db.php                        # Database connection
│   ├── functions.php                 # Helper functions
│   ├── header.php                    # Layout header
│   └── footer.php                    # Layout footer
├── index.php                         # Dashboard
├── items.php                         # Items list
├── item_edit.php                     # Add/edit items
├── item_barcodes.php                 # Print barcodes
├── shows.php                         # Shows list
├── show_create.php                   # Create show
├── show_edit.php                     # Edit show
├── pullsheets.php                    # Pullsheets list
├── pullsheet_create.php
├── pullsheet_edit.php
├── pullsheet_view.php
├── pick_mode.php                     # Pick mode interface
├── return_mode.php                   # Return mode interface
├── change_orders.php
├── change_order_create.php
├── change_order_edit.php
├── reports.php                       # Reports
├── settings.php                      # Settings
├── user_settings.php                 # User hotkey preferences
├── production_calendar.php           # Production calendar
├── api_notifications.php             # Notifications API
├── test_barcode.php                  # Test barcode generation
├── setup_advanced_features.php       # Setup script for advanced features
├── uploads/                          # Uploaded files (logos)
├── ADVANCED_FEATURES.md              # Advanced features documentation
└── .htaccess                         # Apache configuration
```

## Technologies Used

- **Backend**: Pure PHP (no framework, no external dependencies)
- **Database**: MySQL with PDO
- **Frontend**: Tabler UI (Bootstrap 5) via CDN
- **Barcodes**: Pure PHP Code128 for items, PDF417 for pullsheets/change orders
- **PDF Generation**: Pure PHP SimplePDF implementation
- **Icons**: Tabler Icons via CDN

## Security Notes

- Always use prepared statements (PDO) for database queries
- Store sensitive configuration securely
- Use HTTPS in production
- Regularly backup the database
- Keep PHP updated to the latest secure version

## License

[Add your license here]

## Support

For issues and questions, please open an issue on GitHub.
CMFT Sound Shop Inventory 
