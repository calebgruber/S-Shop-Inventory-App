<?php
/**
 * Serve a Pick Receipt PDF for a given pullsheet ID.
 * GET /api/pick-receipt?id=<pullsheet_id>
 * Requires admin role.
 */
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
requireRole('admin');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    http_response_code(400);
    echo 'Invalid ID';
    exit;
}

$pullsheet = getPullsheetById($id);
if (!$pullsheet) {
    http_response_code(404);
    echo 'Shop order not found';
    exit;
}

$pdfData = generatePickReceiptPDF($id);
if (!$pdfData) {
    http_response_code(500);
    echo 'Failed to generate PDF';
    exit;
}

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="pick_receipt_' . $id . '.pdf"');
header('Content-Length: ' . strlen($pdfData));
echo $pdfData;
