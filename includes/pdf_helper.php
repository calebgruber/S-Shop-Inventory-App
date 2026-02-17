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

/**
 * Generate Pullsheet PDF with color coding
 */
function generatePullsheetPDFWithColor($pullsheetId) {
    require_once __DIR__ . '/functions.php';
    
    $pullsheet = getPullsheetById($pullsheetId);
    if (!$pullsheet) {
        return false;
    }
    
    $items = getPullsheetItems($pullsheetId);
    
    // Initialize PDF
    $pdf = initPDF('P', 'mm', 'LETTER');
    $pdf->SetTitle('Pullsheet - ' . ($pullsheet['show_name'] ?? 'Unknown'));
    
    // Add a page
    $pdf->AddPage();
    
    // Header with company name
    $pdf->SetFont('helvetica', 'B', 18);
    $pdf->SetTextColor(33, 37, 41); // Dark color
    $pdf->Cell(0, 10, 'EQUIPMENT PULL SHEET', 0, 1, 'C');
    $pdf->Ln(5);
    
    // Barcode and show information
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetTextColor(0, 0, 0);
    
    // Info box with light blue background
    $pdf->SetFillColor(219, 234, 254); // Light blue
    $pdf->SetDrawColor(59, 130, 246); // Blue border
    $pdf->SetLineWidth(0.5);
    $pdf->RoundedRect(15, $pdf->GetY(), 180, 40, 3, '1111', 'DF');
    
    $infoY = $pdf->GetY() + 5;
    $pdf->SetXY(20, $infoY);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(80, 6, 'Production:', 0, 0);
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(0, 6, $pullsheet['show_name'] ?? 'N/A', 0, 1);
    
    $pdf->SetX(20);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(80, 6, 'Designer:', 0, 0);
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(0, 6, $pullsheet['designer'] ?? 'N/A', 0, 1);
    
    $pdf->SetX(20);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(80, 6, 'Shop Order ID:', 0, 0);
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(0, 6, $pullsheet['barcode'], 0, 1);
    
    $pdf->SetX(20);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(80, 6, 'Status:', 0, 0);
    $pdf->SetFont('helvetica', '', 11);
    
    // Color-code status
    $status = ucfirst($pullsheet['status']);
    switch($pullsheet['status']) {
        case 'draft':
            $pdf->SetTextColor(255, 193, 7); // Yellow
            break;
        case 'pending':
            $pdf->SetTextColor(13, 110, 253); // Blue
            break;
        case 'approved':
        case 'finalized':
            $pdf->SetTextColor(25, 135, 84); // Green
            break;
        case 'picked':
            $pdf->SetTextColor(111, 66, 193); // Purple
            break;
    }
    $pdf->Cell(0, 6, $status, 0, 1);
    $pdf->SetTextColor(0, 0, 0);
    
    $pdf->Ln(10);
    
    // Group items by category
    $itemsByCategory = [];
    foreach ($items as $item) {
        $catName = $item['category_name'] ?? 'Uncategorized';
        if (!isset($itemsByCategory[$catName])) {
            $itemsByCategory[$catName] = [];
        }
        $itemsByCategory[$catName][] = $item;
    }
    
    // Items table
    $itemNum = 1;
    foreach ($itemsByCategory as $categoryName => $categoryItems) {
        // Check for page break
        if ($pdf->GetY() > 240) {
            $pdf->AddPage();
        }
        
        // Category header with color
        $pdf->SetFillColor(52, 58, 64); // Dark gray
        $pdf->SetTextColor(255, 255, 255); // White text
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 8, strtoupper($categoryName), 0, 1, 'L', true);
        $pdf->SetTextColor(0, 0, 0);
        
        // Table header
        $pdf->SetFillColor(233, 236, 239); // Light gray
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(10, 7, '#', 1, 0, 'C', true);
        $pdf->Cell(60, 7, 'Item', 1, 0, 'L', true);
        $pdf->Cell(35, 7, 'Barcode', 1, 0, 'L', true);
        $pdf->Cell(15, 7, 'Qty', 1, 0, 'C', true);
        $pdf->Cell(35, 7, 'Location', 1, 0, 'L', true);
        $pdf->Cell(25, 7, 'Checked', 1, 1, 'C', true);
        
        // Items
        $pdf->SetFont('helvetica', '', 9);
        foreach ($categoryItems as $item) {
            if ($pdf->GetY() > 250) {
                $pdf->AddPage();
                // Re-add header
                $pdf->SetFillColor(233, 236, 239);
                $pdf->SetFont('helvetica', 'B', 9);
                $pdf->Cell(10, 7, '#', 1, 0, 'C', true);
                $pdf->Cell(60, 7, 'Item', 1, 0, 'L', true);
                $pdf->Cell(35, 7, 'Barcode', 1, 0, 'L', true);
                $pdf->Cell(15, 7, 'Qty', 1, 0, 'C', true);
                $pdf->Cell(35, 7, 'Location', 1, 0, 'L', true);
                $pdf->Cell(25, 7, 'Checked', 1, 1, 'C', true);
                $pdf->SetFont('helvetica', '', 9);
            }
            
            $pdf->Cell(10, 6, $itemNum++, 1, 0, 'C');
            $pdf->Cell(60, 6, substr($item['item_name'], 0, 30), 1, 0, 'L');
            $pdf->Cell(35, 6, $item['item_barcode'], 1, 0, 'L');
            $pdf->Cell(15, 6, $item['quantity_needed'], 1, 0, 'C');
            $pdf->Cell(35, 6, substr($item['location'] ?? '', 0, 15), 1, 0, 'L');
            $pdf->Cell(25, 6, '', 1, 1, 'C'); // Checkbox area
        }
        
        $pdf->Ln(3);
    }
    
    // Signature section
    if ($pdf->GetY() > 230) {
        $pdf->AddPage();
    }
    
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 6, 'AUTHORIZATION', 0, 1);
    $pdf->Ln(2);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Cell(80, 20, '', 'B', 0);
    $pdf->Cell(20, 20, '', 0, 0);
    $pdf->Cell(80, 20, '', 'B', 1);
    $pdf->SetFont('helvetica', '', 8);
    $pdf->Cell(80, 4, 'Signature', 0, 0);
    $pdf->Cell(20, 4, '', 0, 0);
    $pdf->Cell(80, 4, 'Date', 0, 1);
    
    return $pdf;
}

/**
 * Generate Change Order PDF with color coding
 */
function generateChangeOrderPDFWithColor($changeOrderId) {
    require_once __DIR__ . '/functions.php';
    
    $changeOrder = getChangeOrderById($changeOrderId);
    if (!$changeOrder) {
        return false;
    }
    
    $items = getChangeOrderItems($changeOrderId);
    
    // Initialize PDF
    $pdf = initPDF('P', 'mm', 'LETTER');
    $pdf->SetTitle('Change Order - ' . ($changeOrder['show_name'] ?? 'Unknown'));
    
    // Add a page
    $pdf->AddPage();
    
    // Header with company name
    $pdf->SetFont('helvetica', 'B', 18);
    $pdf->SetTextColor(220, 53, 69); // Red color for change order
    $pdf->Cell(0, 10, 'EQUIPMENT CHANGE ORDER', 0, 1, 'C');
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(5);
    
    // Info box with light yellow background (warning color)
    $pdf->SetFillColor(255, 243, 205); // Light yellow
    $pdf->SetDrawColor(255, 193, 7); // Yellow border
    $pdf->SetLineWidth(0.5);
    $pdf->RoundedRect(15, $pdf->GetY(), 180, 40, 3, '1111', 'DF');
    
    $infoY = $pdf->GetY() + 5;
    $pdf->SetXY(20, $infoY);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(80, 6, 'Production:', 0, 0);
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(0, 6, $changeOrder['show_name'] ?? 'N/A', 0, 1);
    
    $pdf->SetX(20);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(80, 6, 'Designer:', 0, 0);
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(0, 6, $changeOrder['designer'] ?? 'N/A', 0, 1);
    
    $pdf->SetX(20);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(80, 6, 'Change Order ID:', 0, 0);
    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell(0, 6, $changeOrder['barcode'], 0, 1);
    
    $pdf->SetX(20);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(80, 6, 'Status:', 0, 0);
    $pdf->SetFont('helvetica', '', 11);
    
    // Color-code status
    $status = ucfirst($changeOrder['status']);
    switch($changeOrder['status']) {
        case 'draft':
            $pdf->SetTextColor(255, 193, 7);
            break;
        case 'finalized':
            $pdf->SetTextColor(25, 135, 84);
            break;
        case 'picked':
            $pdf->SetTextColor(111, 66, 193);
            break;
    }
    $pdf->Cell(0, 6, $status, 0, 1);
    $pdf->SetTextColor(0, 0, 0);
    
    $pdf->Ln(10);
    
    // Separate items by type
    $addItems = [];
    $removeItems = [];
    foreach ($items as $item) {
        if ($item['type'] === 'add') {
            $addItems[] = $item;
        } else {
            $removeItems[] = $item;
        }
    }
    
    $itemNum = 1;
    
    // Items to ADD section
    if (!empty($addItems)) {
        if ($pdf->GetY() > 240) {
            $pdf->AddPage();
        }
        
        // Section header with green background
        $pdf->SetFillColor(25, 135, 84); // Green
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 8, 'ITEMS TO ADD (+)', 0, 1, 'L', true);
        $pdf->SetTextColor(0, 0, 0);
        
        // Table header
        $pdf->SetFillColor(209, 231, 221); // Light green
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(10, 7, '#', 1, 0, 'C', true);
        $pdf->Cell(70, 7, 'Item', 1, 0, 'L', true);
        $pdf->Cell(35, 7, 'Barcode', 1, 0, 'L', true);
        $pdf->Cell(15, 7, 'Qty', 1, 0, 'C', true);
        $pdf->Cell(50, 7, 'Category', 1, 1, 'L', true);
        
        // Items
        $pdf->SetFont('helvetica', '', 9);
        foreach ($addItems as $item) {
            if ($pdf->GetY() > 250) {
                $pdf->AddPage();
            }
            
            $pdf->Cell(10, 6, $itemNum++, 1, 0, 'C');
            $pdf->Cell(70, 6, substr($item['item_name'], 0, 35), 1, 0, 'L');
            $pdf->Cell(35, 6, $item['item_barcode'], 1, 0, 'L');
            $pdf->SetTextColor(25, 135, 84); // Green for add
            $pdf->Cell(15, 6, '+' . abs($item['quantity_change']), 1, 0, 'C');
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell(50, 6, substr($item['category_name'] ?? '', 0, 20), 1, 1, 'L');
        }
        
        $pdf->Ln(5);
    }
    
    // Items to REMOVE section
    if (!empty($removeItems)) {
        if ($pdf->GetY() > 240) {
            $pdf->AddPage();
        }
        
        // Section header with red background
        $pdf->SetFillColor(220, 53, 69); // Red
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 8, 'ITEMS TO REMOVE (-)', 0, 1, 'L', true);
        $pdf->SetTextColor(0, 0, 0);
        
        // Table header
        $pdf->SetFillColor(248, 215, 218); // Light red
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(10, 7, '#', 1, 0, 'C', true);
        $pdf->Cell(70, 7, 'Item', 1, 0, 'L', true);
        $pdf->Cell(35, 7, 'Barcode', 1, 0, 'L', true);
        $pdf->Cell(15, 7, 'Qty', 1, 0, 'C', true);
        $pdf->Cell(50, 7, 'Category', 1, 1, 'L', true);
        
        // Items
        $pdf->SetFont('helvetica', '', 9);
        foreach ($removeItems as $item) {
            if ($pdf->GetY() > 250) {
                $pdf->AddPage();
            }
            
            $pdf->Cell(10, 6, $itemNum++, 1, 0, 'C');
            $pdf->Cell(70, 6, substr($item['item_name'], 0, 35), 1, 0, 'L');
            $pdf->Cell(35, 6, $item['item_barcode'], 1, 0, 'L');
            $pdf->SetTextColor(220, 53, 69); // Red for remove
            $pdf->Cell(15, 6, '-' . abs($item['quantity_change']), 1, 0, 'C');
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell(50, 6, substr($item['category_name'] ?? '', 0, 20), 1, 1, 'L');
        }
    }
    
    // Signature section
    if ($pdf->GetY() > 230) {
        $pdf->AddPage();
    }
    
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 6, 'CHANGE AUTHORIZATION', 0, 1);
    $pdf->Ln(2);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->Cell(80, 20, '', 'B', 0);
    $pdf->Cell(20, 20, '', 0, 0);
    $pdf->Cell(80, 20, '', 'B', 1);
    $pdf->SetFont('helvetica', '', 8);
    $pdf->Cell(80, 4, 'Signature', 0, 0);
    $pdf->Cell(20, 4, '', 0, 0);
    $pdf->Cell(80, 4, 'Date', 0, 1);
    
    return $pdf;
}

