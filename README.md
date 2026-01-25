# S-Shop Inventory App

**CMFT Sound Shop Inventory - Theatre Sound Equipment Management System**

A self-contained Windows Electron application for managing theatre sound shop inventory with barcode-driven workflows, SQLite database, and offline-first design.

## Features

### Core Functionality
- **Barcode-Driven Workflows** - Optimized for Zebra DS-series scanners in keyboard-wedge mode
- **Dual Inventory Tracking** - Both quantity-based (cables, adapters) and serialized items (microphones, transmitters)
- **Show Management** - Create shows, build pull sheets, manage equipment lifecycle
- **Change Orders** - Add or remove items during show runs
- **Return Processing** - Guided workflows with item matching and accountability
- **Offline Operation** - Local SQLite database, no cloud dependencies

### Advanced Features
- **PDF Generation** - Pull sheets with unique show barcodes, inventory reports, return summaries
- **Real-Time Status** - Availability checking, shortage alerts, item status tracking
- **Maintenance Tracking** - Flag serialized gear for repair, exclude from availability
- **Activity Logging** - Complete audit trail of all actions
- **Data Management** - Backup, restore, and export utilities
- **Auto-Updates** - Automatic update checking and installation from GitHub releases

## Installation

### Prerequisites
- Node.js 16.x or higher
- Windows 10/11 (primary target platform)
- Zebra DS-series barcode scanner (optional, keyboard input also supported)

### Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/calebgruber/S-Shop-Inventory-App.git
   cd S-Shop-Inventory-App
   ```

2. **Install dependencies**
   ```bash
   npm install
   ```

3. **Run in development mode**
   ```bash
   npm run dev
   ```

4. **Build for Windows**
   ```bash
   npm run build
   ```

## Auto-Updates

The application automatically checks for updates from GitHub releases:

1. **Automatic Check** - On startup (after 10 seconds), the app silently checks for updates
2. **Manual Check** - Click "Check for Updates" in the Tools menu or footer
3. **Download Prompt** - If an update is available, you'll be asked if you want to download it
4. **Background Download** - Updates download in the background while you work
5. **Install on Restart** - Updates are installed when you close and restart the app

### Publishing Updates

To release a new version:

1. Update version in `package.json`
2. Commit and tag the release:
   ```bash
   git commit -am "Release v1.1.0"
   git tag v1.1.0
   git push origin main --tags
   ```
3. Build and create GitHub release:
   ```bash
   npm run build
   # Upload dist/S-Shop Inventory Setup.exe to GitHub release
   ```
4. Users will be notified of the update automatically

The updater uses `electron-updater` which integrates with GitHub Releases. When you create a new release on GitHub and attach the installer, all installed apps will detect and offer to download it.

## Usage

### Getting Started

1. **Add Inventory Items**
   - Navigate to Inventory → Click "Add Item"
   - Enter item details (name, category, barcode, quantity)
   - For serialized items, add serial numbers
   - Use barcode scanner or type manually

2. **Create a Show**
   - Navigate to Shows → Click "Create Show"
   - Enter show name and theatre/venue
   - Set dates and status

3. **Build Pull Sheet**
   - From show details → Click "Pull Sheet"
   - Scan items with barcode scanner (F1 or keyboard wedge mode)
   - System checks availability and prevents over-allocation
   - Generate PDF with unique show barcode

4. **Process Returns**
   - Navigate to Returns → "Start Return"
   - Scan items back in
   - System matches serialized items individually
   - Complete return when all items accounted for

### Barcode Scanning

- **Press F1** to open barcode scan modal
- **Keyboard Wedge Mode** - Scanner acts as keyboard, automatic detection
- **Manual Entry** - Type barcodes manually if needed
- **Show Barcodes** - Scan pull sheet PDFs to quickly recall shows

### Keyboard Shortcuts

- `F1` - Open barcode scan modal
- `Ctrl+S` - Save current form (where applicable)
- `Esc` - Close modals

## Architecture

### Technology Stack
- **Electron** - Desktop application framework
- **Tabler UI** - Modern, clean Bootstrap-based interface
- **SQLite (better-sqlite3)** - Embedded local database
- **jsPDF** - PDF generation
- **JsBarcode** - Barcode generation

### Project Structure
```
S-Shop-Inventory-App/
├── src/
│   ├── main.js                 # Main Electron process
│   ├── database/
│   │   └── db.js              # SQLite database operations
│   ├── renderer/
│   │   ├── index.html         # Main UI
│   │   ├── styles/
│   │   │   └── main.css       # Custom styles
│   │   └── scripts/
│   │       ├── ipc.js         # IPC communication
│   │       ├── navigation.js  # Page routing
│   │       ├── barcode.js     # Barcode handling
│   │       ├── inventory.js   # Inventory management
│   │       ├── shows.js       # Show management
│   │       ├── pullsheets.js  # Pull sheet workflows
│   │       ├── returns.js     # Return processing
│   │       ├── reports.js     # Reports & analytics
│   │       └── main.js        # App initialization
│   └── utils/
│       ├── pdfGenerator.js    # PDF generation utilities
│       ├── logger.js          # Activity logging
│       └── backup.js          # Backup/restore utilities
├── assets/                     # Icons and resources
├── package.json
└── README.md
```

### Database Schema

**Items** - Inventory items with quantity and serial number tracking
- Supports both quantity-based and serialized items
- Tracks availability, location, status

**Shows** - Theatre productions
- Basic show information, dates, status

**Pull Sheets** - Equipment checkout lists
- Associated with shows
- Tracks pulled items and status

**Change Orders** - Modifications during show runs
- Add/remove items
- Complete audit trail

**Returns** - Equipment returns after shows
- Item-by-item matching
- Condition tracking

**Activity Log** - Complete audit trail
- All actions logged with timestamps
- User attribution

## Data Management

### Backup
- **Automatic backups** on application start (optional)
- **Manual backup** via Tools → Backup Database
- Backups stored in user data directory

### Export
- **Export database** to external location
- **PDF reports** for inventory, pull sheets, returns
- **Activity logs** exportable to JSON

### Restore
- Import database from backup file
- Pre-restore backup created automatically

## Long-Term Stability

This application is designed for long-term reliability:

- **No cloud dependencies** - All data stored locally
- **SQLite migrations** - Safe schema evolution
- **Well-documented code** - Clear comments and structure
- **Standard technologies** - Widely supported stack
- **Bundled dependencies** - No external API calls
- **Static assets** - UI built from local files

Future students can maintain and extend the system with basic JavaScript knowledge.

## Development

### Adding New Features

1. **Database changes** - Update schema in `src/database/db.js`
2. **UI pages** - Create new module in `src/renderer/scripts/`
3. **IPC handlers** - Add to `src/main.js` for database operations
4. **Styling** - Extend `src/renderer/styles/main.css`

### Code Standards

- Clear variable and function names
- Comprehensive comments for complex logic
- Consistent code formatting
- Error handling for all operations
- Logging for audit trails

## Troubleshooting

### Database Issues
- Check user data directory: `%APPDATA%/s-shop-inventory-app/`
- Database file: `inventory.db`
- Restore from backup if corrupted

### Barcode Scanner
- Ensure scanner is in keyboard wedge mode
- Test with notepad to verify scanner output
- Check USB connection

### PDF Generation
- PDFs saved to user data directory under `pdfs/`
- Check file permissions
- Verify jsPDF dependencies installed

## License

MIT License - Free to use and modify

## Support

For questions or issues:
- Check documentation in code comments
- Review database schema in `db.js`
- Consult SQLite and Electron documentation

---

**Built for CMFT Theatre Sound Shop**

*A durable, intuitive solution for managing theatre sound inventory*
 
