<?php
/**
 * Add New Inventory Item
 * Form to create a new item with barcode generation
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/helpers.php';

requireAuth();
requireRole(['admin', 'designer', 'production_audio']);

$pageName = 'Add Item';
$error = '';
$success = '';

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
        // Generate or use custom barcode
        $barcode = !empty($customBarcode) ? $customBarcode : generateUniqueBarcode();
        
        // Check if barcode already exists
        $checkResult = executeQuery(
            'SELECT id FROM items WHERE barcode = ?',
            [$barcode],
            's'
        );
        
        if ($checkResult && numRows($checkResult) > 0) {
            $error = 'Barcode already exists. Please use a different barcode.';
        } else {
            // Handle photo upload
            $photoPath = null;
            if (!empty($_FILES['photo']['name'])) {
                $uploadResult = uploadItemPhoto($_FILES['photo']);
                if ($uploadResult['success']) {
                    $photoPath = $uploadResult['path'];
                } else {
                    $error = $uploadResult['error'];
                }
            }
            
            if (empty($error)) {
                // Calculate quantities based on tracking type
                if ($trackingType === 'quantity') {
                    $totalQty = $quantity;
                    $inStockQty = $quantity;
                    $serialsJson = null;
                } else {
                    $serials = formatSerialNumbers($serialNumbers);
                    $serialsArray = parseSerialNumbers($serials);
                    $totalQty = count($serialsArray);
                    $inStockQty = $totalQty;
                    $serialsJson = $serials;
                }
                
                // Insert item
                $insertResult = executeQuery(
                    'INSERT INTO items (name, description, barcode, category_id, subcategory_id, tracking_type, total_quantity, in_stock_quantity, serial_numbers, location, photo_path) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [$name, $description, $barcode, $categoryId, $subcategoryId, $trackingType, $totalQty, $inStockQty, $serialsJson, $location, $photoPath],
                    'sssiisissss'
                );
                
                if ($insertResult) {
                    $itemId = getLastInsertId();
                    
                    // Generate barcode image
                    $barcodeImagePath = generateBarcodeImage($barcode);
                    
                    $success = 'Item added successfully!';
                    
                    // Redirect to item view
                    header('Location: ' . BASE_URL . 'inventory/view.php?id=' . $itemId . '&success=added');
                    exit;
                } else {
                    $error = 'Failed to add item. Please try again.';
                }
            }
        }
    }
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
                    <a href="<?php echo BASE_URL; ?>inventory/">Inventory</a>
                </div>
                <h2 class="page-title">
                    <i class="ti ti-plus me-2"></i>
                    Add New Item
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
                                <input type="text" class="form-control" name="name" required autofocus value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" rows="3"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Category</label>
                                    <select class="form-select" name="category_id" id="category-select">
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" <?php echo ($_POST['category_id'] ?? '') == $cat['id'] ? 'selected' : ''; ?>>
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
                                <input type="text" class="form-control" name="custom_barcode" placeholder="Leave blank to auto-generate" value="<?php echo htmlspecialchars($_POST['custom_barcode'] ?? ''); ?>">
                                <small class="form-hint">Auto-generated barcode will be created if left blank.</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label required">Tracking Type</label>
                                <div>
                                    <label class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="tracking_type" value="quantity" <?php echo ($_POST['tracking_type'] ?? 'quantity') === 'quantity' ? 'checked' : ''; ?> id="tracking-quantity">
                                        <span class="form-check-label">Quantity</span>
                                    </label>
                                    <label class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="tracking_type" value="serial" <?php echo ($_POST['tracking_type'] ?? '') === 'serial' ? 'checked' : ''; ?> id="tracking-serial">
                                        <span class="form-check-label">Serial Numbers</span>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="mb-3" id="quantity-field">
                                <label class="form-label">Quantity</label>
                                <input type="number" class="form-control" name="quantity" min="0" value="<?php echo htmlspecialchars($_POST['quantity'] ?? '0'); ?>">
                            </div>
                            
                            <div class="mb-3 d-none" id="serial-field">
                                <label class="form-label">Serial Numbers</label>
                                <textarea class="form-control" name="serial_numbers" rows="5" placeholder="Enter serial numbers (one per line or comma-separated)"><?php echo htmlspecialchars($_POST['serial_numbers'] ?? ''); ?></textarea>
                                <small class="form-hint">Enter one serial number per line or separate with commas.</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Location</label>
                                <input type="text" class="form-control" name="location" placeholder="e.g., Shelf A3, Storage Room B" value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Photo</label>
                                <input type="file" class="form-control" name="photo" accept="image/jpeg,image/png,image/gif">
                                <small class="form-hint">JPEG, PNG, or GIF. Max <?php echo MAX_FILE_SIZE / 1024 / 1024; ?>MB.</small>
                            </div>
                        </div>
                        <div class="card-footer text-end">
                            <a href="<?php echo BASE_URL; ?>inventory/" class="btn btn-link">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-device-floppy me-2"></i>
                                Save Item
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
                        <h4>Tracking Types</h4>
                        <dl>
                            <dt>Quantity</dt>
                            <dd>Track items by total count. Use for consumables or non-serialized equipment.</dd>
                            
                            <dt>Serial Numbers</dt>
                            <dd>Track individual items by unique serial numbers. Use for high-value equipment.</dd>
                        </dl>
                        
                        <h4>Barcodes</h4>
                        <p>Barcodes are automatically generated using Code128 format. You can also enter a custom barcode if needed.</p>
                        
                        <h4>Photos</h4>
                        <p>Upload a photo to help identify the item quickly. Photos appear in search results and item views.</p>
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
                    if (sub.id == '<?php echo $_POST['subcategory_id'] ?? ''; ?>') {
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
