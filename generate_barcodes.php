<?php
require_once 'includes/functions.php';

// Check permissions FIRST
if (!hasPermission('paperwork')) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Permission denied']);
    } else {
        setAlert('You do not have permission to generate barcodes', 'danger');
        redirect('master_barcode_list.php');
    }
    exit;
}

// Handle AJAX request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    try {
        $result = generateAllBarcodes();
        
        // Check if at least some barcodes were generated
        if ($result['success'] > 0) {
            echo json_encode([
                'success' => true,
                'generated' => $result['success'],
                'failed' => $result['failed'],
                'total' => $result['total']
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Failed to generate any barcodes. Check logs for details.',
                'failed' => $result['failed'],
                'total' => $result['total']
            ]);
        }
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
