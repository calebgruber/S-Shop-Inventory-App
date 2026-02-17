<?php
/**
 * PDF Preview Generator
 * Shows a preview of PDF with current settings
 */

require_once 'includes/functions.php';
require_once 'includes/pdf_helper.php';

// Only admins can preview PDFs
requireRole('admin');

try {
    // Generate preview PDF
    $pdf = generatePreviewPDF();
    
    // Output to browser
    outputPDF($pdf, 'preview.pdf', 'I');
    
} catch (Exception $e) {
    // If there's an error, show it
    header('Content-Type: text/html');
    echo '<h1>Error Generating PDF</h1>';
    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p><a href="/settings/#pdf">Back to Settings</a></p>';
}
