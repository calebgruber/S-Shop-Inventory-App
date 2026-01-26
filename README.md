# S-Shop Inventory App

CMFT Sound Shop Inventory Management System

## Features

- **Shows Management**: Create and track shows with designer, shop lead, and theatre space assignments
- **Pull Sheets**: Build pull sheets with barcode scanning, stock validation, and PDF generation
- **Change Orders**: Track changes to existing pull sheets (add or return items)
- **Pick Mode**: Fullscreen picking interface with color-coded status and audio feedback
- **Return Mode**: Return items back to shop inventory
- **Inventory Management**: Track items by quantity or serial number
- **Reports**: Generate filtered reports by show, theatre space, or all items
- **Barcode Support**: Code128 for items, PDF417 for pull sheets and change orders
- **PDF Generation**: Print pull sheets, change orders, reports, and Avery 8195 labels
- **Dark/Light Mode**: Toggle between themes with persistent settings
- **Settings**: Configure logo, categories, and theatre spaces

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache or Nginx web server
- MySQLi extension enabled

## Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/calebgruber/S-Shop-Inventory-App.git
   cd S-Shop-Inventory-App
   ```

2. **Create the database**
   ```bash
   mysql -u voxelnodes_sshop -p < database_schema.sql
   ```

3. **Configure database connection**
   The database is already configured in `config.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'voxelnodes_sshop');
   define('DB_PASS', ').sBi.*B=}rp');
   define('DB_NAME', 'voxelnodes_sshop');
   ```

4. **Set up web server**
   Point your web server document root to the project directory.

5. **Create required directories**
   The app will automatically create these directories, but you can create them manually:
   ```bash
   mkdir uploads pdfs assets
   chmod 755 uploads pdfs assets
   ```

6. **Access the application**
   Navigate to `http://localhost/` (or your configured domain) in your web browser.

## Usage

### Creating a Show

1. Navigate to **Shows** from the dashboard
2. Click **Create New Show**
3. Enter show name, designer, shop lead, and select theatre space
4. Submit to create the show

### Building a Pull Sheet

1. Go to a show and click **Create Pull Sheet**
2. Search or scan items to add
3. Specify quantity needed for each item
4. Save as draft or click **Build Show** to finalize
5. System generates a PDF417 barcode and PDF document

### Pick Mode

1. Click **Pick Mode** from dashboard
2. Enter your name
3. Scan or search for pull sheet barcode
4. Scan items - cards turn:
   - **Red**: Not started
   - **Yellow**: Partial or extra items
   - **Green**: Complete
5. Click **Done** when all cards are green

### Return Mode

1. Click **Return Mode** from dashboard
2. Scan or search for pull sheet/change order barcode
3. Scan items to return them to shop inventory

### Reports

1. Navigate to **Reports**
2. Select filter (All Items, By Show, By Theatre Space)
3. View on screen or download as PDF

## File Structure

```
S-Shop-Inventory-App/
├── config.php              # Configuration
├── db.php                  # Database connection
├── functions.php           # Helper functions
├── layout.php              # Base template
├── index.php               # Dashboard
├── shows.php               # Shows list
├── show_create.php         # Create show
├── show_view.php           # View show
├── pull_sheets.php         # Pull sheets list
├── pull_sheet_create.php   # Create pull sheet
├── pull_sheet_view.php     # View pull sheet
├── change_orders.php       # Change orders list
├── change_order_create.php # Create change order
├── change_order_view.php   # View change order
├── pick_mode.php           # Pick mode interface
├── return_mode.php         # Return mode interface
├── inventory.php           # Inventory list
├── inventory_create.php    # Add inventory
├── inventory_edit.php      # Edit inventory
├── reports.php             # Reports
├── settings.php            # Settings
├── ajax_toggle_theme.php   # Theme toggle
├── barcode_generator.php   # Barcode generation
├── pdf_generator.php       # PDF generation
└── database_schema.sql     # Database schema
```

## Database Schema

The database includes the following tables:
- `settings` - Application settings
- `categories` - Inventory categories
- `theatre_spaces` - Theatre locations
- `items` - Inventory items
- `item_serials` - Serial number tracking
- `shows` - Show information
- `pull_sheets` - Pull sheets
- `pull_sheet_items` - Items on pull sheets
- `change_orders` - Change orders
- `change_order_items` - Items on change orders
- `item_transactions` - Audit log
- `item_locations` - Current location tracking

## Technology Stack

- **Backend**: PHP (vanilla, no frameworks)
- **Database**: MySQL
- **Frontend**: Tabler UI (Bootstrap-based)
- **Barcode**: SVG-based Code128 and PDF417
- **PDF**: HTML-based generation

## Security

- All database queries use prepared statements
- Input sanitization on all user inputs
- SQL injection protection
- XSS protection with htmlspecialchars

## License

MIT License

## Author

Caleb Gruber

## Support

For issues or questions, please open an issue on GitHub 
