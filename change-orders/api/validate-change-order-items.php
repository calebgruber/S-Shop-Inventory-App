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
    if (!isset($item['id']) || !isset($item['quantity']) || !isset($item['action'])) {
        continue;
    }
    
    $itemId = (int)$item['id'];
    $quantity = (int)$item['quantity'];
    $action = $item['action'];
    
    // Only validate "add" actions (items being added don't need stock check)
    // "remove" actions need stock validation
    if ($action === 'add') {
        // Add actions don't need validation - we're adding to stock
        continue;
    }
    
    // Validate remove actions
    $query = "SELECT id, name, barcode, in_stock_quantity, total_quantity FROM items WHERE id = ?";
    $result = executeQuery($query, [$itemId], 'i');
    
    if ($result && numRows($result) > 0) {
        $itemData = fetchAssoc($result);
        
        $available = min($itemData['in_stock_quantity'], $itemData['total_quantity']);
        if ($available < $quantity) {
            $errors[] = [
                'item_id' => $itemId,
                'item_name' => $itemData['name'],
                'barcode' => $itemData['barcode'],
                'action' => 'remove',
                'needed' => $quantity,
                'available' => $available
            ];
        }
    }
}

echo json_encode([
    'valid' => empty($errors),
    'errors' => $errors
]);
