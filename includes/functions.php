<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/barcode/Code128.php';
require_once __DIR__ . '/barcode/PDF417.php';
require_once __DIR__ . '/pdf/SimplePDF.php';

// Settings functions
function getSetting($key, $default = '') {
    $db = getDB();
    $result = $db->fetchOne("SELECT setting_value FROM settings WHERE setting_key = ?", [$key]);
    return $result ? $result['setting_value'] : $default;
}

function setSetting($key, $value) {
    $db = getDB();
    $db->query(
        "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
         ON DUPLICATE KEY UPDATE setting_value = ?",
        [$key, $value, $value]
    );
}

// Barcode generation functions
function generateCode128Barcode($text, $format = 'png') {
    $generator = new Code128();
    if ($format === 'svg') {
        return $generator->generateSVG($text, 2, 50);
    } else {
        return $generator->generatePNG($text, 2, 50);
    }
}

function generatePDF417Barcode($text) {
    $generator = new PDF417();
    return $generator->generatePNG($text, 2, 50);
}

function generateUniqueBarcode($prefix = 'ITEM') {
    return $prefix . '-' . strtoupper(substr(uniqid(), -8));
}

// Item functions
function getItemById($id) {
    $db = getDB();
    return $db->fetchOne(
        "SELECT i.*, c.name as category_name 
         FROM items i 
         LEFT JOIN categories c ON i.category_id = c.id 
         WHERE i.id = ?",
        [$id]
    );
}

function getItemByBarcode($barcode) {
    $db = getDB();
    return $db->fetchOne(
        "SELECT i.*, c.name as category_name 
         FROM items i 
         LEFT JOIN categories c ON i.category_id = c.id 
         WHERE i.barcode = ?",
        [$barcode]
    );
}

function getAllItems() {
    $db = getDB();
    return $db->fetchAll(
        "SELECT i.*, c.name as category_name 
         FROM items i 
         LEFT JOIN categories c ON i.category_id = c.id 
         ORDER BY i.name"
    );
}

function updateItemStock($itemId, $quantityChange) {
    $db = getDB();
    $db->query(
        "UPDATE items SET in_stock_quantity = in_stock_quantity + ? WHERE id = ?",
        [$quantityChange, $itemId]
    );
}

function getItemAvailableQuantity($itemId) {
    $db = getDB();
    $item = $db->fetchOne("SELECT in_stock_quantity FROM items WHERE id = ?", [$itemId]);
    return $item ? (int)$item['in_stock_quantity'] : 0;
}

// Show functions
function getShowById($id) {
    $db = getDB();
    return $db->fetchOne(
        "SELECT s.*, t.name as theatre_space_name 
         FROM shows s 
         LEFT JOIN theatre_spaces t ON s.theatre_space_id = t.id 
         WHERE s.id = ?",
        [$id]
    );
}

function getAllShows() {
    $db = getDB();
    return $db->fetchAll(
        "SELECT s.*, t.name as theatre_space_name 
         FROM shows s 
         LEFT JOIN theatre_spaces t ON s.theatre_space_id = t.id 
         ORDER BY s.created_at DESC"
    );
}

function getActiveShows() {
    $db = getDB();
    return $db->fetchAll(
        "SELECT s.*, t.name as theatre_space_name 
         FROM shows s 
         LEFT JOIN theatre_spaces t ON s.theatre_space_id = t.id 
         WHERE s.status = 'active'
         ORDER BY s.name"
    );
}

// Pullsheet functions
function getPullsheetById($id) {
    $db = getDB();
    return $db->fetchOne(
        "SELECT p.*, s.name as show_name, s.shop_lead, s.designer 
         FROM pullsheets p 
         JOIN shows s ON p.show_id = s.id 
         WHERE p.id = ?",
        [$id]
    );
}

function getPullsheetByBarcode($barcode) {
    $db = getDB();
    return $db->fetchOne(
        "SELECT p.*, s.name as show_name, s.shop_lead, s.designer, s.theatre_space_id 
         FROM pullsheets p 
         JOIN shows s ON p.show_id = s.id 
         WHERE p.barcode = ?",
        [$barcode]
    );
}

function getPullsheetItems($pullsheetId) {
    $db = getDB();
    return $db->fetchAll(
        "SELECT pi.*, i.name as item_name, i.barcode as item_barcode, i.in_stock_quantity 
         FROM pullsheet_items pi 
         JOIN items i ON pi.item_id = i.id 
         WHERE pi.pullsheet_id = ?",
        [$pullsheetId]
    );
}

function getAllPullsheets() {
    $db = getDB();
    return $db->fetchAll(
        "SELECT p.*, s.name as show_name 
         FROM pullsheets p 
         JOIN shows s ON p.show_id = s.id 
         ORDER BY p.created_at DESC"
    );
}

// Change order functions
function getChangeOrderById($id) {
    $db = getDB();
    return $db->fetchOne(
        "SELECT co.*, s.name as show_name, s.shop_lead, s.designer 
         FROM change_orders co 
         JOIN shows s ON co.show_id = s.id 
         WHERE co.id = ?",
        [$id]
    );
}

function getChangeOrderByBarcode($barcode) {
    $db = getDB();
    return $db->fetchOne(
        "SELECT co.*, s.name as show_name, s.shop_lead, s.designer, s.theatre_space_id 
         FROM change_orders co 
         JOIN shows s ON co.show_id = s.id 
         WHERE co.barcode = ?",
        [$barcode]
    );
}

function getChangeOrderItems($changeOrderId) {
    $db = getDB();
    return $db->fetchAll(
        "SELECT coi.*, i.name as item_name, i.barcode as item_barcode, i.in_stock_quantity 
         FROM change_order_items coi 
         JOIN items i ON coi.item_id = i.id 
         WHERE coi.change_order_id = ?",
        [$changeOrderId]
    );
}

function getAllChangeOrders() {
    $db = getDB();
    return $db->fetchAll(
        "SELECT co.*, s.name as show_name 
         FROM change_orders co 
         JOIN shows s ON co.show_id = s.id 
         ORDER BY co.created_at DESC"
    );
}

// Category functions
function getAllCategories() {
    $db = getDB();
    return $db->fetchAll("SELECT * FROM categories ORDER BY name");
}

// Theatre space functions
function getAllTheatreSpaces() {
    $db = getDB();
    return $db->fetchAll("SELECT * FROM theatre_spaces ORDER BY name");
}

// Dashboard statistics
function getDashboardStats() {
    $db = getDB();
    
    $stats = [];
    
    // Pending picks
    $result = $db->fetchOne(
        "SELECT COUNT(*) as count FROM pullsheets WHERE status = 'finalized'"
    );
    $stats['pending_picks'] = $result['count'];
    
    // Pending returns (completed shows with items still checked out)
    $result = $db->fetchOne(
        "SELECT COUNT(DISTINCT show_id) as count FROM item_allocations 
         WHERE status = 'checked_out'"
    );
    $stats['pending_returns'] = $result['count'];
    
    // Active shows
    $result = $db->fetchOne(
        "SELECT COUNT(*) as count FROM shows WHERE status = 'active'"
    );
    $stats['active_shows'] = $result['count'];
    
    return $stats;
}

// Alert/message functions
function setAlert($message, $type = 'success') {
    $_SESSION['alert'] = ['message' => $message, 'type' => $type];
}

function getAlert() {
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
        unset($_SESSION['alert']);
        return $alert;
    }
    return null;
}

// Validation functions
function validateRequired($value, $fieldName) {
    if (empty($value)) {
        throw new Exception("$fieldName is required");
    }
}

function redirect($url) {
    header("Location: $url");
    exit;
}

// PDF Generation using SimplePDF
function generatePullsheetPDF($pullsheetId) {
    $pullsheet = getPullsheetById($pullsheetId);
    $items = getPullsheetItems($pullsheetId);
    
    $pdf = new SimplePDF();
    $page = $pdf->addPage(612, 792); // Letter size
    
    // Logo
    $logoPath = getSetting('logo_path');
    if ($logoPath && file_exists(__DIR__ . '/../uploads/logos/' . $logoPath)) {
        // Logo would be added here if image support was complete
        // For now, add text placeholder
        $pdf->addText($page, 50, 750, 'CMFT Sound Shop', 14, 'Helvetica');
    }
    
    // Barcode (PDF417 for pullsheets)
    $barcodeData = generatePDF417Barcode($pullsheet['barcode']);
    // Save barcode temporarily and add to PDF
    $barcodeFile = sys_get_temp_dir() . '/barcode_' . bin2hex(random_bytes(16)) . '.png';
    file_put_contents($barcodeFile, $barcodeData);
    if (file_exists($barcodeFile)) {
        $pdf->addImage($page, file_get_contents($barcodeFile), 450, 720, 100, 50);
        unlink($barcodeFile);
    }
    
    // Title
    $pdf->addText($page, 250, 700, 'PULLSHEET', 18, 'Helvetica');
    
    // Show details
    $y = 670;
    $pdf->addText($page, 50, $y, 'Show: ' . $pullsheet['show_name'], 12);
    $y -= 20;
    $pdf->addText($page, 50, $y, 'Shop Lead: ' . $pullsheet['shop_lead'], 12);
    $y -= 20;
    $pdf->addText($page, 50, $y, 'Designer: ' . $pullsheet['designer'], 12);
    $y -= 20;
    $pdf->addText($page, 50, $y, 'Created: ' . date('m/d/Y', strtotime($pullsheet['created_at'])), 12);
    $y -= 20;
    $pdf->addText($page, 50, $y, 'Barcode: ' . $pullsheet['barcode'], 10);
    
    // Table headers
    $y -= 40;
    $pdf->addText($page, 50, $y, 'Item', 12);
    $pdf->addText($page, 300, $y, 'Barcode', 12);
    $pdf->addText($page, 400, $y, 'Qty Needed', 12);
    $pdf->addText($page, 500, $y, 'Qty Picked', 12);
    $pdf->addLine($page, 50, $y - 5, 550, $y - 5);
    
    // Items
    $y -= 25;
    foreach ($items as $item) {
        $pdf->addText($page, 50, $y, substr($item['item_name'], 0, 35), 10);
        $pdf->addText($page, 300, $y, $item['item_barcode'], 10);
        $pdf->addText($page, 420, $y, (string)$item['quantity_needed'], 10);
        $pdf->addText($page, 520, $y, (string)$item['quantity_picked'], 10);
        $y -= 20;
        
        if ($y < 50) {
            $page = $pdf->addPage(612, 792);
            $y = 750;
        }
    }
    
    return $pdf->output('pullsheet_' . $pullsheetId . '.pdf', 'S');
}

function generateChangeOrderPDF($changeOrderId) {
    $changeOrder = getChangeOrderById($changeOrderId);
    $items = getChangeOrderItems($changeOrderId);
    
    $pdf = new SimplePDF();
    $page = $pdf->addPage(612, 792); // Letter size
    
    // Logo
    $logoPath = getSetting('logo_path');
    if ($logoPath && file_exists(__DIR__ . '/../uploads/logos/' . $logoPath)) {
        $pdf->addText($page, 50, 750, 'CMFT Sound Shop', 14, 'Helvetica');
    }
    
    // Barcode (PDF417 for change orders)
    $barcodeData = generatePDF417Barcode($changeOrder['barcode']);
    $barcodeFile = sys_get_temp_dir() . '/barcode_' . bin2hex(random_bytes(16)) . '.png';
    file_put_contents($barcodeFile, $barcodeData);
    if (file_exists($barcodeFile)) {
        $pdf->addImage($page, file_get_contents($barcodeFile), 450, 720, 100, 50);
        unlink($barcodeFile);
    }
    
    // Title
    $pdf->addText($page, 220, 700, 'CHANGE ORDER', 18, 'Helvetica');
    
    // Show details
    $y = 670;
    $pdf->addText($page, 50, $y, 'Show: ' . $changeOrder['show_name'], 12);
    $y -= 20;
    $pdf->addText($page, 50, $y, 'Shop Lead: ' . $changeOrder['shop_lead'], 12);
    $y -= 20;
    $pdf->addText($page, 50, $y, 'Designer: ' . $changeOrder['designer'], 12);
    $y -= 20;
    $pdf->addText($page, 50, $y, 'Created: ' . date('m/d/Y', strtotime($changeOrder['created_at'])), 12);
    $y -= 20;
    $pdf->addText($page, 50, $y, 'Barcode: ' . $changeOrder['barcode'], 10);
    
    // Table headers
    $y -= 40;
    $pdf->addText($page, 50, $y, 'Item', 12);
    $pdf->addText($page, 280, $y, 'Barcode', 12);
    $pdf->addText($page, 380, $y, 'Type', 12);
    $pdf->addText($page, 480, $y, 'Quantity', 12);
    $pdf->addLine($page, 50, $y - 5, 550, $y - 5);
    
    // Items
    $y -= 25;
    foreach ($items as $item) {
        $pdf->addText($page, 50, $y, substr($item['item_name'], 0, 30), 10);
        $pdf->addText($page, 280, $y, $item['item_barcode'], 10);
        $pdf->addText($page, 380, $y, ucfirst($item['type']), 10);
        $pdf->addText($page, 490, $y, (string)abs($item['quantity_change']), 10);
        $y -= 20;
        
        if ($y < 50) {
            $page = $pdf->addPage(612, 792);
            $y = 750;
        }
    }
    
    return $pdf->output('change_order_' . $changeOrderId . '.pdf', 'S');
}
