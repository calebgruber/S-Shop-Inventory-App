# LightWright Paperwork - Visual Comparison

## Before and After Examples

### Pullsheet Format

#### BEFORE (Basic Text Layout)
```
CMFT Sound Shop

                            PULLSHEET

Show: The Sound of Music
Shop Lead: Mike Johnson
Designer: Sarah Williams
Created: 02/03/2026
Barcode: PS-2026-001

Item                              Barcode      Qty Needed    Qty Picked
--------------------------------------------------------------------------------
Shure SM58 Microphone             MIC-SM58          5             0
Sennheiser e835 Microphone        MIC-E835          3             0
XLR Cable 25ft Black              CAB-XLR25         10            0
XLR Cable 50ft Black              CAB-XLR50         5             0
Mic Stand Boom                    STD-BOOM          8             0
DI Box Active                     DI-ACT            4             0
```

#### AFTER (LightWright-Style)
```
┌────────────────────────────────────────────────────────────────────┐
│ CMFT Sound Shop              EQUIPMENT PULL SHEET      [PDF417]    │
├────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  ┌─────────────────────────┐                                       │
│  │ Show Information        │                                       │
│  ├─────────────────────────┤                                       │
│  │ Production: The Sound   │                                       │
│  │             of Music    │                                       │
│  │ Designer:   Sarah       │                                       │
│  │             Williams    │                                       │
│  │ Shop Lead:  Mike        │                                       │
│  │             Johnson     │                                       │
│  │ Date:       02/03/2026  │                                       │
│  │ Status:     Finalized   │                                       │
│  └─────────────────────────┘                                       │
│                                      Pullsheet ID: PS-2026-001     │
├────────────────────────────────────────────────────────────────────┤
│  ═══════════════════════ MICROPHONES ═══════════════════════      │
│  ┌────┬────────────────────────┬───────────┬─────┬──────────┬────┐│
│  │ #  │ Item Description       │ Barcode   │ Qty │ Location │Note││
│  ├────┼────────────────────────┼───────────┼─────┼──────────┼────┤│
│  │ 1  │ Shure SM58 Microphone  │ MIC-SM58  │  5  │ A-12     │    ││
│  │ 2  │ Sennheiser e835 Mic    │ MIC-E835  │  3  │ A-14     │    ││
│  │ 3  │ DI Box Active          │ DI-ACT    │  4  │ B-3      │    ││
│  └────┴────────────────────────┴───────────┴─────┴──────────┴────┘│
│                                                                     │
│  ═══════════════════════ CABLES ════════════════════════          │
│  ┌────┬────────────────────────┬───────────┬─────┬──────────┬────┐│
│  │ #  │ Item Description       │ Barcode   │ Qty │ Location │Note││
│  ├────┼────────────────────────┼───────────┼─────┼──────────┼────┤│
│  │ 4  │ XLR Cable 25ft Black   │ CAB-XLR25 │ 10  │ C-5      │    ││
│  │ 5  │ XLR Cable 50ft Black   │ CAB-XLR50 │  5  │ C-6      │    ││
│  └────┴────────────────────────┴───────────┴─────┴──────────┴────┘│
│                                                                     │
│  ═══════════════════════ STANDS ════════════════════════          │
│  ┌────┬────────────────────────┬───────────┬─────┬──────────┬────┐│
│  │ #  │ Item Description       │ Barcode   │ Qty │ Location │Note││
│  ├────┼────────────────────────┼───────────┼─────┼──────────┼────┤│
│  │ 6  │ Mic Stand Boom         │ STD-BOOM  │  8  │ D-1      │    ││
│  └────┴────────────────────────┴───────────┴─────┴──────────┴────┘│
│                                                                     │
├────────────────────────────────────────────────────────────────────┤
│  CHECKOUT AUTHORIZATION                                            │
│                                                                     │
│  Pulled By / Date:      _______________________________            │
│                                                                     │
│  Authorized By / Date:  _______________________________            │
│                                                                     │
│                                                    Page 1 of 1     │
└────────────────────────────────────────────────────────────────────┘
```

### Change Order Format

#### BEFORE (Basic Text Layout)
```
CMFT Sound Shop

                            CHANGE ORDER

Show: The Sound of Music
Shop Lead: Mike Johnson
Designer: Sarah Williams
Created: 02/05/2026
Barcode: CO-2026-003

Item                              Barcode         Type      Quantity
--------------------------------------------------------------------------------
Shure SM58 Microphone             MIC-SM58        add           2
XLR Cable 25ft Black              CAB-XLR25       remove        3
Mic Stand Boom                    STD-BOOM        add           1
```

#### AFTER (LightWright-Style)
```
┌────────────────────────────────────────────────────────────────────┐
│ CMFT Sound Shop          EQUIPMENT CHANGE ORDER      [PDF417]      │
├────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  ┌─────────────────────────┐                                       │
│  │ Show Information        │                                       │
│  ├─────────────────────────┤                                       │
│  │ Production: The Sound   │                                       │
│  │             of Music    │                                       │
│  │ Designer:   Sarah       │                                       │
│  │             Williams    │                                       │
│  │ Shop Lead:  Mike        │                                       │
│  │             Johnson     │                                       │
│  │ Date:       02/05/2026  │                                       │
│  │ Status:     Finalized   │                                       │
│  └─────────────────────────┘                                       │
│                                   Change Order ID: CO-2026-003     │
├────────────────────────────────────────────────────────────────────┤
│  ═══════════════════════ ITEMS TO ADD ══════════════════════      │
│  ┌────┬─────────────────────┬──────────┬─────┬───────────┬──────┐ │
│  │ #  │ Item Description    │ Barcode  │ Qty │ Category  │ Notes││ │
│  ├────┼─────────────────────┼──────────┼─────┼───────────┼──────┤ │
│  │ 1  │ Shure SM58 Mic      │ MIC-SM58 │ +2  │ Microphone│      ││ │
│  │ 2  │ Mic Stand Boom      │ STD-BOOM │ +1  │ Stands    │      ││ │
│  └────┴─────────────────────┴──────────┴─────┴───────────┴──────┘ │
│                                                                     │
│  ═══════════════════════ ITEMS TO REMOVE ════════════════════     │
│  ┌────┬─────────────────────┬──────────┬─────┬───────────┬──────┐ │
│  │ #  │ Item Description    │ Barcode  │ Qty │ Category  │ Notes││ │
│  ├────┼─────────────────────┼──────────┼─────┼───────────┼──────┤ │
│  │ 3  │ XLR Cable 25ft Blk  │CAB-XLR25 │ -3  │ Cables    │      ││ │
│  └────┴─────────────────────┴──────────┴─────┴───────────┴──────┘ │
│                                                                     │
├────────────────────────────────────────────────────────────────────┤
│  CHANGE AUTHORIZATION                                              │
│                                                                     │
│  Processed By / Date:   _______________________________            │
│                                                                     │
│  Authorized By / Date:  _______________________________            │
│                                                                     │
│                                                    Page 1 of 1     │
└────────────────────────────────────────────────────────────────────┘
```

## Key Visual Improvements

### 1. Professional Tables
- **Before**: Simple space-separated columns
- **After**: Bordered cells with clear divisions

### 2. Category Organization
- **Before**: All items in one flat list
- **After**: Items grouped by category with gray headers

### 3. Information Display
- **Before**: Line-by-line text
- **After**: Structured information box with title bar

### 4. Visual Hierarchy
- **Before**: Minimal visual separation
- **After**: 
  - Gray backgrounds for headers
  - Bordered sections
  - Clear spacing between elements
  - Professional typography

### 5. Change Order Clarity
- **Before**: Mixed "add" and "remove" in single list
- **After**: Separate sections with clear labels and +/- prefixes

### 6. Authorization
- **Before**: No signature area
- **After**: Professional signature lines with labels

### 7. Barcode Integration
- **Before**: Text reference only
- **After**: Visual barcode in top right corner (scannable)

## Professional Benefits

### For Shop Staff
✅ Easier to read and scan quickly
✅ Clear category grouping speeds up pulling
✅ Space for handwritten notes during checkout
✅ Professional appearance for external vendors

### For Designers
✅ Familiar format (matches LightWright reports)
✅ Clear organization by equipment type
✅ Easy to verify what was ordered
✅ Professional documentation for producers

### For Administration
✅ Signature lines provide accountability
✅ Professional appearance for records
✅ Clear authorization trail
✅ Industry-standard format

## Technical Implementation

### SimplePDF Methods Used
```php
// Information box with title bar
$pdf->addInfoBox($page, $x, $y, $width, $height, $title, $fields);

// Table header with gray background
$pdf->addTableHeader($page, $x, $y, $columns, $height);

// Table row with borders
$pdf->addTableRow($page, $x, $y, $columns, $data, $height);

// Signature lines
$pdf->addSignatureLine($page, $x, $y, $width, $label);

// Page numbers
$pdf->addPageNumber($page, $pageNum, $totalPages);
```

### Column Configurations
```php
// Pullsheet columns
$columns = [
    ['field' => 'item_num', 'label' => '#', 'width' => 30, 'align' => 'center'],
    ['field' => 'item_name', 'label' => 'Item Description', 'width' => 200],
    ['field' => 'item_barcode', 'label' => 'Barcode', 'width' => 90],
    ['field' => 'quantity_needed', 'label' => 'Qty', 'width' => 40],
    ['field' => 'location', 'label' => 'Location', 'width' => 80],
    ['field' => 'notes', 'label' => 'Notes/Picked', 'width' => 92],
];
```

## Testing the New Format

### Quick Test
1. Go to an existing finalized pullsheet
2. Click "Download PDF"
3. Compare with old format
4. Notice:
   - Bordered tables
   - Category grouping
   - Professional spacing
   - Signature lines

### Create Test Data
```bash
# Access test script
http://your-domain.com/test_lightwright_pdf
```

## Compatibility

✅ Works with existing database
✅ No changes to workflow
✅ Compatible with pick/return modes
✅ Barcode still scannable
✅ All user roles supported
✅ Mobile-friendly printing

## Print Settings

For best results:
- Paper: Letter (8.5" x 11")
- Orientation: Portrait
- Margins: Default
- Scale: 100%
- Color: Color or grayscale works

---

**Note:** All examples are ASCII representations. Actual PDFs use proper borders, fonts, and spacing for professional appearance.
