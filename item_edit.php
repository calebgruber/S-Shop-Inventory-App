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
        $barcode = trim($_POST['barcode'] ?? '');
        $location = trim($_POST['location'] ?? '');
        
        // Handle photo upload
        $photoPath = $item['photo_path'] ?? null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/uploads/items/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileExt = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $allowedExts = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($fileExt, $allowedExts)) {
                $fileName = uniqid('item_') . '.' . $fileExt;
                $uploadPath = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadPath)) {
                    // Delete old photo if exists
                    if ($photoPath && file_exists($uploadDir . $photoPath)) {
                        unlink($uploadDir . $photoPath);
                    }
                    $photoPath = $fileName;
                }
            }
        }
        
        if ($itemId) {
            // Update existing item - barcode can't be changed once set
            getDB()->query(
                "UPDATE items SET name = ?, description = ?, category_id = ?, tracking_type = ?, 
                 total_quantity = ?, in_stock_quantity = ?, location = ?, photo_path = ? WHERE id = ?",
                [$name, $description, $categoryId, $trackingType, $totalQuantity, $inStockQuantity, $location, $photoPath, $itemId]
            );
            setAlert('Item updated successfully');
        } else {
            // Create new item - use custom barcode or generate one
            if (empty($barcode)) {
                $barcode = generateUniqueBarcode('ITEM');
            } else {
                // Check if barcode already exists
                $existing = getDB()->fetchOne("SELECT id FROM items WHERE barcode = ?", [$barcode]);
                if ($existing) {
                    throw new Exception("Barcode already exists");
                }
            }
            
            getDB()->query(
                "INSERT INTO items (name, description, barcode, category_id, tracking_type, total_quantity, in_stock_quantity, location, photo_path) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [$name, $description, $barcode, $categoryId, $trackingType, $totalQuantity, $inStockQuantity, $location, $photoPath]
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
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label required">Name</label>
                        <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($item['name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($item['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Location in Shop</label>
                        <input type="text" class="form-control" name="location" value="<?php echo htmlspecialchars($item['location'] ?? ''); ?>" placeholder="e.g., Shelf A-3, Cabinet 2">
                        <small class="form-hint">Where this item is physically located in the shop</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Item Photo</label>
                        <?php if ($item && $item['photo_path']): ?>
                        <div class="mb-2">
                            <img src="uploads/items/<?php echo htmlspecialchars($item['photo_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                 class="img-thumbnail" style="max-height: 200px;">
                        </div>
                        <?php endif; ?>
                        <input type="file" class="form-control" name="photo" accept="image/*">
                        <small class="form-hint">Upload a photo of the item (JPG, PNG, or GIF)</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Barcode</label>
                        <?php if ($item): ?>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($item['barcode']); ?>" readonly>
                            <small class="form-hint">Barcode cannot be changed after creation</small>
                        <?php else: ?>
                            <input type="text" class="form-control" name="barcode" placeholder="Leave blank to auto-generate">
                            <small class="form-hint">Leave blank to auto-generate a unique barcode</small>
                        <?php endif; ?>
                    </div>
                    
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
