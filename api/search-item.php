<?php
/**
 * Search Item API
 * Returns item details for quick lookup
 */

require_once dirname(__DIR__) . '/config/config.php';

header('Content-Type: application/json');

requireAuth();

$query = $_GET['q'] ?? '';

if (empty($query)) {
    echo json_encode(['success' => false, 'message' => 'No query provided']);
    exit;
}

// Search by barcode or name
$result = executeQuery(
    'SELECT i.*, c.name as category_name, s.name as subcategory_name 
     FROM items i
     LEFT JOIN categories c ON i.category_id = c.id
     LEFT JOIN subcategories s ON i.subcategory_id = s.id
     WHERE i.barcode = ? OR i.name LIKE ?
     LIMIT 1',
    [$query, '%' . $query . '%'],
    'ss'
);

if ($result && $result->num_rows > 0) {
    $item = $result->fetch_assoc();
    
    // Add full photo path if exists
    if ($item['photo_path']) {
        $item['photo_path'] = BASE_URL . $item['photo_path'];
    }
    
    echo json_encode([
        'success' => true,
        'item' => $item
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Item not found'
    ]);
}
