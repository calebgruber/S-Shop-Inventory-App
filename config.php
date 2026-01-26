<?php
/**
 * S-Shop Inventory Management System
 * Configuration File
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'voxelnodes_sshop');
define('DB_PASS', ').sBi.*B=}rp');
define('DB_NAME', 'voxelnodes_sshop');

// Application Settings
define('APP_NAME', 'S-Shop Inventory');
define('BASE_URL', 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']));

// Audio Feedback (MP3 or WAV)
define('AUDIO_SUCCESS_PATH', BASE_URL . '/assets/sounds/success.mp3');
define('AUDIO_ERROR_PATH', BASE_URL . '/assets/sounds/error.mp3');

// SVG Instructions for Pick/Return Modes
define('PICK_MODE_INSTRUCTION_SVG', '<svg width="200" height="200" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg"><rect width="200" height="200" fill="none"/><text x="100" y="100" font-size="24" text-anchor="middle" fill="currentColor">SCAN PDF</text><text x="100" y="130" font-size="24" text-anchor="middle" fill="currentColor">BARCODE</text></svg>');

define('RETURN_MODE_INSTRUCTION_SVG', '<svg width="200" height="200" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg"><rect width="200" height="200" fill="none"/><text x="100" y="100" font-size="24" text-anchor="middle" fill="currentColor">SCAN PDF</text><text x="100" y="130" font-size="24" text-anchor="middle" fill="currentColor">BARCODE</text></svg>');

// Theme
define('THEME_MODE', 'dark'); // Always dark mode
