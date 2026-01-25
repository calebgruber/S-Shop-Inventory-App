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
- **Node.js 16.x or higher** - [Download here](https://nodejs.org/)
- Windows 10/11 or macOS 10.13+ (High Sierra or later)
- Zebra DS-series barcode scanner (optional, keyboard input also supported)

### ✨ Easier Installation with Electron Forge

This project now uses **Electron Forge**, which automatically handles native module compilation! This means:
- ✅ **No manual Visual Studio Build Tools installation required** (Forge handles it)
- ✅ Simplified build commands
- ✅ Better cross-platform support
- ✅ Automatic native module rebuilding

### Quick Start

**Option 1: Download Pre-Built Installer (Easiest)** ⭐
> **Note**: Pre-built releases are not yet available. Once created, this will be the easiest installation method with no build tools required.

**Option 2: Build from Source** (Current Method)

1. **Clone the repository**
   ```bash
   git clone https://github.com/calebgruber/S-Shop-Inventory-App.git
   cd S-Shop-Inventory-App
   ```

2. **Install dependencies**
   ```bash
   npm install
   ```
   
   Electron Forge will automatically rebuild native modules. You may see warnings about deprecated packages - these are normal and can be ignored.

3. **Run in development mode**
   ```bash
   npm run dev
   # or
   npm start
   ```

4. **Build installers** (optional)
   ```bash
   # Package the app (creates distributable but not installer)
   npm run package
   
   # Create installers for your platform
   npm run make
   ```
   
   The `make` command will create:
   - **Windows**: Squirrel installer in `out/make/squirrel.windows/`
   - **macOS**: ZIP archive in `out/make/`
   - **Linux**: DEB and RPM packages in `out/make/`

### Windows Installation Troubleshooting

⚠️ **If you still encounter build errors**, Electron Forge should handle most issues automatically, but if you see persistent `gyp ERR! find VS` errors:

#### Install Visual Studio Build Tools (if needed)

1. **Download Visual Studio Build Tools 2022**:
   - Go to: https://visualstudio.microsoft.com/downloads/
   - Scroll down to "All Downloads" → "Tools for Visual Studio"
   - Click "Build Tools for Visual Studio 2022" → Download

2. **Install with C++ workload**:
   - Run the installer
   - Check the box for **"Desktop development with C++"**
   - Click Install (this will download ~7GB)

3. **Restart your terminal and try again**:
   ```bash
   npm install
   ```
   
   **Note**: You may see deprecation warnings during install - these are normal and safe to ignore. The important thing is that the install completes without errors.

#### Alternative: Use Pre-Built Releases (When Available)

**Note**: This project doesn't have pre-built releases yet because it's still in development. Once the first release is created, you'll be able to download a ready-to-use `.exe` installer that doesn't require any build tools.

To create your first release:
1. Build the project (after installing VS Build Tools above)
2. Run `npm run build:win`
3. The installer will be in `dist/S-Shop Inventory Setup.exe`
4. You can then distribute this installer to others

#### Other Solutions (If Build Tools Don't Work)

**Solution 3: Use a different SQLite library** (Advanced)
If you absolutely cannot install Visual Studio Build Tools, you could modify the project to use `sql.js` instead of `better-sqlite3`, but this would require code changes and is not recommended.

**Solution 4: Use WSL2 (Windows Subsystem for Linux)**
Install WSL2 and run the project from Linux - no Visual Studio needed.

**Solution 5: Clear Cache and Retry**
```bash
npm cache clean --force
rm -rf node_modules package-lock.json
npm install
```
npm install
```

**Still Having Issues?**
- Make sure you're running Command Prompt or PowerShell as **Administrator**
- Ensure Node.js is properly installed: `node --version` should show v16 or higher
- Check that Python is available: `python --version` (required for node-gyp)
- Try closing any antivirus software temporarily during installation

## Platform-Specific Notes

### Windows
- **Installer**: NSIS installer (.exe)
- **Database Location**: `%APPDATA%/s-shop-inventory-app/`
- **Keyboard Shortcuts**: Work as documented

### macOS
- **Installer**: DMG disk image (.dmg)
- **Database Location**: `~/Library/Application Support/s-shop-inventory-app/`
- **Keyboard Shortcuts**: 
  - `F1` for barcode scanning (may need to disable macOS function keys)
  - Or use the scan button in the navigation bar
- **Note**: On first run, you may need to allow the app in System Preferences → Security & Privacy

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
- **Windows**: Check `%APPDATA%/s-shop-inventory-app/`
- **macOS**: Check `~/Library/Application Support/s-shop-inventory-app/`
- Database file: `inventory.db`
- Restore from backup if corrupted

### Barcode Scanner
- Ensure scanner is in keyboard wedge mode
- Test with notepad (Windows) or TextEdit (macOS) to verify scanner output
- Check USB connection
- **macOS**: If F1 doesn't work, use the scan button or disable macOS function keys in System Preferences

### PDF Generation
- **Windows**: PDFs saved to `%APPDATA%/s-shop-inventory-app/pdfs/`
- **macOS**: PDFs saved to `~/Library/Application Support/s-shop-inventory-app/pdfs/`
- Check file permissions
- Verify jsPDF dependencies installed

### macOS-Specific Issues
- **"App can't be opened"**: Go to System Preferences → Security & Privacy and click "Open Anyway"
- **F1 key not working**: Disable function keys or use the scan button in the toolbar
- **Permission denied**: Grant Full Disk Access in System Preferences → Security & Privacy → Privacy

### Installation Issues
- **npm install fails on Windows**: See [Windows Installation Troubleshooting](#windows-installation-troubleshooting) section above
- **better-sqlite3 build errors**: Download the pre-built installer instead, or install Windows Build Tools
- **Permission errors**: Run Command Prompt or PowerShell as Administrator
- **Module not found errors**: Try `npm cache clean --force` then `npm install` again

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
 
