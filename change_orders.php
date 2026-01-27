<?php
require_once 'includes/functions.php';

// Check permissions - designers and admins can access change orders
if (!hasPermission('change_orders')) {
    setAlert('You do not have permission to access change orders', 'danger');
    redirect('index.php');
}

$pageTitle = 'Change Orders';
require_once 'includes/header.php';

$currentUser = getCurrentUser();
$isDesigner = $currentUser['role'] === 'designer';

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    try {
        $db = getDB();
        $deleteId = (int)$_POST['delete_id'];
        
        // Get change order details
        $changeOrder = getChangeOrderById($deleteId);
        if (!$changeOrder) {
            throw new Exception('Change order not found');
        }
        
        // Check permission for designers
        if ($isDesigner && !canAccessShow($currentUser['id'], $changeOrder['show_id'])) {
            throw new Exception('You do not have permission to delete this change order');
        }
        
        // Start transaction
        $db->query("START TRANSACTION");
        
        // If change order was finalized, unreserve the items
        if ($changeOrder['status'] === 'finalized' || $changeOrder['status'] === 'picked') {
            $items = getChangeOrderItems($deleteId);
            foreach ($items as $item) {
                // Only unreserve items that were being added (removed from stock)
                if ($item['type'] === 'add') {
                    $db->query(
                        "UPDATE items SET in_stock_quantity = in_stock_quantity + ? WHERE id = ?",
                        [$item['quantity'], $item['item_id']]
                    );
                    logMessage("Unreserved {$item['quantity']} of item ID {$item['item_id']} from change order ID $deleteId", 'INFO');
                }
                // Items with type='remove' were being returned, so no stock adjustment needed
            }
        }
        
        // Delete change order items and change order
        $db->query("DELETE FROM change_order_items WHERE change_order_id = ?", [$deleteId]);
        $db->query("DELETE FROM change_orders WHERE id = ?", [$deleteId]);
        
        $db->query("COMMIT");
        
        logMessage("Change order ID $deleteId deleted by user ID {$currentUser['id']}", 'INFO');
        setAlert('Change order deleted successfully and items returned to stock');
        redirect();
    } catch (Exception $e) {
        if (isset($db)) {
            $db->query("ROLLBACK");
        }
        logException($e, 'Error deleting change order');
        setAlert('Error: ' . $e->getMessage(), 'danger');
    }
}

// Handle create change order request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_change_order'])) {
    try {
        $showId = !empty($_POST['show_id']) ? (int)$_POST['show_id'] : null;
        
        // Check permission for designers
        if ($isDesigner && $showId && !canAccessShow($currentUser['id'], $showId)) {
            throw new Exception('You do not have permission to create change order for this show');
        }
        
        $createdBy = $_POST['created_by'] ?? 'Unknown';
        $barcode = generateUniqueBarcode('CO');
        
        getDB()->query(
            "INSERT INTO change_orders (show_id, barcode, created_by, status) VALUES (?, ?, ?, 'draft')",
            [$showId, $barcode, $createdBy]
        );
        
        $changeOrderId = getDB()->lastInsertId();
        setAlert('Change order created successfully');
        redirect('change_order_edit.php?id=' . $changeOrderId);
    } catch (Exception $e) {
        setAlert('Error: ' . $e->getMessage(), 'danger');
    }
}

// Get change orders filtered by show access for designers
if ($isDesigner) {
    $assignedShows = getAssignedShows($currentUser['id']);
    $assignedShowIds = array_column($assignedShows, 'id');
    
    if (empty($assignedShowIds)) {
        $changeOrders = [];
    } else {
        $placeholders = implode(',', array_fill(0, count($assignedShowIds), '?'));
        $changeOrders = getDB()->fetchAll(
            "SELECT co.*, s.name as show_name 
             FROM change_orders co 
             LEFT JOIN shows s ON co.show_id = s.id 
             WHERE co.show_id IN ($placeholders)
             ORDER BY co.created_at DESC",
            $assignedShowIds
        );
    }
    
    // Filter shows for dropdown
    $shows = $assignedShows;
} else {
    // Admins see all change orders and shows
    $changeOrders = getDB()->fetchAll("SELECT co.*, s.name as show_name 
        FROM change_orders co 
        LEFT JOIN shows s ON co.show_id = s.id 
        ORDER BY co.created_at DESC");
    
    $shows = getDB()->fetchAll("SELECT id, name FROM shows ORDER BY name ASC");
}

// Group change orders by show
$changeOrdersByShow = [];
$changeOrdersNoShow = [];

foreach ($changeOrders as $changeOrder) {
    if ($changeOrder['show_id']) {
        $showId = $changeOrder['show_id'];
        if (!isset($changeOrdersByShow[$showId])) {
            $changeOrdersByShow[$showId] = [
                'show_name' => $changeOrder['show_name'],
                'change_orders' => []
            ];
        }
        $changeOrdersByShow[$showId]['change_orders'][] = $changeOrder;
    } else {
        $changeOrdersNoShow[] = $changeOrder;
    }
}
?>

<div class="row mb-3">
    <div class="col-md-8">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createChangeOrderModal">
            <i class="ti ti-plus"></i> Create New Change Order
        </button>
    </div>
</div>

<!-- Create Change Order Modal -->
<div class="modal fade" id="createChangeOrderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Change Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Show (Optional)</label>
                        <select name="show_id" class="form-select">
                            <option value="">No Show (Standalone Change Order)</option>
                            <?php foreach ($shows as $show): ?>
                                <option value="<?php echo $show['id']; ?>"><?php echo htmlspecialchars($show['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-hint">You can create a change order without a show if needed</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Your Name</label>
                        <input type="text" class="form-control" name="created_by" placeholder="Enter your name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="create_change_order" class="btn btn-primary">Create Change Order</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if (empty($changeOrders)): ?>
    <div class="empty">
        <div class="empty-icon">
            <i class="ti ti-file-text icon"></i>
        </div>
        <p class="empty-title">No change orders yet</p>
        <p class="empty-subtitle text-muted">Click "Create New Change Order" to get started</p>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body">
            <div class="accordion" id="changeOrdersAccordion">
                <?php 
                $accordionIndex = 0;
                
                // Show change orders grouped by show
                foreach ($changeOrdersByShow as $showId => $showData): 
                    $accordionIndex++;
                ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading-show-<?php echo $showId; ?>">
                            <button class="accordion-button <?php echo $accordionIndex > 1 ? 'collapsed' : ''; ?>" type="button" 
                                    data-bs-toggle="collapse" data-bs-target="#collapse-show-<?php echo $showId; ?>" 
                                    aria-expanded="<?php echo $accordionIndex === 1 ? 'true' : 'false'; ?>">
                                <strong><?php echo htmlspecialchars($showData['show_name']); ?></strong>
                                <span class="badge bg-primary ms-2"><?php echo count($showData['change_orders']); ?> change order<?php echo count($showData['change_orders']) !== 1 ? 's' : ''; ?></span>
                            </button>
                        </h2>
                        <div id="collapse-show-<?php echo $showId; ?>" 
                             class="accordion-collapse collapse <?php echo $accordionIndex === 1 ? 'show' : ''; ?>" 
                             data-bs-parent="#changeOrdersAccordion">
                            <div class="accordion-body">
                                <div class="row">
                                    <?php foreach ($showData['change_orders'] as $co): ?>
                                        <div class="col-md-6 col-lg-4 mb-3">
                                            <div class="card">
                                                <div class="card-header">
                                                    <h3 class="card-title">Change Order</h3>
                                                    <div class="card-actions">
                                                        <span class="badge bg-<?php echo $co['status'] === 'draft' ? 'secondary' : 'success'; ?>">
                                                            <?php echo ucfirst($co['status']); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="card-body">
                                                    <div class="mb-2">
                                                        <small class="text-muted">Created by:</small>
                                                        <div><?php echo htmlspecialchars($co['created_by'] ?? 'N/A'); ?></div>
                                                    </div>
                                                    <div class="mb-2">
                                                        <small class="text-muted">Created:</small>
                                                        <div><?php echo date('m/d/Y', strtotime($co['created_at'])); ?></div>
                                                    </div>
                                                </div>
                                                <div class="card-footer">
                                                    <div class="d-flex gap-2">
                                                        <?php if ($co['status'] === 'draft'): ?>
                                                            <a href="change_order_edit.php?id=<?php echo $co['id']; ?>" class="btn btn-sm btn-primary">
                                                                <i class="ti ti-edit"></i> Edit
                                                            </a>
                                                        <?php else: ?>
                                                            <a href="change_order_view.php?id=<?php echo $co['id']; ?>" class="btn btn-sm btn-info">
                                                                <i class="ti ti-eye"></i> View
                                                            </a>
                                                        <?php endif; ?>
                                                        <form method="POST" class="d-inline ms-auto" onsubmit="return confirm('Are you sure you want to delete this change order? This cannot be undone.');">
                                                            <input type="hidden" name="delete_id" value="<?php echo $co['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                                <i class="ti ti-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <!-- Change orders not attached to any show -->
                <?php if (!empty($changeOrdersNoShow)): 
                    $accordionIndex++;
                ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading-no-show">
                            <button class="accordion-button <?php echo $accordionIndex > 1 ? 'collapsed' : ''; ?>" type="button" 
                                    data-bs-toggle="collapse" data-bs-target="#collapse-no-show" 
                                    aria-expanded="<?php echo $accordionIndex === 1 ? 'true' : 'false'; ?>">
                                <strong>Not Attached to Show</strong>
                                <span class="badge bg-secondary ms-2"><?php echo count($changeOrdersNoShow); ?> change order<?php echo count($changeOrdersNoShow) !== 1 ? 's' : ''; ?></span>
                            </button>
                        </h2>
                        <div id="collapse-no-show" 
                             class="accordion-collapse collapse <?php echo $accordionIndex === 1 ? 'show' : ''; ?>" 
                             data-bs-parent="#changeOrdersAccordion">
                            <div class="accordion-body">
                                <div class="row">
                                    <?php foreach ($changeOrdersNoShow as $co): ?>
                                        <div class="col-md-6 col-lg-4 mb-3">
                                            <div class="card">
                                                <div class="card-header">
                                                    <h3 class="card-title">Change Order</h3>
                                                    <div class="card-actions">
                                                        <span class="badge bg-<?php echo $co['status'] === 'draft' ? 'secondary' : 'success'; ?>">
                                                            <?php echo ucfirst($co['status']); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="card-body">
                                                    <div class="mb-2">
                                                        <small class="text-muted">Created by:</small>
                                                        <div><?php echo htmlspecialchars($co['created_by'] ?? 'N/A'); ?></div>
                                                    </div>
                                                    <div class="mb-2">
                                                        <small class="text-muted">Created:</small>
                                                        <div><?php echo date('m/d/Y', strtotime($co['created_at'])); ?></div>
                                                    </div>
                                                </div>
                                                <div class="card-footer">
                                                    <div class="d-flex gap-2">
                                                        <?php if ($co['status'] === 'draft'): ?>
                                                            <a href="change_order_edit.php?id=<?php echo $co['id']; ?>" class="btn btn-sm btn-primary">
                                                                <i class="ti ti-edit"></i> Edit
                                                            </a>
                                                        <?php else: ?>
                                                            <a href="change_order_view.php?id=<?php echo $co['id']; ?>" class="btn btn-sm btn-info">
                                                                <i class="ti ti-eye"></i> View
                                                            </a>
                                                        <?php endif; ?>
                                                        <form method="POST" class="d-inline ms-auto" onsubmit="return confirm('Are you sure you want to delete this change order? This cannot be undone.');">
                                                            <input type="hidden" name="delete_id" value="<?php echo $co['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                                <i class="ti ti-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
