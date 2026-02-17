<?php
require_once '../includes/functions.php';

// Only admins can download templates
requireRole('admin');

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="order_import_template.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Write UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Write header row with instructions
fputcsv($output, ['Order Import Template - Instructions']);
fputcsv($output, ['1. Order Type: Must be either "shop_order" or "change_order"']);
fputcsv($output, ['2. Show Name: The name of the show (must exist in database)']);
fputcsv($output, ['3. Item Barcode: The barcode of the item (must exist in inventory)']);
fputcsv($output, ['4. Item Name: (Optional) For reference only - system uses barcode']);
fputcsv($output, ['5. Quantity: Number of items (positive integer)']);
fputcsv($output, ['6. Type: For change orders only - "add" or "remove". Leave blank for shop orders.']);
fputcsv($output, ['']);
fputcsv($output, ['Notes:']);
fputcsv($output, ['- All rows with the same Order Type and Show Name will be grouped into one order']);
fputcsv($output, ['- Delete these instruction rows before importing']);
fputcsv($output, ['- Keep the header row below']);
fputcsv($output, ['']);

// Write column headers
fputcsv($output, ['Order Type', 'Show Name', 'Item Barcode', 'Item Name', 'Quantity', 'Type']);

// Write example rows
fputcsv($output, ['shop_order', 'Example Show', 'PS-001', 'Shure SM58 Microphone', '5', '']);
fputcsv($output, ['shop_order', 'Example Show', 'PS-002', 'XLR Cable 50ft', '10', '']);
fputcsv($output, ['change_order', 'Another Show', 'PS-003', 'Speaker Stand', '2', 'add']);
fputcsv($output, ['change_order', 'Another Show', 'PS-001', 'Shure SM58 Microphone', '1', 'remove']);

fclose($output);
exit;
