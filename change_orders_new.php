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

        </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="create_change_order" class="btn btn-primary">Create Change Order</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Search and Filter Bar -->
<div class="card mb-3">
    <div class="card-body">
        <div class="row g-2">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text"><i class="ti ti-search"></i></span>
                    <input type="text" class="form-control" id="searchInput" placeholder="Search by barcode or creator...">
                </div>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="statusFilter">
                    <option value="">All Statuses</option>
                    <option value="draft">Draft</option>
                    <option value="finalized">Finalized</option>
                    <option value="picked">Picked</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="showFilter">
                    <option value="">All Shows</option>
                    <option value="_no_show">No Show</option>
                    <?php foreach ($shows as $show): ?>
                        <option value="<?php echo $show['id']; ?>"><?php echo htmlspecialchars($show['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-secondary w-100" onclick="clearFilters()">
                    <i class="ti ti-x"></i> Clear
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Desktop Table View -->
<div class="card d-none d-md-block" id="changeOrdersDesktopCard">
    <div class="table-responsive">
        <table class="table table-vcenter card-table table-hover">
            <thead>
                <tr>
                    <th>Barcode</th>
                    <th>Show</th>
                    <th>Status</th>
                    <th>Created By</th>
                    <th>Created Date</th>
                    <th class="w-1">Actions</th>
                </tr>
            </thead>
            <tbody id="changeOrdersTableBody">
                <?php foreach ($changeOrders as $co): ?>
                    <tr class="change-order-row" 
                        data-barcode="<?php echo htmlspecialchars($co['barcode'] ?? ''); ?>"
                        data-creator="<?php echo htmlspecialchars($co['created_by'] ?? ''); ?>"
                        data-status="<?php echo htmlspecialchars($co['status'] ?? ''); ?>"
                        data-show="<?php echo $co['show_id'] ? $co['show_id'] : '_no_show'; ?>">
                        <td>
                            <i class="ti ti-barcode text-muted me-1"></i>
                            <strong><?php echo htmlspecialchars($co['barcode'] ?? 'N/A'); ?></strong>
                        </td>
                        <td>
                            <?php if ($co['show_name']): ?>
                                <span class="badge bg-blue-lt"><?php echo htmlspecialchars($co['show_name']); ?></span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php $badge = getStatusBadge($co); ?>
                            <span class="badge <?php echo $badge['class']; ?>">
                                <?php echo $badge['text']; ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($co['created_by'] ?? 'N/A'); ?></td>
                        <td><?php echo date('m/d/Y', strtotime($co['created_at'])); ?></td>
                        <td>
                            <div class="btn-group">
                                <?php if ($co['status'] === 'draft'): ?>
                                    <a href="change_order_edit?id=<?php echo $co['id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                        <i class="ti ti-edit"></i>
                                    </a>
                                <?php else: ?>
                                    <a href="change_order_view?id=<?php echo $co['id']; ?>" class="btn btn-sm btn-info" title="View">
                                        <i class="ti ti-eye"></i>
                                    </a>
                                <?php endif; ?>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this change order? This cannot be undone.');">
                                    <input type="hidden" name="delete_id" value="<?php echo $co['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Mobile Card View -->
<div class="d-md-none" id="changeOrdersMobileCards">
    <?php foreach ($changeOrders as $co): ?>
        <div class="card mb-3 change-order-card" 
             data-barcode="<?php echo htmlspecialchars($co['barcode'] ?? ''); ?>"
             data-creator="<?php echo htmlspecialchars($co['created_by'] ?? ''); ?>"
             data-status="<?php echo htmlspecialchars($co['status'] ?? ''); ?>"
             data-show="<?php echo $co['show_id'] ? $co['show_id'] : '_no_show'; ?>">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="text-muted small">Barcode</div>
                        <strong><?php echo htmlspecialchars($co['barcode'] ?? 'N/A'); ?></strong>
                    </div>
                    <div>
                        <?php $badge = getStatusBadge($co); ?>
                        <span class="badge <?php echo $badge['class']; ?>">
                            <?php echo $badge['text']; ?>
                        </span>
                    </div>
                </div>
                <?php if ($co['show_name']): ?>
                    <div class="mb-2">
                        <span class="badge bg-blue-lt"><?php echo htmlspecialchars($co['show_name']); ?></span>
                    </div>
                <?php endif; ?>
                <div class="text-muted small mb-1">
                    Created by: <?php echo htmlspecialchars($co['created_by'] ?? 'N/A'); ?>
                </div>
                <div class="text-muted small mb-3">
                    Created: <?php echo date('m/d/Y', strtotime($co['created_at'])); ?>
                </div>
                <div class="d-flex gap-2">
                    <?php if ($co['status'] === 'draft'): ?>
                        <a href="change_order_edit?id=<?php echo $co['id']; ?>" class="btn btn-sm btn-primary flex-fill">
                            <i class="ti ti-edit"></i> Edit
                        </a>
                    <?php else: ?>
                        <a href="change_order_view?id=<?php echo $co['id']; ?>" class="btn btn-sm btn-info flex-fill">
                            <i class="ti ti-eye"></i> View
                        </a>
                    <?php endif; ?>
                    <form method="POST" class="flex-fill" onsubmit="return confirm('Are you sure you want to delete this change order? This cannot be undone.');">
                        <input type="hidden" name="delete_id" value="<?php echo $co['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-danger w-100">
                            <i class="ti ti-trash"></i> Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- No Results Message -->
<div id="noResults" class="empty" style="display: none;">
    <div class="empty-icon">
        <i class="ti ti-search icon"></i>
    </div>
    <p class="empty-title">No change orders found</p>
    <p class="empty-subtitle text-muted">Try adjusting your filters</p>
</div>

<script>
function filterChangeOrders() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const statusFilter = document.getElementById('statusFilter').value.toLowerCase();
    const showFilter = document.getElementById('showFilter').value;
    
    // Desktop table rows
    const desktopRows = document.querySelectorAll('.change-order-row');
    // Mobile cards
    const mobileCards = document.querySelectorAll('.change-order-card');
    
    let visibleCount = 0;
    
    // Filter desktop rows
    desktopRows.forEach(row => {
        const barcode = (row.dataset.barcode || '').toLowerCase();
        const creator = (row.dataset.creator || '').toLowerCase();
        const status = (row.dataset.status || '').toLowerCase();
        const show = row.dataset.show || '';
        
        const matchesSearch = barcode.includes(searchTerm) || creator.includes(searchTerm);
        const matchesStatus = !statusFilter || status === statusFilter;
        const matchesShow = !showFilter || show === showFilter;
        
        if (matchesSearch && matchesStatus && matchesShow) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    // Filter mobile cards
    mobileCards.forEach(card => {
        const barcode = (card.dataset.barcode || '').toLowerCase();
        const creator = (card.dataset.creator || '').toLowerCase();
        const status = (card.dataset.status || '').toLowerCase();
        const show = card.dataset.show || '';
        
        const matchesSearch = barcode.includes(searchTerm) || creator.includes(searchTerm);
        const matchesStatus = !statusFilter || status === statusFilter;
        const matchesShow = !showFilter || show === showFilter;
        
        if (matchesSearch && matchesStatus && matchesShow) {
            card.style.display = '';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });
    
    // Show/hide no results message
    const noResults = document.getElementById('noResults');
    const desktopCard = document.getElementById('changeOrdersDesktopCard');
    const mobileCards = document.getElementById('changeOrdersMobileCards');
    
    if (visibleCount === 0) {
        if (noResults) noResults.style.display = 'block';
        if (desktopCard) desktopCard.style.display = 'none';
        if (mobileCards) mobileCards.style.display = 'none';
    } else {
        if (noResults) noResults.style.display = 'none';
        if (desktopCard) desktopCard.style.display = 'block';
        if (mobileCards) mobileCards.style.display = 'block';
    }
}

function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('statusFilter').value = '';
    document.getElementById('showFilter').value = '';
    filterChangeOrders();
}

// Attach event listeners
document.getElementById('searchInput').addEventListener('input', filterChangeOrders);
document.getElementById('statusFilter').addEventListener('change', filterChangeOrders);
document.getElementById('showFilter').addEventListener('change', filterChangeOrders);
</script>

<?php if (empty($changeOrders)): ?>
    <div class="empty">
        <div class="empty-icon">
            <i class="ti ti-file-text icon"></i>
        </div>
        <p class="empty-title">No change orders yet</p>
        <p class="empty-subtitle text-muted">Click "Create New Change Order" to get started</p>
    </div>
<?php else: ?>
