<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/vendor/autoload.php';

use Picqer\Barcode\BarcodeGeneratorPNG;
use Picqer\Barcode\BarcodeGeneratorSVG;

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
    if ($format === 'svg') {
        $generator = new BarcodeGeneratorSVG();
        return $generator->getBarcode($text, $generator::TYPE_CODE_128);
    } else {
        $generator = new BarcodeGeneratorPNG();
        return $generator->getBarcode($text, $generator::TYPE_CODE_128, 2, 50);
    }
}

function generatePDF417Barcode($text) {
    $generator = new BarcodeGeneratorPNG();
    return $generator->getBarcode($text, $generator::TYPE_PDF_417, 2, 50);
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

// PDF Generation using TCPDF
function generatePullsheetPDF($pullsheetId) {
    require_once __DIR__ . '/vendor/autoload.php';
    
    $pullsheet = getPullsheetById($pullsheetId);
    $items = getPullsheetItems($pullsheetId);
    
    $pdf = new TCPDF('P', 'mm', 'LETTER', true, 'UTF-8');
    $pdf->SetCreator('Sound Shop Inventory');
    $pdf->SetAuthor('Sound Shop');
    $pdf->SetTitle('Pullsheet - ' . $pullsheet['show_name']);
    
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(true, 15);
    $pdf->AddPage();
    
    // Logo
    $logoPath = getSetting('logo_path');
    if ($logoPath && file_exists(UPLOAD_DIR . $logoPath)) {
        $pdf->Image(UPLOAD_DIR . $logoPath, 15, 10, 40);
    }
    
    // Barcode
    $barcodeData = generatePDF417Barcode($pullsheet['barcode']);
    $barcodeFile = tempnam(sys_get_temp_dir(), 'barcode') . '.png';
    file_put_contents($barcodeFile, $barcodeData);
    $pdf->Image($barcodeFile, 155, 10, 40);
    unlink($barcodeFile);
    
    $pdf->SetY(35);
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'PULLSHEET', 0, 1, 'C');
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 6, 'Show: ' . $pullsheet['show_name'], 0, 1);
    $pdf->Cell(0, 6, 'Shop Lead: ' . $pullsheet['shop_lead'], 0, 1);
    $pdf->Cell(0, 6, 'Designer: ' . $pullsheet['designer'], 0, 1);
    $pdf->Cell(0, 6, 'Created: ' . date('m/d/Y', strtotime($pullsheet['created_at'])), 0, 1);
    
    $pdf->Ln(5);
    
    // Items table
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(80, 7, 'Item', 1);
    $pdf->Cell(30, 7, 'Barcode', 1);
    $pdf->Cell(30, 7, 'Qty Needed', 1);
    $pdf->Cell(30, 7, 'Qty Picked', 1);
    $pdf->Ln();
    
    $pdf->SetFont('helvetica', '', 9);
    foreach ($items as $item) {
        $pdf->Cell(80, 6, $item['item_name'], 1);
        $pdf->Cell(30, 6, $item['item_barcode'], 1);
        $pdf->Cell(30, 6, $item['quantity_needed'], 1, 0, 'C');
        $pdf->Cell(30, 6, $item['quantity_picked'], 1, 0, 'C');
        $pdf->Ln();
    }
    
    return $pdf->Output('', 'S');
}

function generateChangeOrderPDF($changeOrderId) {
    require_once __DIR__ . '/vendor/autoload.php';
    
    $changeOrder = getChangeOrderById($changeOrderId);
    $items = getChangeOrderItems($changeOrderId);
    
    $pdf = new TCPDF('P', 'mm', 'LETTER', true, 'UTF-8');
    $pdf->SetCreator('Sound Shop Inventory');
    $pdf->SetAuthor('Sound Shop');
    $pdf->SetTitle('Change Order - ' . $changeOrder['show_name']);
    
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(true, 15);
    $pdf->AddPage();
    
    // Logo
    $logoPath = getSetting('logo_path');
    if ($logoPath && file_exists(UPLOAD_DIR . $logoPath)) {
        $pdf->Image(UPLOAD_DIR . $logoPath, 15, 10, 40);
    }
    
    // Barcode
    $barcodeData = generatePDF417Barcode($changeOrder['barcode']);
    $barcodeFile = tempnam(sys_get_temp_dir(), 'barcode') . '.png';
    file_put_contents($barcodeFile, $barcodeData);
    $pdf->Image($barcodeFile, 155, 10, 40);
    unlink($barcodeFile);
    
    $pdf->SetY(35);
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'CHANGE ORDER', 0, 1, 'C');
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 6, 'Show: ' . $changeOrder['show_name'], 0, 1);
    $pdf->Cell(0, 6, 'Shop Lead: ' . $changeOrder['shop_lead'], 0, 1);
    $pdf->Cell(0, 6, 'Designer: ' . $changeOrder['designer'], 0, 1);
    $pdf->Cell(0, 6, 'Created: ' . date('m/d/Y', strtotime($changeOrder['created_at'])), 0, 1);
    
    $pdf->Ln(5);
    
    // Items table
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(80, 7, 'Item', 1);
    $pdf->Cell(30, 7, 'Barcode', 1);
    $pdf->Cell(25, 7, 'Type', 1);
    $pdf->Cell(30, 7, 'Quantity', 1);
    $pdf->Ln();
    
    $pdf->SetFont('helvetica', '', 9);
    foreach ($items as $item) {
        $pdf->Cell(80, 6, $item['item_name'], 1);
        $pdf->Cell(30, 6, $item['item_barcode'], 1);
        $pdf->Cell(25, 6, ucfirst($item['type']), 1, 0, 'C');
        $pdf->Cell(30, 6, abs($item['quantity_change']), 1, 0, 'C');
        $pdf->Ln();
    }
    
    return $pdf->Output('', 'S');
}
