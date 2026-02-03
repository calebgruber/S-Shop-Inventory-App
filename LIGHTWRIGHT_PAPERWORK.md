# LightWright-Style Paperwork Documentation

## Overview

The S-Shop Inventory App now generates professional, LightWright-inspired paperwork for pullsheets and change orders. This document describes the new formatting and features.

## What is LightWright?

LightWright is industry-standard lighting design software used in theatrical productions. It produces clean, professional paperwork with:
- Bordered tables for clarity
- Gray header rows for visual hierarchy
- Organized sections by category/type
- Professional fonts and spacing
- Signature lines for authorization
- Clear column alignment

## Implementation

### Enhanced PDF Features

The `SimplePDF.php` class now includes these LightWright-style methods:

#### Table Methods
- `addTableCell()` - Draws bordered cells with text and alignment
- `addTableHeader()` - Creates gray-background header rows
- `addTableRow()` - Draws complete table rows with borders

#### Layout Methods
- `addInfoBox()` - Professional information boxes with title bars
- `addSignatureLine()` - Signature lines with labels
- `addPageNumber()` - Page numbering
- `setGray()` - Set gray levels for backgrounds

### Pullsheet Format

**Layout:**
```
┌─────────────────────────────────────────────────┐
│ CMFT Sound Shop        EQUIPMENT PULL SHEET     │
│                                    [Barcode]     │
├─────────────────────────────────────────────────┤
│ ┌─────────────────┐                            │
│ │ Show Information│                            │
│ ├─────────────────┤                            │
│ │ Production: xxx │                            │
│ │ Designer:   xxx │                            │
│ │ Shop Lead:  xxx │                            │
│ │ Date:       xxx │                            │
│ │ Status:     xxx │                            │
│ └─────────────────┘                            │
├─────────────────────────────────────────────────┤
│ ┌───────────── CATEGORY NAME ─────────────────┐│
│ ├──┬──────────┬─────────┬────┬────────┬──────┤│
│ │#│Description│Barcode  │Qty│Location│Notes │││
│ ├──┼──────────┼─────────┼────┼────────┼──────┤│
│ │1│ Item 1   │BAR-001  │ 5 │ A-12   │      │││
│ │2│ Item 2   │BAR-002  │ 2 │ B-3    │      │││
│ └──┴──────────┴─────────┴────┴────────┴──────┘│
├─────────────────────────────────────────────────┤
│ CHECKOUT AUTHORIZATION                          │
│                                                 │
│ Pulled By / Date: ________________              │
│                                                 │
│ Authorized By / Date: ________________          │
│                                                 │
│                                    Page 1 of 1  │
└─────────────────────────────────────────────────┘
```

**Features:**
- Items grouped by category
- Gray category headers
- Bordered table with 6 columns
- Professional spacing
- Signature lines for authorization
- Barcode in top right corner

### Change Order Format

**Layout:**
```
┌─────────────────────────────────────────────────┐
│ CMFT Sound Shop    EQUIPMENT CHANGE ORDER       │
│                                    [Barcode]     │
├─────────────────────────────────────────────────┤
│ ┌─────────────────┐                            │
│ │ Show Information│                            │
│ └─────────────────┘                            │
├─────────────────────────────────────────────────┤
│ ┌───────────── ITEMS TO ADD ──────────────────┐│
│ ├──┬──────────┬─────────┬────┬────────┬──────┤│
│ │#│Description│Barcode  │Qty│Category│Notes │││
│ ├──┼──────────┼─────────┼────┼────────┼──────┤│
│ │1│ Item 1   │BAR-001  │+5 │Audio   │      │││
│ └──┴──────────┴─────────┴────┴────────┴──────┘│
│                                                 │
│ ┌───────────── ITEMS TO REMOVE ───────────────┐│
│ ├──┬──────────┬─────────┬────┬────────┬──────┤│
│ │#│Description│Barcode  │Qty│Category│Notes │││
│ ├──┼──────────┼─────────┼────┼────────┼──────┤│
│ │2│ Item 2   │BAR-002  │-3 │Cables  │      │││
│ └──┴──────────┴─────────┴────┴────────┴──────┘│
├─────────────────────────────────────────────────┤
│ CHANGE AUTHORIZATION                            │
│                                                 │
│ Processed By / Date: ________________           │
│                                                 │
│ Authorized By / Date: ________________          │
│                                                 │
│                                    Page 1 of 1  │
└─────────────────────────────────────────────────┘
```

**Features:**
- Separated "ITEMS TO ADD" and "ITEMS TO REMOVE" sections
- Quantity shows +/- prefix for clarity
- Same professional table format
- Signature lines for authorization
- Barcode preserved

## Key Differences from Previous Format

### Before:
- Plain text layout
- No cell borders
- Simple line separators
- Basic column alignment
- No visual hierarchy
- No signature section

### After (LightWright-style):
- ✓ Professional bordered tables
- ✓ Gray header rows
- ✓ Category-based organization
- ✓ Information boxes with structured data
- ✓ Signature lines with labels
- ✓ Consistent column widths
- ✓ Professional spacing
- ✓ Page numbers
- ✓ Clear visual hierarchy

## Barcode Integration

**Preserved Features:**
- PDF417 barcode remains in top right corner
- Barcode reference ID displayed
- Scannable from printed documents
- Same size and position as before

## Column Specifications

### Pullsheet Columns:
1. **#** (30px, center) - Item number
2. **Item Description** (200px, left) - Full item name
3. **Barcode** (90px, left) - Item barcode
4. **Qty** (40px, center) - Quantity needed
5. **Location** (80px, left) - Storage location
6. **Notes/Picked** (92px, left) - Handwritten notes field

### Change Order Columns:
1. **#** (30px, center) - Item number
2. **Item Description** (200px, left) - Full item name
3. **Barcode** (90px, left) - Item barcode
4. **Qty** (40px, center) - Quantity with +/- prefix
5. **Category** (90px, left) - Item category
6. **Notes** (82px, left) - Handwritten notes field

## Testing

### Test Script
Use `test_lightwright_pdf.php` to generate sample PDFs:

```bash
# Access via web browser:
http://your-domain.com/test_lightwright_pdf
```

The script will:
1. Find a finalized pullsheet
2. Generate LightWright-style PDF
3. Provide download link
4. Repeat for change orders
5. Display feature checklist

### Manual Testing
1. Create a pullsheet with multiple categories
2. Finalize the pullsheet
3. Download PDF from pullsheet view page
4. Verify:
   - ✓ Professional table layout
   - ✓ Category grouping
   - ✓ Barcode in top right
   - ✓ Signature lines at bottom
   - ✓ All data displays correctly

## Compatibility

- Works with existing database schema
- No changes to data structure
- Compatible with all user roles
- Works with approval workflow
- Compatible with pick/return modes

## Future Enhancements

Possible additions:
- [ ] Custom header logos (image support)
- [ ] Configurable column widths
- [ ] Additional signature fields
- [ ] Color coding for status
- [ ] QR codes alongside barcodes
- [ ] Footer with company information
- [ ] Custom fonts
- [ ] Landscape orientation option

## Support

For issues or questions:
1. Check test_lightwright_pdf.php output
2. Verify PDF generation in logs/
3. Review SimplePDF.php methods
4. Test with sample data

## Credits

Inspired by LightWright 6/7 theatrical lighting design software paperwork format.
