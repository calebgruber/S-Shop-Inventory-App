# Installing Dompdf for PDF Generation

Dompdf is a PHP library that allows you to generate PDFs from HTML and CSS, making it much easier to style and maintain PDF documents.

## Installation Steps for cPanel

### Option 1: Manual Installation (Recommended for cPanel)

1. **Download Dompdf:**
   - Go to https://github.com/dompdf/dompdf/releases
   - Download the latest release (e.g., `dompdf_2.x.x.zip`)

2. **Upload to Server:**
   - Extract the zip file
   - Upload the entire `dompdf` folder to `/includes/` directory
   - Your structure should be: `/includes/dompdf/`

3. **Set Permissions:**
   - Via cPanel File Manager, set permissions to 755 for the dompdf folder
   - Set 755 for all subdirectories

4. **Verify Installation:**
   - The autoloader should be at: `/includes/dompdf/autoload.inc.php`

### Option 2: Using Composer (If Available)

If your cPanel hosting has Composer installed:

```bash
cd /home/your_username/public_html
composer require dompdf/dompdf
```

This will install Dompdf in the `vendor` directory.

## File Structure After Installation

```
your-app/
├── includes/
│   ├── dompdf/           # Dompdf library
│   │   ├── autoload.inc.php
│   │   ├── src/
│   │   └── lib/
│   ├── pdf/
│   │   └── DompdfWrapper.php  # Our custom wrapper
│   └── functions.php
```

## Configuration

The `DompdfWrapper.php` file in `includes/pdf/` provides an easy interface:

```php
// Example usage in your code:
$pdf = new DompdfWrapper();
$html = '<h1>Hello PDF</h1><p>This is styled with CSS!</p>';
$pdf->generateFromHtml($html, 'document.pdf', 'download'); // or 'inline'
```

## Troubleshooting

### Issue: "Class 'Dompdf\Dompdf' not found"
**Solution:** Make sure the dompdf folder is in the correct location and the autoload file exists.

### Issue: "Permission denied" errors
**Solution:** Set correct permissions (755) on the dompdf directory and all subdirectories.

### Issue: Images or fonts not loading
**Solution:** 
- Make sure image paths are absolute URLs or full server paths
- Check that the dompdf font cache directory has write permissions

## Benefits of Dompdf

1. **HTML/CSS Styling:** Write PDFs using familiar HTML and CSS
2. **Easy Maintenance:** Update PDF layouts by changing HTML templates
3. **Barcode Support:** Embed barcode images from barcodeapi.org easily
4. **Responsive Design:** Use CSS media queries for print layouts
5. **Logo Integration:** Simple image embedding with `<img>` tags

## Alternative: TCPDF

If you prefer TCPDF instead:
- Download from: https://github.com/tecnickcom/TCPDF
- TCPDF uses PHP methods instead of HTML/CSS
- More complex but offers fine-grained control

**Recommendation:** Use Dompdf for easier styling and maintenance.
