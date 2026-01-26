<?php
require_once 'config.php';
require_once 'db.php';
require_once 'functions.php';

$db = getDB();

// Get parameters
$type = $_GET['type'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// For PDF generation, we'll use TCPDF or a simple HTML-to-PDF approach
// This is a simplified implementation that generates HTML that can be printed or converted to PDF

class SimplePDF {
    private $html = '';
    private $title = '';
    
    public function __construct($title = '') {
        $this->title = $title;
    }
    
    public function addHeader($logoPath, $barcode = '', $barcodeType = 'code128') {
        $this->html .= '<div style="margin-bottom: 20px; overflow: hidden;">';
        
        // Logo on left
        if (file_exists($logoPath)) {
            $this->html .= '<div style="float: left; width: 30%;">';
            $this->html .= '<img src="' . $logoPath . '" style="max-width: 150px; max-height: 75px;">';
            $this->html .= '</div>';
        }
        
        // Title in center
        $this->html .= '<div style="float: left; width: 40%; text-align: center; padding-top: 20px;">';
        $this->html .= '<h1 style="margin: 0; font-size: 24px;">' . htmlspecialchars($this->title) . '</h1>';
        $this->html .= '</div>';
        
        // Barcode on right
        if ($barcode) {
            $this->html .= '<div style="float: right; width: 30%; text-align: right;">';
            $this->html .= '<img src="barcode_generator.php?type=' . $barcodeType . '&data=' . urlencode($barcode) . '" style="max-width: 150px;">';
            $this->html .= '</div>';
        }
        
        $this->html .= '<div style="clear: both;"></div>';
        $this->html .= '</div>';
        $this->html .= '<hr style="border: 1px solid #ccc; margin: 20px 0;">';
    }
    
    public function addContent($content) {
        $this->html .= $content;
    }
    
    public function output($filename = 'document.pdf') {
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . htmlspecialchars($this->title) . '</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f4f4f4; font-weight: bold; }
        .info-table { margin-bottom: 20px; }
        .info-table td { border: none; padding: 5px 10px; }
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 10px;">
        <button onclick="window.print()">Print / Save as PDF</button>
        <button onclick="window.close()">Close</button>
    </div>
    ' . $this->html . '
</body>
</html>';
        
        echo $html;
    }
}

// Handle different PDF types
if ($type === 'pull_sheet' && $id > 0) {
    // Generate pull sheet PDF
    $stmt = $db->prepare("SELECT ps.*, s.name as show_name, s.designer, s.shop_lead, ts.name as theatre_space_name
                          FROM pull_sheets ps
                          INNER JOIN shows s ON ps.show_id = s.id
                          LEFT JOIN theatre_spaces ts ON s.theatre_space_id = ts.id
                          WHERE ps.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $pullSheet = $stmt->get_result()->fetch_assoc();
    
    if (!$pullSheet) {
        die('Pull sheet not found');
    }
    
    // Get items
    $stmt = $db->prepare("SELECT psi.*, i.name, i.barcode
                          FROM pull_sheet_items psi
                          INNER JOIN items i ON psi.item_id = i.id
                          WHERE psi.pull_sheet_id = ?
                          ORDER BY i.name");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $items = $stmt->get_result();
    
    $pdf = new SimplePDF('Pull Sheet');
    $pdf->addHeader(getSetting('logo_path', 'assets/logo.png'), $pullSheet['barcode'], 'pdf417');
    
    $content = '<table class="info-table">
        <tr><td><strong>Show:</strong></td><td>' . htmlspecialchars($pullSheet['show_name']) . '</td></tr>
        <tr><td><strong>Designer:</strong></td><td>' . htmlspecialchars($pullSheet['designer'] ?? 'N/A') . '</td></tr>
        <tr><td><strong>Shop Lead:</strong></td><td>' . htmlspecialchars($pullSheet['shop_lead'] ?? 'N/A') . '</td></tr>
        <tr><td><strong>Theatre Space:</strong></td><td>' . htmlspecialchars($pullSheet['theatre_space_name'] ?? 'N/A') . '</td></tr>
        <tr><td><strong>Created:</strong></td><td>' . date('M d, Y g:i A', strtotime($pullSheet['created_at'])) . '</td></tr>
        <tr><td><strong>Status:</strong></td><td>' . ucfirst($pullSheet['status']) . '</td></tr>
    </table>';
    
    $content .= '<h2>Items</h2>';
    $content .= '<table>
        <thead>
            <tr>
                <th>Item Name</th>
                <th>Barcode</th>
                <th>Quantity Needed</th>
                <th>Quantity Picked</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>';
    
    while ($item = $items->fetch_assoc()) {
        $content .= '<tr>
            <td>' . htmlspecialchars($item['name']) . '</td>
            <td>' . htmlspecialchars($item['barcode']) . '</td>
            <td>' . $item['quantity_needed'] . '</td>
            <td>' . $item['quantity_picked'] . '</td>
            <td>&nbsp;</td>
        </tr>';
    }
    
    $content .= '</tbody></table>';
    
    $pdf->addContent($content);
    $pdf->output('pull-sheet-' . $pullSheet['barcode'] . '.pdf');
    
} elseif ($type === 'change_order' && $id > 0) {
    // Generate change order PDF
    $stmt = $db->prepare("SELECT co.*, s.name as show_name, s.designer, s.shop_lead, ts.name as theatre_space_name
                          FROM change_orders co
                          INNER JOIN shows s ON co.show_id = s.id
                          LEFT JOIN theatre_spaces ts ON s.theatre_space_id = ts.id
                          WHERE co.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $changeOrder = $stmt->get_result()->fetch_assoc();
    
    if (!$changeOrder) {
        die('Change order not found');
    }
    
    // Get items
    $stmt = $db->prepare("SELECT coi.*, i.name, i.barcode
                          FROM change_order_items coi
                          INNER JOIN items i ON coi.item_id = i.id
                          WHERE coi.change_order_id = ?
                          ORDER BY i.name");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $items = $stmt->get_result();
    
    $pdf = new SimplePDF('Change Order');
    $pdf->addHeader(getSetting('logo_path', 'assets/logo.png'), $changeOrder['barcode'], 'pdf417');
    
    $content = '<table class="info-table">
        <tr><td><strong>Show:</strong></td><td>' . htmlspecialchars($changeOrder['show_name']) . '</td></tr>
        <tr><td><strong>Designer:</strong></td><td>' . htmlspecialchars($changeOrder['designer'] ?? 'N/A') . '</td></tr>
        <tr><td><strong>Shop Lead:</strong></td><td>' . htmlspecialchars($changeOrder['shop_lead'] ?? 'N/A') . '</td></tr>
        <tr><td><strong>Theatre Space:</strong></td><td>' . htmlspecialchars($changeOrder['theatre_space_name'] ?? 'N/A') . '</td></tr>
        <tr><td><strong>Created:</strong></td><td>' . date('M d, Y g:i A', strtotime($changeOrder['created_at'])) . '</td></tr>
        <tr><td><strong>Status:</strong></td><td>' . ucfirst($changeOrder['status']) . '</td></tr>
    </table>';
    
    $content .= '<h2>Items</h2>';
    $content .= '<table>
        <thead>
            <tr>
                <th>Item Name</th>
                <th>Barcode</th>
                <th>Action</th>
                <th>Quantity</th>
                <th>Processed</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>';
    
    while ($item = $items->fetch_assoc()) {
        $content .= '<tr>
            <td>' . htmlspecialchars($item['name']) . '</td>
            <td>' . htmlspecialchars($item['barcode']) . '</td>
            <td>' . ucfirst($item['action_type']) . '</td>
            <td>' . $item['quantity'] . '</td>
            <td>' . $item['quantity_processed'] . '</td>
            <td>&nbsp;</td>
        </tr>';
    }
    
    $content .= '</tbody></table>';
    
    $pdf->addContent($content);
    $pdf->output('change-order-' . $changeOrder['barcode'] . '.pdf');
    
} elseif ($type === 'report') {
    // Generate report PDF
    $reportType = $_GET['report_type'] ?? 'all';
    $showId = isset($_GET['show_id']) ? (int)$_GET['show_id'] : 0;
    $theatreSpaceId = isset($_GET['theatre_space_id']) ? (int)$_GET['theatre_space_id'] : 0;
    
    $reportTitle = 'Inventory Report';
    $items = [];
    
    if ($reportType === 'all') {
        $reportTitle = 'All Inventory Items';
        $items = $db->query("SELECT i.*, c.name as category_name 
                            FROM items i 
                            LEFT JOIN categories c ON i.category_id = c.id 
                            ORDER BY i.name");
                            
    } elseif ($reportType === 'show' && $showId > 0) {
        $stmt = $db->prepare("SELECT name FROM shows WHERE id = ?");
        $stmt->bind_param("i", $showId);
        $stmt->execute();
        $show = $stmt->get_result()->fetch_assoc();
        
        if ($show) {
            $reportTitle = 'Items for Show: ' . $show['name'];
            
            $sql = "SELECT DISTINCT i.*, c.name as category_name
                    FROM items i
                    LEFT JOIN categories c ON i.category_id = c.id
                    LEFT JOIN pull_sheet_items psi ON i.id = psi.item_id
                    LEFT JOIN pull_sheets ps ON psi.pull_sheet_id = ps.id AND ps.show_id = ?
                    LEFT JOIN change_order_items coi ON i.id = coi.item_id
                    LEFT JOIN change_orders co ON coi.change_order_id = co.id AND co.show_id = ?
                    WHERE ps.show_id = ? OR co.show_id = ?
                    ORDER BY i.name";
            $stmt = $db->prepare($sql);
            $stmt->bind_param("iiii", $showId, $showId, $showId, $showId);
            $stmt->execute();
            $items = $stmt->get_result();
        }
    }
    
    $pdf = new SimplePDF($reportTitle);
    $pdf->addHeader(getSetting('logo_path', 'assets/logo.png'));
    
    $content = '<h2>' . htmlspecialchars($reportTitle) . '</h2>';
    $content .= '<p>Generated: ' . date('M d, Y g:i A') . '</p>';
    
    $content .= '<table>
        <thead>
            <tr>
                <th>Item Name</th>
                <th>Barcode</th>
                <th>Category</th>
                <th>Total Qty</th>
                <th>Available</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>';
    
    while ($item = $items->fetch_assoc()) {
        $status = $item['available_quantity'] === 0 ? 'Out of Stock' : 
                 ($item['available_quantity'] < 5 ? 'Low Stock' : 'In Stock');
        
        $content .= '<tr>
            <td>' . htmlspecialchars($item['name']) . '</td>
            <td>' . htmlspecialchars($item['barcode']) . '</td>
            <td>' . htmlspecialchars($item['category_name'] ?? '-') . '</td>
            <td>' . $item['total_quantity'] . '</td>
            <td>' . $item['available_quantity'] . '</td>
            <td>' . $status . '</td>
        </tr>';
    }
    
    $content .= '</tbody></table>';
    
    $pdf->addContent($content);
    $pdf->output('inventory-report.pdf');
    
} elseif ($type === 'labels') {
    // Generate Avery 8195 labels
    $pullSheetId = isset($_GET['pull_sheet_id']) ? (int)$_GET['pull_sheet_id'] : 0;
    
    if ($pullSheetId > 0) {
        $stmt = $db->prepare("SELECT psi.*, i.name, i.barcode
                              FROM pull_sheet_items psi
                              INNER JOIN items i ON psi.item_id = i.id
                              WHERE psi.pull_sheet_id = ?
                              ORDER BY i.name");
        $stmt->bind_param("i", $pullSheetId);
        $stmt->execute();
        $items = $stmt->get_result();
        
        // Avery 8195: 2.75" x 1.75" labels, 3 columns x 4 rows per page
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Item Labels</title>
    <style>
        body { margin: 0; padding: 0; }
        .label-page { width: 8.5in; height: 11in; padding: 0.5in; }
        .label { 
            width: 2.75in; 
            height: 1.75in; 
            float: left; 
            border: 1px dashed #ccc;
            padding: 10px;
            box-sizing: border-box;
            text-align: center;
        }
        .label-name { font-weight: bold; font-size: 14px; margin-bottom: 5px; }
        .label-barcode { font-family: monospace; font-size: 12px; margin-top: 5px; }
        @media print {
            .label { border: none; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="padding: 10px;">
        <button onclick="window.print()">Print Labels</button>
        <button onclick="window.close()">Close</button>
    </div>
    <div class="label-page">';
        
        $labelCount = 0;
        while ($item = $items->fetch_assoc()) {
            for ($i = 0; $i < $item['quantity_needed']; $i++) {
                if ($labelCount > 0 && $labelCount % 12 === 0) {
                    $html .= '</div><div class="label-page" style="page-break-before: always;">';
                }
                
                $html .= '<div class="label">
                    <div class="label-name">' . htmlspecialchars($item['name']) . '</div>
                    <img src="barcode_generator.php?type=code128&data=' . urlencode($item['barcode']) . '" style="max-width: 90%; height: 60px;">
                    <div class="label-barcode">' . htmlspecialchars($item['barcode']) . '</div>
                </div>';
                
                $labelCount++;
            }
        }
        
        $html .= '</div>
</body>
</html>';
        
        echo $html;
    }
    
} else {
    die('Invalid PDF type or missing parameters');
}
