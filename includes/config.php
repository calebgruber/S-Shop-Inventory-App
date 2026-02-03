<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'voxelnodes_sshop_dev');
define('DB_PASS', '*#4W=CL&Ni(s');
define('DB_NAME', 'voxelnodes_sshop_dev');

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

// Error Reporting and Logging
// Set to 0 for production, 1 for development
$isDevelopment = ($_SERVER['SERVER_NAME'] === 'localhost' || strpos($_SERVER['SERVER_NAME'], '127.0.0.1') !== false);

// Always enable error logging
ini_set('log_errors', 1);
$logDir = __DIR__ . '/../logs';
if (!file_exists($logDir)) {
    @mkdir($logDir, 0755, true);
}
ini_set('error_log', $logDir . '/php_errors_' . date('Y-m-d') . '.log');

if ($isDevelopment) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL); // Still report all errors, but don't display them
    ini_set('display_errors', 0);
}

// Set custom error and exception handlers
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    $logDir = __DIR__ . '/../logs';
    if (!file_exists($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    $logFile = $logDir . '/app_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $errorTypes = [
        E_ERROR => 'ERROR',
        E_WARNING => 'WARNING',
        E_PARSE => 'PARSE',
        E_NOTICE => 'NOTICE',
        E_CORE_ERROR => 'CORE_ERROR',
        E_CORE_WARNING => 'CORE_WARNING',
        E_COMPILE_ERROR => 'COMPILE_ERROR',
        E_COMPILE_WARNING => 'COMPILE_WARNING',
        E_USER_ERROR => 'USER_ERROR',
        E_USER_WARNING => 'USER_WARNING',
        E_USER_NOTICE => 'USER_NOTICE',
        E_STRICT => 'STRICT',
        E_RECOVERABLE_ERROR => 'RECOVERABLE_ERROR',
        E_DEPRECATED => 'DEPRECATED',
        E_USER_DEPRECATED => 'USER_DEPRECATED'
    ];
    $level = $errorTypes[$errno] ?? 'UNKNOWN';
    $logEntry = "[$timestamp] [$level] $errstr in $errfile:$errline" . PHP_EOL;
    @file_put_contents($logFile, $logEntry, FILE_APPEND);
    
    // Don't execute PHP internal error handler
    return true;
});

set_exception_handler(function($exception) {
    $logDir = __DIR__ . '/../logs';
    if (!file_exists($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    $logFile = $logDir . '/app_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $message = get_class($exception) . ': ' . $exception->getMessage();
    $message .= ' in ' . $exception->getFile() . ':' . $exception->getLine();
    $logEntry = "[$timestamp] [EXCEPTION] $message" . PHP_EOL;
    $logEntry .= "Stack trace:" . PHP_EOL . $exception->getTraceAsString() . PHP_EOL;
    @file_put_contents($logFile, $logEntry, FILE_APPEND);
    
    // Display user-friendly error
    if (!headers_sent()) {
        http_response_code(500);
    }
    if ($isDevelopment) {
        echo "<h1>An error occurred</h1>";
        echo "<pre>" . htmlspecialchars($message) . "</pre>";
        echo "<pre>" . htmlspecialchars($exception->getTraceAsString()) . "</pre>";
    } else {
        echo "<h1>An error occurred</h1>";
        echo "<p>An unexpected error occurred. Please try again later.</p>";
    }
});
