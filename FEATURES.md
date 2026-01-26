# Features Documentation

## Complete Feature List

### 1. Dashboard (index.php)
- **Statistics Cards**
  - Active shows count
  - Pending picks count
  - Pending returns count
  - Total items count
  
- **Quick Action Buttons**
  - Pick Mode
  - Return Mode
  - Pullsheets
  - Change Orders
  - Reports
  - Shows

- **Recent Activity**
  - Pending pullsheets list
  - Active shows list

### 2. Settings Management (settings.php)
- **Application Settings**
  - Application name configuration
  - Logo upload for PDFs
  
- **Categories Management**
  - Add categories with descriptions
  - Delete categories
  - View all categories
  
- **Theatre Spaces Management**
  - Add theatre spaces with descriptions
  - Delete spaces
  - View all spaces

### 3. Items Management

#### Items List (items.php)
- View all items in a table
- Real-time search/filter
- Columns: Name, Barcode, Category, Type, In Stock, Total
- Quick actions: Edit, Print Barcodes

#### Add/Edit Item (item_edit.php)
- Create new items
- Edit existing items
- Fields:
  - Name (required)
  - Description
  - Auto-generated barcode (for new items)
  - Category dropdown
  - Tracking type (quantity or serial)
  - Total quantity
  - In stock quantity
- Save and return to list
- Direct link to barcode printing

#### Print Barcodes (item_barcodes.php)
- Generate Code128 barcodes
- Print multiple copies (configurable quantity)
- Avery 8195 label template format
- Shows item name and barcode
- Print-ready layout

### 4. Shows Management

#### Shows List (shows.php)
- View all shows
- Columns: Name, Shop Lead, Designer, Theatre Space, Status, Created Date
- Quick actions:
  - Edit show
  - Create pullsheet
  - Create change order
- Status badges (Active, Completed, Cancelled)

#### Create Show (show_create.php)
- Fields:
  - Show name (required)
  - Shop lead
  - Designer
  - Theatre space (dropdown)
- Automatically set to "Active" status

#### Edit Show (show_edit.php)
- Update show details
- Change status
- Delete show (with confirmation)
- View associated pullsheets
- View associated change orders
- Quick action buttons for creating pullsheets/change orders

### 5. Pullsheets Management

#### Pullsheets List (pullsheets.php)
- Card-based layout
- Shows:
  - Show name
  - Status (Draft, Finalized, Picked, Completed)
  - Created by
  - Created date
  - Picked by (when applicable)
- Quick actions: View, Edit (if draft)

#### Create Pullsheet (pullsheet_create.php)
- Select show
- Enter creator name
- Only one pullsheet per show
- Redirects to edit mode after creation

#### Edit Pullsheet (pullsheet_edit.php)
- **Only editable in Draft status**
- Barcode search/scan input with auto-focus
- Add items with modal showing:
  - Item details
  - Current stock level
  - Quantity needed input
- View all items in pullsheet
- Shows availability status for each item
- Remove items button
- **Build Show (Finalize)** button:
  - Marks items as RESERVED
  - Generates PDF417 barcode
  - Creates PDF document
  - Changes status to "Finalized"

#### View Pullsheet (pullsheet_view.php)
- Display PDF417 barcode
- Show all items with:
  - Quantity needed
  - Quantity picked
  - Completion status
- Download PDF button
- Show details sidebar:
  - Status
  - Created by/date
  - Picked by/date (if applicable)
  - Show information

### 6. Pick Mode (pick_mode.php)

**Fullscreen Interface**

- **Start Pick Screen:**
  - Enter picker name
  - Scan pullsheet/change order barcode
  - Auto-focus on barcode field
  - Automatically detects and resumes partial picks

- **Picking Interface:**
  - Large barcode input with auto-focus
  - **Draft Resume Alert:** Shows when resuming a partial pick with timestamp
  - Item cards showing:
    - Item name and barcode
    - Scanned count / Needed count
    - Color-coded status:
      - RED: Incomplete (not enough scanned)
      - GREEN: Complete (exact match)
      - YELLOW: Overage (too many scanned)
  - Real-time updates as items are scanned
  - Sound effects:
    - Success ding on correct scan
    - Error buzz on invalid scan

- **Draft/Partial Completion:**
  - **Save as Draft button** (yellow/warning style)
  - Saves current scanning progress to database
  - Sets `is_partial = 1` and `partial_saved_at = NOW()`
  - Preserves `quantity_picked` values for each item
  - Status remains "finalized" (not changed to "picked")
  - Can resume later from the same state
  - Shows info alert when resuming: "Resuming partial pick from [date]"

- **Completion:**
  - Complete button (disabled until all items correct)
  - Updates database:
    - Marks quantities picked
    - Changes allocations to "checked_out"
    - Updates pullsheet status to "picked"
    - Records picker name and timestamp
    - Clears partial flags (`is_partial = 0`, `partial_saved_at = NULL`)

### 7. Return Mode (return_mode.php)

**EXACT SAME UI as Pick Mode but for returns**

- Scan pullsheet barcode to start
- Automatically detects and resumes partial returns
- **Draft Resume Alert:** Shows when resuming a partial return with timestamp
- Scan items to return them
- **Save as Draft button** for partial returns
- Updates:
  - Returns items to "in_stock"
  - Removes allocations
  - Marks pullsheet as "completed"
  - Clears partial flags on completion
- Sound effects for feedback

### 8. Change Orders

#### Change Orders List (change_orders.php)
- Card-based layout
- Shows status (Draft, Finalized, Processed, Completed)
- View/Edit buttons

#### Create Change Order (change_order_create.php)
- Select show
- Enter creator name
- Multiple change orders allowed per show

#### Edit Change Order (change_order_edit.php)
- Scan items to add
- Modal for each item:
  - Type selection (Add or Remove)
  - Quantity input
- View all items in change order
- Finalize button:
  - Generates PDF417 barcode
  - Locks change order
  - Ready for processing in Pick/Return Mode

### 9. Reports (reports.php)

#### Inventory Report
- Complete list of all items
- Shows:
  - Name
  - Category
  - Barcode
  - In Stock quantity
  - Total quantity
- Sortable table
- Print button

#### Items by Show
- Groups items by show
- Shows:
  - Item name
  - Quantity allocated
  - Status (Reserved, Checked Out, etc.)
- Filterable by show

#### Items by Theatre Space
- Groups items by location
- Shows:
  - Item name
  - Quantity
  - Associated show
- Only shows checked out items

### 10. Technical Features

#### Barcode Generation
- **Code128** for items (standard linear barcode)
- **PDF417** for pullsheets and change orders (2D barcode, more data)
- High-quality PNG generation
- Print-ready formats

#### PDF Generation (TCPDF)
- Professional layout
- Logo in upper left
- Barcode in upper right
- Comprehensive item tables
- Show details header

#### Database Features
- **Full referential integrity**
- **Cascading deletes** where appropriate
- **Item allocations tracking**:
  - Links items to shows
  - Links items to theatre spaces
  - Tracks status (reserved, checked_out, in_shop)
  - Maintains history

#### User Interface
- **Tabler UI Framework** (Bootstrap 5 based)
- **Dark/Light Mode Toggle**:
  - Persists in localStorage
  - Smooth transitions
  - Fully styled for both modes
- **Auto-focus on barcode fields**
- **Responsive design** (mobile-friendly)
- **Toast notifications** for actions
- **Confirmation dialogs** for destructive actions

#### Security Features
- PDO prepared statements (SQL injection protection)
- Input validation
- CSRF protection ready
- Session management
- Error handling

#### Stock Management
- **Automatic stock tracking**
- **Reservation system**
- **Check-out/Check-in tracking**
- **Available vs. Total quantities**
- **Allocation history**

#### Search & Filter
- Real-time search on items page
- Barcode scanning support
- Quick search across all pages

#### Workflow
1. Create Show
2. Create Pullsheet (add items)
3. Finalize Pullsheet (reserves items)
4. Pick Mode (scan items, check them out)
5. Return Mode (scan items, return to inventory)
6. Create Change Orders as needed
7. View Reports for inventory status

## Browser Compatibility
- Chrome/Edge (Recommended)
- Firefox
- Safari
- Mobile browsers

## Barcode Scanner Compatibility
- USB barcode scanners (keyboard emulation)
- Bluetooth barcode scanners
- Camera-based scanners (if configured)

## Print Compatibility
- **Barcode Labels**: Avery 8195 (4" x 2" labels)
- **PDFs**: Standard letter size
- Works with any standard printer
