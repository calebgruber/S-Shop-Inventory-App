<?php
$pageTitle = 'Edit Item';
require_once 'includes/header.php';

$itemId = $_GET['id'] ?? null;
$item = $itemId ? getItemById($itemId) : null;
$categories = getAllCategories();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name = $_POST['name'];
        $description = $_POST['description'];
        $categoryId = $_POST['category_id'] ?: null;
        $trackingType = $_POST['tracking_type'];
        $totalQuantity = (int)$_POST['total_quantity'];
        $inStockQuantity = (int)$_POST['in_stock_quantity'];
        
        if ($itemId) {
            // Update existing item
            getDB()->query(
                "UPDATE items SET name = ?, description = ?, category_id = ?, tracking_type = ?, 
                 total_quantity = ?, in_stock_quantity = ? WHERE id = ?",
                [$name, $description, $categoryId, $trackingType, $totalQuantity, $inStockQuantity, $itemId]
            );
            setAlert('Item updated successfully');
        } else {
            // Create new item
            $barcode = generateUniqueBarcode('ITEM');
            getDB()->query(
                "INSERT INTO items (name, description, barcode, category_id, tracking_type, total_quantity, in_stock_quantity) 
                 VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$name, $description, $barcode, $categoryId, $trackingType, $totalQuantity, $inStockQuantity]
            );
            setAlert('Item created successfully');
        }
        
        redirect('items.php');
    } catch (Exception $e) {
        setAlert($e->getMessage(), 'danger');
    }
}
?>

<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><?php echo $item ? 'Edit' : 'Add New'; ?> Item</h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label required">Name</label>
                        <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($item['name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($item['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <?php if ($item): ?>
                        <div class="mb-3">
                            <label class="form-label">Barcode</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($item['barcode']); ?>" readonly>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select class="form-select" name="category_id">
                            <option value="">-- No Category --</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" 
                                    <?php echo ($item && $item['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label required">Tracking Type</label>
                        <select class="form-select" name="tracking_type" required>
                            <option value="quantity" <?php echo ($item && $item['tracking_type'] === 'quantity') ? 'selected' : ''; ?>>Quantity</option>
                            <option value="serial" <?php echo ($item && $item['tracking_type'] === 'serial') ? 'selected' : ''; ?>>Serial Number</option>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label required">Total Quantity</label>
                            <input type="number" class="form-control" name="total_quantity" 
                                   value="<?php echo $item['total_quantity'] ?? 0; ?>" min="0" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label required">In Stock Quantity</label>
                            <input type="number" class="form-control" name="in_stock_quantity" 
                                   value="<?php echo $item['in_stock_quantity'] ?? 0; ?>" min="0" required>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-check"></i> Save Item
                        </button>
                        <a href="items.php" class="btn btn-secondary">Cancel</a>
                        
                        <?php if ($item): ?>
                            <a href="item_barcodes.php?id=<?php echo $item['id']; ?>" class="btn btn-info ms-auto">
                                <i class="ti ti-barcode"></i> Print Barcodes
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
