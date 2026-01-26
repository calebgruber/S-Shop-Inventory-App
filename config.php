<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'voxelnodes_sshop');
define('DB_PASS', ').sBi.*B=}rp');
define('DB_NAME', 'voxelnodes_sshop');

// Application settings
define('APP_NAME', 'CMFT Sound Shop Inventory');
define('BASE_URL', '');

// File paths
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('ASSETS_DIR', __DIR__ . '/assets/');
define('PDF_DIR', __DIR__ . '/pdfs/');

// Pick Mode SVG Instruction (can be inline SVG or path to SVG file)
define('PICK_MODE_INSTRUCTION_SVG', '<svg width="200" height="150" viewBox="0 0 200 150" xmlns="http://www.w3.org/2000/svg">
    <rect x="20" y="30" width="160" height="90" fill="none" stroke="currentColor" stroke-width="3" stroke-dasharray="8 4"/>
    <text x="100" y="80" text-anchor="middle" font-size="14" fill="currentColor">SCAN PDF BARCODE</text>
    <text x="100" y="100" text-anchor="middle" font-size="12" fill="currentColor">TO START PICKING</text>
    <path d="M 80 110 L 90 120 L 110 100" fill="none" stroke="currentColor" stroke-width="3"/>
</svg>');

// Return Mode SVG Instruction (can be inline SVG or path to SVG file)
define('RETURN_MODE_INSTRUCTION_SVG', '<svg width="200" height="150" viewBox="0 0 200 150" xmlns="http://www.w3.org/2000/svg">
    <rect x="20" y="30" width="160" height="90" fill="none" stroke="currentColor" stroke-width="3" stroke-dasharray="8 4"/>
    <text x="100" y="80" text-anchor="middle" font-size="14" fill="currentColor">SCAN PDF BARCODE</text>
    <text x="100" y="100" text-anchor="middle" font-size="12" fill="currentColor">TO START RETURNING</text>
    <path d="M 110 110 L 100 120 L 90 110" fill="none" stroke="currentColor" stroke-width="3"/>
    <path d="M 100 120 L 100 95" stroke="currentColor" stroke-width="3"/>
</svg>');

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
