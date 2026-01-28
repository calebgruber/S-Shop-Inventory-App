<?php
/**
 * Helper Functions for Inventory Management
 * Barcode generation, file uploads, and utilities
 */

/**
 * Generate a unique barcode for an item
 * @param string $prefix Optional prefix (default: ITM)
 * @return string
 */
function generateUniqueBarcode($prefix = 'ITM') {
    $timestamp = time();
    $random = strtoupper(substr(md5(uniqid()), 0, 6));
    return $prefix . '-' . $timestamp . '-' . $random;
}

/**
 * Generate barcode image using barcodeapi.org
 * @param string $barcode The barcode text
 * @param string $type Barcode type (code128, pdf417, etc.)
 * @return string|false Path to saved barcode image or false on failure
 */
function generateBarcodeImage($barcode, $type = 'code128') {
    // Create barcodes directory if it doesn't exist
    $barcodesDir = BARCODES_PATH;
    if (!file_exists($barcodesDir)) {
        mkdir($barcodesDir, 0755, true);
    }
    
    $filename = $barcode . '.png';
    $filepath = $barcodesDir . '/' . $filename;
    
    // Check if barcode already exists
    if (file_exists($filepath)) {
        return 'assets/barcodes/' . $filename;
    }
    
    // Build API URL
    $apiUrl = 'https://barcodeapi.org/api/' . $type . '/' . urlencode($barcode);
    
    // Try to download the barcode image
    $imageData = @file_get_contents($apiUrl);
    
    if ($imageData === false) {
        error_log('Failed to generate barcode from API for: ' . $barcode);
        return false;
    }
    
    // Save the image
    if (file_put_contents($filepath, $imageData) === false) {
        error_log('Failed to save barcode image: ' . $filepath);
        return false;
    }
    
    return 'assets/barcodes/' . $filename;
}

/**
 * Upload item photo
 * @param array $file The $_FILES array element
 * @return array ['success' => bool, 'path' => string|null, 'error' => string|null]
 */
function uploadItemPhoto($file) {
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return [
            'success' => false,
            'path' => null,
            'error' => 'File upload error: ' . $file['error']
        ];
    }
    
    // Validate file size
    if ($file['size'] > MAX_FILE_SIZE) {
        return [
            'success' => false,
            'path' => null,
            'error' => 'File too large. Maximum size: ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB'
        ];
    }
    
    // Validate file type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, ALLOWED_IMAGE_TYPES)) {
        return [
            'success' => false,
            'path' => null,
            'error' => 'Invalid file type. Allowed: JPEG, PNG, GIF'
        ];
    }
    
    // Create items directory if it doesn't exist
    $itemsDir = UPLOADS_PATH . '/items';
    if (!file_exists($itemsDir)) {
        mkdir($itemsDir, 0755, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('item_', true) . '.' . $extension;
    $filepath = $itemsDir . '/' . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        return [
            'success' => false,
            'path' => null,
            'error' => 'Failed to save uploaded file'
        ];
    }
    
    return [
        'success' => true,
        'path' => 'uploads/items/' . $filename,
        'error' => null
    ];
}

/**
 * Delete item photo
 * @param string $path Path to photo file
 * @return bool
 */
function deleteItemPhoto($path) {
    if (empty($path)) {
        return true;
    }
    
    $fullPath = BASE_PATH . '/' . $path;
    
    if (file_exists($fullPath)) {
        return unlink($fullPath);
    }
    
    return true;
}

/**
 * Get all categories
 * @return array
 */
function getAllCategories() {
    $result = executeQuery('SELECT * FROM categories ORDER BY name ASC');
    
    $categories = [];
    if ($result) {
        while ($row = fetchAssoc($result)) {
            $categories[] = $row;
        }
    }
    
    return $categories;
}

/**
 * Get subcategories for a category
 * @param int $categoryId
 * @return array
 */
function getSubcategoriesByCategory($categoryId) {
    $result = executeQuery(
        'SELECT * FROM subcategories WHERE category_id = ? ORDER BY name ASC',
        [$categoryId],
        'i'
    );
    
    $subcategories = [];
    if ($result) {
        while ($row = fetchAssoc($result)) {
            $subcategories[] = $row;
        }
    }
    
    return $subcategories;
}

/**
 * Format serial numbers for storage
 * @param string $serialNumbers Comma or newline separated serial numbers
 * @return string JSON encoded array
 */
function formatSerialNumbers($serialNumbers) {
    if (empty($serialNumbers)) {
        return null;
    }
    
    // Split by comma or newline
    $serials = preg_split('/[,\n\r]+/', $serialNumbers);
    
    // Trim and filter empty values
    $serials = array_filter(array_map('trim', $serials));
    
    return json_encode(array_values($serials));
}

/**
 * Parse serial numbers from storage
 * @param string $serialNumbers JSON encoded array
 * @return array
 */
function parseSerialNumbers($serialNumbers) {
    if (empty($serialNumbers)) {
        return [];
    }
    
    $serials = json_decode($serialNumbers, true);
    
    return is_array($serials) ? $serials : [];
}

/**
 * Check if item is safe to delete
 * @param int $itemId
 * @return array ['safe' => bool, 'reason' => string|null]
 */
function canDeleteItem($itemId) {
    // Check if item is on any pull sheets
    $result = executeQuery(
        'SELECT COUNT(*) as count FROM pullsheet_items WHERE item_id = ?',
        [$itemId],
        'i'
    );
    
    if ($result) {
        $row = fetchAssoc($result);
        if ($row['count'] > 0) {
            return [
                'safe' => false,
                'reason' => 'Item is referenced in ' . $row['count'] . ' pull sheet(s)'
            ];
        }
    }
    
    // Check if item is on any change orders
    $result = executeQuery(
        'SELECT COUNT(*) as count FROM change_order_items WHERE item_id = ?',
        [$itemId],
        'i'
    );
    
    if ($result) {
        $row = fetchAssoc($result);
        if ($row['count'] > 0) {
            return [
                'safe' => false,
                'reason' => 'Item is referenced in ' . $row['count'] . ' change order(s)'
            ];
        }
    }
    
    return ['safe' => true, 'reason' => null];
}

/**
 * Generate a simple barcode as fallback (text-based)
 * @param string $text
 * @return string SVG barcode
 */
function generateSimpleBarcodeText($text) {
    // Simple text representation for fallback
    return '<svg width="200" height="60" xmlns="http://www.w3.org/2000/svg">
        <rect width="200" height="60" fill="white"/>
        <text x="100" y="30" font-family="monospace" font-size="16" text-anchor="middle" fill="black">' . 
        htmlspecialchars($text) . 
        '</text>
    </svg>';
}

/**
 * =====================================================
 * SHOWS MANAGEMENT HELPER FUNCTIONS
 * =====================================================
 */

/**
 * Get all theatre spaces from database
 * @return array
 */
function getAllTheatreSpaces() {
    global $conn;
    $query = "SELECT * FROM theatre_spaces ORDER BY name ASC";
    $result = executeQuery($query);
    
    $spaces = [];
    if ($result && numRows($result) > 0) {
        while ($row = fetchAssoc($result)) {
            $spaces[] = $row;
        }
    }
    return $spaces;
}

/**
 * Get users filtered by role
 * @param string $role The role to filter by (admin, designer, production_audio, student)
 * @return array
 */
function getUsersByRole($role = null) {
    global $conn;
    
    if ($role) {
        $query = "SELECT id, username, first_name, last_name, email, role 
                  FROM users 
                  WHERE role = ? 
                  ORDER BY first_name ASC, last_name ASC";
        $result = executeQuery($query, [$role]);
    } else {
        $query = "SELECT id, username, first_name, last_name, email, role 
                  FROM users 
                  ORDER BY first_name ASC, last_name ASC";
        $result = executeQuery($query);
    }
    
    $users = [];
    if ($result && numRows($result) > 0) {
        while ($row = fetchAssoc($result)) {
            $users[] = $row;
        }
    }
    return $users;
}

/**
 * Get show by ID with all related information
 * @param int $showId
 * @return array|null
 */
function getShowById($showId) {
    global $conn;
    
    $query = "SELECT s.*, 
              ts.name as theatre_space_name,
              d.username as designer_username, d.first_name as designer_first_name, d.last_name as designer_last_name,
              pa.username as production_audio_username, pa.first_name as production_audio_first_name, pa.last_name as production_audio_last_name
              FROM shows s
              LEFT JOIN theatre_spaces ts ON s.theatre_space_id = ts.id
              LEFT JOIN users d ON s.designer_id = d.id
              LEFT JOIN users pa ON s.production_audio_id = pa.id
              WHERE s.id = ?";
    
    $result = executeQuery($query, [$showId]);
    
    if ($result && numRows($result) > 0) {
        return fetchAssoc($result);
    }
    return null;
}

/**
 * Get all shows with basic information
 * @param bool $includeArchived Whether to include archived shows
 * @return array
 */
function getAllShows($includeArchived = false) {
    global $conn;
    
    $query = "SELECT s.*, 
              ts.name as theatre_space_name,
              d.username as designer_username, d.first_name as designer_first_name, d.last_name as designer_last_name,
              pa.username as production_audio_username, pa.first_name as production_audio_first_name, pa.last_name as production_audio_last_name
              FROM shows s
              LEFT JOIN theatre_spaces ts ON s.theatre_space_id = ts.id
              LEFT JOIN users d ON s.designer_id = d.id
              LEFT JOIN users pa ON s.production_audio_id = pa.id";
    
    if (!$includeArchived) {
        $query .= " WHERE s.archived = 0";
    }
    
    $query .= " ORDER BY s.archived ASC, s.created_at DESC";
    
    $result = executeQuery($query);
    
    $shows = [];
    if ($result && numRows($result) > 0) {
        while ($row = fetchAssoc($result)) {
            $shows[] = $row;
        }
    }
    return $shows;
}

/**
 * Check if a show can be safely archived
 * Always returns true - archiving is always allowed
 * @param int $showId
 * @return bool
 */
function canArchiveShow($showId) {
    // Shows can always be archived - this will archive all associated orders
    return true;
}

/**
 * Get show color for calendar
 * @param int $showId
 * @return string Hex color code
 */
function getShowColor($showId) {
    global $conn;
    
    $query = "SELECT color FROM shows WHERE id = ?";
    $result = executeQuery($query, [$showId]);
    
    if ($result && numRows($result) > 0) {
        $row = fetchAssoc($result);
        return $row['color'] ?? '#3b82f6';
    }
    return '#3b82f6'; // Default blue
}

/**
 * Get calendar events for a show (or all shows)
 * @param int|null $showId Optional show ID to filter by
 * @return array
 */
function getCalendarEvents($showId = null) {
    global $conn;
    
    $query = "SELECT ce.*, s.name as show_name, s.color as show_color
              FROM calendar_events ce
              INNER JOIN shows s ON ce.show_id = s.id
              WHERE s.archived = 0";
    
    $params = [];
    if ($showId) {
        $query .= " AND ce.show_id = ?";
        $params[] = $showId;
    }
    
    $query .= " ORDER BY ce.start_date ASC";
    
    $result = executeQuery($query, $params);
    
    $events = [];
    if ($result && numRows($result) > 0) {
        while ($row = fetchAssoc($result)) {
            $events[] = $row;
        }
    }
    return $events;
}

/**
 * Format user name for display
 * @param array $user User data array
 * @return string
 */
function formatUserName($user) {
    if (!$user) return 'N/A';
    
    if (!empty($user['first_name']) && !empty($user['last_name'])) {
        return trim($user['first_name'] . ' ' . $user['last_name']);
    }
    return $user['username'] ?? 'N/A';
}

/**
 * ========================================
 * PULL SHEET HELPER FUNCTIONS
 * ========================================
 */

/**
 * Generate a unique barcode for a pull sheet
 * @return string
 */
function generatePullSheetBarcode() {
    return generateUniqueBarcode('PUL');
}

/**
 * Get shows accessible to a user based on their role
 * @param int $userId User ID
 * @param string $role User role
 * @return array
 */
function getShowsForUser($userId, $role) {
    if ($role === 'admin') {
        // Admins see all non-archived shows
        $query = "SELECT s.*, ts.name as theatre_space_name 
                  FROM shows s 
                  LEFT JOIN theatre_spaces ts ON s.theatre_space_id = ts.id 
                  WHERE s.archived = 0 
                  ORDER BY s.name ASC";
        $result = executeQuery($query, [], '');
    } else {
        // Designers and Production Audio see shows they're assigned to
        $query = "SELECT s.*, ts.name as theatre_space_name 
                  FROM shows s 
                  LEFT JOIN theatre_spaces ts ON s.theatre_space_id = ts.id 
                  WHERE s.archived = 0 
                  AND (s.designer_id = ? OR s.production_audio_id = ?) 
                  ORDER BY s.name ASC";
        $result = executeQuery($query, [$userId, $userId], 'ii');
    }
    
    $shows = [];
    if ($result && numRows($result) > 0) {
        while ($row = fetchAssoc($result)) {
            $shows[] = $row;
        }
    }
    return $shows;
}

/**
 * Get pull sheet by ID with related data
 * @param int $id Pull sheet ID
 * @return array|null
 */
function getPullSheetById($id) {
    $query = "SELECT p.*, 
              s.name as show_name, s.color as show_color,
              ts.name as theatre_space_name,
              u1.first_name as creator_first, u1.last_name as creator_last,
              u2.first_name as approver_first, u2.last_name as approver_last,
              u3.first_name as picker_first, u3.last_name as picker_last
              FROM pullsheets p
              LEFT JOIN shows s ON p.show_id = s.id
              LEFT JOIN theatre_spaces ts ON s.theatre_space_id = ts.id
              LEFT JOIN users u1 ON p.created_by = u1.id
              LEFT JOIN users u2 ON p.approved_by = u2.id
              LEFT JOIN users u3 ON p.picked_by = u3.id
              WHERE p.id = ?";
    $result = executeQuery($query, [$id], 'i');
    
    if ($result && numRows($result) > 0) {
        return fetchAssoc($result);
    }
    return null;
}

/**
 * Get items in a pull sheet with full details
 * @param int $pullsheetId Pull sheet ID
 * @return array
 */
function getPullSheetItems($pullsheetId) {
    $query = "SELECT pi.*, 
              i.name as item_name, i.barcode as item_barcode,
              i.tracking_type, i.in_stock_quantity, i.location,
              i.photo_path,
              c.name as category_name,
              sc.name as subcategory_name
              FROM pullsheet_items pi
              LEFT JOIN items i ON pi.item_id = i.id
              LEFT JOIN categories c ON i.category_id = c.id
              LEFT JOIN subcategories sc ON i.subcategory_id = sc.id
              WHERE pi.pullsheet_id = ?
              ORDER BY c.name, sc.name, i.name";
    $result = executeQuery($query, [$pullsheetId], 'i');
    
    $items = [];
    if ($result && numRows($result) > 0) {
        while ($row = fetchAssoc($result)) {
            $items[] = $row;
        }
    }
    return $items;
}

/**
 * Check if user can edit a pull sheet
 * @param int $pullsheetId Pull sheet ID
 * @param int $userId User ID
 * @param string $role User role
 * @return bool
 */
function canEditPullSheet($pullsheetId, $userId, $role) {
    if ($role === 'admin') {
        return true;
    }
    
    $pullsheet = getPullSheetById($pullsheetId);
    if (!$pullsheet) {
        return false;
    }
    
    // Only drafts can be edited
    if ($pullsheet['status'] !== 'draft') {
        return false;
    }
    
    // Owner can edit their own draft
    return $pullsheet['created_by'] == $userId;
}

/**
 * Check if user can approve pull sheets
 * @param string $role User role
 * @return bool
 */
function canApprovePullSheet($role) {
    return $role === 'admin';
}

/**
 * Get pull sheet status badge HTML
 * @param string $status Status
 * @return string
 */
function getPullSheetStatusBadge($status) {
    $badges = [
        'draft' => '<span class="badge bg-secondary">Draft</span>',
        'pending_approval' => '<span class="badge bg-warning">Pending Approval</span>',
        'approved' => '<span class="badge bg-success">Approved</span>',
        'picked' => '<span class="badge bg-info">Picked</span>',
        'returned' => '<span class="badge bg-dark">Returned</span>',
        'cancelled' => '<span class="badge bg-danger">Cancelled</span>',
    ];
    return $badges[$status] ?? '<span class="badge bg-secondary">' . htmlspecialchars($status) . '</span>';
}

/**
 * Reserve items for a pull sheet (reduce stock)
 * @param int $pullsheetId Pull sheet ID
 * @return bool
 */
function reserveItemsForPullSheet($pullsheetId) {
    $items = getPullSheetItems($pullsheetId);
    
    foreach ($items as $item) {
        $query = "UPDATE items 
                  SET in_stock_quantity = in_stock_quantity - ? 
                  WHERE id = ? AND in_stock_quantity >= ?";
        $result = executeQuery($query, [
            $item['quantity_needed'],
            $item['item_id'],
            $item['quantity_needed']
        ], 'iii');
        
        if (!$result) {
            error_log("Failed to reserve items for pull sheet {$pullsheetId}, item {$item['item_id']}");
            return false;
        }
    }
    
    return true;
}

/**
 * Release items from a pull sheet (return to stock)
 * @param int $pullsheetId Pull sheet ID
 * @return bool
 */
function releaseItemsForPullSheet($pullsheetId) {
    $items = getPullSheetItems($pullsheetId);
    
    foreach ($items as $item) {
        $query = "UPDATE items 
                  SET in_stock_quantity = in_stock_quantity + ? 
                  WHERE id = ?";
        $result = executeQuery($query, [
            $item['quantity_needed'],
            $item['item_id']
        ], 'ii');
        
        if (!$result) {
            error_log("Failed to release items for pull sheet {$pullsheetId}, item {$item['item_id']}");
            return false;
        }
    }
    
    return true;
}

/**
 * Validate that all items in pull sheet have sufficient stock
 * @param int $pullsheetId Pull sheet ID
 * @return array ['valid' => bool, 'errors' => array]
 */
function validatePullSheetStock($pullsheetId) {
    $items = getPullSheetItems($pullsheetId);
    $errors = [];
    
    foreach ($items as $item) {
        if ($item['in_stock_quantity'] < $item['quantity_needed']) {
            $errors[] = [
                'item_name' => $item['item_name'],
                'needed' => $item['quantity_needed'],
                'available' => $item['in_stock_quantity']
            ];
        }
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Get current stock info for an item
 * @param int $itemId Item ID
 * @return array
 */
function getItemStockInfo($itemId) {
    $query = "SELECT id, name, barcode, in_stock_quantity, total_quantity, tracking_type 
              FROM items WHERE id = ?";
    $result = executeQuery($query, [$itemId], 'i');
    
    if ($result && numRows($result) > 0) {
        return fetchAssoc($result);
    }
    return null;
}

/**
 * Generate unique barcode for change order
 * @return string
 */
function generateChangeOrderBarcode() {
    $prefix = 'CHG-';
    $attempts = 0;
    $maxAttempts = 10;
    
    do {
        $number = str_pad(random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        $barcode = $prefix . $number;
        
        $query = "SELECT id FROM change_orders WHERE barcode = ?";
        $result = executeQuery($query, [$barcode], 's');
        
        if (!$result || numRows($result) === 0) {
            return $barcode;
        }
        
        $attempts++;
    } while ($attempts < $maxAttempts);
    
    return $prefix . uniqid();
}

/**
 * Get change order by ID with full details
 * @param int $id Change order ID
 * @return array|null
 */
function getChangeOrderById($id) {
    $query = "SELECT co.*, 
              s.name as show_name, s.color as show_color,
              ts.name as theatre_space_name,
              u1.first_name as creator_first, u1.last_name as creator_last,
              u2.first_name as approver_first, u2.last_name as approver_last
              FROM change_orders co
              LEFT JOIN shows s ON co.show_id = s.id
              LEFT JOIN theatre_spaces ts ON s.theatre_space_id = ts.id
              LEFT JOIN users u1 ON co.created_by = u1.id
              LEFT JOIN users u2 ON co.finalized_by = u2.id
              WHERE co.id = ?";
    $result = executeQuery($query, [$id], 'i');
    
    if ($result && numRows($result) > 0) {
        return fetchAssoc($result);
    }
    return null;
}

/**
 * Get items in a change order with full details
 * @param int $changeOrderId Change order ID
 * @return array
 */
function getChangeOrderItems($changeOrderId) {
    $query = "SELECT coi.*, 
              i.name as item_name, i.barcode as item_barcode,
              i.tracking_type, i.in_stock_quantity, i.location,
              i.photo_path, i.total_quantity,
              c.name as category_name,
              sc.name as subcategory_name
              FROM change_order_items coi
              LEFT JOIN items i ON coi.item_id = i.id
              LEFT JOIN categories c ON i.category_id = c.id
              LEFT JOIN subcategories sc ON i.subcategory_id = sc.id
              WHERE coi.change_order_id = ?
              ORDER BY coi.action DESC, c.name, sc.name, i.name";
    $result = executeQuery($query, [$changeOrderId], 'i');
    
    $items = [];
    if ($result && numRows($result) > 0) {
        while ($row = fetchAssoc($result)) {
            $items[] = $row;
        }
    }
    return $items;
}

/**
 * Check if user can edit a change order
 * @param int $changeOrderId Change order ID
 * @param int $userId User ID
 * @param string $role User role
 * @return bool
 */
function canEditChangeOrder($changeOrderId, $userId, $role) {
    if ($role === 'admin') {
        return true;
    }
    
    $changeOrder = getChangeOrderById($changeOrderId);
    if (!$changeOrder) {
        return false;
    }
    
    // Only drafts can be edited
    if ($changeOrder['status'] !== 'draft') {
        return false;
    }
    
    // Owner can edit their own draft
    return (int)$changeOrder['created_by'] === (int)$userId;
}

/**
 * Check if user can finalize change orders
 * @param string $role User role
 * @return bool
 */
function canFinalizeChangeOrder($role) {
    return $role === 'admin';
}

/**
 * Get change order status badge HTML
 * @param string $status Status
 * @return string
 */
function getChangeOrderStatusBadge($status) {
    $badges = [
        'draft' => '<span class="badge bg-secondary">Draft</span>',
        'pending_approval' => '<span class="badge bg-warning">Pending Approval</span>',
        'finalized' => '<span class="badge bg-success">Finalized</span>',
        'cancelled' => '<span class="badge bg-danger">Cancelled</span>',
    ];
    return $badges[$status] ?? '<span class="badge bg-secondary">' . htmlspecialchars($status) . '</span>';
}

/**
 * Apply stock changes for a change order
 * @param int $changeOrderId Change order ID
 * @return bool
 */
function applyChangeOrderStockChanges($changeOrderId) {
    $items = getChangeOrderItems($changeOrderId);
    
    foreach ($items as $item) {
        if ($item['action'] === 'add') {
            // Adding items - increase in_stock and total
            $query = "UPDATE items 
                      SET in_stock_quantity = in_stock_quantity + ?,
                          total_quantity = total_quantity + ?
                      WHERE id = ?";
            $result = executeQuery($query, [
                $item['quantity'],
                $item['quantity'],
                $item['item_id']
            ], 'iii');
        } else {
            // Removing items - decrease both in_stock and total
            // Note: The WHERE clause ensures removal only succeeds if sufficient stock exists
            // This function should only be called after validateChangeOrderStock() passes
            $query = "UPDATE items 
                      SET in_stock_quantity = in_stock_quantity - ?,
                          total_quantity = total_quantity - ?
                      WHERE id = ? AND in_stock_quantity >= ? AND total_quantity >= ?";
            $result = executeQuery($query, [
                $item['quantity'],
                $item['quantity'],
                $item['item_id'],
                $item['quantity'],
                $item['quantity']
            ], 'iiiii');
        }
        
        if (!$result) {
            error_log("Failed to apply stock changes for change order {$changeOrderId}, item {$item['item_id']}");
            return false;
        }
    }
    
    return true;
}

/**
 * Validate that items being removed have sufficient stock
 * @param int $changeOrderId Change order ID
 * @return array ['valid' => bool, 'errors' => array]
 */
function validateChangeOrderStock($changeOrderId) {
    $items = getChangeOrderItems($changeOrderId);
    $errors = [];
    
    foreach ($items as $item) {
        // Only validate "remove" actions
        if ($item['action'] === 'remove') {
            $available = min($item['in_stock_quantity'], $item['total_quantity']);
            if ($available < $item['quantity']) {
                $errors[] = [
                    'item_name' => $item['item_name'],
                    'action' => 'remove',
                    'needed' => $item['quantity'],
                    'available' => $available
                ];
            }
        }
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}
