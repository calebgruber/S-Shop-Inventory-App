<?php
/**
 * Main Configuration File
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define base paths
define('BASE_PATH', dirname(__DIR__));
define('UPLOADS_PATH', BASE_PATH . '/uploads');
define('BARCODES_PATH', BASE_PATH . '/assets/barcodes');
define('PDFS_PATH', BASE_PATH . '/pdfs');

// Define base URL (adjust for your environment)
define('BASE_URL', '/');

// Include database configuration
require_once BASE_PATH . '/config/database.php';

// Timezone
date_default_timezone_set('America/New_York');

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Barcode API configuration
define('BARCODE_API_URL', 'https://barcodeapi.org/api');

// File upload limits
define('MAX_FILE_SIZE', 10485760); // 10MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif']);

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Require authentication
 * Redirect to login if not authenticated
 */
function requireAuth() {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}

/**
 * Fetch result as associative array (works with both MySQL and SQLite)
 * @param mixed $result
 * @return array|null
 */
function fetchAssoc($result) {
    if (!$result) {
        return null;
    }
    
    if (defined('DEMO_MODE') && DEMO_MODE && $result instanceof SQLite3Result) {
        return $result->fetchArray(SQLITE3_ASSOC);
    }
    
    return $result->fetch_assoc();
}

/**
 * Get number of rows (works with both MySQL and SQLite)
 * @param mixed $result
 * @return int
 */
function numRows($result) {
    if (!$result) {
        return 0;
    }
    
    if (defined('DEMO_MODE') && DEMO_MODE && $result instanceof SQLite3Result) {
        // SQLite doesn't have num_rows, so we need to count
        $count = 0;
        while ($result->fetchArray(SQLITE3_ASSOC)) {
            $count++;
        }
        $result->reset();
        return $count;
    }
    
    return $result->num_rows ?? 0;
}

/**
 * Get current user data
 * @return array|null
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $userId = $_SESSION['user_id'];
    $result = executeQuery(
        'SELECT id, username, email, first_name, last_name, role, avatar_seed, hotkeys FROM users WHERE id = ?',
        [$userId],
        'i'
    );
    
    if ($result && numRows($result) > 0) {
        return fetchAssoc($result);
    }
    
    return null;
}

/**
 * Check if user has required role
 * @param string|array $roles Role(s) to check
 * @return bool
 */
function hasRole($roles) {
    $user = getCurrentUser();
    if (!$user) {
        return false;
    }
    
    if (is_array($roles)) {
        return in_array($user['role'], $roles);
    }
    
    return $user['role'] === $roles;
}

/**
 * Require specific role
 * @param string|array $roles
 */
function requireRole($roles) {
    if (!hasRole($roles)) {
        http_response_code(403);
        die('Access denied');
    }
}

/**
 * Generate random string
 * @param int $length
 * @return string
 */
function generateRandomString($length = 10) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Sanitize input
 * @param string $input
 * @return string
 */
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Get setting value
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function getSetting($key, $default = null) {
    // For demo mode, just return defaults since we don't have settings table in SQLite
    if (defined('DEMO_MODE') && DEMO_MODE) {
        $defaults = [
            'site_name' => 'S-Shop Inventory System',
            'site_logo' => null,
            'login_cover' => null,
            'default_theme' => 'light'
        ];
        return $defaults[$key] ?? $default;
    }
    
    $result = executeQuery(
        'SELECT setting_value FROM settings WHERE setting_key = ?',
        [$key],
        's'
    );
    
    if ($result && numRows($result) > 0) {
        $row = fetchAssoc($result);
        return $row['setting_value'];
    }
    
    return $default;
}

/**
 * Set setting value
 * @param string $key
 * @param mixed $value
 * @return bool
 */
function setSetting($key, $value) {
    // For demo mode, just return true
    if (defined('DEMO_MODE') && DEMO_MODE) {
        return true;
    }
    
    $result = executeQuery(
        'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
         ON DUPLICATE KEY UPDATE setting_value = ?',
        [$key, $value, $value],
        'sss'
    );
    
    return $result !== false;
}
