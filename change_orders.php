<?php
$pageTitle = 'Change Orders';
require_once 'includes/header.php';

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    try {
        $deleteId = (int)$_POST['delete_id'];
        getDB()->query("DELETE FROM change_orders WHERE id = ?", [$deleteId]);
        setAlert('Change order deleted successfully');
        redirect('index.php');
    } catch (Exception $e) {
        setAlert('Error: ' . $e->getMessage(), 'danger');
    }
}

// Handle create change order request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_change_order'])) {
    try {
        $showId = !empty($_POST['show_id']) ? (int)$_POST['show_id'] : null;
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

// Get all change orders
$changeOrders = getDB()->fetchAll("SELECT co.*, s.name as show_name 
    FROM change_orders co 
    LEFT JOIN shows s ON co.show_id = s.id 
    ORDER BY co.created_at DESC");

// Get all shows for the dropdown
$shows = getDB()->fetchAll("SELECT id, name FROM shows ORDER BY name ASC");
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

<div class="row">
    <?php foreach ($changeOrders as $co): ?>
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><?php echo htmlspecialchars($co['show_name']); ?></h3>
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
                        <a href="change_order_edit.php?id=<?php echo $co['id']; ?>" class="btn btn-sm btn-primary">
                            <i class="ti ti-edit"></i> View/Edit
                        </a>
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
    
    <?php if (empty($changeOrders)): ?>
        <div class="col-12">
            <div class="empty">
                <p class="empty-title">No change orders yet</p>
                <p class="empty-subtitle text-muted">Click "Create New Change Order" to get started</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
