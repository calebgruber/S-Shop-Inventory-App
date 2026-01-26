<?php
require_once 'config.php';
require_once 'db.php';
require_once 'functions.php';

$db = getDB();

// Get change order ID
$changeOrderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($changeOrderId <= 0) {
    redirectTo('change_orders.php');
}

// Check if confirmed
if (isset($_POST['confirm_delete'])) {
    // Get change order info to check status
    $stmt = $db->prepare("SELECT status FROM change_orders WHERE id = ?");
    $stmt->bind_param("i", $changeOrderId);
    $stmt->execute();
    $co = $stmt->get_result()->fetch_assoc();
    
    if ($co && in_array($co['status'], ['draft', 'finalized'])) {
        // Delete the change order (CASCADE will handle related records)
        $stmt = $db->prepare("DELETE FROM change_orders WHERE id = ?");
        $stmt->bind_param("i", $changeOrderId);
        
        if ($stmt->execute()) {
            redirectTo('change_orders.php');
        } else {
            $error = "Failed to delete change order. Please try again.";
        }
    } else {
        $error = "Cannot delete change orders that have been processed. Status: " . $co['status'];
    }
}

// Get change order details for confirmation
$stmt = $db->prepare("SELECT co.*, s.name as show_name 
                      FROM change_orders co 
                      LEFT JOIN shows s ON co.show_id = s.id 
                      WHERE co.id = ?");
$stmt->bind_param("i", $changeOrderId);
$stmt->execute();
$result = $stmt->get_result();
$changeOrder = $result->fetch_assoc();

if (!$changeOrder) {
    redirectTo('change_orders.php');
}

// Get items count
$stmt = $db->prepare("SELECT COUNT(*) as count FROM change_order_items WHERE change_order_id = ?");
$stmt->bind_param("i", $changeOrderId);
$stmt->execute();
$itemsCount = $stmt->get_result()->fetch_assoc()['count'];

$pageTitle = "Delete Change Order - " . APP_NAME;
$pageHeader = "Delete Change Order";
$pageActions = '<a href="change_order_view.php?id=' . $changeOrderId . '" class="btn btn-secondary"><i class="ti ti-arrow-left"></i> Cancel</a>';

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
                        You are about to permanently delete this change order. This action cannot be undone.
                    </p>
                </div>
                
                <div class="mb-4">
                    <h4>Change Order to be deleted:</h4>
                    <div class="card bg-light">
                        <div class="card-body">
                            <h3><?php echo sanitize($changeOrder['barcode']); ?></h3>
                            <div class="mb-1">
                                <strong>Show:</strong> <?php echo sanitize($changeOrder['show_name']); ?>
                            </div>
                            <div class="mb-1">
                                <strong>Status:</strong> 
                                <span class="badge badge-outline text-<?php 
                                    echo $changeOrder['status'] === 'finalized' ? 'blue' : 
                                        ($changeOrder['status'] === 'picked' ? 'green' : 'gray'); 
                                ?>">
                                    <?php echo ucfirst($changeOrder['status']); ?>
                                </span>
                            </div>
                            <div class="mb-1">
                                <strong>Items:</strong> <?php echo $itemsCount; ?>
                            </div>
                            <div class="mt-2">
                                <small class="text-muted">Created: <?php echo formatDate($changeOrder['created_at']); ?></small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if (!in_array($changeOrder['status'], ['draft', 'finalized'])): ?>
                <div class="alert alert-info">
                    <h4 class="alert-title">Note</h4>
                    <p class="mb-0">
                        Change orders that have been processed cannot be deleted to maintain inventory accuracy.
                    </p>
                </div>
                <?php endif; ?>
                
                <form method="POST" onsubmit="return confirm('Are you absolutely sure you want to delete this change order? This cannot be undone.');">
                    <div class="d-flex justify-content-end gap-2">
                        <a href="change_order_view.php?id=<?php echo $changeOrderId; ?>" class="btn btn-secondary">
                            <i class="ti ti-x"></i> Cancel
                        </a>
                        <?php if (in_array($changeOrder['status'], ['draft', 'finalized'])): ?>
                        <button type="submit" name="confirm_delete" class="btn btn-danger">
                            <i class="ti ti-trash"></i> Delete Change Order
                        </button>
                        <?php endif; ?>
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
