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
