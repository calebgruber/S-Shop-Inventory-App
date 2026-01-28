<?php
require_once dirname(dirname(__DIR__)) . '/config/config.php';
require_once dirname(dirname(__DIR__)) . '/includes/helpers.php';

requireAuth();

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['items']) || !is_array($input['items'])) {
    echo json_encode(['valid' => false, 'error' => 'Invalid input']);
    exit;
}

$errors = [];

foreach ($input['items'] as $item) {
    if (!isset($item['id']) || !isset($item['quantity'])) {
        continue;
    }
    
    $itemId = (int)$item['id'];
    $quantityNeeded = (int)$item['quantity'];
    
    $query = "SELECT id, name, barcode, in_stock_quantity FROM items WHERE id = ?";
    $result = executeQuery($query, [$itemId], 'i');
    
    if ($result && numRows($result) > 0) {
        $itemData = fetchAssoc($result);
        
        if ($itemData['in_stock_quantity'] < $quantityNeeded) {
            $errors[] = [
                'item_id' => $itemId,
                'item_name' => $itemData['name'],
                'barcode' => $itemData['barcode'],
                'needed' => $quantityNeeded,
                'available' => (int)$itemData['in_stock_quantity']
            ];
        }
    }
}

echo json_encode([
    'valid' => empty($errors),
    'errors' => $errors
]);
