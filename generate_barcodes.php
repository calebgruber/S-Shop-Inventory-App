<?php
require_once 'includes/functions.php';

// Check permissions
if (!hasPermission('paperwork')) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Permission denied']);
    exit;
}

// Handle AJAX request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    try {
        $result = generateAllBarcodes();
        echo json_encode([
            'success' => true,
            'generated' => $result['success'],
            'failed' => $result['failed'],
            'total' => $result['total']
        ]);
    } catch (Exception $e) {
        logException($e, 'Error generating barcodes');
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

// Not an AJAX request, redirect
redirect('master_barcode_list.php');
