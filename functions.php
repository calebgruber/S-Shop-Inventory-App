<?php
// Helper functions

function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function generateBarcode($type = 'item') {
    $db = getDB();
    $prefix = ($type === 'item') ? 'ITM' : (($type === 'pullsheet') ? 'PS' : 'CO');
    
    // Generate unique barcode with collision check
    $maxAttempts = 10;
    for ($i = 0; $i < $maxAttempts; $i++) {
        $barcode = $prefix . '-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
        
        // Check for uniqueness in appropriate table
        $table = ($type === 'item') ? 'items' : (($type === 'pullsheet') ? 'pull_sheets' : 'change_orders');
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM $table WHERE barcode = ?");
        $stmt->bind_param("s", $barcode);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result['count'] == 0) {
            return $barcode;
        }
    }
    
    // Fallback with timestamp if all attempts fail
    return $prefix . '-' . date('YmdHis') . '-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
}

function generatePDF417Barcode($type = 'pullsheet') {
    // For pull sheets and change orders
    $prefix = ($type === 'pullsheet') ? 'PS' : 'CO';
    return $prefix . '-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 8));
}

function getSetting($key, $default = '') {
    $db = getDB();
    $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return $row['setting_value'];
    }
    
    return $default;
}

function setSetting($key, $value) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                          ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->bind_param("sss", $key, $value, $value);
    return $stmt->execute();
}

function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function redirectTo($page) {
    header("Location: $page");
    exit;
}

function getItemAvailability($itemId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT available_quantity FROM items WHERE id = ?");
    $stmt->bind_param("i", $itemId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return (int)$row['available_quantity'];
    }
    
    return 0;
}

function updateItemQuantity($itemId, $quantityChange, $type = 'reserve') {
    $db = getDB();
    
    // Get current quantities
    $stmt = $db->prepare("SELECT total_quantity, available_quantity FROM items WHERE id = ?");
    $stmt->bind_param("i", $itemId);
    $stmt->execute();
    $result = $stmt->get_result();
    $item = $result->fetch_assoc();
    
    if (!$item) {
        return false;
    }
    
    $newAvailable = $item['available_quantity'];
    
    if ($type === 'reserve' || $type === 'pick') {
        $newAvailable -= $quantityChange;
    } else if ($type === 'return') {
        $newAvailable += $quantityChange;
    }
    
    // Ensure we don't go negative
    if ($newAvailable < 0) {
        $newAvailable = 0;
    }
    
    // Update the item
    $stmt = $db->prepare("UPDATE items SET available_quantity = ? WHERE id = ?");
    $stmt->bind_param("ii", $newAvailable, $itemId);
    return $stmt->execute();
}

function logTransaction($itemId, $type, $quantity, $performedBy, $pullSheetId = null, $changeOrderId = null, $showId = null, $theatreSpaceId = null, $notes = '') {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO item_transactions 
                          (item_id, transaction_type, quantity, pull_sheet_id, change_order_id, show_id, theatre_space_id, performed_by, notes) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isiiiisss", $itemId, $type, $quantity, $pullSheetId, $changeOrderId, $showId, $theatreSpaceId, $performedBy, $notes);
    return $stmt->execute();
}

function formatDate($date) {
    return date('M d, Y g:i A', strtotime($date));
}

function getThemeMode() {
    return 'dark';
}
