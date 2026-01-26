# Asset Directories

This directory structure is for storing application assets:

## assets/
- Logo files for PDF generation
- Default logo should be named `logo.png`
- Supported formats: PNG, JPG, GIF

## uploads/
- User-uploaded files
- Category images (future feature)
- Other uploaded content

## pdfs/
- Generated PDF files
- Pull sheets
- Change orders
- Reports
- Barcode labels

**Note:** These directories are excluded from git via .gitignore for security and to avoid bloating the repository.

## Permissions

Ensure these directories are writable by the web server:

```bash
chmod 755 assets uploads pdfs
chown www-data:www-data assets uploads pdfs  # Apache
# OR
chown nginx:nginx assets uploads pdfs  # Nginx
```
