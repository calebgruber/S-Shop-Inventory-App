<?php
$pageTitle = 'Change Orders';
require_once '../includes/header.php';
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
        
        // Return ALL items to stock regardless of status (draft, pending, finalized, picked, etc.)
        $items = getChangeOrderItems($deleteId);
        foreach ($items as $item) {
            // Return items based on type
            if ($item['type'] === 'add') {
                // Items that were added (removed from stock) - return them
                $quantityToReturn = abs($item['quantity_change'] ?? $item['quantity'] ?? 0);
                if ($quantityToReturn > 0) {
                    $db->query(
                        "UPDATE items SET in_stock_quantity = in_stock_quantity + ? WHERE id = ?",
                        [$quantityToReturn, $item['item_id']]
                    );
                    logMessage("Returned {$quantityToReturn} of item ID {$item['item_id']} to stock from change order ID $deleteId (status: {$changeOrder['status']})", 'INFO');
                }
            }
            // Items with type='remove' were being returned, so no stock adjustment needed
        }
        
        // Note: pending_picks and pending_returns tables removed - not needed
        
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
        redirect('/change-orders/edit?id=' . $changeOrderId);
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
            "SELECT co.*, s.name as show_name, p.barcode as pullsheet_barcode
             FROM change_orders co 
             LEFT JOIN shows s ON co.show_id = s.id 
             LEFT JOIN pullsheets p ON co.pullsheet_id = p.id
             WHERE co.show_id IN ($placeholders)
             ORDER BY co.created_at DESC",
            $assignedShowIds
        );
    }
    
    // Filter shows for dropdown
    $shows = $assignedShows;
} else {
    // Admins see all change orders and shows
    $changeOrders = getDB()->fetchAll("SELECT co.*, s.name as show_name, p.barcode as pullsheet_barcode
        FROM change_orders co 
        LEFT JOIN shows s ON co.show_id = s.id 
        LEFT JOIN pullsheets p ON co.pullsheet_id = p.id
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
    <!-- Search and Filter Bar -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-4">
                    <div class="input-icon">
                        <span class="input-icon-addon">
                            <i class="ti ti-search"></i>
                        </span>
                        <input type="text" id="searchInput" class="form-control" placeholder="Search by barcode or creator...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select id="statusFilter" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="draft">Draft</option>
                        <option value="finalized">Finalized</option>
                        <option value="picked">Picked</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select id="showFilter" class="form-select">
                        <option value="">All Shows</option>
                        <?php foreach ($changeOrdersByShow as $showId => $showData): ?>
                            <option value="<?php echo $showId; ?>"><?php echo htmlspecialchars($showData['show_name']); ?></option>
                        <?php endforeach; ?>
                        <?php if (!empty($changeOrdersNoShow)): ?>
                            <option value="no-show">Not Attached to Show</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="button" id="clearFilters" class="btn btn-secondary w-100">
                        <i class="ti ti-x"></i> Clear
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Desktop Table View -->
    <div class="card d-none d-md-block" id="desktopTableCard">
        <div class="table-responsive">
            <table class="table table-vcenter table-hover card-table">
                <thead>
                    <tr>
                        <th>Barcode</th>
                        <th>Show</th>
                        <th>Shop Order</th>
                        <th>Status</th>
                        <th>Created By</th>
                        <th>Created Date</th>
                        <th>Last Modified</th>
                        <th class="w-1">Actions</th>
                    </tr>
                </thead>
                <tbody id="changeOrdersTableBody">
                    <?php foreach ($changeOrders as $co): ?>
                        <tr class="change-order-row" 
                            data-barcode="<?php echo htmlspecialchars($co['barcode']); ?>"
                            data-creator="<?php echo htmlspecialchars($co['created_by'] ?? ''); ?>"
                            data-status="<?php echo htmlspecialchars($co['status']); ?>"
                            data-show-id="<?php echo $co['show_id'] ?: 'no-show'; ?>">
                            <td>
                                <span class="text-muted">
                                    <i class="ti ti-barcode"></i>
                                    <?php echo htmlspecialchars($co['barcode']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($co['show_name']): ?>
                                    <span class="badge bg-blue-lt"><?php echo htmlspecialchars($co['show_name']); ?></span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($co['pullsheet_barcode']): ?>
                                    <span class="badge bg-purple-lt">
                                        <i class="ti ti-clipboard-list"></i>
                                        <?php echo htmlspecialchars($co['pullsheet_barcode']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">None</span>
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
                                <span class="text-muted small" title="<?php echo date('M d, Y g:i A', strtotime($co['updated_at'])); ?>">
                                    <?php echo timeAgo($co['updated_at']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <?php if ($co['status'] === 'draft'): ?>
                                        <a href="/change-orders/edit?id=<?php echo $co['id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                            <i class="ti ti-edit"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="/change-orders/view?id=<?php echo $co['id']; ?>" class="btn btn-sm btn-info" title="View">
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
    <div class="d-md-none" id="mobileCardsContainer">
        <?php foreach ($changeOrders as $co): ?>
            <div class="card mb-3 change-order-card" 
                 data-barcode="<?php echo htmlspecialchars($co['barcode']); ?>"
                 data-creator="<?php echo htmlspecialchars($co['created_by'] ?? ''); ?>"
                 data-status="<?php echo htmlspecialchars($co['status']); ?>"
                 data-show-id="<?php echo $co['show_id'] ?: 'no-show'; ?>">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <div class="text-muted small">Barcode</div>
                            <strong><i class="ti ti-barcode"></i> <?php echo htmlspecialchars($co['barcode']); ?></strong>
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
                            <a href="/change-orders/edit?id=<?php echo $co['id']; ?>" class="btn btn-sm btn-primary flex-fill">
                                <i class="ti ti-edit"></i> Edit
                            </a>
                        <?php else: ?>
                            <a href="/change-orders/view?id=<?php echo $co['id']; ?>" class="btn btn-sm btn-info flex-fill">
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
        const searchInput = document.getElementById('searchInput');
        const statusFilter = document.getElementById('statusFilter');
        const showFilter = document.getElementById('showFilter');
        
        if (!searchInput || !statusFilter || !showFilter) return;
        
        const searchTerm = searchInput.value.toLowerCase();
        const statusValue = statusFilter.value.toLowerCase();
        const showValue = showFilter.value;
        
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
            const showId = row.dataset.showId || '';
            
            const matchesSearch = barcode.includes(searchTerm) || creator.includes(searchTerm);
            const matchesStatus = !statusValue || status === statusValue;
            const matchesShow = !showValue || showId === showValue;
            
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
            const showId = card.dataset.showId || '';
            
            const matchesSearch = barcode.includes(searchTerm) || creator.includes(searchTerm);
            const matchesStatus = !statusValue || status === statusValue;
            const matchesShow = !showValue || showId === showValue;
            
            if (matchesSearch && matchesStatus && matchesShow) {
                card.style.display = '';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });
        
        // Show/hide no results message
        const noResults = document.getElementById('noResults');
        const desktopCard = document.getElementById('desktopTableCard');
        const mobileCards = document.getElementById('mobileCardsContainer');
        
        if (visibleCount === 0) {
            if (noResults) noResults.style.display = 'block';
            if (desktopCard) desktopCard.style.display = 'none';
            if (mobileCards) mobileCards.style.display = 'none';
        } else {
            if (noResults) noResults.style.display = 'none';
            if (desktopCard) desktopCard.classList.remove('d-none');
            desktopCard.classList.add('d-md-block');
            if (mobileCards) mobileCards.classList.remove('d-none');
            mobileCards.classList.add('d-md-none');
        }
    }
    
    // Initialize event listeners when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        const statusFilter = document.getElementById('statusFilter');
        const showFilter = document.getElementById('showFilter');
        const clearButton = document.getElementById('clearFilters');
        
        if (searchInput) searchInput.addEventListener('input', filterChangeOrders);
        if (statusFilter) statusFilter.addEventListener('change', filterChangeOrders);
        if (showFilter) showFilter.addEventListener('change', filterChangeOrders);
        if (clearButton) {
            clearButton.addEventListener('click', function() {
                if (searchInput) searchInput.value = '';
                if (statusFilter) statusFilter.value = '';
                if (showFilter) showFilter.value = '';
                filterChangeOrders();
            });
        }
    });
    </script>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
