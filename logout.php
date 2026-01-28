<?php
/**
 * Logout Handler
 * Destroys session and redirects to login
 */

require_once __DIR__ . '/config/config.php';

// Destroy session
session_unset();
session_destroy();

// Redirect to login
header('Location: ' . BASE_URL . 'login.php');
exit;
