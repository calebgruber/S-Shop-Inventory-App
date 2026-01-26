<?php
require_once 'config.php';
require_once 'db.php';
require_once 'functions.php';

$db = getDB();

// Get item ID
$itemId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($itemId <= 0) {
    redirectTo('inventory.php');
}

// Check if confirmed
if (isset($_POST['confirm_delete'])) {
    // Check if item is currently in use
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM pull_sheet_items WHERE item_id = ?");
    $stmt->bind_param("i", $itemId);
    $stmt->execute();
    $pullSheetCount = $stmt->get_result()->fetch_assoc()['count'];
    
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM change_order_items WHERE item_id = ?");
    $stmt->bind_param("i", $itemId);
    $stmt->execute();
    $changeOrderCount = $stmt->get_result()->fetch_assoc()['count'];
    
    if ($pullSheetCount > 0 || $changeOrderCount > 0) {
        $error = "Cannot delete item. It is currently in use on pull sheets or change orders.";
    } else {
        // Delete the item (CASCADE will handle related records)
        $stmt = $db->prepare("DELETE FROM items WHERE id = ?");
        $stmt->bind_param("i", $itemId);
        
        if ($stmt->execute()) {
            redirectTo('inventory.php');
        } else {
            $error = "Failed to delete item. Please try again.";
        }
    }
}

// Get item details for confirmation
$stmt = $db->prepare("SELECT i.*, c.name as category_name 
                      FROM items i 
                      LEFT JOIN categories c ON i.category_id = c.id 
                      WHERE i.id = ?");
$stmt->bind_param("i", $itemId);
$stmt->execute();
$result = $stmt->get_result();
$item = $result->fetch_assoc();

if (!$item) {
    redirectTo('inventory.php');
}

$pageTitle = "Delete Item - " . APP_NAME;
$pageHeader = "Delete Inventory Item";
$pageActions = '<a href="inventory.php" class="btn btn-secondary"><i class="ti ti-arrow-left"></i> Cancel</a>';

ob_start();
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-danger text-white">
                <h3 class="card-title">
                    <i class="ti ti-alert-triangle me-2"></i>Confirm Deletion
                </h3>
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <?php echo sanitize($error); ?>
                </div>
                <?php endif; ?>
                
                <div class="alert alert-warning">
                    <h4 class="alert-title">Warning!</h4>
                    <p class="mb-0">
                        You are about to permanently delete this inventory item. This action cannot be undone.
                    </p>
                </div>
                
                <div class="mb-4">
                    <h4>Item to be deleted:</h4>
                    <div class="card bg-light">
                        <div class="card-body">
                            <h3><?php echo sanitize($item['name']); ?></h3>
                            <div class="mb-1">
                                <strong>Barcode:</strong> <span class="font-monospace"><?php echo sanitize($item['barcode']); ?></span>
                            </div>
                            <?php if ($item['category_name']): ?>
                            <div class="mb-1">
                                <strong>Category:</strong> <?php echo sanitize($item['category_name']); ?>
                            </div>
                            <?php endif; ?>
                            <div class="mb-1">
                                <strong>Total Quantity:</strong> <?php echo $item['total_quantity']; ?>
                            </div>
                            <div class="mb-1">
                                <strong>Available:</strong> <?php echo $item['available_quantity']; ?>
                            </div>
                            <?php if ($item['description']): ?>
                            <div class="mt-2">
                                <strong>Description:</strong><br>
                                <?php echo nl2br(sanitize($item['description'])); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <form method="POST" onsubmit="return confirm('Are you absolutely sure you want to delete this item? This cannot be undone.');">
                    <div class="d-flex justify-content-end gap-2">
                        <a href="inventory.php" class="btn btn-secondary">
                            <i class="ti ti-x"></i> Cancel
                        </a>
                        <button type="submit" name="confirm_delete" class="btn btn-danger">
                            <i class="ti ti-trash"></i> Delete Item
                        </button>
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
