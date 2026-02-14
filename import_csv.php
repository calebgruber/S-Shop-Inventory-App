<?php
require_once 'includes/functions.php';

// Only authorized users can import
requirePermission('pullsheets'); // or change_orders

$db = getDB();
$currentUser = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    try {
        // Validate file upload
        if ($_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload error: ' . $_FILES['csv_file']['error']);
        }
        
        // Validate file type
        $fileExt = strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION));
        if ($fileExt !== 'csv') {
            throw new Exception('Invalid file type. Please upload a CSV file.');
        }
        
        // Validate file size (max 5MB)
        $maxSize = 5 * 1024 * 1024;
        if ($_FILES['csv_file']['size'] > $maxSize) {
            throw new Exception('File too large. Maximum size is 5MB.');
        }
        
        // Open and read CSV file
        $csvFile = fopen($_FILES['csv_file']['tmp_name'], 'r');
        if ($csvFile === false) {
            throw new Exception('Could not open CSV file.');
        }
        
        // Skip BOM if present
        $bom = fread($csvFile, 3);
        if ($bom !== chr(0xEF).chr(0xBB).chr(0xBF)) {
            rewind($csvFile);
        }
        
        // Read header row
        $headers = fgetcsv($csvFile);
        if ($headers === false) {
            throw new Exception('CSV file is empty or invalid.');
        }
        
        // Expected headers
        $expectedHeaders = ['Order Type', 'Show Name', 'Item Barcode', 'Item Name', 'Quantity', 'Type'];
        
        // Normalize headers (trim whitespace, case insensitive)
        $headers = array_map('trim', $headers);
        $headerMap = [];
        foreach ($expectedHeaders as $expected) {
            $found = false;
            foreach ($headers as $index => $header) {
                if (strcasecmp($header, $expected) === 0) {
                    $headerMap[$expected] = $index;
                    $found = true;
                    break;
                }
            }
            if (!$found && $expected !== 'Item Name' && $expected !== 'Type') {
                throw new Exception("Missing required column: $expected");
            }
        }
        
        // Parse rows and group by order type and show
        $orders = [];
        $rowNum = 1; // Start from 1 (header is row 1, data starts at row 2)
        $errors = [];
        
        while (($row = fgetcsv($csvFile)) !== false) {
            $rowNum++;
            
            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }
            
            // Extract data
            $orderType = trim($row[$headerMap['Order Type']] ?? '');
            $showName = trim($row[$headerMap['Show Name']] ?? '');
            $itemBarcode = trim($row[$headerMap['Item Barcode']] ?? '');
            $quantity = trim($row[$headerMap['Quantity']] ?? '');
            $type = trim($row[$headerMap['Type']] ?? '');
            
            // Validate order type
            if ($orderType !== 'shop_order' && $orderType !== 'change_order') {
                $errors[] = "Row $rowNum: Invalid order type '$orderType'. Must be 'shop_order' or 'change_order'.";
                continue;
            }
            
            // Validate show name
            if (empty($showName)) {
                $errors[] = "Row $rowNum: Show name is required.";
                continue;
            }
            
            // Validate item barcode
            if (empty($itemBarcode)) {
                $errors[] = "Row $rowNum: Item barcode is required.";
                continue;
            }
            
            // Validate quantity
            if (!is_numeric($quantity) || $quantity <= 0) {
                $errors[] = "Row $rowNum: Invalid quantity '$quantity'. Must be a positive number.";
                continue;
            }
            $quantity = (int)$quantity;
            
            // Validate type for change orders
            if ($orderType === 'change_order') {
                if (empty($type)) {
                    $errors[] = "Row $rowNum: Type is required for change orders (add or remove).";
                    continue;
                }
                if ($type !== 'add' && $type !== 'remove') {
                    $errors[] = "Row $rowNum: Invalid type '$type'. Must be 'add' or 'remove' for change orders.";
                    continue;
                }
            }
            
            // Check if show exists
            $show = $db->fetchOne("SELECT id FROM shows WHERE LOWER(name) = LOWER(?)", [$showName]);
            if (!$show) {
                $errors[] = "Row $rowNum: Show '$showName' not found in database.";
                continue;
            }
            
            // Check if item exists
            $item = $db->fetchOne("SELECT id, name, in_stock_quantity FROM items WHERE barcode = ?", [$itemBarcode]);
            if (!$item) {
                $errors[] = "Row $rowNum: Item with barcode '$itemBarcode' not found in inventory.";
                continue;
            }
            
            // Group items by order type and show
            $orderKey = $orderType . '|' . $show['id'];
            if (!isset($orders[$orderKey])) {
                $orders[$orderKey] = [
                    'type' => $orderType,
                    'show_id' => $show['id'],
                    'show_name' => $showName,
                    'items' => []
                ];
            }
            
            $orders[$orderKey]['items'][] = [
                'item_id' => $item['id'],
                'item_name' => $item['name'],
                'barcode' => $itemBarcode,
                'quantity' => $quantity,
                'type' => $type,
                'row_num' => $rowNum
            ];
        }
        
        fclose($csvFile);
        
        // If there are errors, show them
        if (!empty($errors)) {
            $errorHtml = '<ul><li>' . implode('</li><li>', $errors) . '</li></ul>';
            setAlert('CSV import had errors:<br>' . $errorHtml, 'danger');
            redirect($_POST['redirect_to'] ?? 'pullsheets');
        }
        
        // If no orders to create, show error
        if (empty($orders)) {
            setAlert('No valid orders found in CSV file.', 'warning');
            redirect($_POST['redirect_to'] ?? 'pullsheets');
        }
        
        // Create orders
        $db->beginTransaction();
        try {
            $createdOrders = [];
            
            foreach ($orders as $orderData) {
                if ($orderData['type'] === 'shop_order') {
                    // Create pullsheet (shop order)
                    $barcode = 'PS-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
                    
                    $db->query(
                        "INSERT INTO pullsheets (show_id, barcode, created_by, assigned_by, status, requires_approval) 
                         VALUES (?, ?, ?, ?, 'draft', 0)",
                        [$orderData['show_id'], $barcode, $currentUser['id'], $currentUser['id']]
                    );
                    $pullsheetId = $db->lastInsertId();
                    
                    // Add items
                    foreach ($orderData['items'] as $item) {
                        $db->query(
                            "INSERT INTO pullsheet_items (pullsheet_id, item_id, quantity_needed) 
                             VALUES (?, ?, ?)",
                            [$pullsheetId, $item['item_id'], $item['quantity']]
                        );
                    }
                    
                    $createdOrders[] = [
                        'type' => 'Shop Order',
                        'show' => $orderData['show_name'],
                        'barcode' => $barcode,
                        'items' => count($orderData['items'])
                    ];
                    
                } else {
                    // Create change order
                    $barcode = 'CO-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
                    
                    $db->query(
                        "INSERT INTO change_orders (show_id, barcode, created_by, assigned_by, status, requires_approval) 
                         VALUES (?, ?, ?, ?, 'draft', 0)",
                        [$orderData['show_id'], $barcode, $currentUser['id'], $currentUser['id']]
                    );
                    $changeOrderId = $db->lastInsertId();
                    
                    // Add items
                    foreach ($orderData['items'] as $item) {
                        $db->query(
                            "INSERT INTO change_order_items (change_order_id, item_id, type, quantity) 
                             VALUES (?, ?, ?, ?)",
                            [$changeOrderId, $item['item_id'], $item['type'], $item['quantity']]
                        );
                    }
                    
                    $createdOrders[] = [
                        'type' => 'Change Order',
                        'show' => $orderData['show_name'],
                        'barcode' => $barcode,
                        'items' => count($orderData['items'])
                    ];
                }
            }
            
            $db->commit();
            
            // Create success message
            $successMsg = 'Successfully imported ' . count($createdOrders) . ' order(s):<br><ul>';
            foreach ($createdOrders as $order) {
                $successMsg .= '<li>' . $order['type'] . ' for ' . $order['show'] . 
                              ' (' . $order['barcode'] . ') with ' . $order['items'] . ' items</li>';
            }
            $successMsg .= '</ul>';
            
            setAlert($successMsg, 'success');
            redirect($_POST['redirect_to'] ?? 'pullsheets');
            
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
        
    } catch (Exception $e) {
        setAlert('Import failed: ' . $e->getMessage(), 'danger');
        redirect($_POST['redirect_to'] ?? 'pullsheets');
    }
} else {
    setAlert('No file uploaded.', 'danger');
    redirect('pullsheets');
}
