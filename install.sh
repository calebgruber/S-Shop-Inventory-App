#!/bin/bash

echo "======================================"
echo "Sound Shop Inventory - Installation"
echo "======================================"
echo ""

# Check PHP
if ! command -v php &> /dev/null; then
    echo "❌ PHP is not installed. Please install PHP 7.4 or higher."
    exit 1
fi
echo "✓ PHP detected: $(php -v | head -n 1)"

# Check Composer
if ! command -v composer &> /dev/null; then
    echo "❌ Composer is not installed. Please install Composer first."
    exit 1
fi
echo "✓ Composer detected"

# Check MySQL
if ! command -v mysql &> /dev/null; then
    echo "⚠ MySQL client not found. You'll need to import the schema manually."
else
    echo "✓ MySQL detected"
fi

echo ""
echo "Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader

# Create uploads directory
mkdir -p uploads
chmod 755 uploads
echo "✓ Created uploads directory"

# Create .env.example
cat > .env.example << 'ENVEOF'
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=sound_shop_inventory
ENVEOF
echo "✓ Created .env.example"

echo ""
echo "======================================"
echo "Installation Complete!"
echo "======================================"
echo ""
echo "Next steps:"
echo "1. Create a MySQL database:"
echo "   mysql -u root -p -e \"CREATE DATABASE sound_shop_inventory;\""
echo ""
echo "2. Import the schema:"
echo "   mysql -u root -p sound_shop_inventory < database/schema.sql"
echo ""
echo "3. Configure database connection in includes/config.php"
echo "   or set environment variables (DB_HOST, DB_USER, DB_PASS, DB_NAME)"
echo ""
echo "4. Point your web server to the /public directory"
echo ""
echo "5. Access the application in your browser"
echo ""
