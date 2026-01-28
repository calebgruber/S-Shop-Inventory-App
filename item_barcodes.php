<?php
require_once 'includes/functions.php';

// Validate item BEFORE including header
$itemId = $_GET['id'] ?? null;
if (!$itemId) {
    redirect('items.php');
}

$item = getItemById($itemId);
if (!$item) {
    setAlert('Item not found', 'danger');
    redirect('items.php');
}

$pageTitle = 'Print Barcodes';
require_once 'includes/header.php';

$quantity = $_GET['quantity'] ?? 30;
?>

<div class="row">
    <div class="col-12 mb-3">
        <a href="items.php" class="btn btn-secondary">
            <i class="ti ti-arrow-left"></i> Back to Items
        </a>
        <button onclick="window.print()" class="btn btn-primary">
            <i class="ti ti-printer"></i> Print
        </button>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Barcodes for <?php echo htmlspecialchars($item['name']); ?></h3>
                <div class="ms-auto">
                    <form method="GET" class="d-flex gap-2">
                        <input type="hidden" name="id" value="<?php echo $itemId; ?>">
                        <input type="number" name="quantity" class="form-control" value="<?php echo $quantity; ?>" min="1" max="100">
                        <button type="submit" class="btn btn-primary">Update</button>
                    </form>
                </div>
            </div>
            <div class="card-body">
                <style>
                    @media print {
                        body * { visibility: hidden; }
                        .barcode-grid, .barcode-grid * { visibility: visible; }
                        .barcode-grid { position: absolute; left: 0; top: 0; }
                        .barcode-label { page-break-inside: avoid; }
                    }
                    
                    .barcode-grid {
                        display: grid;
                        grid-template-columns: repeat(2, 1fr);
                        gap: 0.25in;
                        padding: 0.5in;
                    }
                    
                    .barcode-label {
                        width: 4in;
                        height: 2in;
                        border: 1px solid #ddd;
                        padding: 0.25in;
                        display: flex;
                        flex-direction: column;
                        justify-content: center;
                        align-items: center;
                        text-align: center;
                    }
                    
                    .barcode-image {
                        max-width: 100%;
                        height: auto;
                        margin: 0.1in 0;
                    }
                    
                    .barcode-text {
                        font-size: 12pt;
                        font-weight: bold;
                        margin-bottom: 0.1in;
                    }
                    
                    .barcode-code {
                        font-family: monospace;
                        font-size: 10pt;
                    }
                </style>
                
                <div class="barcode-grid">
                    <?php 
                    // Use cached barcode or generate
                    $barcodeUrl = getBarcodeImageUrl($item['barcode']);
                    if ($barcodeUrl) {
                        $barcodeData = $barcodeUrl;
                    } else {
                        $imageData = getOrGenerateBarcodeImage($item['barcode'], true);
                        $barcodeData = 'data:image/png;base64,' . base64_encode($imageData);
                    }
                    
                    for ($i = 0; $i < $quantity; $i++): 
                    ?>
                        <div class="barcode-label">
                            <div class="barcode-text"><?php echo htmlspecialchars($item['name']); ?></div>
                            <img src="<?php echo $barcodeData; ?>" alt="Barcode" class="barcode-image">
                            <div class="barcode-code"><?php echo htmlspecialchars($item['barcode']); ?></div>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
