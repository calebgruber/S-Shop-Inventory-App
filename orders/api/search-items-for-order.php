<?php
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/includes/helpers.php';

requireAuth();

header('Content-Type: application/json');

$search = isset($_GET['q']) ? trim($_GET['q']) : '';

if (empty($search)) {
    echo json_encode([]);
    exit;
}

$conn = getDbConnection();

// Search by name or barcode
$searchTerm = '%' . $search . '%';
$query = "SELECT i.id, i.name, i.barcode, i.in_stock_quantity, i.total_quantity,
          i.tracking_type, i.location, i.photo_path,
          c.name as category_name,
          sc.name as subcategory_name
          FROM items i
          LEFT JOIN categories c ON i.category_id = c.id
          LEFT JOIN subcategories sc ON i.subcategory_id = sc.id
          WHERE (i.name LIKE ? OR i.barcode LIKE ?)
          ORDER BY i.name ASC
          LIMIT 20";

$result = executeQuery($conn, $query, [$searchTerm, $searchTerm]);

$items = [];
if ($result && numRows($result) > 0) {
    while ($row = fetchAssoc($result)) {
        $items[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'barcode' => $row['barcode'],
            'in_stock' => (int)$row['in_stock_quantity'],
            'total' => (int)$row['total_quantity'],
            'tracking_type' => $row['tracking_type'],
            'location' => $row['location'],
            'photo_path' => $row['photo_path'],
            'category' => $row['category_name'],
            'subcategory' => $row['subcategory_name'],
            'display_name' => $row['name'] . ' (' . $row['barcode'] . ') - In Stock: ' . $row['in_stock_quantity']
        ];
    }
}

echo json_encode($items);
