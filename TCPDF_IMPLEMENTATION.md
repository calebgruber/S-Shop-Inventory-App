# TCPDF Implementation Guide

## Installation Complete ✅

### Location
TCPDF has been installed at: `/includes/tcpdf/`

**Note:** The TCPDF library directory is added to `.gitignore` due to its size (~16MB).

### What Was Installed
- **TCPDF Version:** 6.7.5
- **Source:** https://github.com/tecnickcom/TCPDF
- **Files:** Complete TCPDF library with all fonts, examples, and documentation

## PDF Helper Functions ✅

### File: `includes/pdf_helper.php`

This file provides helper functions for PDF generation:

#### `initPDF($orientation, $unit, $format)`
Initializes a TCPDF instance with settings from the database:
- Loads all PDF settings (margins, fonts, page size, etc.)
- Sets up headers and footers
- Configures page orientation and size
- Returns configured TCPDF object

#### `generatePreviewPDF()`
Generates a sample preview PDF showing:
- Current PDF settings
- Sample content (text, tables)
- Actual margins and formatting

#### `outputPDF($pdf, $filename, $dest)`
Outputs PDF to browser with options:
- `'I'` - Display inline in browser
- `'D'` - Force download
- `'F'` - Save to file
- `'S'` - Return as string

## PDF Settings in Database ✅

All settings are stored in the `settings` table:

| Setting Key | Default | Description |
|-------------|---------|-------------|
| `pdf_company_name` | App name | Company name for PDFs |
| `pdf_header_text` | Empty | Optional header text |
| `pdf_footer_text` | Empty | Optional footer text |
| `pdf_page_orientation` | portrait | portrait or landscape |
| `pdf_page_size` | LETTER | LETTER, LEGAL, or A4 |
| `pdf_margin_top` | 15 | Top margin in mm |
| `pdf_margin_bottom` | 15 | Bottom margin in mm |
| `pdf_margin_left` | 15 | Left margin in mm |
| `pdf_margin_right` | 15 | Right margin in mm |
| `pdf_font_size` | 10 | Font size in points |
| `pdf_show_logo` | 1 | Show/hide logo |

## PDF Preview Feature ✅

### Endpoint: `pdf_preview.php`

Accessible from Settings → PDF tab via "Preview PDF" button.

**Features:**
- Shows sample PDF with current settings
- Opens in new window/tab
- Displays actual margins and formatting
- Shows configured headers/footers
- Includes sample table and text

**Access:** Admin only (via `requireRole('admin')`)

## How to Use TCPDF in Your Code

### Basic Example

```php
<?php
require_once 'includes/pdf_helper.php';

// Initialize PDF with database settings
$pdf = initPDF();

// Add a page
$pdf->AddPage();

// Write some HTML content
$html = '<h1>My Document</h1>';
$html .= '<p>This is some content.</p>';
$pdf->writeHTML($html, true, false, true, false, '');

// Output to browser
outputPDF($pdf, 'my-document.pdf', 'I');
```

### Advanced Example with Tables

```php
<?php
require_once 'includes/pdf_helper.php';

$pdf = initPDF();
$pdf->AddPage();

$html = '<h1>Inventory Report</h1>';
$html .= '<table border="1" cellpadding="5">';
$html .= '<tr><th>Item</th><th>Quantity</th><th>Status</th></tr>';

foreach ($items as $item) {
    $html .= '<tr>';
    $html .= '<td>' . htmlspecialchars($item['name']) . '</td>';
    $html .= '<td>' . htmlspecialchars($item['quantity']) . '</td>';
    $html .= '<td>' . htmlspecialchars($item['status']) . '</td>';
    $html .= '</tr>';
}

$html .= '</table>';
$pdf->writeHTML($html, true, false, true, false, '');

outputPDF($pdf, 'inventory-report.pdf', 'D'); // Force download
```

## Next Steps - Replacing window.print()

### Files That Need Updates

1. **reports.php** (3 instances)
   - Inventory report
   - Status report
   - Usage report

2. **reports/index.php** (3 instances)
   - Same as reports.php

3. **item_barcodes.php** (1 instance)
   - Barcode printing

4. **items/barcodes.php** (1 instance)
   - Same as item_barcodes.php

5. **paperwork.php** (Not found - may have been moved)

### Implementation Pattern

Replace:
```html
<button onclick="window.print()" class="btn btn-primary">
    <i class="ti ti-printer"></i> Print
</button>
```

With:
```html
<a href="/generate_report_pdf.php?type=inventory" class="btn btn-primary" target="_blank">
    <i class="ti ti-file-pdf"></i> Download PDF
</a>
```

Then create corresponding PDF generation endpoints that:
1. Fetch the data
2. Use `initPDF()` to create PDF
3. Format data as HTML
4. Use `outputPDF()` to send to browser

## Login Redirect Fixed ✅

All authentication redirects now point to `/index/` instead of `/auth/login`:
- `includes/functions.php` - requireLogin(), requireRole(), requirePermission()
- `logout.php` and `auth/logout.php` - redirect after logout
- `change_password.php` and `auth/change-password.php` - redirect if not logged in
- Created `index/index.php` - login page at /index/

## Testing

1. **Test PDF Preview:**
   - Go to Settings → PDF tab
   - Click "Preview PDF" button
   - Should open new tab with sample PDF

2. **Test PDF Settings:**
   - Change margins, font size, orientation
   - Save settings
   - Preview PDF to see changes

3. **Test Login Redirect:**
   - Logout
   - Should redirect to /index/
   - Login page should display

## Troubleshooting

### PDF Generation Errors

If you see "Error Generating PDF":
1. Check PHP error logs
2. Verify TCPDF is installed in `includes/tcpdf/`
3. Ensure write permissions on temp directories
4. Check that `includes/pdf_helper.php` is properly included

### Missing TCPDF

If TCPDF folder is missing (was in .gitignore):
1. Download from: https://github.com/tecnickcom/TCPDF/archive/refs/tags/6.7.5.tar.gz
2. Extract to `includes/tcpdf/`
3. Verify `includes/tcpdf/tcpdf.php` exists

### Login Redirect Loop

If experiencing redirect loops:
1. Verify `/index/index.php` exists
2. Check that it's a copy of the login page
3. Ensure .htaccess is properly configured
