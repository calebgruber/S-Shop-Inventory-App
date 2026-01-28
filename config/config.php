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
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
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
    $result = executeQuery(
        'SELECT setting_value FROM settings WHERE setting_key = ?',
        [$key],
        's'
    );
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
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
    $result = executeQuery(
        'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
         ON DUPLICATE KEY UPDATE setting_value = ?',
        [$key, $value, $value],
        'sss'
    );
    
    return $result !== false;
}
