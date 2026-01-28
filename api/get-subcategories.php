<?php
/**
 * Get Subcategories API
 * Returns subcategories for a given category
 */

require_once dirname(__DIR__) . '/config/config.php';

header('Content-Type: application/json');

requireAuth();

$categoryId = $_GET['category_id'] ?? '';

if (empty($categoryId)) {
    echo json_encode(['success' => false, 'message' => 'Category ID required']);
    exit;
}

$result = executeQuery(
    'SELECT id, name, description FROM subcategories WHERE category_id = ? ORDER BY name ASC',
    [$categoryId],
    'i'
);

$subcategories = [];
if ($result) {
    while ($row = fetchAssoc($result)) {
        $subcategories[] = $row;
    }
}

echo json_encode([
    'success' => true,
    'subcategories' => $subcategories
]);
