<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'voxelnodes_sshop');
define('DB_PASS', ').sBi.*B=}rp');
define('DB_NAME', 'voxelnodes_sshop');

// Application Configuration
define('BASE_URL', '/');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('ASSETS_DIR', __DIR__ . '/../assets/');

// Ensure upload directory exists
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// Session Configuration
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Timezone
date_default_timezone_set('America/New_York');

// Error Reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
