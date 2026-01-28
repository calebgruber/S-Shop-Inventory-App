<?php
/**
 * S-Shop Inventory System
 * Main entry point
 */

require_once __DIR__ . '/config/config.php';

// Redirect to dashboard if logged in, otherwise to login
if (isLoggedIn()) {
    header('Location: /dashboard.php');
} else {
    header('Location: /login.php');
}
exit;
