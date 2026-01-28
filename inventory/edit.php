<?php
/**
 * Edit Inventory Item
 * Form to update existing item
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/helpers.php';

requireAuth();
requireRole(['admin', 'designer', 'production_audio']);

$itemId = $_GET['id'] ?? '';
$error = '';
$success = '';

if (empty($itemId)) {
    header('Location: ' . BASE_URL . 'inventory/');
    exit;
}

// Get existing item
$result = executeQuery(
    'SELECT * FROM items WHERE id = ?',
    [$itemId],
    'i'
);

if (!$result || numRows($result) === 0) {
    header('Location: ' . BASE_URL . 'inventory/?error=notfound');
    exit;
}

$item = fetchAssoc($result);
$pageName = 'Edit: ' . $item['name'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $categoryId = $_POST['category_id'] ?? null;
    $subcategoryId = $_POST['subcategory_id'] ?? null;
    $trackingType = $_POST['tracking_type'] ?? 'quantity';
    $quantity = intval($_POST['quantity'] ?? 0);
    $serialNumbers = $_POST['serial_numbers'] ?? '';
    $location = sanitize($_POST['location'] ?? '');
    $customBarcode = sanitize($_POST['custom_barcode'] ?? '');
    
    // Validation
    if (empty($name)) {
        $error = 'Item name is required.';
    } elseif ($trackingType === 'quantity' && $quantity < 0) {
        $error = 'Quantity cannot be negative.';
    } elseif ($trackingType === 'serial' && empty($serialNumbers)) {
        $error = 'Serial numbers are required for serial tracking.';
    } else {
        // Check barcode uniqueness (if changed)
        $barcode = !empty($customBarcode) ? $customBarcode : $item['barcode'];
        
        if ($barcode !== $item['barcode']) {
            $checkResult = executeQuery(
                'SELECT id FROM items WHERE barcode = ? AND id != ?',
                [$barcode, $itemId],
                'si'
            );
            
            if ($checkResult && numRows($checkResult) > 0) {
                $error = 'Barcode already exists. Please use a different barcode.';
            }
        }
        
        if (empty($error)) {
            // Handle photo upload
            $photoPath = $item['photo_path'];
            $deleteOldPhoto = isset($_POST['delete_photo']);
            
            if ($deleteOldPhoto && !empty($photoPath)) {
                deleteItemPhoto($photoPath);
                $photoPath = null;
            }
            
            if (!empty($_FILES['photo']['name'])) {
                $uploadResult = uploadItemPhoto($_FILES['photo']);
                if ($uploadResult['success']) {
                    // Delete old photo
                    if (!empty($photoPath)) {
                        deleteItemPhoto($photoPath);
                    }
                    $photoPath = $uploadResult['path'];
                } else {
                    $error = $uploadResult['error'];
                }
            }
            
            if (empty($error)) {
                // Calculate quantities based on tracking type
                if ($trackingType === 'quantity') {
                    // When switching from serial to quantity, or updating quantity
                    if ($item['tracking_type'] === 'quantity') {
                        // Keep existing stock calculation, just update total
                        $diff = $quantity - $item['total_quantity'];
                        $inStockQty = max(0, $item['in_stock_quantity'] + $diff);
                    } else {
                        // Switching from serial to quantity
                        $inStockQty = $quantity;
                    }
                    $totalQty = $quantity;
                    $serialsJson = null;
                } else {
                    // Serial tracking
                    $serials = formatSerialNumbers($serialNumbers);
                    $serialsArray = parseSerialNumbers($serials);
                    $totalQty = count($serialsArray);
                    
                    if ($item['tracking_type'] === 'serial') {
                        // Keep existing in stock calculation
                        $inStockQty = min($totalQty, $item['in_stock_quantity']);
                    } else {
                        // Switching from quantity to serial
                        $inStockQty = $totalQty;
                    }
                    
                    $serialsJson = $serials;
                }
                
                // Update item
                $updateResult = executeQuery(
                    'UPDATE items SET 
                     name = ?, description = ?, barcode = ?, category_id = ?, subcategory_id = ?, 
                     tracking_type = ?, total_quantity = ?, in_stock_quantity = ?, 
                     serial_numbers = ?, location = ?, photo_path = ?
                     WHERE id = ?',
                    [$name, $description, $barcode, $categoryId, $subcategoryId, $trackingType, 
                     $totalQty, $inStockQty, $serialsJson, $location, $photoPath, $itemId],
                    'sssiisiiisssi'
                );
                
                if ($updateResult) {
                    // Generate barcode image if barcode changed
                    if ($barcode !== $item['barcode']) {
                        generateBarcodeImage($barcode);
                    }
                    
                    header('Location: ' . BASE_URL . 'inventory/view.php?id=' . $itemId . '&success=updated');
                    exit;
                } else {
                    $error = 'Failed to update item. Please try again.';
                }
            }
        }
    }
}

// Parse current serial numbers
$currentSerials = '';
if ($item['tracking_type'] === 'serial') {
    $serials = parseSerialNumbers($item['serial_numbers']);
    $currentSerials = implode("\n", $serials);
}

// Get categories
$categories = getAllCategories();

include dirname(__DIR__) . '/includes/header.php';
?>

<!-- Page Header -->
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">
                    <a href="<?php echo BASE_URL; ?>inventory/">Inventory</a> / 
                    <a href="<?php echo BASE_URL; ?>inventory/view.php?id=<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item['name']); ?></a>
                </div>
                <h2 class="page-title">
                    <i class="ti ti-edit me-2"></i>
                    Edit Item
                </h2>
            </div>
        </div>
    </div>
</div>

<!-- Page Body -->
<div class="page-body">
    <div class="container-xl">
        <div class="row">
            <div class="col-md-8">
                <form method="POST" enctype="multipart/form-data" action="">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Item Details</h3>
                        </div>
                        <div class="card-body">
                            <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible" role="alert">
                                <div class="d-flex">
                                    <div><i class="ti ti-alert-circle me-2"></i></div>
                                    <div><?php echo htmlspecialchars($error); ?></div>
                                </div>
                                <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
                            </div>
                            <?php endif; ?>
                            
                            <div class="mb-3">
                                <label class="form-label required">Item Name</label>
                                <input type="text" class="form-control" name="name" required autofocus 
                                       value="<?php echo htmlspecialchars($_POST['name'] ?? $item['name']); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($_POST['description'] ?? $item['description']); ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Category</label>
                                    <select class="form-select" name="category_id" id="category-select">
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" 
                                                <?php echo ($_POST['category_id'] ?? $item['category_id']) == $cat['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Subcategory</label>
                                    <select class="form-select" name="subcategory_id" id="subcategory-select">
                                        <option value="">Select Subcategory</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Barcode</label>
                                <input type="text" class="form-control" name="custom_barcode" 
                                       value="<?php echo htmlspecialchars($_POST['custom_barcode'] ?? $item['barcode']); ?>">
                                <small class="form-hint">Current: <?php echo htmlspecialchars($item['barcode']); ?></small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label required">Tracking Type</label>
                                <div>
                                    <label class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="tracking_type" value="quantity" 
                                               <?php echo ($_POST['tracking_type'] ?? $item['tracking_type']) === 'quantity' ? 'checked' : ''; ?> 
                                               id="tracking-quantity">
                                        <span class="form-check-label">Quantity</span>
                                    </label>
                                    <label class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="tracking_type" value="serial" 
                                               <?php echo ($_POST['tracking_type'] ?? $item['tracking_type']) === 'serial' ? 'checked' : ''; ?> 
                                               id="tracking-serial">
                                        <span class="form-check-label">Serial Numbers</span>
                                    </label>
                                </div>
                                <?php if ($item['in_stock_quantity'] != $item['total_quantity']): ?>
                                <small class="text-warning">
                                    <i class="ti ti-alert-triangle me-1"></i>
                                    Warning: Some items are checked out. Changing tracking type may affect inventory counts.
                                </small>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mb-3" id="quantity-field">
                                <label class="form-label">Quantity</label>
                                <input type="number" class="form-control" name="quantity" min="0" 
                                       value="<?php echo htmlspecialchars($_POST['quantity'] ?? $item['total_quantity']); ?>">
                                <small class="form-hint">Currently in stock: <?php echo $item['in_stock_quantity']; ?></small>
                            </div>
                            
                            <div class="mb-3 d-none" id="serial-field">
                                <label class="form-label">Serial Numbers</label>
                                <textarea class="form-control" name="serial_numbers" rows="5" 
                                          placeholder="Enter serial numbers (one per line or comma-separated)"><?php echo htmlspecialchars($_POST['serial_numbers'] ?? $currentSerials); ?></textarea>
                                <small class="form-hint">Enter one serial number per line or separate with commas.</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Location</label>
                                <input type="text" class="form-control" name="location" placeholder="e.g., Shelf A3, Storage Room B" 
                                       value="<?php echo htmlspecialchars($_POST['location'] ?? $item['location']); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Photo</label>
                                <?php if (!empty($item['photo_path']) && file_exists(BASE_PATH . '/' . $item['photo_path'])): ?>
                                <div class="mb-2">
                                    <img src="<?php echo BASE_URL . htmlspecialchars($item['photo_path']); ?>" 
                                         alt="Current photo" class="img-thumbnail" style="max-width: 200px;">
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" name="delete_photo" id="delete-photo">
                                        <label class="form-check-label" for="delete-photo">
                                            Delete current photo
                                        </label>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <input type="file" class="form-control" name="photo" accept="image/jpeg,image/png,image/gif">
                                <small class="form-hint">Upload a new photo to replace the current one. JPEG, PNG, or GIF. Max <?php echo MAX_FILE_SIZE / 1024 / 1024; ?>MB.</small>
                            </div>
                        </div>
                        <div class="card-footer text-end">
                            <a href="<?php echo BASE_URL; ?>inventory/view.php?id=<?php echo $item['id']; ?>" class="btn btn-link">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-device-floppy me-2"></i>
                                Update Item
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Help</h3>
                    </div>
                    <div class="card-body">
                        <h4>Editing Items</h4>
                        <p>You can update any field except the barcode. To change the barcode, enter a new value in the barcode field.</p>
                        
                        <h4>Tracking Type</h4>
                        <p>Changing the tracking type will reset the inventory counts. Make sure all items are returned before making this change.</p>
                        
                        <h4>Photos</h4>
                        <p>Upload a new photo to replace the existing one, or check "Delete current photo" to remove it.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle quantity/serial fields based on tracking type
function updateTrackingFields() {
    const trackingType = document.querySelector('input[name="tracking_type"]:checked').value;
    const quantityField = document.getElementById('quantity-field');
    const serialField = document.getElementById('serial-field');
    
    if (trackingType === 'quantity') {
        quantityField.classList.remove('d-none');
        serialField.classList.add('d-none');
    } else {
        quantityField.classList.add('d-none');
        serialField.classList.remove('d-none');
    }
}

document.querySelectorAll('input[name="tracking_type"]').forEach(radio => {
    radio.addEventListener('change', updateTrackingFields);
});

// Load subcategories when category changes
document.getElementById('category-select').addEventListener('change', function() {
    const categoryId = this.value;
    const subcategorySelect = document.getElementById('subcategory-select');
    
    // Clear subcategories
    subcategorySelect.innerHTML = '<option value="">Select Subcategory</option>';
    
    if (!categoryId) {
        return;
    }
    
    // Fetch subcategories
    fetch('<?php echo BASE_URL; ?>api/get-subcategories.php?category_id=' + categoryId)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.subcategories) {
                data.subcategories.forEach(sub => {
                    const option = document.createElement('option');
                    option.value = sub.id;
                    option.textContent = sub.name;
                    if (sub.id == '<?php echo $_POST['subcategory_id'] ?? $item['subcategory_id']; ?>') {
                        option.selected = true;
                    }
                    subcategorySelect.appendChild(option);
                });
            }
        })
        .catch(error => console.error('Error loading subcategories:', error));
});

// Initialize tracking fields on page load
window.addEventListener('DOMContentLoaded', function() {
    updateTrackingFields();
    
    // Load subcategories if category is selected
    const categoryId = document.getElementById('category-select').value;
    if (categoryId) {
        document.getElementById('category-select').dispatchEvent(new Event('change'));
    }
});
</script>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
