<?php
/**
 * Delete Inventory Item
 * Handle item deletion with safety checks
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/helpers.php';

requireAuth();
requireRole('admin'); // Only admins can delete

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . 'inventory/');
    exit;
}

$itemId = $_POST['id'] ?? '';

if (empty($itemId)) {
    header('Location: ' . BASE_URL . 'inventory/?error=invalid');
    exit;
}

// Get item details
$result = executeQuery(
    'SELECT * FROM items WHERE id = ?',
    [$itemId],
    'i'
);

if (!$result || numRows($result) === 0) {
    header('Location: ' . BASE_URL . 'inventory/?error=notfound');
    exit;
}

$item = fetchAssoc($result);

// Check if item can be safely deleted
$canDelete = canDeleteItem($itemId);

if (!$canDelete['safe']) {
    header('Location: ' . BASE_URL . 'inventory/?error=inuse&reason=' . urlencode($canDelete['reason']));
    exit;
}

// Delete photo if exists
if (!empty($item['photo_path'])) {
    deleteItemPhoto($item['photo_path']);
}

// Delete item
$deleteResult = executeQuery(
    'DELETE FROM items WHERE id = ?',
    [$itemId],
    'i'
);

if ($deleteResult) {
    header('Location: ' . BASE_URL . 'inventory/?success=deleted');
} else {
    header('Location: ' . BASE_URL . 'inventory/?error=failed');
}
exit;
