<?php
require_once 'config.php';
require_once 'db.php';
require_once 'functions.php';

$db = getDB();
$itemId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$errors = [];

if ($itemId <= 0) {
    redirectTo('inventory.php');
}

// Get item
$stmt = $db->prepare("SELECT * FROM items WHERE id = ?");
$stmt->bind_param("i", $itemId);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();

if (!$item) {
    redirectTo('inventory.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $totalQuantity = (int)($_POST['total_quantity'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    
    // Validation
    if (empty($name)) {
        $errors[] = "Item name is required.";
    }
    
    if ($totalQuantity < 0) {
        $errors[] = "Quantity cannot be negative.";
    }
    
    // Calculate new available quantity
    $quantityDiff = $totalQuantity - $item['total_quantity'];
    $newAvailable = $item['available_quantity'] + $quantityDiff;
    if ($newAvailable < 0) {
        $newAvailable = 0;
    }
    
    if (empty($errors)) {
        $stmt = $db->prepare("UPDATE items 
                              SET name = ?, category_id = ?, total_quantity = ?, available_quantity = ?, description = ? 
                              WHERE id = ?");
        $stmt->bind_param("siiisi", $name, $categoryId, $totalQuantity, $newAvailable, $description, $itemId);
        
        if ($stmt->execute()) {
            redirectTo("inventory.php");
        } else {
            $errors[] = "Failed to update item. Please try again.";
        }
    }
}

// Get categories
$categories = $db->query("SELECT * FROM categories ORDER BY name");

$pageTitle = "Edit Item - " . APP_NAME;
$pageHeader = "Edit Inventory Item";
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
                        <label class="form-label">Barcode</label>
                        <div class="input-group">
                            <input type="text" class="form-control font-monospace" 
                                   value="<?php echo sanitize($item['barcode']); ?>" disabled>
                            <a href="barcode_generator.php?type=code128&data=<?php echo urlencode($item['barcode']); ?>&download=1" 
                               class="btn btn-primary" target="_blank">
                                <i class="ti ti-download"></i> Download
                            </a>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label required">Item Name</label>
                        <input type="text" name="name" class="form-control auto-focus" 
                               value="<?php echo sanitize($_POST['name'] ?? $item['name']); ?>" 
                               placeholder="Enter item name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-select">
                            <option value="">Select category</option>
                            <?php while ($cat = $categories->fetch_assoc()): ?>
                            <option value="<?php echo $cat['id']; ?>"
                                <?php 
                                $selected = isset($_POST['category_id']) ? 
                                    ($_POST['category_id'] == $cat['id']) : 
                                    ($item['category_id'] == $cat['id']);
                                echo $selected ? 'selected' : ''; 
                                ?>>
                                <?php echo sanitize($cat['name']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tracking Type</label>
                        <input type="text" class="form-control" 
                               value="<?php echo ucfirst($item['tracking_type']); ?>" disabled>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Total Quantity</label>
                        <input type="number" name="total_quantity" class="form-control" 
                               value="<?php echo sanitize($_POST['total_quantity'] ?? $item['total_quantity']); ?>" 
                               min="0">
                        <small class="form-hint">
                            Current available: <?php echo $item['available_quantity']; ?>
                        </small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3" 
                                  placeholder="Enter item description"><?php echo sanitize($_POST['description'] ?? $item['description']); ?></textarea>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-check"></i> Update Item
                        </button>
                        <a href="inventory.php" class="btn btn-secondary">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Item Information</h3>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Total Quantity</label>
                    <div class="h3"><?php echo $item['total_quantity']; ?></div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Available</label>
                    <div class="h3 text-success"><?php echo $item['available_quantity']; ?></div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Reserved/In Use</label>
                    <div class="h3 text-warning"><?php echo $item['total_quantity'] - $item['available_quantity']; ?></div>
                </div>
                <div class="mb-0">
                    <label class="form-label">Created</label>
                    <div><?php echo formatDate($item['created_at']); ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require 'layout.php';
?>
