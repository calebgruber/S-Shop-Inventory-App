<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/helpers.php';

requireAuth();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php?error=' . urlencode('Invalid change order ID'));
    exit;
}

$changeOrderId = (int)$_GET['id'];
$changeOrder = getChangeOrderById($changeOrderId);

if (!$changeOrder) {
    header('Location: index.php?error=' . urlencode('Change order not found'));
    exit;
}

// Check if PDF already exists
if (!empty($changeOrder['pdf_path']) && file_exists($changeOrder['pdf_path'])) {
    // Serve existing PDF
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="change-order-' . $changeOrder['barcode'] . '.pdf"');
    readfile($changeOrder['pdf_path']);
    exit;
}

// Only approved or later status can generate PDF
if (!in_array($changeOrder['status'], ['approved', 'picked', 'returned'])) {
    header('Location: view.php?id=' . $changeOrderId . '&error=' . urlencode('Change order must be approved first'));
    exit;
}

// Generate PDF
$pdfPath = generateChangeOrderPDF($changeOrderId);

if ($pdfPath) {
    // Update database with PDF path
    $query = "UPDATE change_orders SET pdf_path = ? WHERE id = ?";
    executeQuery($query, [$pdfPath, $changeOrderId], 'si');
    
    // Serve PDF
    if (file_exists($pdfPath)) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="change-order-' . $changeOrder['barcode'] . '.pdf"');
        readfile($pdfPath);
        exit;
    }
}

// Error generating PDF
header('Location: view.php?id=' . $changeOrderId . '&error=' . urlencode('Failed to generate PDF'));
exit;

/**
 * Generate PDF for change order
 */
function generateChangeOrderPDF($changeOrderId) {
    $changeOrder = getChangeOrderById($changeOrderId);
    $items = getChangeOrderItems($changeOrderId);
    
    // Create PDF directory if needed
    $pdfDir = dirname(__DIR__) . '/pdfs/change-orders';
    if (!file_exists($pdfDir)) {
        mkdir($pdfDir, 0755, true);
    }
    
    $pdfFilename = $changeOrder['barcode'] . '.pdf';
    $pdfPath = $pdfDir . '/' . $pdfFilename;
    
    // Check if TCPDF is available
    $tcpdfPath = dirname(__DIR__) . '/includes/tcpdf/tcpdf.php';
    
    if (file_exists($tcpdfPath)) {
        return generatePDFWithTCPDF($changeOrder, $items, $pdfPath);
    } else {
        return generateSimplePDF($changeOrder, $items, $pdfPath);
    }
}

/**
 * Generate PDF using TCPDF (if available)
 */
function generatePDFWithTCPDF($changeOrder, $items, $pdfPath) {
    require_once dirname(__DIR__) . '/includes/tcpdf/tcpdf.php';
    
    // Create PDF
    $pdf = new TCPDF('P', 'mm', 'LETTER', true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('S-Shop Inventory System');
    $pdf->SetAuthor('S-Shop');
    $pdf->SetTitle('Change Order - ' . $changeOrder['barcode']);
    
    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Add a page
    $pdf->AddPage();
    
    // Set font
    $pdf->SetFont('helvetica', '', 10);
    
    // Title
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Change Order', 0, 1, 'C');
    
    // Barcode (CHG- prefix)
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, 'Barcode: ' . $changeOrder['barcode'], 0, 1, 'R');
    
    $pdf->Ln(5);
    
    // Show info
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 7, 'Show Information', 0, 1);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(40, 6, 'Show:', 0, 0);
    $pdf->Cell(0, 6, $changeOrder['show_name'], 0, 1);
    $pdf->Cell(40, 6, 'Theatre Space:', 0, 0);
    $pdf->Cell(0, 6, $changeOrder['theatre_space_name'] ?? 'N/A', 0, 1);
    $pdf->Cell(40, 6, 'Created By:', 0, 0);
    $pdf->Cell(0, 6, $changeOrder['creator_first'] . ' ' . $changeOrder['creator_last'], 0, 1);
    $pdf->Cell(40, 6, 'Created:', 0, 0);
    $pdf->Cell(0, 6, date('m/d/Y', strtotime($changeOrder['created_at'])), 0, 1);
    
    $pdf->Ln(5);
    
    // Separate items by action
    $itemsToAdd = [];
    $itemsToRemove = [];
    foreach ($items as $item) {
        if ($item['action'] === 'add') {
            $itemsToAdd[] = $item;
        } else {
            $itemsToRemove[] = $item;
        }
    }
    
    // Items to Add
    if (!empty($itemsToAdd)) {
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetFillColor(200, 255, 200);
        $pdf->Cell(0, 7, 'Items to Add (' . count($itemsToAdd) . ')', 0, 1, 'L', true);
        
        // Table header
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(70, 5, 'Item', 1, 0, 'L');
        $pdf->Cell(40, 5, 'Barcode', 1, 0, 'L');
        $pdf->Cell(20, 5, 'Qty', 1, 0, 'C');
        $pdf->Cell(45, 5, 'Location', 1, 1, 'L');
        
        // Items
        $pdf->SetFont('helvetica', '', 9);
        foreach ($itemsToAdd as $item) {
            $pdf->Cell(70, 5, $item['item_name'], 1, 0, 'L');
            $pdf->Cell(40, 5, $item['item_barcode'], 1, 0, 'L');
            $pdf->Cell(20, 5, '+' . $item['quantity'], 1, 0, 'C');
            $pdf->Cell(45, 5, $item['location'] ?? '', 1, 1, 'L');
        }
        
        $pdf->Ln(5);
    }
    
    // Items to Remove
    if (!empty($itemsToRemove)) {
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetFillColor(255, 200, 200);
        $pdf->Cell(0, 7, 'Items to Remove (' . count($itemsToRemove) . ')', 0, 1, 'L', true);
        
        // Table header
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(70, 5, 'Item', 1, 0, 'L');
        $pdf->Cell(40, 5, 'Barcode', 1, 0, 'L');
        $pdf->Cell(20, 5, 'Qty', 1, 0, 'C');
        $pdf->Cell(45, 5, 'Location', 1, 1, 'L');
        
        // Items
        $pdf->SetFont('helvetica', '', 9);
        foreach ($itemsToRemove as $item) {
            $pdf->Cell(70, 5, $item['item_name'], 1, 0, 'L');
            $pdf->Cell(40, 5, $item['item_barcode'], 1, 0, 'L');
            $pdf->Cell(20, 5, '-' . $item['quantity'], 1, 0, 'C');
            $pdf->Cell(45, 5, $item['location'] ?? '', 1, 1, 'L');
        }
    }
    
    // Save PDF
    $pdf->Output($pdfPath, 'F');
    return $pdfPath;
}

/**
 * Generate simple HTML-based PDF (fallback)
 */
function generateSimplePDF($changeOrder, $items, $pdfPath) {
    // Separate items by action
    $itemsToAdd = [];
    $itemsToRemove = [];
    foreach ($items as $item) {
        if ($item['action'] === 'add') {
            $itemsToAdd[] = $item;
        } else {
            $itemsToRemove[] = $item;
        }
    }
    
    // Create HTML content
    $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Change Order - ' . htmlspecialchars($changeOrder['barcode']) . '</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { text-align: center; }
        .barcode { text-align: right; font-size: 14px; margin: 10px 0; }
        .info { margin: 20px 0; }
        .info div { margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .section-add { background-color: #e6ffe6; padding: 10px; margin-top: 20px; }
        .section-remove { background-color: #ffe6e6; padding: 10px; margin-top: 20px; }
        .section-title { font-weight: bold; margin-bottom: 10px; }
    </style>
</head>
<body>
    <h1>Change Order</h1>
    <div class="barcode">Barcode: ' . htmlspecialchars($changeOrder['barcode']) . '</div>
    
    <div class="info">
        <h3>Show Information</h3>
        <div><strong>Show:</strong> ' . htmlspecialchars($changeOrder['show_name']) . '</div>
        <div><strong>Theatre Space:</strong> ' . htmlspecialchars($changeOrder['theatre_space_name'] ?? 'N/A') . '</div>
        <div><strong>Created By:</strong> ' . htmlspecialchars($changeOrder['creator_first'] . ' ' . $changeOrder['creator_last']) . '</div>
        <div><strong>Created:</strong> ' . date('m/d/Y', strtotime($changeOrder['created_at'])) . '</div>
    </div>';
    
    if (!empty($itemsToAdd)) {
        $html .= '<div class="section-add">
        <div class="section-title">Items to Add (' . count($itemsToAdd) . ')</div>
        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Barcode</th>
                    <th>Quantity</th>
                    <th>Location</th>
                </tr>
            </thead>
            <tbody>';
        
        foreach ($itemsToAdd as $item) {
            $html .= '<tr>
                <td>' . htmlspecialchars($item['item_name']) . '</td>
                <td>' . htmlspecialchars($item['item_barcode']) . '</td>
                <td>+' . htmlspecialchars($item['quantity']) . '</td>
                <td>' . htmlspecialchars($item['location'] ?? '') . '</td>
            </tr>';
        }
        
        $html .= '</tbody></table></div>';
    }
    
    if (!empty($itemsToRemove)) {
        $html .= '<div class="section-remove">
        <div class="section-title">Items to Remove (' . count($itemsToRemove) . ')</div>
        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Barcode</th>
                    <th>Quantity</th>
                    <th>Location</th>
                </tr>
            </thead>
            <tbody>';
        
        foreach ($itemsToRemove as $item) {
            $html .= '<tr>
                <td>' . htmlspecialchars($item['item_name']) . '</td>
                <td>' . htmlspecialchars($item['item_barcode']) . '</td>
                <td>-' . htmlspecialchars($item['quantity']) . '</td>
                <td>' . htmlspecialchars($item['location'] ?? '') . '</td>
            </tr>';
        }
        
        $html .= '</tbody></table></div>';
    }
    
    $html .= '</body></html>';
    
    // Note: This creates an HTML file, not a true PDF
    // For production, install TCPDF or use a server-side HTML-to-PDF converter
    file_put_contents($pdfPath . '.html', $html);
    
    // Return HTML path (would need conversion to PDF)
    return $pdfPath . '.html';
}
