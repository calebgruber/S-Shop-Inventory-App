# S-Shop Inventory App - Architecture Documentation

## Overview

The S-Shop Inventory application is a self-contained desktop application built with Electron, designed for long-term stability and ease of maintenance. This document provides a comprehensive overview of the system architecture for future developers.

## Design Principles

1. **Offline-First** - All functionality works without internet connection
2. **Self-Contained** - No external dependencies or cloud services
3. **Simple & Clear** - Well-documented, easy to understand code
4. **Durable** - Built to last for years without updates
5. **Barcode-Optimized** - Primary interaction through barcode scanning

## System Architecture

### Three-Layer Architecture

```
┌─────────────────────────────────────────────┐
│          Renderer Process (UI)              │
│  - HTML/CSS/JavaScript                      │
│  - Tabler UI Framework                      │
│  - Page Modules (Inventory, Shows, etc.)   │
└─────────────────┬───────────────────────────┘
                  │ IPC Communication
┌─────────────────▼───────────────────────────┐
│          Main Process (Electron)            │
│  - Window Management                        │
│  - IPC Handlers                             │
│  - Database Coordination                    │
└─────────────────┬───────────────────────────┘
                  │ SQL Operations
┌─────────────────▼───────────────────────────┐
│          Data Layer (SQLite)                │
│  - Local Database File                      │
│  - Schema & Migrations                      │
│  - Activity Logging                         │
└─────────────────────────────────────────────┘
```

## Core Components

### 1. Main Process (`src/main.js`)

**Responsibilities:**
- Create and manage application window
- Initialize SQLite database
- Handle IPC (Inter-Process Communication) requests
- Coordinate between UI and database
- Manage application lifecycle

**Key Functions:**
- `createWindow()` - Creates the main application window
- `initDatabase()` - Initializes and connects to SQLite database
- IPC handlers - Process requests from renderer (e.g., `inventory:getAll`)

### 2. Database Layer (`src/database/db.js`)

**Responsibilities:**
- SQLite database management
- CRUD operations for all entities
- Data integrity and validation
- Query optimization

**Database Tables:**

**items** - Inventory items
- Supports both quantity-based and serialized items
- Fields: id, name, description, category, manufacturer, model, serial_number, barcode, quantity_total, quantity_available, location, status, notes

**shows** - Theatre productions
- Fields: id, name, description, venue, start_date, end_date, status, notes

**pull_sheets** - Equipment checkout lists
- Fields: id, show_id, name, status, pulled_date, pulled_by, notes

**pull_sheet_items** - Items on pull sheets (junction table)
- Fields: id, pull_sheet_id, item_id, quantity_requested, quantity_pulled, status, notes

**change_orders** - Equipment changes during shows
- Fields: id, show_id, pull_sheet_id, type, description, requested_by, status

**returns** - Equipment returns
- Fields: id, pull_sheet_id, return_date, returned_by, status, notes

**activity_log** - Audit trail
- Fields: id, action_type, description, user, data, created_at

**Key Methods:**
- `getAllItems()` - Retrieve all inventory items
- `createItem()` - Add new inventory item
- `updateItem()` - Modify existing item
- `checkItemAvailability()` - Verify item availability

### 3. Renderer Process (`src/renderer/`)

**UI Structure:**
- `index.html` - Main HTML shell with Tabler UI
- `styles/main.css` - Custom styling
- `scripts/` - JavaScript modules

**JavaScript Modules:**

**ipc.js** - IPC Communication
- Wraps all Electron IPC calls
- Exposes clean API to UI modules
- Example: `window.api.inventory.getAll()`

**navigation.js** - Page Routing
- Manages page transitions
- Updates active navigation state
- Loads and initializes page modules

**barcode.js** - Barcode Scanning
- Keyboard wedge mode detection
- Barcode lookup and processing
- F1 hotkey for manual scan entry

**inventory.js** - Inventory Management
- Item listing, search, filtering
- Add/edit/delete operations
- Availability checking

**shows.js** - Show Management
- Show creation and editing
- Status tracking
- Pull sheet creation

**pullsheets.js** - Pull Sheet Workflows
- Pull sheet management
- Item scanning for checkout
- PDF generation

**returns.js** - Return Processing
- Return workflow
- Item scanning for check-in
- Return completion

**reports.js** - Reports & Analytics
- Shortage detection
- Activity logs
- Quick statistics

**main.js** - Application Initialization
- Initializes all modules
- Sets up event listeners
- Displays welcome message

### 4. Utility Modules (`src/utils/`)

**pdfGenerator.js** - PDF Generation
- Pull sheet PDFs with barcodes
- Inventory reports
- Return summaries
- Uses jsPDF and jsPDF-autotable

**logger.js** - Activity Logging
- Structured logging
- File and database logging
- Export functionality

**backup.js** - Data Management
- Database backup creation
- Backup restoration
- Export/import utilities

## Data Flow

### Example: Adding an Inventory Item

1. **User Action** - User fills out form in UI and clicks "Save"
2. **Event Handler** - `saveInventoryItem()` in `inventory.js` collects form data
3. **IPC Call** - `window.api.inventory.create(itemData)` sends data to main process
4. **IPC Handler** - `ipcMain.handle('inventory:create')` in `main.js` receives request
5. **Database Operation** - `database.createItem(item)` inserts into SQLite
6. **Response** - New item with ID returned through IPC
7. **UI Update** - `inventory.js` refreshes the inventory list

### Example: Barcode Scanning

1. **Scanner Input** - Zebra scanner sends keystrokes (keyboard wedge)
2. **Input Detection** - `handleBarcodeInput()` in `barcode.js` buffers characters
3. **Timeout Processing** - After 100ms pause, barcode is processed
4. **Database Lookup** - `window.api.inventory.getByBarcode(barcode)` called
5. **Result Display** - Item details shown or error message displayed

## Barcode Workflows

### Keyboard Wedge Mode

Zebra DS-series scanners act as a keyboard:
- Scan sends characters rapidly (< 100ms between keystrokes)
- `barcode.js` detects rapid input and buffers
- After timeout, processes as complete barcode
- Works in any input context (global listener)

### Show Barcode System

Pull sheet PDFs include unique show barcodes:
- Format: `SHOW-{id}-{timestamp}`
- Scanning recalls the show
- Enables quick access to pull sheets

## PDF Generation

### Pull Sheet PDFs

Structure:
1. Header with show information
2. Unique show barcode for scanning
3. Table of items with checkboxes
4. Footer with page numbers and timestamp

Files saved to: `%APPDATA%/s-shop-inventory-app/pdfs/`

## Database Management

### Schema Migrations

For future schema changes:

1. **Add new column** - Use `ALTER TABLE ADD COLUMN` with default value
2. **Create new table** - Use `CREATE TABLE IF NOT EXISTS`
3. **Backward compatibility** - Always provide defaults for new fields
4. **Version tracking** - Consider adding schema_version table

Example:
```javascript
// Adding a new column to items table
db.exec(`
  ALTER TABLE items 
  ADD COLUMN last_maintenance_date DATE DEFAULT NULL
`);
```

### Backup Strategy

Automatic backups:
- On application start (optional)
- Before schema migrations
- Manual via UI

Backup location: `%APPDATA%/s-shop-inventory-app/backups/`

## Error Handling

### UI Layer
- Try-catch blocks around all async operations
- User-friendly error messages via modals
- Console logging for debugging

### Main Process
- Database errors caught and returned via IPC
- Window errors caught in ready handler
- Graceful degradation when possible

### Database Layer
- SQLite errors caught and re-thrown with context
- Foreign key constraint violations handled
- Unique constraint violations caught

## Performance Considerations

### Database Optimization
- Indexes on frequently queried columns (barcode, serial_number)
- Write-Ahead Logging (WAL) mode for concurrency
- Prepared statements for repeated queries

### UI Optimization
- Lazy loading of page content
- Debounced search inputs
- Efficient DOM manipulation

### Memory Management
- Database connections closed on app exit
- Event listeners removed when pages change
- Large datasets paginated

## Security Considerations

### Data Protection
- Local database only (no network exposure)
- File system permissions via OS
- No password storage required

### Input Validation
- SQL injection prevented via parameterized queries
- XSS prevented via text nodes (not innerHTML with user data)
- File path validation for exports

## Testing Strategy

### Manual Testing
- Test each workflow end-to-end
- Verify barcode scanning
- Check PDF generation
- Test offline functionality

### Future Automated Testing
- Unit tests for database operations
- Integration tests for IPC handlers
- UI tests with Playwright/Spectron

## Deployment

### Building for Windows

```bash
npm run build
```

Output:
- `dist/S-Shop Inventory Setup.exe` - Installer
- Includes all dependencies bundled
- No installation of Node.js required

### Installation Package Contents
- Electron runtime
- Node.js runtime
- Application code
- SQLite binaries
- All npm dependencies

## Maintenance Guide

### Common Modifications

**Adding a new inventory field:**
1. Update database schema in `db.js`
2. Add column to table definition
3. Update `createItem()` and `updateItem()` methods
4. Add form field in `inventory.js`
5. Update PDF generation if needed

**Adding a new report:**
1. Create query method in `db.js`
2. Add IPC handler in `main.js`
3. Create render function in `reports.js`
4. Add UI controls and display

**Modifying barcode format:**
1. Update `generateBarcode()` in `pdfGenerator.js`
2. Modify parsing logic in `barcode.js`
3. Update validation if needed

### Troubleshooting

**Database locked error:**
- Close all database connections properly
- Check for long-running transactions
- Enable WAL mode (already configured)

**Barcode not scanning:**
- Verify scanner in keyboard wedge mode
- Check BARCODE_TIMEOUT constant (100ms)
- Test scanner with notepad

**PDF not generating:**
- Check user data directory exists
- Verify jsPDF dependencies installed
- Check file permissions

## Future Enhancement Ideas

1. **Serialized Item Instances** - Individual tracking records for each serial number
2. **Maintenance Scheduler** - Calendar for equipment maintenance
3. **Reporting Dashboard** - Visual charts and graphs
4. **Multi-User Support** - User accounts and permissions
5. **Network Sync** - Optional cloud backup
6. **Mobile Companion** - Barcode scanning from phone
7. **Equipment Photos** - Image storage for items
8. **QR Code Support** - Alternative to linear barcodes

## Conclusion

This architecture provides a solid foundation for long-term operation. The separation of concerns, clear data flow, and comprehensive documentation ensure that future developers can maintain and extend the system with confidence.

For questions about specific implementation details, refer to the inline comments in the source code.
