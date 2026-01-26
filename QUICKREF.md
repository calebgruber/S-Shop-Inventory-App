# S-Shop Inventory Quick Reference Guide

## Quick Start

1. **First Time Setup**
   ```bash
   mysql -u voxelnodes_sshop -p < database_schema.sql
   # Upload logo in Settings
   # Add inventory items
   ```

2. **Daily Workflow**
   - Create Show → Build Pull Sheet → Pick Items → Return Items

## Common Tasks

### Create a New Show
```
Shows → Create New Show → Enter details → Submit
```

### Build a Pull Sheet
```
Show Details → Create Pull Sheet → Search items → Add quantities → Build Show
```

### Pick Items for a Show
```
Dashboard → Pick Mode → Enter name → Scan pull sheet barcode → Scan items → Done
```

### Return Items to Shop
```
Dashboard → Return Mode → Scan pull sheet barcode → Scan items
```

### View Reports
```
Reports → Select filter → Generate → Download PDF
```

## Barcode Types

| Type | Format | Example | Use |
|------|--------|---------|-----|
| Items | Code128 | ITM-20260126-A1B2C3D4 | Inventory items |
| Pull Sheets | PDF417 | PS-20260126-A1B2C3D4 | Pull sheet documents |
| Change Orders | PDF417 | CO-20260126-A1B2C3D4 | Change order documents |

## Status Indicators

### Pull Sheets
- **Draft**: Being created, not finalized
- **Finalized**: Ready to pick
- **Picked**: Items have been picked
- **Completed**: All items returned

### Pick Mode Colors
- 🔴 **Red**: Not started (0 picked)
- 🟡 **Yellow**: Partial or extra (some picked, or too many)
- 🟢 **Green**: Complete (exact quantity picked)

### Inventory Status
- **Available**: In shop, ready to use
- **Reserved**: On a finalized pull sheet
- **Checked Out**: Currently with a show

## Keyboard Shortcuts

| Action | Shortcut |
|--------|----------|
| Focus search | Auto-focus on page load |
| Submit scan | Enter key |
| Toggle fullscreen | Fullscreen button in Pick/Return mode |

## File Locations

```
/uploads/         # Uploaded logos
/pdfs/           # Generated PDFs
/assets/         # Application assets
```

## Database Tables Quick Reference

```
shows              # Theatre productions
pull_sheets        # Main pull lists
change_orders      # Modifications to shows
items              # Inventory
item_locations     # Where items are
item_transactions  # Audit log
categories         # Item categories
theatre_spaces     # Performance venues
```

## Common Issues & Solutions

### Can't scan barcode
- ✓ Check field is auto-focused
- ✓ Verify barcode format matches (Code128 for items, PDF417 for documents)
- ✓ Ensure barcode exists in database

### Item shows as unavailable
- ✓ Check available_quantity in inventory
- ✓ Verify not already on another pull sheet
- ✓ Look at item_locations to see where it is

### Pull sheet won't finalize
- ✓ Ensure all items are added
- ✓ Check quantities don't exceed available stock
- ✓ Verify show exists and is active

### Pick mode won't complete
- ✓ All cards must be green (exact quantities)
- ✓ Remove any extra items (yellow cards)
- ✓ Scan all items that show 0/X

### PDF won't generate
- ✓ Check pdfs/ directory is writable (chmod 755)
- ✓ Verify logo exists in assets/
- ✓ Check PHP error log for details

## Tips & Best Practices

1. **Always finalize pull sheets** before picking
2. **Enter your name** when picking so there's an audit trail
3. **Use barcode scanners** for speed and accuracy
4. **Save drafts often** when building pull sheets
5. **Check reports regularly** to track inventory
6. **Return items promptly** after shows close
7. **Use change orders** for mid-show adjustments
8. **Keep categories organized** for easier searching
9. **Print barcode labels** for new items immediately
10. **Back up database regularly**

## Report Types

1. **All Items**: Complete inventory list
2. **By Show**: Items currently out for a specific show
3. **By Theatre Space**: Items in a specific location
4. **Barcode Labels**: Print Avery 8195 labels

## PDF Layouts

### Pull Sheets & Change Orders
```
+----------------------------------+
| Logo                    Barcode  |
| Show Name                        |
| Theatre Space                    |
|                                  |
| Item List:                       |
| - Item 1: Qty 5                  |
| - Item 2: Qty 3                  |
+----------------------------------+
```

### Reports
```
+----------------------------------+
| Logo          Report Title       |
|                                  |
| Filtered by: [Filter Type]       |
|                                  |
| Item List:                       |
| Item | Quantity | Location       |
+----------------------------------+
```

### Avery 8195 Labels (3x4 grid)
```
+-------+-------+-------+
| Item1 | Item2 | Item3 |
| BC128 | BC128 | BC128 |
+-------+-------+-------+
| Item4 | Item5 | Item6 |
| BC128 | BC128 | BC128 |
+-------+-------+-------+
```

## API Endpoints (AJAX)

All pages that handle AJAX requests expect:
```javascript
POST /?ajax=1&action=<action_name>
Content-Type: application/x-www-form-urlencoded

// Example:
fetch('pick_mode.php?pull_sheet_id=1', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'ajax=1&action=scan_item&barcode=ITM-123&picker_name=John'
})
```

## Configuration Files

- **config.php**: Database credentials, paths
- **.htaccess**: Apache security settings
- **database_schema.sql**: Database structure

## Support

For issues:
1. Check TESTING.md for troubleshooting
2. Review PHP error logs
3. Check MySQL error logs
4. Verify file permissions
5. Open GitHub issue with details

## Version Info

- PHP: 7.4+
- MySQL: 5.7+
- UI: Tabler (Bootstrap-based)
- Barcodes: Custom SVG generation
- PDFs: HTML-based printing

---

**Remember**: When in doubt, save as draft! You can always finalize later.
