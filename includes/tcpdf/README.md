# TCPDF Installation

This directory should contain the TCPDF library for PDF generation.

## Installation Instructions

1. Download TCPDF from: https://github.com/tecnickcom/TCPDF/archive/refs/tags/6.7.5.tar.gz
2. Extract the archive
3. Copy all files from the extracted TCPDF-6.7.5 folder to this directory
4. Verify that `tcpdf.php` exists in this directory

## Alternative: Using Composer

If you have composer installed:
```bash
cd includes
composer require tecnickcom/tcpdf
```

## Verification

After installation, verify that the following file exists:
- `includes/tcpdf/tcpdf.php`

The PDF preview and generation features will work once TCPDF is properly installed.
