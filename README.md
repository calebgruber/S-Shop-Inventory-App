# S-Shop Inventory System
CMFT Sound Shop Inventory & Workflow Management System

## Overview
A comprehensive PHP-based inventory and workflow management system designed for college theatre sound shops. The system tracks inventory, manages shop orders (pull sheets), change orders, student equipment requests, repairs, and show workflows.

## Technology Stack
- **Backend**: Pure PHP (no frameworks)
- **Database**: MySQL/MariaDB
- **Frontend**: Tabler UI framework
- **Deployment**: cPanel compatible

## Project Status
This is a foundational implementation providing the database schema and core configuration. The full system requires extensive development across multiple phases.

## Installation

### 1. Database Setup
```bash
mysql -u root -p < database.sql
```

### 2. Configuration
Edit `config/database.php` with your database credentials:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'sshop_inventory');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

### 3. Directory Permissions
Ensure write permissions for upload directories:
```bash
chmod -R 755 uploads/
chmod -R 755 assets/barcodes/
chmod -R 755 pdfs/
```

## Default Credentials
- **Username**: admin
- **Password**: admin123
- **Note**: Change immediately after first login

## Features (Planned)

### Core Modules
- ✅ Database Schema
- ✅ Configuration System
- ⏳ Authentication & Authorization
- ⏳ Dashboard with Navigation
- ⏳ Inventory Management
- ⏳ Shows Management
- ⏳ Shop Orders (Pull Sheets)
- ⏳ Change Orders
- ⏳ Pick & Return Modes
- ⏳ Student Equipment Requests
- ⏳ Repairs Tracking
- ⏳ Reports & Analytics
- ⏳ Production Calendar
- ⏳ Paperwork Generation
- ⏳ Settings Management
- ⏳ Notification System

### User Roles
1. **Admin**: Full system access
2. **Designer**: Create orders, read-only inventory
3. **Production Audio**: Create orders, pick/return with signature
4. **Student**: Read-only inventory, create requests

### Key Features
- Barcode scanning (Code128 for items, PDF417 for orders)
- Dark/Light mode support
- Signature capture for equipment checkout
- PDF generation for paperwork
- Real-time inventory tracking
- Multi-level approval workflows
- Production calendar integration
- Comprehensive reporting

## Development Phases

### Phase 1: Foundation ✅
- Database schema
- Configuration system
- Basic structure

### Phase 2: Authentication & UI (Required Next)
- Login system
- Session management
- Tabler UI integration
- Dashboard layout

### Phase 3: Inventory System
- Item CRUD operations
- Barcode generation
- Category management
- Serial number tracking

### Phase 4: Shows & Orders
- Show management
- Pull sheets
- Change orders
- Approval workflows

### Phase 5: Pick/Return Modes
- Barcode scanning interface
- Signature capture
- Audio feedback

### Phase 6: Additional Modules
- Student requests
- Repairs tracking
- Calendar events
- Notifications

### Phase 7: Reports & Polish
- Report generation
- PDF exports
- Easter eggs
- Testing & optimization

## Architecture Notes

### Database
- Follows provided schema exactly
- Uses InnoDB for foreign key support
- Includes cascading deletes where appropriate

### Security
- Prepared statements for all queries
- Role-based access control
- Session management
- Input sanitization

### File Organization
```
/
├── config/          # Configuration files
├── includes/        # Reusable PHP includes
├── assets/          # Static assets (CSS, JS, images, sounds)
├── uploads/         # User-uploaded files
├── pdfs/           # Generated PDF documents
├── public/         # Public-facing pages
└── database.sql    # Database schema
```

## API Integrations
- **BarcodeAPI.org**: Barcode generation (cached locally)
- **DiceBear**: User avatar generation

## Contributing
This is a large-scale project requiring significant development effort. Each module should be developed incrementally with proper testing.

## License
Internal use for CMFT Sound Shop

## Support
Contact the development team for assistance with deployment and customization.
