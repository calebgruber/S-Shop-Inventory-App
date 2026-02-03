<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/barcode/Code128.php';
require_once __DIR__ . '/barcode/PDF417.php';
require_once __DIR__ . '/pdf/SimplePDF.php';

// Error logging function
function logMessage($message, $level = 'INFO') {
    $logDir = __DIR__ . '/../logs';
    if (!file_exists($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . '/app_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [$level] $message" . PHP_EOL;
    
    // Write to log file
    @file_put_contents($logFile, $logEntry, FILE_APPEND);
    
    // Also log to PHP error log for critical errors
    if (in_array($level, ['ERROR', 'CRITICAL'])) {
        error_log("S-Shop [$level]: $message");
    }
}

// Exception handler
function logException($e, $context = '') {
    $message = $context ? "$context: " : '';
    $message .= get_class($e) . ': ' . $e->getMessage();
    $message .= ' in ' . $e->getFile() . ':' . $e->getLine();
    logMessage($message, 'ERROR');
    logMessage('Stack trace: ' . $e->getTraceAsString(), 'DEBUG');
}

// Settings functions
function getSetting($key, $default = '') {
    try {
        $db = getDB();
        $result = $db->fetchOne("SELECT setting_value FROM settings WHERE setting_key = ?", [$key]);
        return $result ? $result['setting_value'] : $default;
    } catch (Exception $e) {
        logException($e, "Error getting setting '$key'");
        return $default;
    }
}

function setSetting($key, $value) {
    try {
        $db = getDB();
        $db->query(
            "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
             ON DUPLICATE KEY UPDATE setting_value = ?",
            [$key, $value, $value]
        );
        logMessage("Setting updated: $key", 'INFO');
    } catch (Exception $e) {
        logException($e, "Error setting '$key'");
        throw $e;
    }
}

// Barcode generation functions using barcodeapi.org
function generateCode128Barcode($text, $format = 'png') {
    // Use barcodeapi.org for Code128 barcodes
    $url = 'https://barcodeapi.org/api/128/' . urlencode($text);
    $imageData = @file_get_contents($url);
    
    if ($imageData === false) {
        // Fallback to old method if API fails
        $generator = new Code128();
        return $generator->generatePNG($text, 2, 50);
    }
    
    return $imageData;
}

function generatePDF417Barcode($text) {
    // Use barcodeapi.org for PDF417 barcodes
    $url = 'https://barcodeapi.org/api/pdf417/' . urlencode($text);
    $imageData = @file_get_contents($url);
    
    if ($imageData === false) {
        // Fallback to old method if API fails
        $generator = new PDF417();
        return $generator->generatePNG($text, 2, 50);
    }
    
    return $imageData;
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
        "SELECT i.*, c.name as category_name, sc.name as subcategory_name 
         FROM items i 
         LEFT JOIN categories c ON i.category_id = c.id 
         LEFT JOIN subcategories sc ON i.subcategory_id = sc.id
         ORDER BY c.name, sc.name, i.name"
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
         LEFT JOIN shows s ON p.show_id = s.id 
         WHERE p.id = ?",
        [$id]
    );
}

function getPullsheetByBarcode($barcode) {
    $db = getDB();
    return $db->fetchOne(
        "SELECT p.*, s.name as show_name, s.shop_lead, s.designer, s.theatre_space_id 
         FROM pullsheets p 
         LEFT JOIN shows s ON p.show_id = s.id 
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
         LEFT JOIN shows s ON co.show_id = s.id 
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

function getAllSubcategories() {
    $db = getDB();
    return $db->fetchAll("SELECT s.*, c.name as category_name FROM subcategories s LEFT JOIN categories c ON s.category_id = c.id ORDER BY s.name");
}

function getSubcategoriesByCategory($categoryId) {
    $db = getDB();
    return $db->fetchAll("SELECT * FROM subcategories WHERE category_id = ? ORDER BY name", [$categoryId]);
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
    
    // Pending student requests
    $result = $db->fetchOne(
        "SELECT COUNT(*) as count FROM student_requests WHERE status = 'pending'"
    );
    $stats['pending_student_requests'] = $result ? $result['count'] : 0;
    
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

function redirect($url = null) {
    // Ignores $url parameter - always reloads the current page
    // This is intentional to prevent accidental redirects away from the current page
    header("Location: " . $_SERVER['PHP_SELF'] . ($_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : ''));
    exit;
}

// Authentication functions
function requireLogin() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['user_id'])) {
        header('Location: login');
        exit;
    }
}

function getCurrentUser() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'email' => $_SESSION['user_email'],
        'name' => $_SESSION['user_name'],
        'role' => $_SESSION['user_role']
    ];
}

function isAdmin() {
    $user = getCurrentUser();
    return $user && $user['role'] === 'admin';
}

function isDesigner() {
    $user = getCurrentUser();
    return $user && in_array($user['role'], ['admin', 'designer']);
}

function isStudent() {
    $user = getCurrentUser();
    return $user && $user['role'] === 'student';
}

function isProductionAudio() {
    $user = getCurrentUser();
    return $user && $user['role'] === 'production_audio';
}

function requireRole($role) {
    $user = getCurrentUser();
    if (!$user) {
        header('Location: login');
        exit;
    }
    
    $allowedRoles = is_array($role) ? $role : [$role];
    if (!in_array($user['role'], $allowedRoles)) {
        setAlert('You do not have permission to access this page.', 'danger');
        header('Location: index');
        exit;
    }
}

function hasPermission($permissionKey) {
    $user = getCurrentUser();
    if (!$user) {
        return false;
    }
    
    // Admins have all permissions
    if ($user['role'] === 'admin') {
        return true;
    }
    
    // Check user-specific permissions
    $db = getDB();
    $perm = $db->fetchOne(
        "SELECT can_access FROM user_permissions WHERE user_id = ? AND permission_key = ?",
        [$user['id'], $permissionKey]
    );
    
    // Default permissions based on role
    if (!$perm) {
        if ($user['role'] === 'designer') {
            // Designers can view dashboard, submit student requests (not approve), view pullsheets, change orders, and quick lookup
            $designerPermissions = ['dashboard', 'student_requests', 'pullsheets', 
                                   'change_orders', 'quick_lookup', 'shows', 'items'];
            return in_array($permissionKey, $designerPermissions);
        } else if ($user['role'] === 'production_audio') {
            // Production Audio: read-only inventory, create orders, assigned to shows, can pick/return (with signature)
            $productionAudioPermissions = ['dashboard', 'items', 'shows', 'pullsheets', 
                                          'change_orders', 'pick_mode', 'return_mode', 
                                          'student_requests', 'quick_lookup'];
            return in_array($permissionKey, $productionAudioPermissions);
        } else if ($user['role'] === 'student') {
            // Students can only view dashboard, read-only inventory, and make requests
            $studentPermissions = ['dashboard', 'student_requests', 'items', 'quick_lookup'];
            return in_array($permissionKey, $studentPermissions);
        }
        return false;
    }
    
    return (bool)$perm['can_access'];
}

function getUserAvatarUrl($user) {
    $seed = $user['email'] ?? $user['id'] ?? 'default';
    return "https://api.dicebear.com/7.x/thumbs/svg?seed=" . urlencode($seed);
}

// PDF Generation using SimplePDF
function generatePullsheetPDF($pullsheetId) {
    $pullsheet = getPullsheetById($pullsheetId);
    $items = getPullsheetItems($pullsheetId);
    
    $pdf = new SimplePDF();
    $page = $pdf->addPage(612, 792); // Letter size
    
    // Logo in top left
    $logoPath = getSetting('logo_path');
    if ($logoPath && file_exists(__DIR__ . '/../uploads/logos/' . $logoPath)) {
        $pdf->addText($page, 40, 760, 'CMFT Sound Shop', 10);
    } else {
        $pdf->addText($page, 40, 760, 'CMFT Sound Shop', 10);
    }
    
    // Barcode in top right (PDF417 for pullsheets)
    $barcodeData = generatePDF417Barcode($pullsheet['barcode']);
    $barcodeFile = sys_get_temp_dir() . '/barcode_' . bin2hex(random_bytes(16)) . '.png';
    file_put_contents($barcodeFile, $barcodeData);
    if (file_exists($barcodeFile)) {
        $pdf->addImage($page, file_get_contents($barcodeFile), 470, 730, 100, 40);
        unlink($barcodeFile);
    }
    
    // Title - LightWright style
    $pdf->addText($page, 40, 735, 'EQUIPMENT PULL SHEET', 16);
    
    // Show information box - LightWright style
    $showFields = [
        ['label' => 'Production', 'value' => $pullsheet['show_name'] ?? 'N/A'],
        ['label' => 'Designer', 'value' => $pullsheet['designer'] ?? 'N/A'],
        ['label' => 'Shop Lead', 'value' => $pullsheet['shop_lead'] ?? 'N/A'],
        ['label' => 'Date Created', 'value' => date('m/d/Y', strtotime($pullsheet['created_at']))],
        ['label' => 'Status', 'value' => ucfirst($pullsheet['status'])],
    ];
    
    $pdf->addInfoBox($page, 40, 700, 250, 110, 'Show Information', $showFields);
    
    // Pullsheet barcode reference
    $pdf->addText($page, 310, 650, 'Pullsheet ID: ' . $pullsheet['barcode'], 8);
    
    // Group items by category for LightWright-style organization
    $itemsByCategory = [];
    foreach ($items as $item) {
        $catName = $item['category_name'] ?? 'Uncategorized';
        if (!isset($itemsByCategory[$catName])) {
            $itemsByCategory[$catName] = [];
        }
        $itemsByCategory[$catName][] = $item;
    }
    
    // Table columns - LightWright style
    $columns = [
        ['field' => 'item_num', 'label' => '#', 'width' => 30, 'align' => 'center', 'maxlen' => 5],
        ['field' => 'item_name', 'label' => 'Item Description', 'width' => 200, 'align' => 'left', 'maxlen' => 35],
        ['field' => 'item_barcode', 'label' => 'Barcode', 'width' => 90, 'align' => 'left', 'maxlen' => 15],
        ['field' => 'quantity_needed', 'label' => 'Qty', 'width' => 40, 'align' => 'center', 'maxlen' => 5],
        ['field' => 'location', 'label' => 'Location', 'width' => 80, 'align' => 'left', 'maxlen' => 12],
        ['field' => 'notes', 'label' => 'Notes/Picked', 'width' => 92, 'align' => 'left', 'maxlen' => 15],
    ];
    
    $y = 620;
    $itemNum = 1;
    
    foreach ($itemsByCategory as $categoryName => $categoryItems) {
        // Check if we need a new page
        if ($y < 100) {
            $page = $pdf->addPage(612, 792);
            $y = 750;
        }
        
        // Category header - LightWright style
        $pdf->setGray($page, 0.7);
        $pdf->addRect($page, 40, $y - 18, 532, 18, true);
        $pdf->setGray($page, 0);
        $pdf->addText($page, 45, $y - 12, strtoupper($categoryName), 10);
        $y -= 20;
        
        // Table header
        $y = $pdf->addTableHeader($page, 40, $y, $columns, 18);
        
        // Items in this category
        foreach ($categoryItems as $item) {
            if ($y < 80) {
                $page = $pdf->addPage(612, 792);
                $y = 750;
                // Re-add header on new page
                $y = $pdf->addTableHeader($page, 40, $y, $columns, 18);
            }
            
            $rowData = [
                'item_num' => $itemNum++,
                'item_name' => $item['item_name'],
                'item_barcode' => $item['item_barcode'],
                'quantity_needed' => $item['quantity_needed'],
                'location' => $item['location'] ?? '',
                'notes' => ''
            ];
            
            $y = $pdf->addTableRow($page, 40, $y, $columns, $rowData, 18);
        }
        
        $y -= 10; // Space between categories
    }
    
    // Signature section - LightWright style
    if ($y < 120) {
        $page = $pdf->addPage(612, 792);
        $y = 750;
    }
    
    $y -= 30;
    $pdf->addText($page, 40, $y, 'CHECKOUT AUTHORIZATION', 10);
    $y -= 25;
    $pdf->addSignatureLine($page, 40, $y, 250, 'Pulled By / Date');
    $pdf->addSignatureLine($page, 320, $y, 250, 'Authorized By / Date');
    
    // Page number
    $pdf->addPageNumber($page, 1, $pdf->getPageCount());
    
    return $pdf->output('pullsheet_' . $pullsheetId . '.pdf', 'S');
}

function generateChangeOrderPDF($changeOrderId) {
    $changeOrder = getChangeOrderById($changeOrderId);
    $items = getChangeOrderItems($changeOrderId);
    
    $pdf = new SimplePDF();
    $page = $pdf->addPage(612, 792); // Letter size
    
    // Logo in top left
    $logoPath = getSetting('logo_path');
    if ($logoPath && file_exists(__DIR__ . '/../uploads/logos/' . $logoPath)) {
        $pdf->addText($page, 40, 760, 'CMFT Sound Shop', 10);
    } else {
        $pdf->addText($page, 40, 760, 'CMFT Sound Shop', 10);
    }
    
    // Barcode in top right (PDF417 for change orders)
    $barcodeData = generatePDF417Barcode($changeOrder['barcode']);
    $barcodeFile = sys_get_temp_dir() . '/barcode_' . bin2hex(random_bytes(16)) . '.png';
    file_put_contents($barcodeFile, $barcodeData);
    if (file_exists($barcodeFile)) {
        $pdf->addImage($page, file_get_contents($barcodeFile), 470, 730, 100, 40);
        unlink($barcodeFile);
    }
    
    // Title - LightWright style
    $pdf->addText($page, 40, 735, 'EQUIPMENT CHANGE ORDER', 16);
    
    // Show information box - LightWright style
    $showFields = [
        ['label' => 'Production', 'value' => $changeOrder['show_name'] ?? 'N/A'],
        ['label' => 'Designer', 'value' => $changeOrder['designer'] ?? 'N/A'],
        ['label' => 'Shop Lead', 'value' => $changeOrder['shop_lead'] ?? 'N/A'],
        ['label' => 'Date Created', 'value' => date('m/d/Y', strtotime($changeOrder['created_at']))],
        ['label' => 'Status', 'value' => ucfirst($changeOrder['status'])],
    ];
    
    $pdf->addInfoBox($page, 40, 700, 250, 110, 'Show Information', $showFields);
    
    // Change Order barcode reference
    $pdf->addText($page, 310, 650, 'Change Order ID: ' . $changeOrder['barcode'], 8);
    
    // Group items by type (add/remove) for LightWright-style organization
    $addItems = [];
    $removeItems = [];
    foreach ($items as $item) {
        if ($item['type'] === 'add') {
            $addItems[] = $item;
        } else {
            $removeItems[] = $item;
        }
    }
    
    // Table columns - LightWright style
    $columns = [
        ['field' => 'item_num', 'label' => '#', 'width' => 30, 'align' => 'center', 'maxlen' => 5],
        ['field' => 'item_name', 'label' => 'Item Description', 'width' => 200, 'align' => 'left', 'maxlen' => 35],
        ['field' => 'item_barcode', 'label' => 'Barcode', 'width' => 90, 'align' => 'left', 'maxlen' => 15],
        ['field' => 'quantity_change', 'label' => 'Qty', 'width' => 40, 'align' => 'center', 'maxlen' => 5],
        ['field' => 'category_name', 'label' => 'Category', 'width' => 90, 'align' => 'left', 'maxlen' => 15],
        ['field' => 'notes', 'label' => 'Notes', 'width' => 82, 'align' => 'left', 'maxlen' => 12],
    ];
    
    $y = 620;
    $itemNum = 1;
    
    // Items to ADD section
    if (!empty($addItems)) {
        // Section header - LightWright style
        $pdf->setGray($page, 0.7);
        $pdf->addRect($page, 40, $y - 18, 532, 18, true);
        $pdf->setGray($page, 0);
        $pdf->addText($page, 45, $y - 12, 'ITEMS TO ADD', 10);
        $y -= 20;
        
        // Table header
        $y = $pdf->addTableHeader($page, 40, $y, $columns, 18);
        
        // Items
        foreach ($addItems as $item) {
            if ($y < 80) {
                $page = $pdf->addPage(612, 792);
                $y = 750;
                $y = $pdf->addTableHeader($page, 40, $y, $columns, 18);
            }
            
            $rowData = [
                'item_num' => $itemNum++,
                'item_name' => $item['item_name'],
                'item_barcode' => $item['item_barcode'],
                'quantity_change' => '+' . abs($item['quantity_change']),
                'category_name' => $item['category_name'] ?? '',
                'notes' => ''
            ];
            
            $y = $pdf->addTableRow($page, 40, $y, $columns, $rowData, 18);
        }
        
        $y -= 10; // Space between sections
    }
    
    // Items to REMOVE section
    if (!empty($removeItems)) {
        if ($y < 100) {
            $page = $pdf->addPage(612, 792);
            $y = 750;
        }
        
        // Section header - LightWright style
        $pdf->setGray($page, 0.7);
        $pdf->addRect($page, 40, $y - 18, 532, 18, true);
        $pdf->setGray($page, 0);
        $pdf->addText($page, 45, $y - 12, 'ITEMS TO REMOVE', 10);
        $y -= 20;
        
        // Table header
        $y = $pdf->addTableHeader($page, 40, $y, $columns, 18);
        
        // Items
        foreach ($removeItems as $item) {
            if ($y < 80) {
                $page = $pdf->addPage(612, 792);
                $y = 750;
                $y = $pdf->addTableHeader($page, 40, $y, $columns, 18);
            }
            
            $rowData = [
                'item_num' => $itemNum++,
                'item_name' => $item['item_name'],
                'item_barcode' => $item['item_barcode'],
                'quantity_change' => '-' . abs($item['quantity_change']),
                'category_name' => $item['category_name'] ?? '',
                'notes' => ''
            ];
            
            $y = $pdf->addTableRow($page, 40, $y, $columns, $rowData, 18);
        }
    }
    
    // Signature section - LightWright style
    if ($y < 120) {
        $page = $pdf->addPage(612, 792);
        $y = 750;
    }
    
    $y -= 30;
    $pdf->addText($page, 40, $y, 'CHANGE AUTHORIZATION', 10);
    $y -= 25;
    $pdf->addSignatureLine($page, 40, $y, 250, 'Processed By / Date');
    $pdf->addSignatureLine($page, 320, $y, 250, 'Authorized By / Date');
    
    // Page number
    $pdf->addPageNumber($page, 1, $pdf->getPageCount());
    
    return $pdf->output('change_order_' . $changeOrderId . '.pdf', 'S');
}

// Notification functions
function createNotification($userId, $type, $message, $link = null) {
    $db = getDB();
    $db->query(
        "INSERT INTO notifications (user_id, type, message, link) VALUES (?, ?, ?, ?)",
        [$userId, $type, $message, $link]
    );
}

function createNotificationForAdmins($type, $message, $link = null) {
    $db = getDB();
    $admins = $db->fetchAll("SELECT id FROM users WHERE role = 'admin' AND is_active = 1");
    foreach ($admins as $admin) {
        createNotification($admin['id'], $type, $message, $link);
    }
}

function createNotificationForDesigners($type, $message, $link = null) {
    $db = getDB();
    $designers = $db->fetchAll("SELECT id FROM users WHERE role IN ('admin', 'designer') AND is_active = 1");
    foreach ($designers as $designer) {
        createNotification($designer['id'], $type, $message, $link);
    }
}

function getUserNotifications($userId, $unreadOnly = false) {
    $db = getDB();
    $sql = "SELECT * FROM notifications WHERE user_id = ?";
    $params = [$userId];
    
    if ($unreadOnly) {
        $sql .= " AND is_read = 0";
    }
    
    $sql .= " ORDER BY created_at DESC LIMIT 50";
    
    return $db->fetchAll($sql, $params);
}

function getUnreadNotificationCount($userId) {
    $db = getDB();
    $result = $db->fetchOne(
        "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0",
        [$userId]
    );
    return $result ? (int)$result['count'] : 0;
}

function markNotificationAsRead($notificationId, $userId) {
    $db = getDB();
    $db->query(
        "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?",
        [$notificationId, $userId]
    );
}

function markAllNotificationsAsRead($userId) {
    $db = getDB();
    $db->query(
        "UPDATE notifications SET is_read = 1 WHERE user_id = ?",
        [$userId]
    );
}

// Hotkey functions
function getUserHotkeys($userId) {
    $db = getDB();
    $hotkeys = $db->fetchAll(
        "SELECT action, hotkey FROM user_hotkeys WHERE user_id = ?",
        [$userId]
    );
    
    $result = [];
    foreach ($hotkeys as $row) {
        $result[$row['action']] = $row['hotkey'];
    }
    
    return $result;
}

// Time formatting helper
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $difference = time() - $timestamp;
    
    if ($difference < 60) {
        return 'Just now';
    } elseif ($difference < 3600) {
        $minutes = floor($difference / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($difference < 86400) {
        $hours = floor($difference / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($difference < 604800) {
        $days = floor($difference / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', $timestamp);
    }
}

// Show-based permission functions
function getAssignedShows($userId) {
    $db = getDB();
    return $db->fetchAll(
        "SELECT s.* FROM shows s 
         INNER JOIN user_show_assignments usa ON s.id = usa.show_id 
         WHERE usa.user_id = ?
         ORDER BY s.name",
        [$userId]
    );
}

function canAccessShow($userId, $showId) {
    $user = getCurrentUser();
    
    // Admins can access all shows
    if ($user && $user['role'] === 'admin') {
        return true;
    }
    
    // For designers and production audio, check if they are assigned to the show
    if ($user && in_array($user['role'], ['designer', 'production_audio'])) {
        $db = getDB();
        $assignment = $db->fetchOne(
            "SELECT id FROM show_assignments WHERE user_id = ? AND show_id = ?",
            [$userId, $showId]
        );
        return $assignment !== null;
    }
    
    return false;
}

// Approval system functions
function requiresApproval() {
    $user = getCurrentUser();
    if (!$user) return false;
    
    // Designers and Production Audio users need approval
    return in_array($user['role'], ['designer', 'production_audio']);
}

function approvePullsheet($pullsheetId, $adminId) {
    $db = getDB();
    try {
        $db->query(
            "UPDATE pullsheets SET approval_status = 'approved', approved_by = ?, approved_at = NOW() 
             WHERE id = ?",
            [$adminId, $pullsheetId]
        );
        
        // Get pullsheet details for notification
        $pullsheet = getPullsheetById($pullsheetId);
        if ($pullsheet && $pullsheet['created_by_id']) {
            createNotification(
                $pullsheet['created_by_id'],
                'pullsheet_approved',
                'Your pullsheet for ' . $pullsheet['show_name'] . ' has been approved',
                'pullsheet_view.php?id=' . $pullsheetId
            );
        }
        
        logMessage("Pullsheet $pullsheetId approved by admin $adminId", 'INFO');
        return true;
    } catch (Exception $e) {
        logException($e, "Error approving pullsheet $pullsheetId");
        return false;
    }
}

function rejectPullsheet($pullsheetId, $adminId) {
    $db = getDB();
    try {
        $db->query(
            "UPDATE pullsheets SET approval_status = 'rejected', approved_by = ?, approved_at = NOW() 
             WHERE id = ?",
            [$adminId, $pullsheetId]
        );
        
        // Get pullsheet details for notification
        $pullsheet = getPullsheetById($pullsheetId);
        if ($pullsheet && $pullsheet['created_by_id']) {
            createNotification(
                $pullsheet['created_by_id'],
                'pullsheet_rejected',
                'Your pullsheet for ' . $pullsheet['show_name'] . ' has been rejected',
                'pullsheet_edit.php?id=' . $pullsheetId
            );
        }
        
        logMessage("Pullsheet $pullsheetId rejected by admin $adminId", 'INFO');
        return true;
    } catch (Exception $e) {
        logException($e, "Error rejecting pullsheet $pullsheetId");
        return false;
    }
}

function approveChangeOrder($changeOrderId, $adminId) {
    $db = getDB();
    try {
        $db->query(
            "UPDATE change_orders SET approval_status = 'approved', approved_by = ?, approved_at = NOW() 
             WHERE id = ?",
            [$adminId, $changeOrderId]
        );
        
        // Get change order details for notification
        $changeOrder = getChangeOrderById($changeOrderId);
        if ($changeOrder && $changeOrder['created_by_id']) {
            createNotification(
                $changeOrder['created_by_id'],
                'change_order_approved',
                'Your change order for ' . $changeOrder['show_name'] . ' has been approved',
                'change_order_view.php?id=' . $changeOrderId
            );
        }
        
        logMessage("Change order $changeOrderId approved by admin $adminId", 'INFO');
        return true;
    } catch (Exception $e) {
        logException($e, "Error approving change order $changeOrderId");
        return false;
    }
}

function rejectChangeOrder($changeOrderId, $adminId) {
    $db = getDB();
    try {
        $db->query(
            "UPDATE change_orders SET approval_status = 'rejected', approved_by = ?, approved_at = NOW() 
             WHERE id = ?",
            [$adminId, $changeOrderId]
        );
        
        // Get change order details for notification
        $changeOrder = getChangeOrderById($changeOrderId);
        if ($changeOrder && $changeOrder['created_by_id']) {
            createNotification(
                $changeOrder['created_by_id'],
                'change_order_rejected',
                'Your change order for ' . $changeOrder['show_name'] . ' has been rejected',
                'change_order_edit.php?id=' . $changeOrderId
            );
        }
        
        logMessage("Change order $changeOrderId rejected by admin $adminId", 'INFO');
        return true;
    } catch (Exception $e) {
        logException($e, "Error rejecting change order $changeOrderId");
        return false;
    }
}

// Signature functions
function saveSignature($userId, $signatureData, $firstName, $lastName, $pullsheetId = null, $changeOrderId = null) {
    $db = getDB();
    try {
        $db->query(
            "INSERT INTO signatures (user_id, pullsheet_id, change_order_id, signature_data, first_name, last_name) 
             VALUES (?, ?, ?, ?, ?, ?)",
            [$userId, $pullsheetId, $changeOrderId, $signatureData, $firstName, $lastName]
        );
        
        return $db->lastInsertId();
    } catch (Exception $e) {
        logException($e, "Error saving signature");
        return false;
    }
}

function getSignature($signatureId) {
    $db = getDB();
    return $db->fetchOne("SELECT * FROM signatures WHERE id = ?", [$signatureId]);
}

function requiresSignature() {
    $user = getCurrentUser();
    if (!$user) return false;
    
    // Production Audio users need admin signature
    return $user['role'] === 'production_audio';
}
