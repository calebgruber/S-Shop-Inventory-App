<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sshop_inventory');

// Application settings
define('APP_NAME', 'CMFT Sound Shop Inventory');
define('BASE_URL', '');

// File paths
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('ASSETS_DIR', __DIR__ . '/assets/');
define('PDF_DIR', __DIR__ . '/pdfs/');

// Ensure directories exist
if (!file_exists(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
if (!file_exists(ASSETS_DIR)) mkdir(ASSETS_DIR, 0755, true);
if (!file_exists(PDF_DIR)) mkdir(PDF_DIR, 0755, true);

// Timezone
date_default_timezone_set('America/New_York');

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session
session_start();
