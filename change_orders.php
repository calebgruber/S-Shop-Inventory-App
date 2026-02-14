<?php
$pageTitle = 'Change Orders';
require_once 'includes/header.php';
requirePermission('change_orders');

$currentUser = getCurrentUser();
$isDesigner = $currentUser['role'] === 'designer';
$isProductionAudio = $currentUser['role'] === 'production_audio';

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
        
        // Check permission for designers and production audio
        if (($isDesigner || $isProductionAudio) && !canAccessShow($currentUser['id'], $changeOrder['show_id'])) {
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
                    // Use quantity_change field (or quantity if that's the actual column name)
                    $quantityToReturn = abs($item['quantity_change'] ?? $item['quantity'] ?? 0);
                    if ($quantityToReturn > 0) {
                        $db->query(
                            "UPDATE items SET in_stock_quantity = in_stock_quantity + ? WHERE id = ?",
                            [$quantityToReturn, $item['item_id']]
                        );
                        logMessage("Unreserved {$quantityToReturn} of item ID {$item['item_id']} from change order ID $deleteId", 'INFO');
                    }
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
        
        // Check permission for designers and production audio
        if (($isDesigner || $isProductionAudio) && $showId && !canAccessShow($currentUser['id'], $showId)) {
            throw new Exception('You do not have permission to create change order for this show');
        }
        
        $createdBy = $currentUser['name'] ?? 'Unknown'; // Use logged-in user's name with fallback
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

// Get change orders filtered by show access for designers and production audio
if ($isDesigner || $isProductionAudio) {
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
        <button type="button" class="btn btn-success ms-2" data-bs-toggle="modal" data-bs-target="#importCSVModal">
            <i class="ti ti-file-import"></i> Import from CSV
        </button>
    </div>
</div>

<!-- Import CSV Modal -->
<div class="modal fade" id="importCSVModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="import_csv.php" enctype="multipart/form-data">
                <input type="hidden" name="redirect_to" value="change_orders">
                <div class="modal-header">
                    <h5 class="modal-title">Import Orders from CSV</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <h4 class="alert-title"><i class="ti ti-info-circle"></i> CSV Import Instructions</h4>
                        <p>Upload a CSV file to import shop orders and/or change orders in bulk.</p>
                        <ul class="mb-2">
                            <li>Download the template from <a href="settings" target="_blank">Settings</a> page</li>
                            <li>Fill in order details (order type, show name, item barcodes, quantities)</li>
                            <li>Upload the completed CSV file here</li>
                        </ul>
                        <p class="mb-0"><strong>Note:</strong> All rows with the same order type and show name will be grouped into one order.</p>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label required">CSV File</label>
                        <input type="file" class="form-control" name="csv_file" accept=".csv" required>
                        <small class="form-hint">Select a CSV file following the template format</small>
                    </div>
                    
                    <div class="card bg-light">
                        <div class="card-body">
                            <h5>Expected CSV Format:</h5>
                            <table class="table table-sm table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th>Order Type</th>
                                        <th>Show Name</th>
                                        <th>Item Barcode</th>
                                        <th>Item Name</th>
                                        <th>Quantity</th>
                                        <th>Type</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>shop_order</td>
                                        <td>My Show</td>
                                        <td>PS-001</td>
                                        <td>Microphone</td>
                                        <td>5</td>
                                        <td>(blank)</td>
                                    </tr>
                                    <tr>
                                        <td>change_order</td>
                                        <td>My Show</td>
                                        <td>PS-002</td>
                                        <td>Cable</td>
                                        <td>3</td>
                                        <td>add</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="ti ti-upload"></i> Import CSV
                    </button>
                </div>
            </form>
        </div>
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
                                                            <a href="change_order_edit?id=<?php echo $co['id']; ?>" class="btn btn-sm btn-primary">
                                                                <i class="ti ti-edit"></i> Edit
                                                            </a>
                                                        <?php else: ?>
                                                            <a href="change_order_view?id=<?php echo $co['id']; ?>" class="btn btn-sm btn-info">
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
                                                            <a href="change_order_edit?id=<?php echo $co['id']; ?>" class="btn btn-sm btn-primary">
                                                                <i class="ti ti-edit"></i> Edit
                                                            </a>
                                                        <?php else: ?>
                                                            <a href="change_order_view?id=<?php echo $co['id']; ?>" class="btn btn-sm btn-info">
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
