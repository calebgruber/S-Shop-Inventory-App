<?php
$pageTitle = 'Repairs';
require_once '../includes/header.php';
requirePermission('repairs');

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
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Repair</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="createRepairForm">
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="item_id" id="selectedItemId" required>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label required">Search Item</label>
                        <input type="text" class="form-control" id="repairItemSearch" placeholder="Search items by name, barcode, or category..." autocomplete="off">
                        <div class="mt-2" id="selectedItemDisplay" style="display: none;">
                            <div class="alert alert-info mb-0">
                                <strong>Selected:</strong> <span id="selectedItemName"></span>
                                <button type="button" class="btn-close float-end" onclick="clearSelectedItem()" aria-label="Clear"></button>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3" id="itemSearchResults" style="max-height: 300px; overflow-y: auto; display: none;">
                        <div class="list-group" id="repairItemsList">
                            <?php foreach ($allItems as $item): ?>
                            <div class="list-group-item list-group-item-action repair-item-option" style="cursor: pointer;" 
                                 data-item-id="<?php echo $item['id']; ?>"
                                 data-item-name="<?php echo htmlspecialchars($item['name']); ?>"
                                 data-item-stock="<?php echo $item['in_stock_quantity']; ?>"
                                 onclick="selectRepairItem(<?php echo $item['id']; ?>, '<?php echo addslashes($item['name']); ?>', <?php echo $item['in_stock_quantity']; ?>)">
                                <div class="d-flex w-100 align-items-center gap-2">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                        <small class="text-muted"><?php echo htmlspecialchars($item['category_name'] ?? 'Uncategorized'); ?> - <?php echo htmlspecialchars($item['barcode']); ?></small>
                                    </div>
                                    <div class="text-end me-2">
                                        <small class="<?php echo $item['in_stock_quantity'] > 0 ? 'text-success' : 'text-danger'; ?>">
                                            In Stock: <?php echo $item['in_stock_quantity']; ?>
                                        </small>
                                    </div>
                                    <?php if (!empty($item['photo_path']) && file_exists(__DIR__ . '/uploads/items/' . $item['photo_path'])): ?>
                                    <img src="uploads/items/<?php echo htmlspecialchars($item['photo_path']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Quantity</label>
                        <input type="number" class="form-control" name="quantity" id="repairQuantity" value="1" min="1" required>
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

<script>
// Repair item search functionality
let selectedRepairItemStock = 0;

document.getElementById('repairItemSearch')?.addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const resultsDiv = document.getElementById('itemSearchResults');
    const items = document.querySelectorAll('#repairItemsList .repair-item-option');
    
    if (searchTerm.length > 0) {
        resultsDiv.style.display = 'block';
        
        items.forEach(item => {
            const text = item.textContent.toLowerCase();
            if (text.includes(searchTerm)) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    } else {
        resultsDiv.style.display = 'none';
    }
});

// Focus search when modal opens
document.getElementById('createRepairModal')?.addEventListener('shown.bs.modal', function() {
    document.getElementById('repairItemSearch').focus();
});

// Reset form when modal closes
document.getElementById('createRepairModal')?.addEventListener('hidden.bs.modal', function() {
    clearSelectedItem();
    document.getElementById('repairItemSearch').value = '';
    document.getElementById('itemSearchResults').style.display = 'none';
});

function selectRepairItem(itemId, itemName, inStock) {
    // Set hidden field
    document.getElementById('selectedItemId').value = itemId;
    
    // Show selected item display
    document.getElementById('selectedItemName').textContent = itemName + ' (In Stock: ' + inStock + ')';
    document.getElementById('selectedItemDisplay').style.display = 'block';
    
    // Hide search results and clear search input
    document.getElementById('itemSearchResults').style.display = 'none';
    document.getElementById('repairItemSearch').value = '';
    
    // Store stock quantity for validation
    selectedRepairItemStock = inStock;
    
    // Set max quantity
    document.getElementById('repairQuantity').max = inStock;
}

function clearSelectedItem() {
    document.getElementById('selectedItemId').value = '';
    document.getElementById('selectedItemDisplay').style.display = 'none';
    document.getElementById('repairItemSearch').value = '';
    selectedRepairItemStock = 0;
    document.getElementById('repairQuantity').max = '';
}

// Form validation
document.getElementById('createRepairForm')?.addEventListener('submit', function(e) {
    const itemId = document.getElementById('selectedItemId').value;
    const quantity = parseInt(document.getElementById('repairQuantity').value);
    
    if (!itemId) {
        e.preventDefault();
        alert('Please select an item first');
        return false;
    }
    
    if (quantity > selectedRepairItemStock) {
        e.preventDefault();
        alert('Quantity exceeds available stock (' + selectedRepairItemStock + ')');
        return false;
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
