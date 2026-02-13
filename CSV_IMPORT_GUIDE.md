# CSV Import Guide

## Overview
The CSV import feature has been enhanced to support importing complete inventory data with proper relationships between categories, subcategories, and items.

## Fixed Issues
1. **Notification Error**: Fixed PDO exception when creating notifications (missing 'title' column)
2. **CSV Import**: Enhanced to handle ID-based imports with proper relationship management

## CSV Format

### General Format
All CSV files follow this structure:
- **Column 1**: ID (optional, numeric) - If provided and available, this ID will be used
- **Column 2**: Name (required) - The display name
- **Additional columns**: Depend on the data type

### Categories CSV

Example:
```
id,name,description
1,Audio,Audio equipment and accessories
2,Lighting,Stage lighting equipment
3,Cables,Various cables and connectors
```

**Columns:**
- Column 0: ID (optional)
- Column 1: Name (required)
- Column 2: Description (optional)

### Subcategories CSV

Example:
```
id,name,category_id,description
1,Microphones,1,Various microphone types
2,Speakers,1,Amplifiers and speakers
3,Moving Lights,2,Intelligent lighting fixtures
```

**Columns:**
- Column 0: ID (optional)
- Column 1: Name (required)
- Column 2: Category reference (can be ID number or category name)
- Column 3: Description (optional)

### Items CSV

Example:
```
id,name,barcode,description,category_id,subcategory_id,tracking_type,total_quantity,in_stock_quantity,location
1,Shure SM58,123456789,Dynamic microphone,1,1,quantity,10,8,Shelf A1
```

**Required:**
- Column 1: Name (required)
- barcode: Unique barcode (required)

**Optional:**
- Column 0: id - Item ID
- description, category_id, subcategory_id, tracking_type, total_quantity, in_stock_quantity, location

## Import Order

For best results, import in this order:
1. Categories first
2. Subcategories second
3. Items last

## Benefits

✅ Maintain Relationships with IDs
✅ Flexible References (IDs or names)
✅ Batch Import capability
✅ Error Recovery with detailed messages
✅ Duplicate Prevention
✅ Partial Success reporting
