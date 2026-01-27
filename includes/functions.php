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
    if ($url === null) {
        header("Location: " . $_SERVER['PHP_SELF'] . ($_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : ''));
    } else {
        header("Location: " . $url);
    }
    exit;
}

// Authentication functions
function requireLogin() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
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

function requireRole($role) {
    $user = getCurrentUser();
    if (!$user) {
        header('Location: login.php');
        exit;
    }
    
    $allowedRoles = is_array($role) ? $role : [$role];
    if (!in_array($user['role'], $allowedRoles)) {
        setAlert('You do not have permission to access this page.', 'danger');
        header('Location: index.php');
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
                                   'change_orders', 'quick_lookup'];
            return in_array($permissionKey, $designerPermissions);
        } else if ($user['role'] === 'student') {
            // Students can only view dashboard and make requests
            $studentPermissions = ['dashboard', 'student_requests'];
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
