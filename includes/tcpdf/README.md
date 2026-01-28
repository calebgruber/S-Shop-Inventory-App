# TCPDF Library Setup

This directory should contain the TCPDF library for PDF generation.

## Installation Instructions

Since this project does not use Composer, TCPDF must be manually downloaded and installed.

### Steps:

1. **Download TCPDF:**
   - Visit: https://github.com/tecnickcom/TCPDF/releases
   - Download the latest stable release (e.g., `TCPDF-6.7.4.zip`)

2. **Extract Files:**
   - Extract the downloaded ZIP file
   - Copy the contents to this directory (`includes/tcpdf/`)

3. **Verify Installation:**
   - Ensure `tcpdf.php` exists at: `includes/tcpdf/tcpdf.php`
   - The directory structure should look like:
     ```
     includes/tcpdf/
     ├── README.md (this file)
     ├── tcpdf.php
     ├── tcpdf_autoconfig.php
     ├── tcpdf_barcodes_1d.php
     ├── tcpdf_barcodes_2d.php
     ├── config/
     ├── fonts/
     └── ... (other TCPDF files)
     ```

4. **Test:**
   - Try generating a pull sheet PDF from the system
   - If successful, you should see PDF files in `/pdfs/pullsheets/`

## Alternative: Minimal Installation

If you want a minimal installation:

1. Download just the core TCPDF files
2. Place `tcpdf.php` in this directory
3. Create a `config` subdirectory
4. Copy minimal config files

## Troubleshooting

**Error: "tcpdf.php not found"**
- Verify the file path is correct: `includes/tcpdf/tcpdf.php`
- Check file permissions (should be readable by web server)

**Error: "Cannot write PDF file"**
- Check `/pdfs/pullsheets/` directory exists
- Verify write permissions on `/pdfs/pullsheets/`

**Error: "Font not found"**
- TCPDF may need font files
- Download full TCPDF package including fonts

## License

TCPDF is licensed under LGPL v3. See https://github.com/tecnickcom/TCPDF for details.
