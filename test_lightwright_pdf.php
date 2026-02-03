<?php
/**
 * Test script for LightWright-style PDF generation
 * Generate sample pullsheet and change order PDFs
 */

require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

echo "<h1>LightWright-Style PDF Test</h1>";

// Get a test pullsheet
$db = getDB();
$pullsheets = $db->fetchAll("SELECT id FROM pullsheets WHERE status = 'finalized' LIMIT 1");

if (!empty($pullsheets)) {
    $pullsheetId = $pullsheets[0]['id'];
    echo "<h2>Testing Pullsheet PDF Generation</h2>";
    echo "<p>Generating PDF for pullsheet ID: $pullsheetId</p>";
    
    try {
        $pdf = generatePullsheetPDF($pullsheetId);
        
        // Save to temporary location
        $filename = '/tmp/test_pullsheet_lightwright.pdf';
        file_put_contents($filename, $pdf);
        
        echo "<p style='color: green;'>✓ Pullsheet PDF generated successfully!</p>";
        echo "<p>PDF saved to: $filename</p>";
        echo "<p>File size: " . filesize($filename) . " bytes</p>";
        
        // Provide download link
        echo "<p><a href='data:application/pdf;base64," . base64_encode($pdf) . "' download='test_pullsheet.pdf'>Download Pullsheet PDF</a></p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Error generating pullsheet PDF: " . $e->getMessage() . "</p>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
} else {
    echo "<p>No finalized pullsheets found to test.</p>";
}

echo "<hr>";

// Get a test change order
$changeOrders = $db->fetchAll("SELECT id FROM change_orders WHERE status = 'finalized' LIMIT 1");

if (!empty($changeOrders)) {
    $changeOrderId = $changeOrders[0]['id'];
    echo "<h2>Testing Change Order PDF Generation</h2>";
    echo "<p>Generating PDF for change order ID: $changeOrderId</p>";
    
    try {
        $pdf = generateChangeOrderPDF($changeOrderId);
        
        // Save to temporary location
        $filename = '/tmp/test_changeorder_lightwright.pdf';
        file_put_contents($filename, $pdf);
        
        echo "<p style='color: green;'>✓ Change Order PDF generated successfully!</p>";
        echo "<p>PDF saved to: $filename</p>";
        echo "<p>File size: " . filesize($filename) . " bytes</p>";
        
        // Provide download link
        echo "<p><a href='data:application/pdf;base64," . base64_encode($pdf) . "' download='test_changeorder.pdf'>Download Change Order PDF</a></p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Error generating change order PDF: " . $e->getMessage() . "</p>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
} else {
    echo "<p>No finalized change orders found to test.</p>";
}

echo "<hr>";
echo "<h2>LightWright Style Features Implemented:</h2>";
echo "<ul>";
echo "<li>✓ Professional table layout with bordered cells</li>";
echo "<li>✓ Gray header rows for visual hierarchy</li>";
echo "<li>✓ Category-based grouping (pullsheets)</li>";
echo "<li>✓ Add/Remove sections (change orders)</li>";
echo "<li>✓ Information box with show details</li>";
echo "<li>✓ Barcode preserved in top right corner</li>";
echo "<li>✓ Signature lines at bottom</li>";
echo "<li>✓ Professional fonts and spacing</li>";
echo "<li>✓ Page numbers</li>";
echo "<li>✓ Consistent column widths and alignment</li>";
echo "</ul>";
?>
