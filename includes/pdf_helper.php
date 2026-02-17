<?php
/**
 * PDF Generation Helper Functions
 * Uses TCPDF library for PDF generation
 */

require_once __DIR__ . '/tcpdf/tcpdf.php';
require_once __DIR__ . '/functions.php';

/**
 * Initialize TCPDF with application settings
 */
function initPDF($orientation = 'P', $unit = 'mm', $format = 'LETTER') {
    // Load PDF settings from database
    $pdfCompanyName = getSetting('pdf_company_name', getSetting('app_name', 'Sound Shop Inventory'));
    $pdfHeaderText = getSetting('pdf_header_text', '');
    $pdfFooterText = getSetting('pdf_footer_text', '');
    $pdfPageOrientation = getSetting('pdf_page_orientation', 'portrait');
    $pdfPageSize = getSetting('pdf_page_size', 'LETTER');
    $pdfMarginTop = getSetting('pdf_margin_top', '15');
    $pdfMarginBottom = getSetting('pdf_margin_bottom', '15');
    $pdfMarginLeft = getSetting('pdf_margin_left', '15');
    $pdfMarginRight = getSetting('pdf_margin_right', '15');
    $pdfFontSize = getSetting('pdf_font_size', '10');
    $pdfShowLogo = getSetting('pdf_show_logo', '1');
    
    // Use settings if not overridden
    $orientation = ($orientation === 'P' && $pdfPageOrientation === 'landscape') ? 'L' : $orientation;
    $format = ($format === 'LETTER') ? $pdfPageSize : $format;
    
    // Create new PDF document
    $pdf = new TCPDF($orientation, $unit, $format, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor($pdfCompanyName);
    $pdf->SetTitle('Document');
    
    // Set header and footer
    if (!empty($pdfHeaderText)) {
        $pdf->SetHeaderData('', 0, $pdfCompanyName, $pdfHeaderText);
    } else {
        $pdf->setPrintHeader(false);
    }
    
    if (!empty($pdfFooterText)) {
        $pdf->setFooterData(array(0,64,0), array(0,64,128));
        $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
    } else {
        $pdf->setPrintFooter(true); // Keep page numbers
    }
    
    // Set margins
    $pdf->SetMargins((float)$pdfMarginLeft, (float)$pdfMarginTop, (float)$pdfMarginRight);
    $pdf->SetHeaderMargin(5);
    $pdf->SetFooterMargin((float)$pdfMarginBottom);
    
    // Set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, (float)$pdfMarginBottom);
    
    // Set font
    $pdf->SetFont('helvetica', '', (float)$pdfFontSize);
    
    return $pdf;
}

/**
 * Generate a sample preview PDF with current settings
 */
function generatePreviewPDF() {
    $pdf = initPDF();
    
    // Add a page
    $pdf->AddPage();
    
    // Set some content to show
    $html = '<h1>PDF Preview</h1>';
    $html .= '<p>This is a preview of your PDF settings.</p>';
    $html .= '<h2>Current Settings:</h2>';
    $html .= '<ul>';
    $html .= '<li><strong>Company Name:</strong> ' . htmlspecialchars(getSetting('pdf_company_name', 'N/A')) . '</li>';
    $html .= '<li><strong>Page Orientation:</strong> ' . htmlspecialchars(getSetting('pdf_page_orientation', 'portrait')) . '</li>';
    $html .= '<li><strong>Page Size:</strong> ' . htmlspecialchars(getSetting('pdf_page_size', 'LETTER')) . '</li>';
    $html .= '<li><strong>Font Size:</strong> ' . htmlspecialchars(getSetting('pdf_font_size', '10')) . 'pt</li>';
    $html .= '<li><strong>Margins:</strong> T:' . getSetting('pdf_margin_top', '15') . 'mm, ';
    $html .= 'B:' . getSetting('pdf_margin_bottom', '15') . 'mm, ';
    $html .= 'L:' . getSetting('pdf_margin_left', '15') . 'mm, ';
    $html .= 'R:' . getSetting('pdf_margin_right', '15') . 'mm</li>';
    $html .= '</ul>';
    
    $html .= '<h2>Sample Content</h2>';
    $html .= '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>';
    $html .= '<table border="1" cellpadding="5">';
    $html .= '<tr><th>Item</th><th>Quantity</th><th>Status</th></tr>';
    $html .= '<tr><td>Sample Item 1</td><td>5</td><td>Available</td></tr>';
    $html .= '<tr><td>Sample Item 2</td><td>3</td><td>Checked Out</td></tr>';
    $html .= '<tr><td>Sample Item 3</td><td>10</td><td>Available</td></tr>';
    $html .= '</table>';
    
    // Write HTML content
    $pdf->writeHTML($html, true, false, true, false, '');
    
    return $pdf;
}

/**
 * Output PDF to browser
 */
function outputPDF($pdf, $filename = 'document.pdf', $dest = 'I') {
    // I = inline, D = download, F = save to file, S = return as string
    $pdf->Output($filename, $dest);
}
