# Master Barcode List Feature

## Overview
The Master Barcode List feature allows users to generate and print barcode labels for every item in the inventory at once. This is useful for:
- Initial setup when labeling all inventory items
- Reprinting labels after damage or loss
- Creating backup label sheets
- Printing labels for new items in bulk

## Location
The feature is accessible from the **Paperwork** page (`paperwork.php`) under "Paperwork Tools" section.

## Features

### 1. Master Barcode List Page (`master_barcode_list.php`)
- **View All Items**: Displays all inventory items with their barcodes
- **Filtering**: Filter by category to print specific subsets
- **Sorting**: Sort by item name, barcode, or category
- **Print All**: Print all barcodes at once (one label per item)

### 2. Barcode Label Format
- **Dimensions**: 4" x 2" (compatible with Avery 8195 label sheets)
- **Layout**: 2-column grid layout for efficient printing
- **Content**: Each label includes:
  - Item name (bold, 12pt)
  - Code128 barcode image
  - Barcode number (monospace, 10pt)
  - Category name (gray color, 9pt)

### 3. Print Functionality
- Browser-based printing using `window.print()`
- Automatic pagination (spans multiple pages as needed)
- Print-optimized CSS (hides navigation, focuses on labels)
- Page-break protection (labels won't split across pages)

## How to Use

### Accessing the Feature
1. Navigate to **Paperwork** page from the main menu
2. Look for the "Paperwork Tools" section at the top
3. Click the **"View & Print All Barcodes"** button

### Printing All Barcodes
1. On the Master Barcode List page, you'll see all items with preview
2. Optional: Use filters to narrow down items
   - **Category**: Select a specific category
   - **Sort By**: Choose sorting order (name, barcode, category)
   - **Order**: Ascending or Descending
3. Click **"Print All Barcodes"** button
4. Your browser's print dialog will appear
5. Configure print settings:
   - Paper size: Letter (8.5" x 11")
   - Orientation: Portrait
   - Scale: 100%
   - Margins: Default
6. Click "Print" to send to printer

### Filtering Examples
- **Print only microphones**: Select "Microphones" from Category dropdown
- **Alphabetical order**: Sort by "Item Name" in "Ascending" order
- **By barcode number**: Sort by "Barcode" to organize labels sequentially

## Technical Details

### Code Structure
```
master_barcode_list.php
├── Permission check (requires 'paperwork' permission)
├── Filter logic (category, sort, order)
├── Database query (fetch items with categories)
├── HTML/CSS layout
│   ├── Filter controls
│   ├── Info box (item count)
│   └── Barcode grid (2-column)
└── Print styling (@media print)
```

### Database Query
```sql
SELECT i.*, c.name as category_name 
FROM items i 
LEFT JOIN categories c ON i.category_id = c.id 
WHERE 1=1
[AND i.category_id = ?]  -- if category filter applied
ORDER BY [name|barcode|category_name] [ASC|DESC]
```

### Barcode Generation
- Uses existing `generateCode128Barcode()` function from `includes/functions.php`
- Generates Code128 barcodes via barcodeapi.org API
- Falls back to local PHP class if API unavailable
- Returns PNG image data (base64-encoded)

### CSS Grid Layout
- 2 columns per row
- 0.25" gap between labels
- 0.5" padding around grid
- Each label is exactly 4" x 2"
- Compatible with standard Avery label sheets

## Integration with Existing System

### Paperwork Page Updates
The `paperwork.php` page now includes:
- **Paperwork Tools card** with Master Barcode List button
- **Information section** explaining different barcode printing options
- **Clear visual hierarchy** separating tools from show-specific paperwork

### Consistency with Existing Features
- Uses same barcode generation as `item_barcodes.php`
- Uses same permission system (`hasPermission('paperwork')`)
- Uses same database functions (`getDB()`, `fetchAll()`)
- Uses same CSS framework (Tabler UI)
- Uses same barcode format (Code128)

## Testing

### Manual Testing Steps
1. ✅ Verify page loads without errors
2. ✅ Check permission enforcement (requires 'paperwork' access)
3. ✅ Test category filtering
4. ✅ Test sorting options
5. ✅ Verify barcode images load correctly
6. ✅ Test print preview functionality
7. ✅ Check responsive layout
8. ✅ Verify no SQL injection vulnerabilities
9. ✅ Test with empty inventory (displays message)
10. ✅ Test with large inventory (multiple pages)

### Edge Cases Handled
- **Empty inventory**: Shows "No items found" message
- **Invalid category filter**: Falls back to 'all'
- **Invalid sort parameter**: Defaults to 'name'
- **Missing barcode API**: Falls back to local generation
- **Long item names**: Truncated with ellipsis
- **Missing categories**: Shows without category label

## Maintenance

### Future Enhancements (Optional)
- PDF download option (using DompdfWrapper)
- Batch size control (print 10, 20, 50 items at a time)
- Multiple copies per item
- Custom label dimensions
- QR code support
- Export to CSV for external printing services

### Files Modified
- `paperwork.php` - Added Paperwork Tools section
- `master_barcode_list.php` - New file (main feature)

### Dependencies
- Existing: `includes/functions.php`
- Existing: `includes/db.php`
- Existing: `includes/barcode/Code128.php`
- Existing: Tabler UI CSS framework

## Security Considerations
- ✅ Permission check before access
- ✅ SQL injection prevention (parameterized queries)
- ✅ XSS prevention (htmlspecialchars on all output)
- ✅ Input validation (whitelisted sort columns)
- ✅ No file uploads or writes
- ✅ Read-only operation (no data modification)

## Performance
- **Query optimization**: Single query to fetch all items
- **Barcode caching**: Generated once per item during page load
- **Lazy loading**: Barcodes load from API/cache as images
- **Pagination**: Browser handles multiple pages automatically
- **Memory efficient**: PHP releases memory after page generation

## Browser Compatibility
- ✅ Chrome/Edge (tested)
- ✅ Firefox (tested)
- ✅ Safari (tested)
- ✅ Mobile browsers (responsive layout)

## Support
For issues or questions:
1. Check error logs in `logs/` directory
2. Verify database connection in `includes/config.php`
3. Test barcode generation with `test_barcode.php`
4. Check browser console for JavaScript errors
