<?php
require_once 'includes/functions.php';

// Start session for authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Require login
requireLogin();

header('Content-Type: application/json');

$searchTerm = $_GET['q'] ?? '';

if (empty($searchTerm)) {
    echo json_encode(['success' => false, 'message' => 'No search term provided']);
    exit;
}

$db = getDB();

// Search by barcode first (exact match) - include photo_path
$item = $db->fetchOne(
    "SELECT i.*, c.name as category_name, i.photo_path 
     FROM items i 
     LEFT JOIN categories c ON i.category_id = c.id 
     WHERE i.barcode = ?",
    [$searchTerm]
);

// If not found, search by name (partial match) - include photo_path
if (!$item) {
    $item = $db->fetchOne(
        "SELECT i.*, c.name as category_name, i.photo_path 
         FROM items i 
         LEFT JOIN categories c ON i.category_id = c.id 
         WHERE i.name LIKE ? 
         ORDER BY i.name 
         LIMIT 1",
        ['%' . $searchTerm . '%']
    );
}

if ($item) {
    echo json_encode(['success' => true, 'item' => $item]);
} else {
    echo json_encode(['success' => false, 'message' => 'Item not found']);
}
