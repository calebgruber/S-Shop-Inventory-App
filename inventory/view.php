<?php
/**
 * View Item Details
 * Display complete information about an inventory item
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/helpers.php';

requireAuth();

$itemId = $_GET['id'] ?? '';
$success = $_GET['success'] ?? '';

if (empty($itemId)) {
    header('Location: ' . BASE_URL . 'inventory/');
    exit;
}

// Get item details
$result = executeQuery(
    'SELECT i.*, c.name as category_name, s.name as subcategory_name
     FROM items i
     LEFT JOIN categories c ON i.category_id = c.id
     LEFT JOIN subcategories s ON i.subcategory_id = s.id
     WHERE i.id = ?',
    [$itemId],
    'i'
);

if (!$result || numRows($result) === 0) {
    header('Location: ' . BASE_URL . 'inventory/?error=notfound');
    exit;
}

$item = fetchAssoc($result);
$pageName = $item['name'];

// Parse serial numbers if serial tracking
$serials = [];
if ($item['tracking_type'] === 'serial') {
    $serials = parseSerialNumbers($item['serial_numbers']);
}

include dirname(__DIR__) . '/includes/header.php';
?>

<!-- Page Header -->
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">
                    <a href="<?php echo BASE_URL; ?>inventory/">Inventory</a>
                </div>
                <h2 class="page-title">
                    <?php echo htmlspecialchars($item['name']); ?>
                </h2>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <?php if (hasRole(['admin', 'designer', 'production_audio'])): ?>
                    <a href="<?php echo BASE_URL; ?>inventory/edit.php?id=<?php echo $item['id']; ?>" class="btn btn-primary">
                        <i class="ti ti-edit me-2"></i>
                        Edit Item
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Page Body -->
<div class="page-body">
    <div class="container-xl">
        <?php if ($success === 'added'): ?>
        <div class="alert alert-success alert-dismissible" role="alert">
            <div class="d-flex">
                <div><i class="ti ti-check me-2"></i></div>
                <div>Item added successfully!</div>
            </div>
            <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
        </div>
        <?php elseif ($success === 'updated'): ?>
        <div class="alert alert-success alert-dismissible" role="alert">
            <div class="d-flex">
                <div><i class="ti ti-check me-2"></i></div>
                <div>Item updated successfully!</div>
            </div>
            <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
        </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-3">
                    <div class="card-header">
                        <h3 class="card-title">Item Details</h3>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-4 text-muted">Name:</div>
                            <div class="col-md-8 fw-bold"><?php echo htmlspecialchars($item['name']); ?></div>
                        </div>
                        
                        <?php if ($item['description']): ?>
                        <div class="row mb-3">
                            <div class="col-md-4 text-muted">Description:</div>
                            <div class="col-md-8"><?php echo nl2br(htmlspecialchars($item['description'])); ?></div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="row mb-3">
                            <div class="col-md-4 text-muted">Barcode:</div>
                            <div class="col-md-8">
                                <span class="badge bg-dark font-monospace"><?php echo htmlspecialchars($item['barcode']); ?></span>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4 text-muted">Category:</div>
                            <div class="col-md-8">
                                <?php if ($item['category_name']): ?>
                                    <?php echo htmlspecialchars($item['category_name']); ?>
                                    <?php if ($item['subcategory_name']): ?>
                                        <span class="text-muted">/ <?php echo htmlspecialchars($item['subcategory_name']); ?></span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">Not categorized</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4 text-muted">Tracking Type:</div>
                            <div class="col-md-8">
                                <span class="badge bg-<?php echo $item['tracking_type'] === 'serial' ? 'info' : 'secondary'; ?>">
                                    <?php echo ucfirst($item['tracking_type']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <?php if ($item['tracking_type'] === 'quantity'): ?>
                        <div class="row mb-3">
                            <div class="col-md-4 text-muted">Total Quantity:</div>
                            <div class="col-md-8 h3"><?php echo number_format($item['total_quantity']); ?></div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4 text-muted">In Stock:</div>
                            <div class="col-md-8">
                                <span class="h3 text-<?php echo $item['in_stock_quantity'] > 0 ? 'success' : 'danger'; ?>">
                                    <?php echo number_format($item['in_stock_quantity']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-4 text-muted">Checked Out:</div>
                            <div class="col-md-8 h3"><?php echo number_format($item['total_quantity'] - $item['in_stock_quantity']); ?></div>
                        </div>
                        <?php else: ?>
                        <div class="row mb-3">
                            <div class="col-md-4 text-muted">Total Serial Numbers:</div>
                            <div class="col-md-8 h3"><?php echo count($serials); ?></div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($item['location']): ?>
                        <div class="row mb-3">
                            <div class="col-md-4 text-muted">Location:</div>
                            <div class="col-md-8"><?php echo htmlspecialchars($item['location']); ?></div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="row mb-3">
                            <div class="col-md-4 text-muted">Created:</div>
                            <div class="col-md-8"><?php echo date('F j, Y g:i A', strtotime($item['created_at'])); ?></div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 text-muted">Last Updated:</div>
                            <div class="col-md-8"><?php echo date('F j, Y g:i A', strtotime($item['updated_at'])); ?></div>
                        </div>
                    </div>
                </div>
                
                <?php if ($item['tracking_type'] === 'serial' && !empty($serials)): ?>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Serial Numbers (<?php echo count($serials); ?>)</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($serials as $serial): ?>
                            <div class="col-md-6 mb-2">
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($serial); ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="col-md-4">
                <?php if (!empty($item['photo_path']) && file_exists(BASE_PATH . '/' . $item['photo_path'])): ?>
                <div class="card mb-3">
                    <div class="card-header">
                        <h3 class="card-title">Photo</h3>
                    </div>
                    <div class="card-body p-0">
                        <img src="<?php echo BASE_URL . htmlspecialchars($item['photo_path']); ?>" 
                             alt="<?php echo htmlspecialchars($item['name']); ?>" 
                             class="w-100">
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Barcode</h3>
                    </div>
                    <div class="card-body text-center">
                        <?php
                        $barcodePath = BASE_PATH . '/assets/barcodes/' . $item['barcode'] . '.png';
                        if (file_exists($barcodePath)):
                        ?>
                        <img src="<?php echo BASE_URL; ?>assets/barcodes/<?php echo htmlspecialchars($item['barcode']); ?>.png" 
                             alt="Barcode" class="img-fluid mb-2">
                        <?php else: ?>
                        <div class="text-monospace h2"><?php echo htmlspecialchars($item['barcode']); ?></div>
                        <small class="text-muted">Barcode image not generated yet</small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
