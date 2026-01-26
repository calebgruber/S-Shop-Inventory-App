# Theatre Sound Shop Inventory Management System

A complete inventory management application for theatre sound shops built with pure PHP, MySQL, and Tabler UI.

## Features

- **Item Management**: Track items by quantity or serial number with barcode generation
- **Show Management**: Create and manage theatre shows with shop leads and designers
- **Pullsheets**: Create pullsheets for shows with PDF417 barcodes
- **Pick Mode**: Fullscreen barcode scanning interface for picking items
- **Return Mode**: Return items to inventory with barcode scanning
- **Change Orders**: Multiple change orders per show for adding/removing items
- **Reports**: Comprehensive inventory reports by show, space, or full inventory
- **Dark/Light Mode**: User-selectable theme that persists
- **Barcode Printing**: Print Code128 barcodes on Avery 8195 labels

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache with mod_rewrite (or Nginx)
- Composer

## Installation

### 1. Clone the repository

```bash
git clone <repository-url>
cd S-Shop-Inventory-App
```

### 2. Install PHP dependencies

```bash
composer install
```

### 3. Database Setup

Create a MySQL database and import the schema:

```bash
mysql -u root -p -e "CREATE DATABASE sound_shop_inventory CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p sound_shop_inventory < database/schema.sql
```

### 4. Configure Database Connection

Edit `includes/config.php` or set environment variables:

```bash
export DB_HOST=localhost
export DB_USER=root
export DB_PASS=your_password
export DB_NAME=sound_shop_inventory
```

### 5. Set Permissions

```bash
chmod 755 uploads
chmod 755 includes
```

### 6. Web Server Configuration

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

### 7. Access the Application

Navigate to `http://your-domain.com` in your web browser.

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
│   └── schema.sql          # Database schema
├── includes/
│   ├── config.php          # Configuration
│   ├── db.php             # Database connection
│   ├── functions.php       # Helper functions
│   ├── header.php         # Layout header
│   └── footer.php         # Layout footer
├── public/                 # Web root
│   ├── index.php          # Dashboard
│   ├── items.php          # Items list
│   ├── item_edit.php      # Add/edit items
│   ├── item_barcodes.php  # Print barcodes
│   ├── shows.php          # Shows list
│   ├── show_create.php    # Create show
│   ├── show_edit.php      # Edit show
│   ├── pullsheets.php     # Pullsheets list
│   ├── pullsheet_create.php
│   ├── pullsheet_edit.php
│   ├── pullsheet_view.php
│   ├── pick_mode.php      # Pick mode interface
│   ├── return_mode.php    # Return mode interface
│   ├── change_orders.php
│   ├── change_order_create.php
│   ├── change_order_edit.php
│   ├── reports.php        # Reports
│   └── settings.php       # Settings
├── uploads/               # Uploaded files (logos)
├── .htaccess             # Apache configuration
└── composer.json         # PHP dependencies
```

## Technologies Used

- **Backend**: Pure PHP (no framework)
- **Database**: MySQL with PDO
- **Frontend**: Tabler UI (Bootstrap 5)
- **Barcodes**: Code128 for items, PDF417 for pullsheets
- **PDF Generation**: TCPDF
- **Icons**: Tabler Icons

## Security Notes

- Always use prepared statements (PDO) for database queries
- Store sensitive configuration in environment variables
- Keep `composer.json` dependencies updated
- Use HTTPS in production
- Regularly backup the database

## License

[Add your license here]

## Support

For issues and questions, please open an issue on GitHub.
CMFT Sound Shop Inventory 
