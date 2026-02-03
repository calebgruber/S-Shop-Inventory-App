# CSV Import Feature Documentation

## Overview
The CSV Import feature allows administrators to import data from CSV files into the S-Shop Inventory database. This is useful for:
- Migrating data from a production database to development
- Bulk importing items, categories, subcategories, and theatre spaces
- Restoring data from backups

## Accessing the Import Feature
1. Log in as an admin user
2. Navigate to **Settings** page
3. Scroll to the **Import Data from CSV** section

## CSV Format Requirements

### General Requirements
- First row must contain column headers
- Use comma (`,`) as the delimiter
- Save file with `.csv` extension
- UTF-8 encoding recommended
- Duplicate entries (based on name or barcode) will be skipped

### Categories CSV
**Required Columns:**
- `name` - Category name (required, must be unique)
- `description` - Category description (optional)

**Example:**
```csv
name,description
Microphones,Professional microphones and recording equipment
Cables,Audio cables and adapters
Speakers,PA speakers and monitors
```

### Subcategories CSV
**Required Columns:**
- `category_name` - Parent category name (required, must exist in categories table)
- `name` - Subcategory name (required)
- `description` - Subcategory description (optional)

**Example:**
```csv
category_name,name,description
Microphones,Wired Microphones,Standard wired microphones
Microphones,Wireless Microphones,Wireless microphone systems
Cables,XLR Cables,3-pin XLR cables
```

**Note:** Categories must be imported before subcategories.

### Theatre Spaces CSV
**Required Columns:**
- `name` - Theatre space name (required, must be unique)
- `description` - Space description (optional)

**Example:**
```csv
name,description
Main Stage,Primary performance space with 500 seat capacity
Studio Theatre,Intimate black box theatre with flexible seating
Rehearsal Hall,Large rehearsal space with full lighting grid
```

### Items CSV
**Required Columns:**
- `name` - Item name (required)
- `description` - Item description (optional)
- `barcode` - Unique item barcode (required, must be unique)
- `category_name` - Category name (optional, must exist)
- `subcategory_name` - Subcategory name (optional, must exist and match category)
- `tracking_type` - Either "quantity" or "serial" (default: "quantity")
- `total_quantity` - Total quantity (default: 0)
- `in_stock_quantity` - Current in-stock quantity (default: 0)
- `location` - Storage location (optional)

**Example:**
```csv
name,description,barcode,category_name,subcategory_name,tracking_type,total_quantity,in_stock_quantity,location
Shure SM58,Dynamic vocal microphone,MIC-SM58-001,Microphones,Wired Microphones,quantity,10,8,Cabinet A1
Sennheiser EW 100,Wireless handheld system,MIC-EW100-001,Microphones,Wireless Microphones,quantity,5,5,Cabinet A2
XLR Cable 25ft,25 foot XLR cable,CABLE-XLR25-001,Cables,XLR Cables,quantity,50,45,Cable Rack 1
```

**Note:** Categories and subcategories must be imported before items.

## Import Process

### Step-by-Step Instructions

1. **Prepare your CSV file**
   - Ensure it follows the format requirements above
   - Validate that all required columns are present
   - Check that category/subcategory references are correct

2. **Select import type**
   - **Auto-detect from file**: System will attempt to determine the data type from column headers
   - **Categories**: For importing categories only
   - **Subcategories**: For importing subcategories only
   - **Theatre Spaces**: For importing theatre spaces only
   - **Items**: For importing items only

3. **Upload the file**
   - Click "Choose File" and select your CSV
   - Select the import type
   - Click "Import CSV"

4. **Review results**
   - Success message shows number of records imported
   - Skipped records are reported (duplicates)
   - Errors are displayed if any rows failed to import

## Import Order for Complete Database Migration

When importing a complete database backup, follow this order:

1. **Categories** first
2. **Subcategories** second (requires categories)
3. **Theatre Spaces** (independent, can be done anytime)
4. **Items** last (requires categories and subcategories)

## Sample CSV Files

Sample CSV files are available in the `sample_csvs/` directory:
- `categories_sample.csv`
- `subcategories_sample.csv`
- `theatre_spaces_sample.csv`
- `items_sample.csv`

## Error Handling

### Common Errors and Solutions

**"Category not found for subcategory"**
- Solution: Import categories before subcategories

**"Duplicate entry"**
- Solution: Entry already exists, it will be skipped automatically

**"Invalid file type"**
- Solution: Ensure file has .csv extension

**"Please upload a CSV file"**
- Solution: Select a file before clicking Import

**Missing required columns**
- Solution: Verify CSV has correct column headers in first row

## Tips and Best Practices

1. **Test with small files first**: Start with a few rows to verify format
2. **Use sample files as templates**: Modify sample CSVs for your data
3. **Check data before import**: Verify relationships between categories/subcategories
4. **Review import results**: Check for skipped or failed rows
5. **Backup before import**: Always backup your database before large imports
6. **Import in correct order**: Categories → Subcategories → Items
7. **Handle duplicates**: Existing records are automatically skipped

## Limitations

- Maximum file size determined by PHP settings (typically 2MB - 100MB)
- Import runs in one request (may timeout for very large files)
- Duplicate detection based on name (categories/subcategories/spaces) or barcode (items)
- Cannot update existing records, only insert new ones
- Cannot import users, shows, pullsheets, or other advanced data

## Technical Details

### Duplicate Detection
- **Categories**: Checked by `name`
- **Subcategories**: Checked by `name` and `category_id`
- **Theatre Spaces**: Checked by `name`
- **Items**: Checked by `barcode`

### Character Encoding
- UTF-8 recommended
- Special characters should be properly encoded

### File Size
- No explicit limit in the application
- Limited by PHP upload_max_filesize setting
- Large files (1000+ rows) may take several seconds to import

## Troubleshooting

### Import takes too long
- Break large files into smaller chunks
- Import in multiple batches

### Some rows not importing
- Check error messages in the result
- Verify CSV format matches requirements
- Check for missing parent records (categories for subcategories)

### CSV formatting issues
- Use a proper CSV editor or spreadsheet software
- Ensure commas are used as delimiters
- Quote fields that contain commas

### Database errors
- Check database permissions
- Verify foreign key constraints are satisfied
- Ensure database is not read-only

## Support

For additional help:
1. Check sample CSV files in `sample_csvs/` directory
2. Review error messages carefully
3. Verify data format matches documentation
4. Test with smaller datasets first
