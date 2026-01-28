<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/helpers.php';

requireAuth();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php?error=' . urlencode('Invalid pull sheet ID'));
    exit;
}

$pullsheetId = (int)$_GET['id'];
$pullsheet = getPullSheetById($pullsheetId);

if (!$pullsheet) {
    header('Location: index.php?error=' . urlencode('Pull sheet not found'));
    exit;
}

// Check if PDF already exists
if (!empty($pullsheet['pdf_path']) && file_exists($pullsheet['pdf_path'])) {
    // Serve existing PDF
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="pullsheet-' . $pullsheet['barcode'] . '.pdf"');
    readfile($pullsheet['pdf_path']);
    exit;
}

// Only approved or later status can generate PDF
if (!in_array($pullsheet['status'], ['approved', 'picked', 'returned'])) {
    header('Location: view.php?id=' . $pullsheetId . '&error=' . urlencode('Pull sheet must be approved first'));
    exit;
}

// Generate PDF
$pdfPath = generatePullSheetPDF($pullsheetId);

if ($pdfPath) {
    // Update database with PDF path
    $conn = getDbConnection();
    $query = "UPDATE pullsheets SET pdf_path = ? WHERE id = ?";
    executeQuery($conn, $query, [$pdfPath, $pullsheetId]);
    
    // Serve PDF
    if (file_exists($pdfPath)) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="pullsheet-' . $pullsheet['barcode'] . '.pdf"');
        readfile($pdfPath);
        exit;
    }
}

// Error generating PDF
header('Location: view.php?id=' . $pullsheetId . '&error=' . urlencode('Failed to generate PDF'));
exit;

/**
 * Generate PDF for pull sheet
 * This is a simplified version. For production, integrate TCPDF
 */
function generatePullSheetPDF($pullsheetId) {
    $pullsheet = getPullSheetById($pullsheetId);
    $items = getPullSheetItems($pullsheetId);
    
    // Create PDF directory if needed
    $pdfDir = dirname(__DIR__) . '/pdfs/pullsheets';
    if (!file_exists($pdfDir)) {
        mkdir($pdfDir, 0755, true);
    }
    
    $pdfFilename = $pullsheet['barcode'] . '.pdf';
    $pdfPath = $pdfDir . '/' . $pdfFilename;
    
    // Check if TCPDF is available
    $tcpdfPath = dirname(__DIR__) . '/includes/tcpdf/tcpdf.php';
    
    if (file_exists($tcpdfPath)) {
        return generatePDFWithTCPDF($pullsheet, $items, $pdfPath);
    } else {
        return generateSimplePDF($pullsheet, $items, $pdfPath);
    }
}

/**
 * Generate PDF using TCPDF (if available)
 */
function generatePDFWithTCPDF($pullsheet, $items, $pdfPath) {
    require_once dirname(__DIR__) . '/includes/tcpdf/tcpdf.php';
    
    // Create PDF
    $pdf = new TCPDF('P', 'mm', 'LETTER', true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('S-Shop Inventory System');
    $pdf->SetAuthor('S-Shop');
    $pdf->SetTitle('Pull Sheet - ' . $pullsheet['barcode']);
    
    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Add a page
    $pdf->AddPage();
    
    // Set font
    $pdf->SetFont('helvetica', '', 10);
    
    // Title
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Pull Sheet', 0, 1, 'C');
    
    // Barcode (placeholder - would need barcode plugin)
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, 'Barcode: ' . $pullsheet['barcode'], 0, 1, 'R');
    
    $pdf->Ln(5);
    
    // Show info
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 7, 'Show Information', 0, 1);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(40, 6, 'Show:', 0, 0);
    $pdf->Cell(0, 6, $pullsheet['show_name'], 0, 1);
    $pdf->Cell(40, 6, 'Theatre Space:', 0, 0);
    $pdf->Cell(0, 6, $pullsheet['theatre_space_name'] ?? 'N/A', 0, 1);
    $pdf->Cell(40, 6, 'Created By:', 0, 0);
    $pdf->Cell(0, 6, $pullsheet['creator_first'] . ' ' . $pullsheet['creator_last'], 0, 1);
    $pdf->Cell(40, 6, 'Created:', 0, 0);
    $pdf->Cell(0, 6, date('m/d/Y', strtotime($pullsheet['created_at'])), 0, 1);
    
    $pdf->Ln(5);
    
    // Items table
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 7, 'Equipment List', 0, 1);
    
    // Organize by category
    $byCategory = [];
    foreach ($items as $item) {
        $cat = $item['category_name'] ?? 'Uncategorized';
        $subcat = $item['subcategory_name'] ?? '';
        if (!isset($byCategory[$cat])) {
            $byCategory[$cat] = [];
        }
        if (!isset($byCategory[$cat][$subcat])) {
            $byCategory[$cat][$subcat] = [];
        }
        $byCategory[$cat][$subcat][] = $item;
    }
    
    foreach ($byCategory as $category => $subcategories) {
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 6, $category, 0, 1, 'L', false);
        
        foreach ($subcategories as $subcategory => $catItems) {
            if ($subcategory) {
                $pdf->SetFont('helvetica', 'I', 10);
                $pdf->Cell(10, 5, '', 0, 0);
                $pdf->Cell(0, 5, $subcategory, 0, 1);
            }
            
            // Table header
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->Cell(10, 5, '', 0, 0);
            $pdf->Cell(70, 5, 'Item', 1, 0, 'L');
            $pdf->Cell(40, 5, 'Barcode', 1, 0, 'L');
            $pdf->Cell(20, 5, 'Qty', 1, 0, 'C');
            $pdf->Cell(45, 5, 'Location', 1, 1, 'L');
            
            // Items
            $pdf->SetFont('helvetica', '', 9);
            foreach ($catItems as $item) {
                $pdf->Cell(10, 5, '', 0, 0);
                $pdf->Cell(70, 5, $item['item_name'], 1, 0, 'L');
                $pdf->Cell(40, 5, $item['item_barcode'], 1, 0, 'L');
                $pdf->Cell(20, 5, $item['quantity_needed'], 1, 0, 'C');
                $pdf->Cell(45, 5, $item['location'] ?? '', 1, 1, 'L');
            }
            
            $pdf->Ln(2);
        }
    }
    
    // Save PDF
    $pdf->Output($pdfPath, 'F');
    return $pdfPath;
}

/**
 * Generate simple HTML-based PDF (fallback)
 */
function generateSimplePDF($pullsheet, $items, $pdfPath) {
    // Create HTML content
    $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Pull Sheet - ' . htmlspecialchars($pullsheet['barcode']) . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { text-align: center; }
        .barcode { text-align: right; font-size: 14px; margin: 10px 0; }
        .info { margin: 20px 0; }
        .info div { margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .category { font-weight: bold; margin-top: 15px; }
        .subcategory { font-style: italic; margin-left: 20px; }
    </style>
</head>
<body>
    <h1>Pull Sheet</h1>
    <div class="barcode">Barcode: ' . htmlspecialchars($pullsheet['barcode']) . '</div>
    
    <div class="info">
        <h3>Show Information</h3>
        <div><strong>Show:</strong> ' . htmlspecialchars($pullsheet['show_name']) . '</div>
        <div><strong>Theatre Space:</strong> ' . htmlspecialchars($pullsheet['theatre_space_name'] ?? 'N/A') . '</div>
        <div><strong>Created By:</strong> ' . htmlspecialchars($pullsheet['creator_first'] . ' ' . $pullsheet['creator_last']) . '</div>
        <div><strong>Created:</strong> ' . date('m/d/Y', strtotime($pullsheet['created_at'])) . '</div>
    </div>
    
    <h3>Equipment List</h3>';
    
    // Organize by category
    $byCategory = [];
    foreach ($items as $item) {
        $cat = $item['category_name'] ?? 'Uncategorized';
        $subcat = $item['subcategory_name'] ?? '';
        if (!isset($byCategory[$cat])) {
            $byCategory[$cat] = [];
        }
        if (!isset($byCategory[$cat][$subcat])) {
            $byCategory[$cat][$subcat] = [];
        }
        $byCategory[$cat][$subcat][] = $item;
    }
    
    foreach ($byCategory as $category => $subcategories) {
        $html .= '<div class="category">' . htmlspecialchars($category) . '</div>';
        
        foreach ($subcategories as $subcategory => $catItems) {
            if ($subcategory) {
                $html .= '<div class="subcategory">' . htmlspecialchars($subcategory) . '</div>';
            }
            
            $html .= '<table>
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Barcode</th>
                        <th>Quantity</th>
                        <th>Location</th>
                    </tr>
                </thead>
                <tbody>';
            
            foreach ($catItems as $item) {
                $html .= '<tr>
                    <td>' . htmlspecialchars($item['item_name']) . '</td>
                    <td>' . htmlspecialchars($item['item_barcode']) . '</td>
                    <td>' . htmlspecialchars($item['quantity_needed']) . '</td>
                    <td>' . htmlspecialchars($item['location'] ?? '') . '</td>
                </tr>';
            }
            
            $html .= '</tbody></table>';
        }
    }
    
    $html .= '</body></html>';
    
    // Note: This creates an HTML file, not a true PDF
    // For production, install TCPDF or use a server-side HTML-to-PDF converter
    file_put_contents($pdfPath . '.html', $html);
    
    // Return HTML path (would need conversion to PDF)
    return $pdfPath . '.html';
}
