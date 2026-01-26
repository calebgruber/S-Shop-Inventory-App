<?php
require_once 'config.php';
require_once 'db.php';
require_once 'functions.php';

$db = getDB();
$changeOrderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($changeOrderId <= 0) {
    redirectTo('change_orders.php');
}

// Get change order details
$stmt = $db->prepare("SELECT co.*, s.name as show_name, s.id as show_id
                      FROM change_orders co 
                      INNER JOIN shows s ON co.show_id = s.id 
                      WHERE co.id = ?");
$stmt->bind_param("i", $changeOrderId);
$stmt->execute();
$result = $stmt->get_result();
$changeOrder = $result->fetch_assoc();

if (!$changeOrder) {
    redirectTo('change_orders.php');
}

// Get items
$stmt = $db->prepare("SELECT coi.*, i.name, i.barcode 
                      FROM change_order_items coi 
                      INNER JOIN items i ON coi.item_id = i.id 
                      WHERE coi.change_order_id = ?");
$stmt->bind_param("i", $changeOrderId);
$stmt->execute();
$items = $stmt->get_result();

$pageTitle = "Change Order: " . sanitize($changeOrder['barcode']) . " - " . APP_NAME;
$pageHeader = "Change Order Details";
$pageActions = '<a href="change_orders.php" class="btn btn-secondary"><i class="ti ti-arrow-left"></i> Back</a>
                <a href="change_order_delete.php?id=' . $changeOrderId . '" class="btn btn-danger"><i class="ti ti-trash"></i> Delete</a>';

ob_start();
?>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">Change Order Information</h3>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Barcode</label>
                        <div class="font-weight-medium"><?php echo sanitize($changeOrder['barcode']); ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <div>
                            <span class="badge badge-outline text-<?php 
                                echo $changeOrder['status'] === 'finalized' ? 'blue' : 
                                    ($changeOrder['status'] === 'picked' ? 'green' : 
                                    ($changeOrder['status'] === 'completed' ? 'purple' : 'gray')); 
                            ?>">
                                <?php echo ucfirst($changeOrder['status']); ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Show</label>
                        <div>
                            <a href="show_view.php?id=<?php echo $changeOrder['show_id']; ?>">
                                <?php echo sanitize($changeOrder['show_name']); ?>
                            </a>
                        </div>
                    </div>
                    <?php if ($changeOrder['created_by']): ?>
                    <div class="col-md-6">
                        <label class="form-label">Created By</label>
                        <div><?php echo sanitize($changeOrder['created_by']); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label">Created</label>
                        <div><?php echo formatDate($changeOrder['created_at']); ?></div>
                    </div>
                    <?php if ($changeOrder['finalized_at']): ?>
                    <div class="col-md-6">
                        <label class="form-label">Finalized</label>
                        <div><?php echo formatDate($changeOrder['finalized_at']); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Items</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Barcode</th>
                            <th>Action</th>
                            <th>Quantity</th>
                            <th>Processed</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($item = $items->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo sanitize($item['name']); ?></td>
                            <td><?php echo sanitize($item['barcode']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $item['action_type'] === 'add' ? 'success' : 'danger'; ?>">
                                    <?php echo $item['action_type'] === 'add' ? 'Add' : 'Remove'; ?>
                                </span>
                            </td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td><?php echo $item['quantity_processed']; ?></td>
                            <td>
                                <?php if ($item['quantity_processed'] >= $item['quantity']): ?>
                                <span class="badge bg-success">Complete</span>
                                <?php elseif ($item['quantity_processed'] > 0): ?>
                                <span class="badge bg-warning">Partial</span>
                                <?php else: ?>
                                <span class="badge bg-secondary">Pending</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">Barcode</h3>
            </div>
            <div class="card-body text-center">
                <img src="barcode_generator.php?type=pdf417&data=<?php echo urlencode($changeOrder['barcode']); ?>" 
                     alt="Barcode" style="max-width: 100%;">
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Actions</h3>
            </div>
            <div class="list-group list-group-flush">
                <?php if ($changeOrder['status'] !== 'draft'): ?>
                <a href="pdf_generator.php?type=change_order&id=<?php echo $changeOrderId; ?>" 
                   class="list-group-item list-group-item-action" target="_blank">
                    <i class="ti ti-download me-2"></i> Download PDF
                </a>
                <?php endif; ?>
                <?php if ($changeOrder['status'] === 'finalized'): ?>
                <a href="pick_mode.php?change_order_id=<?php echo $changeOrderId; ?>" 
                   class="list-group-item list-group-item-action">
                    <i class="ti ti-scan me-2"></i> Start Processing
                </a>
                <?php endif; ?>
                <a href="show_view.php?id=<?php echo $changeOrder['show_id']; ?>" 
                   class="list-group-item list-group-item-action">
                    <i class="ti ti-theater me-2"></i> View Show
                </a>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require 'layout.php';
?>
