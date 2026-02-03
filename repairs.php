<?php
require_once 'includes/functions.php';

// Check permissions BEFORE including header
if (!hasPermission('repairs')) {
    setAlert('You do not have permission to access repairs', 'danger');
    redirect('index');
}

$pageTitle = 'Repairs';
require_once 'includes/header.php';

$db = getDB();
$currentUser = getCurrentUser();

// Handle repair operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $itemId = $_POST['item_id'] ?? 0;
        $quantity = (int)($_POST['quantity'] ?? 1);
        $description = trim($_POST['description'] ?? '');
        
        $item = getItemById($itemId);
        if (!$item) {
            setAlert('Item not found', 'danger');
        } elseif ($item['in_stock_quantity'] < $quantity) {
            setAlert('Not enough items in stock', 'danger');
        } else {
            // Generate repair ID
            $repairId = 'RPR-' . strtoupper(substr(uniqid(), -8));
            
            // Create repair
            $db->query(
                "INSERT INTO repairs (repair_id, item_id, quantity, category_id, subcategory_id, description, status, reported_by) 
                 VALUES (?, ?, ?, ?, ?, ?, 'pending', ?)",
                [$repairId, $itemId, $quantity, $item['category_id'], $item['subcategory_id'], $description, $currentUser['id']]
            );
            
            // Remove from stock
            $db->query(
                "UPDATE items SET in_stock_quantity = in_stock_quantity - ? WHERE id = ?",
                [$quantity, $itemId]
            );
            
            setAlert('Repair created successfully with ID: ' . $repairId, 'success');
            redirect();
        }
    } elseif ($action === 'update_status') {
        $repairId = $_POST['repair_id'] ?? 0;
        $status = $_POST['status'] ?? '';
        
        $db->query("UPDATE repairs SET status = ? WHERE id = ?", [$status, $repairId]);
        
        // If completed, return items to stock
        if ($status === 'completed') {
            $repair = $db->fetchOne("SELECT item_id, quantity FROM repairs WHERE id = ?", [$repairId]);
            $db->query(
                "UPDATE items SET in_stock_quantity = in_stock_quantity + ? WHERE id = ?",
                [$repair['quantity'], $repair['item_id']]
            );
            $db->query("UPDATE repairs SET completed_at = NOW() WHERE id = ?", [$repairId]);
        }
        
        setAlert('Repair status updated', 'success');
        redirect();
    } elseif ($action === 'delete') {
        $repairId = $_POST['repair_id'] ?? 0;
        
        // Return items to stock
        $repair = $db->fetchOne("SELECT item_id, quantity, status FROM repairs WHERE id = ?", [$repairId]);
        if ($repair && $repair['status'] !== 'completed') {
            $db->query(
                "UPDATE items SET in_stock_quantity = in_stock_quantity + ? WHERE id = ?",
                [$repair['quantity'], $repair['item_id']]
            );
        }
        
        $db->query("DELETE FROM repairs WHERE id = ?", [$repairId]);
        setAlert('Repair cancelled and items returned to stock', 'success');
        redirect();
    }
}

// Get filter parameters
$statusFilter = $_GET['status'] ?? 'all';
$categoryFilter = $_GET['category'] ?? 'all';

// Build query
$query = "SELECT r.*, i.name as item_name, i.barcode, c.name as category_name, 
          u.full_name as reported_by_name
          FROM repairs r
          LEFT JOIN items i ON r.item_id = i.id
          LEFT JOIN categories c ON r.category_id = c.id
          LEFT JOIN users u ON r.reported_by = u.id
          WHERE 1=1";

$params = [];

if ($statusFilter !== 'all') {
    $query .= " AND r.status = ?";
    $params[] = $statusFilter;
}

if ($categoryFilter !== 'all') {
    $query .= " AND r.category_id = ?";
    $params[] = $categoryFilter;
}

$query .= " ORDER BY r.created_at DESC";

$repairs = $db->fetchAll($query, $params);
$categories = getAllCategories();
$allItems = getAllItems();
?>

<div class="row mb-4">
    <div class="col-12">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createRepairModal">
            <i class="ti ti-plus icon"></i> Create New Repair
        </button>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Status</label>
                <select class="form-select" name="status" onchange="this.form.submit()">
                    <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                    <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="in_progress" <?php echo $statusFilter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                    <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Category</label>
                <select class="form-select" name="category" onchange="this.form.submit()">
                    <option value="all" <?php echo $categoryFilter === 'all' ? 'selected' : ''; ?>>All Categories</option>
                    <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['id']; ?>" <?php echo $categoryFilter == $category['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($category['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">&nbsp;</label>
                <a href="repairs" class="btn btn-secondary w-100">Clear Filters</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Repairs</h3>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Repair ID</th>
                    <th>Item</th>
                    <th>Category</th>
                    <th>Quantity</th>
                    <th>Status</th>
                    <th>Reported By</th>
                    <th>Created</th>
                    <th class="w-1">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($repairs)): ?>
                <tr>
                    <td colspan="8" class="text-center text-muted">No repairs found</td>
                </tr>
                <?php else: ?>
                <?php foreach ($repairs as $repair): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($repair['repair_id']); ?></strong></td>
                    <td><?php echo htmlspecialchars($repair['item_name']); ?></td>
                    <td><?php echo htmlspecialchars($repair['category_name'] ?? 'N/A'); ?></td>
                    <td><?php echo $repair['quantity']; ?></td>
                    <td>
                        <?php
                        $statusColors = [
                            'pending' => 'warning',
                            'in_progress' => 'info',
                            'completed' => 'success',
                            'cancelled' => 'danger'
                        ];
                        $color = $statusColors[$repair['status']] ?? 'secondary';
                        ?>
                        <span class="badge bg-<?php echo $color; ?>">
                            <?php echo ucwords(str_replace('_', ' ', $repair['status'])); ?>
                        </span>
                    </td>
                    <td><?php echo htmlspecialchars($repair['reported_by_name'] ?? 'Unknown'); ?></td>
                    <td><?php echo date('m/d/Y', strtotime($repair['created_at'])); ?></td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#viewRepairModal<?php echo $repair['id']; ?>">
                                <i class="ti ti-eye icon"></i>
                            </button>
                            <?php if ($repair['status'] !== 'completed' && $repair['status'] !== 'cancelled'): ?>
                            <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#updateStatusModal<?php echo $repair['id']; ?>">
                                <i class="ti ti-edit icon"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteRepairModal<?php echo $repair['id']; ?>">
                                <i class="ti ti-trash icon"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Create Repair Modal -->
<div class="modal fade" id="createRepairModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Repair</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label required">Item</label>
                        <select class="form-select" name="item_id" required>
                            <option value="">-- Select Item --</option>
                            <?php foreach ($allItems as $item): ?>
                            <option value="<?php echo $item['id']; ?>">
                                <?php echo htmlspecialchars($item['name']); ?> 
                                (In Stock: <?php echo $item['in_stock_quantity']; ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Quantity</label>
                        <input type="number" class="form-control" name="quantity" value="1" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description/Notes</label>
                        <textarea class="form-control" name="description" rows="3" placeholder="Describe the issue..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Repair</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modals for each repair -->
<?php foreach ($repairs as $repair): ?>
<!-- View Repair Modal -->
<div class="modal fade" id="viewRepairModal<?php echo $repair['id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Repair Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <dl class="row">
                    <dt class="col-sm-4">Repair ID:</dt>
                    <dd class="col-sm-8"><?php echo htmlspecialchars($repair['repair_id']); ?></dd>
                    
                    <dt class="col-sm-4">Item:</dt>
                    <dd class="col-sm-8"><?php echo htmlspecialchars($repair['item_name']); ?></dd>
                    
                    <dt class="col-sm-4">Barcode:</dt>
                    <dd class="col-sm-8"><?php echo htmlspecialchars($repair['barcode']); ?></dd>
                    
                    <dt class="col-sm-4">Category:</dt>
                    <dd class="col-sm-8"><?php echo htmlspecialchars($repair['category_name'] ?? 'N/A'); ?></dd>
                    
                    <dt class="col-sm-4">Quantity:</dt>
                    <dd class="col-sm-8"><?php echo $repair['quantity']; ?></dd>
                    
                    <dt class="col-sm-4">Status:</dt>
                    <dd class="col-sm-8">
                        <span class="badge bg-<?php echo $statusColors[$repair['status']] ?? 'secondary'; ?>">
                            <?php echo ucwords(str_replace('_', ' ', $repair['status'])); ?>
                        </span>
                    </dd>
                    
                    <dt class="col-sm-4">Reported By:</dt>
                    <dd class="col-sm-8"><?php echo htmlspecialchars($repair['reported_by_name'] ?? 'Unknown'); ?></dd>
                    
                    <dt class="col-sm-4">Created:</dt>
                    <dd class="col-sm-8"><?php echo date('m/d/Y H:i', strtotime($repair['created_at'])); ?></dd>
                    
                    <?php if ($repair['completed_at']): ?>
                    <dt class="col-sm-4">Completed:</dt>
                    <dd class="col-sm-8"><?php echo date('m/d/Y H:i', strtotime($repair['completed_at'])); ?></dd>
                    <?php endif; ?>
                    
                    <?php if ($repair['description']): ?>
                    <dt class="col-sm-4">Description:</dt>
                    <dd class="col-sm-8"><?php echo nl2br(htmlspecialchars($repair['description'])); ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<?php if ($repair['status'] !== 'completed' && $repair['status'] !== 'cancelled'): ?>
<div class="modal fade" id="updateStatusModal<?php echo $repair['id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Repair Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="repair_id" value="<?php echo $repair['id']; ?>">
                <div class="modal-body">
                    <p>Update status for <strong><?php echo htmlspecialchars($repair['repair_id']); ?></strong></p>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status" required>
                            <option value="pending" <?php echo $repair['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="in_progress" <?php echo $repair['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Repair Modal -->
<div class="modal fade" id="deleteRepairModal<?php echo $repair['id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cancel Repair</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="repair_id" value="<?php echo $repair['id']; ?>">
                <div class="modal-body">
                    <p>Are you sure you want to cancel repair <strong><?php echo htmlspecialchars($repair['repair_id']); ?></strong>?</p>
                    <p class="text-warning">Items will be returned to stock.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Cancel Repair</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
<?php endforeach; ?>

<?php require_once 'includes/footer.php'; ?>
