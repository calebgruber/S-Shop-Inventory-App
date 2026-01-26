<?php
/**
 * Test Barcode Generation
 * Access this file to test that barcodes are generating correctly
 * URL: your-domain.com/public/test_barcode.php
 */

require_once __DIR__ . '/../includes/barcode/Code128.php';
require_once __DIR__ . '/../includes/barcode/PDF417.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Barcode Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .barcode-section { margin: 30px 0; padding: 20px; border: 1px solid #ccc; }
        img { border: 1px solid #000; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>Barcode Generation Test</h1>
    
    <div class="barcode-section">
        <h2>Code128 Barcode (for Items)</h2>
        <p>Test barcode: ITEM-12345678</p>
        <?php
        try {
            $code128 = new Code128();
            $barcodeData = $code128->generatePNG('ITEM-12345678', 2, 50);
            echo '<img src="data:image/png;base64,' . base64_encode($barcodeData) . '" alt="Code128 Barcode">';
            echo '<p style="color: green;">✓ Code128 generation successful!</p>';
        } catch (Exception $e) {
            echo '<p style="color: red;">✗ Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
        ?>
    </div>
    
    <div class="barcode-section">
        <h2>PDF417 Barcode (for Pullsheets/Change Orders)</h2>
        <p>Test barcode: PULL-ABCD1234</p>
        <?php
        try {
            $pdf417 = new PDF417();
            $barcodeData = $pdf417->generatePNG('PULL-ABCD1234', 2, 50);
            echo '<img src="data:image/png;base64,' . base64_encode($barcodeData) . '" alt="PDF417 Barcode">';
            echo '<p style="color: green;">✓ PDF417 generation successful!</p>';
        } catch (Exception $e) {
            echo '<p style="color: red;">✗ Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
        ?>
    </div>
    
    <div class="barcode-section">
        <h2>System Requirements Check</h2>
        <ul>
            <li>PHP Version: <?php echo PHP_VERSION; ?> <?php echo version_compare(PHP_VERSION, '7.4.0') >= 0 ? '✓' : '✗ (Requires 7.4+)'; ?></li>
            <li>GD Extension: <?php echo extension_loaded('gd') ? '✓ Installed' : '✗ Not installed (Required for barcode generation)'; ?></li>
            <li>PDO Extension: <?php echo extension_loaded('pdo') ? '✓ Installed' : '✗ Not installed (Required for database)'; ?></li>
            <li>PDO MySQL: <?php echo extension_loaded('pdo_mysql') ? '✓ Installed' : '✗ Not installed (Required for MySQL)'; ?></li>
        </ul>
    </div>
    
    <p><a href="index.php">← Back to Dashboard</a></p>
</body>
</html>
