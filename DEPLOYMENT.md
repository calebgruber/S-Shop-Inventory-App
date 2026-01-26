# Deployment Guide

## Quick Start

### Local Development (XAMPP/MAMP/WAMP)

1. **Install XAMPP/MAMP**
   - Download from https://www.apachefriends.org/

2. **Clone the repository**
   ```bash
   cd /path/to/xampp/htdocs
   git clone <repo-url> sound-shop
   cd sound-shop
   ```

3. **Install Dependencies**
   ```bash
   composer install
   ```

4. **Create Database**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create database: `sound_shop_inventory`
   - Import `database/schema.sql`
   - Optionally import `database/sample_data.sql` for testing

5. **Configure**
   - Edit `includes/config.php` and update database credentials
   - Or set environment variables in Apache config

6. **Access**
   - Navigate to http://localhost/sound-shop/public/

### Production Deployment

#### 1. Server Requirements
- PHP 7.4+ with extensions: PDO, pdo_mysql, mbstring, gd
- MySQL 5.7+ or MariaDB 10.2+
- Apache 2.4+ with mod_rewrite OR Nginx
- Composer
- SSL certificate (recommended)

#### 2. Deploy Files
```bash
# On your server
cd /var/www
git clone <repo-url> sound-shop
cd sound-shop
composer install --no-dev --optimize-autoloader
```

#### 3. Set Permissions
```bash
chown -R www-data:www-data /var/www/sound-shop
chmod -R 755 /var/www/sound-shop
chmod -R 775 /var/www/sound-shop/uploads
```

#### 4. Database Setup
```bash
mysql -u root -p
CREATE DATABASE sound_shop_inventory CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'soundshop'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT ALL PRIVILEGES ON sound_shop_inventory.* TO 'soundshop'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Import schema
mysql -u soundshop -p sound_shop_inventory < database/schema.sql
```

#### 5. Configure Apache Virtual Host

Create `/etc/apache2/sites-available/sound-shop.conf`:

```apache
<VirtualHost *:80>
    ServerName inventory.yourdomain.com
    DocumentRoot /var/www/sound-shop/public
    
    <Directory /var/www/sound-shop/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    # Redirect to HTTPS
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
    
    ErrorLog ${APACHE_LOG_DIR}/sound-shop-error.log
    CustomLog ${APACHE_LOG_DIR}/sound-shop-access.log combined
</VirtualHost>

<VirtualHost *:443>
    ServerName inventory.yourdomain.com
    DocumentRoot /var/www/sound-shop/public
    
    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/your-cert.crt
    SSLCertificateKeyFile /etc/ssl/private/your-key.key
    
    <Directory /var/www/sound-shop/public>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/sound-shop-error.log
    CustomLog ${APACHE_LOG_DIR}/sound-shop-access.log combined
</VirtualHost>
```

Enable site and restart:
```bash
sudo a2ensite sound-shop
sudo a2enmod rewrite ssl
sudo systemctl restart apache2
```

#### 6. Configure Nginx (Alternative)

Create `/etc/nginx/sites-available/sound-shop`:

```nginx
server {
    listen 80;
    server_name inventory.yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name inventory.yourdomain.com;
    root /var/www/sound-shop/public;
    index index.php;
    
    ssl_certificate /etc/ssl/certs/your-cert.crt;
    ssl_certificate_key /etc/ssl/private/your-key.key;
    
    access_log /var/log/nginx/sound-shop-access.log;
    error_log /var/log/nginx/sound-shop-error.log;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location ~ /\.(?!well-known) {
        deny all;
    }
}
```

Enable and restart:
```bash
sudo ln -s /etc/nginx/sites-available/sound-shop /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

#### 7. Environment Variables (Recommended for Production)

Add to your Apache vhost or Nginx config:

**Apache:**
```apache
SetEnv DB_HOST localhost
SetEnv DB_USER soundshop
SetEnv DB_PASS your_password
SetEnv DB_NAME sound_shop_inventory
```

**Nginx (php-fpm):**
Edit `/etc/php/7.4/fpm/pool.d/www.conf`:
```ini
env[DB_HOST] = localhost
env[DB_USER] = soundshop
env[DB_PASS] = your_password
env[DB_NAME] = sound_shop_inventory
```

#### 8. Security Hardening

1. **Disable PHP errors in production:**
   Edit `includes/config.php`:
   ```php
   error_reporting(0);
   ini_set('display_errors', 0);
   ```

2. **Restrict file uploads:**
   - Ensure uploads directory is writable only by web server
   - Set maximum file size limits

3. **Enable HTTPS:**
   - Use Let's Encrypt for free SSL certificates
   - Force HTTPS redirects

4. **Set up regular backups:**
   ```bash
   # Database backup script
   mysqldump -u soundshop -p sound_shop_inventory > backup_$(date +%Y%m%d).sql
   ```

5. **Update regularly:**
   ```bash
   composer update
   git pull origin main
   ```

### Docker Deployment (Optional)

Create `docker-compose.yml`:

```yaml
version: '3.8'

services:
  web:
    image: php:7.4-apache
    ports:
      - "80:80"
    volumes:
      - .:/var/www/html
    environment:
      DB_HOST: db
      DB_USER: soundshop
      DB_PASS: password
      DB_NAME: sound_shop_inventory
    depends_on:
      - db
      
  db:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: rootpassword
      MYSQL_DATABASE: sound_shop_inventory
      MYSQL_USER: soundshop
      MYSQL_PASSWORD: password
    volumes:
      - db_data:/var/lib/mysql
      - ./database/schema.sql:/docker-entrypoint-initdb.d/schema.sql

volumes:
  db_data:
```

Run:
```bash
docker-compose up -d
```

## Maintenance

### Backup Database
```bash
mysqldump -u soundshop -p sound_shop_inventory > backup.sql
```

### Restore Database
```bash
mysql -u soundshop -p sound_shop_inventory < backup.sql
```

### Update Application
```bash
cd /var/www/sound-shop
git pull origin main
composer install --no-dev
```

### Monitor Logs
```bash
# Apache
tail -f /var/log/apache2/sound-shop-error.log

# Nginx
tail -f /var/log/nginx/sound-shop-error.log
```

## Troubleshooting

### Database Connection Issues
- Check credentials in `includes/config.php`
- Verify MySQL service is running: `sudo systemctl status mysql`
- Check MySQL user permissions

### Permission Issues
```bash
sudo chown -R www-data:www-data /var/www/sound-shop
sudo chmod -R 755 /var/www/sound-shop
sudo chmod -R 775 /var/www/sound-shop/uploads
```

### Apache mod_rewrite not working
```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### Composer not found
```bash
# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

## Support

For issues and questions, refer to the main README.md or open an issue on GitHub.
