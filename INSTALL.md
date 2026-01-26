# Installation Guide for S-Shop Inventory App

## Quick Installation Steps

### Step 1: Database Setup

Run the database schema file:

```bash
mysql -u voxelnodes_sshop -p < database_schema.sql
```

When prompted, enter the password: `).sBi.*B=}rp`

This will:
- Create the `voxelnodes_sshop` database
- Create all necessary tables
- Insert sample categories and theatre spaces
- Insert default settings

### Step 2: Directory Permissions

The application needs to create and write to these directories:

```bash
mkdir -p uploads pdfs assets
chmod 755 uploads pdfs assets
```

### Step 3: Web Server Configuration

#### For Apache:

Point your document root to the S-Shop-Inventory-App directory.

Example Apache vhost configuration:

```apache
<VirtualHost *:80>
    ServerName sshop.local
    DocumentRoot /path/to/S-Shop-Inventory-App
    
    <Directory /path/to/S-Shop-Inventory-App>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/sshop_error.log
    CustomLog ${APACHE_LOG_DIR}/sshop_access.log combined
</VirtualHost>
```

#### For Nginx:

```nginx
server {
    listen 80;
    server_name sshop.local;
    root /path/to/S-Shop-Inventory-App;
    index index.php index.html;

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

### Step 4: Access the Application

Open your browser and navigate to:
- `http://localhost/` (if running locally)
- `http://your-domain.com/` (if on a server)

### Step 5: Initial Setup

1. **Configure Settings**
   - Go to Settings → Upload your logo for PDFs
   - Add/modify categories as needed
   - Add/modify theatre spaces as needed

2. **Add Inventory**
   - Go to Inventory → Create New Item
   - Add items with categories, quantities, and barcodes

3. **Create a Show**
   - Go to Shows → Create New Show
   - Enter designer, shop lead, and select theatre space

4. **Create a Pull Sheet**
   - View a show → Create Pull Sheet
   - Search/scan items to add
   - Click "Build Show" to finalize

## Troubleshooting

### Database Connection Issues

If you see "Database connection error", check:
1. MySQL is running
2. Database credentials in `config.php` are correct
3. The `voxelnodes_sshop` database exists
4. User `voxelnodes_sshop` has proper permissions

### Permission Issues

If you can't upload logos or generate PDFs:
```bash
chmod 755 uploads pdfs assets
chown www-data:www-data uploads pdfs assets  # For Apache
# OR
chown nginx:nginx uploads pdfs assets  # For Nginx
```

### PHP Extensions

Ensure these PHP extensions are enabled:
- mysqli
- gd (for barcode generation)
- mbstring
- json

Check with:
```bash
php -m | grep -E 'mysqli|gd|mbstring|json'
```

## Default Sample Data

The database schema includes:

**Sample Categories:**
- Microphones
- Speakers
- Cables
- Mixers
- Effects
- Accessories

**Sample Theatre Spaces:**
- Main Stage
- Black Box
- Studio Theatre
- Rehearsal Room

## Configuration

### Database Configuration

Located in `config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'voxelnodes_sshop');
define('DB_PASS', ').sBi.*B=}rp');
define('DB_NAME', 'voxelnodes_sshop');
```

### Timezone

Default timezone is `America/New_York`. Change in `config.php` if needed:
```php
date_default_timezone_set('America/New_York');
```

## Security Notes

- The database password is stored in plain text in `config.php`
- Ensure `config.php` is not accessible from the web (use .htaccess or nginx config)
- In production, disable PHP error display in `config.php`:
  ```php
  error_reporting(0);
  ini_set('display_errors', 0);
  ```

## Support

For issues or questions:
- Check the main README.md file
- Open an issue on GitHub
- Contact the system administrator

## Next Steps After Installation

1. Add inventory items
2. Create theatre spaces (or use default ones)
3. Create your first show
4. Build a pull sheet
5. Test pick mode with barcode scanning
6. Generate reports

Enjoy using S-Shop Inventory!
