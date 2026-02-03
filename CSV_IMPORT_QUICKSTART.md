# CSV Import Feature - Quick Start Guide

## 🎯 Purpose
Import your existing database backup into the development environment using CSV files.

## 📋 What You Can Import
- ✅ Categories
- ✅ Subcategories
- ✅ Theatre Spaces
- ✅ Items (with full details)

## 🚀 Quick Start (3 Steps)

### Step 1: Prepare Your CSV Files
Export your production database to CSV files with these formats:

**Categories** (`categories.csv`):
```
name,description
Microphones,Mic equipment
Cables,Cable inventory
```

**Subcategories** (`subcategories.csv`):
```
category_name,name,description
Microphones,Wired,Standard wired mics
Microphones,Wireless,Wireless systems
```

**Theatre Spaces** (`theatre_spaces.csv`):
```
name,description
Main Stage,500 seat venue
Studio,Black box theatre
```

**Items** (`items.csv`):
```
name,description,barcode,category_name,subcategory_name,tracking_type,total_quantity,in_stock_quantity,location
Shure SM58,Vocal mic,MIC-001,Microphones,Wired,quantity,10,8,Cabinet A
```

### Step 2: Import in Correct Order
⚠️ **IMPORTANT**: Import in this sequence:

1. **Categories first** (no dependencies)
2. **Subcategories second** (requires categories)
3. **Theatre Spaces** (no dependencies, optional)
4. **Items last** (requires categories/subcategories)

### Step 3: Upload via Settings Page

1. Login as **Admin**
2. Go to **Settings** page
3. Scroll to **"Import Data from CSV"** section
4. For each file:
   - Click "Choose File"
   - Select your CSV
   - Choose import type (or use "Auto-detect")
   - Click "Import CSV"
   - Review results

## ✅ What Happens During Import

### Success Case:
```
✓ Import completed: 50 records imported
```

### Partial Success:
```
⚠ Import completed: 45 records imported, 5 skipped (already exist)
```

### With Errors:
```
⚠ Import completed: 40 records imported, 5 skipped, 5 errors
Errors: Category 'Audio' not found for subcategory 'Mics'
```

## 🔍 Common Issues & Solutions

| Issue | Solution |
|-------|----------|
| "Category not found" | Import categories before subcategories |
| "Duplicate entry" | Item already exists (will be skipped) |
| "Invalid file type" | Save as `.csv` file |
| Some rows not importing | Check error message, verify CSV format |

## 📊 Import Statistics

After each import, you'll see:
- **Imported**: Number of new records added
- **Skipped**: Duplicates that already exist
- **Errors**: Rows that failed (with reasons)

## 🎓 Sample Files

Example CSV files are included in the `sample_csvs/` folder:
- `categories_sample.csv`
- `subcategories_sample.csv`
- `theatre_spaces_sample.csv`
- `items_sample.csv`

Use these as templates for your data!

## 💡 Pro Tips

1. **Test first**: Start with a small CSV (5-10 rows)
2. **Verify format**: Compare with sample files
3. **Check relationships**: Ensure category names match exactly
4. **Review results**: Check import statistics after each upload
5. **Backup**: Always backup before importing

## 🔗 Need More Help?

See **CSV_IMPORT_GUIDE.md** for:
- Detailed CSV format specifications
- Advanced troubleshooting
- Technical details
- Best practices

## 📸 Visual Walkthrough

### Settings Page - Import Section
```
┌─────────────────────────────────────────────────┐
│ Import Data from CSV                            │
├─────────────────────────────────────────────────┤
│ ℹ CSV Import Instructions                       │
│   Upload a CSV file to import data...           │
│                                                  │
│ CSV File: [Choose File]                         │
│ Import Type: [Auto-detect ▼]                    │
│                                                  │
│ [↑ Import CSV]                                   │
│                                                  │
│ ▶ View CSV Format Examples                      │
└─────────────────────────────────────────────────┘
```

### Import Results
```
┌─────────────────────────────────────────────────┐
│ ✓ Import completed: 25 records imported,        │
│   2 skipped (already exist)                     │
└─────────────────────────────────────────────────┘
```

## 🎯 Complete Database Migration Example

If you're migrating your entire production database:

**Step 1: Export from Production**
```sql
-- Export categories
SELECT name, description FROM categories INTO OUTFILE 'categories.csv';

-- Export subcategories  
SELECT c.name, s.name, s.description 
FROM subcategories s 
JOIN categories c ON s.category_id = c.id 
INTO OUTFILE 'subcategories.csv';

-- Export theatre spaces
SELECT name, description FROM theatre_spaces INTO OUTFILE 'spaces.csv';

-- Export items
SELECT i.name, i.description, i.barcode, c.name, s.name, 
       i.tracking_type, i.total_quantity, i.in_stock_quantity, i.location
FROM items i
LEFT JOIN categories c ON i.category_id = c.id
LEFT JOIN subcategories s ON i.subcategory_id = s.id
INTO OUTFILE 'items.csv';
```

**Step 2: Add Headers**
Add the header row to each CSV file as shown in format examples above.

**Step 3: Import to Development**
1. Upload `categories.csv` (Import Type: Categories)
2. Upload `subcategories.csv` (Import Type: Subcategories)
3. Upload `spaces.csv` (Import Type: Theatre Spaces)
4. Upload `items.csv` (Import Type: Items)

**Done!** ✅ Your development database now has all the data.

## 📝 Quick Reference

### CSV Requirements
- ✅ First row = column headers
- ✅ Comma separated (`,`)
- ✅ UTF-8 encoding
- ✅ `.csv` file extension
- ✅ No special characters in delimiters

### Import Order
```
1. Categories ─────────┐
2. Subcategories ──────┤
3. Theatre Spaces ─────┤──> Items (last)
                       │
```

### Duplicate Detection
- Categories: by `name`
- Subcategories: by `name` + parent category
- Theatre Spaces: by `name`
- Items: by `barcode`

---

**Ready to import?** Head to Settings → Import Data from CSV! 🚀
