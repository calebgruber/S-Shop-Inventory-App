<?php
require_once 'config.php';
require_once 'db.php';
require_once 'functions.php';

$db = getDB();
$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $barcode = trim($_POST['barcode'] ?? '');
    $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $trackingType = $_POST['tracking_type'] ?? 'quantity';
    $totalQuantity = (int)($_POST['total_quantity'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    
    // Validation
    if (empty($name)) {
        $errors[] = "Item name is required.";
    }
    
    if (empty($barcode)) {
        $barcode = generateBarcode('item');
    } else {
        // Check if barcode already exists
        $stmt = $db->prepare("SELECT id FROM items WHERE barcode = ?");
        $stmt->bind_param("s", $barcode);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = "Barcode already exists.";
        }
    }
    
    if ($totalQuantity < 0) {
        $errors[] = "Quantity cannot be negative.";
    }
    
    if (empty($errors)) {
        $availableQuantity = $totalQuantity;
        
        $stmt = $db->prepare("INSERT INTO items (name, barcode, category_id, tracking_type, total_quantity, available_quantity, description) 
                              VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssiisis", $name, $barcode, $categoryId, $trackingType, $totalQuantity, $availableQuantity, $description);
        
        if ($stmt->execute()) {
            redirectTo("inventory.php");
        } else {
            $errors[] = "Failed to create item. Please try again.";
        }
    }
}

// Get categories
$categories = $db->query("SELECT * FROM categories ORDER BY name");

$pageTitle = "Add Item - " . APP_NAME;
$pageHeader = "Add Inventory Item";
$pageActions = '<a href="inventory.php" class="btn btn-secondary"><i class="ti ti-arrow-left"></i> Back</a>';

ob_start();
?>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger alert-dismissible" role="alert">
    <div class="d-flex">
        <div>
            <i class="ti ti-alert-circle"></i>
        </div>
        <div>
            <h4 class="alert-title">Error</h4>
            <div class="text-muted">
                <?php foreach ($errors as $error): ?>
                    <div><?php echo sanitize($error); ?></div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label required">Item Name</label>
                        <input type="text" name="name" class="form-control auto-focus" 
                               value="<?php echo sanitize($_POST['name'] ?? ''); ?>" 
                               placeholder="Enter item name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Barcode</label>
                        <input type="text" name="barcode" class="form-control" 
                               value="<?php echo sanitize($_POST['barcode'] ?? ''); ?>" 
                               placeholder="Leave blank to auto-generate">
                        <small class="form-hint">Leave blank to automatically generate a unique barcode</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select">
                            <option value="">Select category</option>
                            <?php while ($cat = $categories->fetch_assoc()): ?>
                            <option value="<?php echo $cat['id']; ?>"
                                <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                <?php echo sanitize($cat['name']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tracking Type</label>
                        <select name="tracking_type" class="form-select">
                            <option value="quantity" <?php echo (isset($_POST['tracking_type']) && $_POST['tracking_type'] === 'quantity') ? 'selected' : ''; ?>>
                                Quantity (track by count)
                            </option>
                            <option value="serial" <?php echo (isset($_POST['tracking_type']) && $_POST['tracking_type'] === 'serial') ? 'selected' : ''; ?>>
                                Serial (track individual units)
                            </option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Total Quantity</label>
                        <input type="number" name="total_quantity" class="form-control" 
                               value="<?php echo sanitize($_POST['total_quantity'] ?? '0'); ?>" 
                               min="0">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3" 
                                  placeholder="Enter item description"><?php echo sanitize($_POST['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-check"></i> Add Item
                        </button>
                        <a href="inventory.php" class="btn btn-secondary">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require 'layout.php';
?>
