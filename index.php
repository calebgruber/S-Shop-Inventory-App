<?php
$pageTitle = 'Dashboard';
require_once 'includes/header.php';

$stats = getDashboardStats();
$activeShows = getActiveShows();
$currentUser = getCurrentUser();

// Get pending pullsheets (finalized but not picked)
$pendingPullsheets = getDB()->fetchAll(
    "SELECT p.*, s.name as show_name 
     FROM pullsheets p 
     LEFT JOIN shows s ON p.show_id = s.id 
     WHERE p.status = 'finalized'
     ORDER BY p.created_at 
     LIMIT 5"
);

// Get pending change orders (finalized but not processed)
$pendingChangeOrders = getDB()->fetchAll(
    "SELECT co.*, s.name as show_name 
     FROM change_orders co 
     LEFT JOIN shows s ON co.show_id = s.id 
     WHERE co.status = 'finalized'
     ORDER BY co.created_at 
     LIMIT 5"
);

// Get change orders with items to add (need to be picked)
$changeOrdersToPick = getDB()->fetchAll(
    "SELECT co.*, s.name as show_name, COUNT(coi.id) as items_to_add
     FROM change_orders co 
     LEFT JOIN shows s ON co.show_id = s.id 
     LEFT JOIN change_order_items coi ON co.id = coi.change_order_id 
     WHERE co.status = 'finalized' AND coi.type = 'add'
     GROUP BY co.id
     HAVING items_to_add > 0
     ORDER BY co.created_at 
     LIMIT 5"
);

// Get change orders with items to remove (need to be returned)
$changeOrdersToReturn = getDB()->fetchAll(
    "SELECT co.*, s.name as show_name, COUNT(coi.id) as items_to_remove
     FROM change_orders co 
     LEFT JOIN shows s ON co.show_id = s.id 
     LEFT JOIN change_order_items coi ON co.id = coi.change_order_id 
     WHERE co.status = 'finalized' AND coi.type = 'remove'
     GROUP BY co.id
     HAVING items_to_remove > 0
     ORDER BY co.created_at 
     LIMIT 5"
);
?>

<!-- Quick Lookup Modal -->
<div class="modal fade" id="quickLookupModal" tabindex="-1" aria-labelledby="quickLookupModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="quickLookupModalLabel">Quick Item Lookup</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Search or Scan Item</label>
                    <input type="text" class="form-control" id="quickLookupInput" placeholder="Enter item name or scan barcode" autofocus>
                </div>
                <div id="quickLookupResult"></div>
            </div>
        </div>
    </div>
</div>

<!-- Color Key Card -->
<div class="card mb-4">
    <div class="card-header">
        <h3 class="card-title">Cable Color Key</h3>
    </div>
    <div class="card-body">
        <div class="row g-2">
            <div class="col-6 col-md-4 col-lg-2">
                <div class="d-flex align-items-center">
                    <div class="me-2" style="width: 24px; height: 24px; background-color: #dc3545; border-radius: 2px;"></div>
                    <div>
                        <strong>Red</strong><br>
                        <small class="text-muted">5'</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="d-flex align-items-center">
                    <div class="me-2" style="width: 24px; height: 24px; background-color: #6c757d; border-radius: 2px;"></div>
                    <div>
                        <strong>Grey</strong><br>
                        <small class="text-muted">10'</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="d-flex align-items-center">
                    <div class="me-2" style="width: 24px; height: 24px; background-color: #6f42c1; border-radius: 2px;"></div>
                    <div>
                        <strong>Purple</strong><br>
                        <small class="text-muted">15'</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="d-flex align-items-center">
                    <div class="me-2" style="width: 24px; height: 24px; background-color: #ffc107; border-radius: 2px;"></div>
                    <div>
                        <strong>Yellow</strong><br>
                        <small class="text-muted">25'</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="d-flex align-items-center">
                    <div class="me-2" style="width: 24px; height: 24px; background-color: #0dcaf0; border-radius: 2px;"></div>
                    <div>
                        <strong>Blue</strong><br>
                        <small class="text-muted">50'</small>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
                <div class="d-flex align-items-center">
                    <div class="me-2" style="width: 24px; height: 24px; background-color: #ffffff; border: 1px solid #dee2e6; border-radius: 2px;"></div>
                    <div>
                        <strong>White</strong><br>
                        <small class="text-muted">100'</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row row-deck row-cards mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Active Shows</div>
                </div>
                <div class="h1 mb-0"><?php echo $stats['active_shows']; ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Pending Picks</div>
                </div>
                <div class="h1 mb-0"><?php echo $stats['pending_picks']; ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Pending Returns</div>
                </div>
                <div class="h1 mb-0"><?php echo $stats['pending_returns']; ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Total Items</div>
                </div>
                <div class="h1 mb-0"><?php echo getDB()->fetchOne("SELECT COUNT(*) as count FROM items")['count']; ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <h3 class="mb-3">Quick Actions</h3>
    </div>
    
    <!-- Quick Lookup -->
    <div class="col-md-6 col-lg-3 mb-3">
        <button type="button" class="btn btn-cyan w-100 quick-action-btn d-flex flex-column justify-content-center align-items-center" data-bs-toggle="modal" data-bs-target="#quickLookupModal">
            <i class="ti ti-search icon mb-2" style="font-size: 2rem;"></i>
            <span>Quick Lookup</span>
        </button>
    </div>
    
    <?php if (hasPermission('inventory')): ?>
    <!-- Inventory -->
    <div class="col-md-6 col-lg-3 mb-3">
        <a href="items.php" class="btn btn-secondary w-100 quick-action-btn d-flex flex-column justify-content-center align-items-center">
            <i class="ti ti-package icon mb-2" style="font-size: 2rem;"></i>
            <span>Inventory</span>
        </a>
    </div>
    <?php endif; ?>
    
    <?php if (hasPermission('reports')): ?>
    <!-- Show Reports -->
    <div class="col-md-6 col-lg-3 mb-3">
        <a href="reports.php" class="btn btn-info w-100 quick-action-btn d-flex flex-column justify-content-center align-items-center">
            <i class="ti ti-report icon mb-2" style="font-size: 2rem;"></i>
            <span>Show Reports</span>
        </a>
    </div>
    <?php endif; ?>
    
    <?php if (hasPermission('pullsheets')): ?>
    <!-- Create Pullsheet -->
    <div class="col-md-6 col-lg-3 mb-3">
        <a href="pullsheet_create.php" class="btn btn-success w-100 quick-action-btn d-flex flex-column justify-content-center align-items-center">
            <i class="ti ti-file-plus icon mb-2" style="font-size: 2rem;"></i>
            <span>Create Pullsheet</span>
        </a>
    </div>
    <?php endif; ?>
    
    <?php if (hasPermission('pick_mode')): ?>
    <!-- Pick Mode -->
    <div class="col-md-6 col-lg-3 mb-3">
        <a href="pick_mode.php" class="btn btn-primary w-100 quick-action-btn d-flex flex-column justify-content-center align-items-center">
            <i class="ti ti-scan icon mb-2" style="font-size: 2rem;"></i>
            <span>Pick Mode</span>
        </a>
    </div>
    <?php endif; ?>
    
    <?php if (hasPermission('return_mode')): ?>
    <!-- Return Mode -->
    <div class="col-md-6 col-lg-3 mb-3">
        <a href="return_mode.php" class="btn btn-warning w-100 quick-action-btn d-flex flex-column justify-content-center align-items-center">
            <i class="ti ti-arrow-back icon mb-2" style="font-size: 2rem;"></i>
            <span>Return Mode</span>
        </a>
    </div>
    <?php endif; ?>
    
    <?php if (hasPermission('shows')): ?>
    <!-- Shows -->
    <div class="col-md-6 col-lg-3 mb-3">
        <a href="shows.php" class="btn btn-purple w-100 quick-action-btn d-flex flex-column justify-content-center align-items-center">
            <i class="ti ti-theater icon mb-2" style="font-size: 2rem;"></i>
            <span>Shows</span>
        </a>
    </div>
    <?php endif; ?>
    
    <?php if (hasPermission('change_orders')): ?>
    <!-- Change Orders -->
    <div class="col-md-6 col-lg-3 mb-3">
        <a href="change_orders.php" class="btn btn-orange w-100 quick-action-btn d-flex flex-column justify-content-center align-items-center">
            <i class="ti ti-exchange icon mb-2" style="font-size: 2rem;"></i>
            <span>Change Orders</span>
        </a>
    </div>
    <?php endif; ?>
</div>

<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Pending Picks</h3>
            </div>
            <div class="card-body">
                <?php if (empty($pendingPullsheets) && empty($changeOrdersToPick)): ?>
                    <p class="text-muted">No pending picks</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($pendingPullsheets as $pullsheet): ?>
                            <div class="list-group-item">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <strong><?php echo htmlspecialchars($pullsheet['show_name']); ?></strong>
                                        <div class="text-muted small">Pullsheet - Created <?php echo date('m/d/Y', strtotime($pullsheet['created_at'])); ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <span class="badge bg-warning">To Pick</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php foreach ($changeOrdersToPick as $co): ?>
                            <div class="list-group-item">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <strong><?php echo htmlspecialchars($co['show_name']); ?></strong>
                                        <div class="text-muted small">Change Order - <?php echo $co['items_to_add']; ?> items to add</div>
                                    </div>
                                    <div class="col-auto">
                                        <span class="badge bg-info">CO Pick</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Pending Returns</h3>
            </div>
            <div class="card-body">
                <?php if (empty($changeOrdersToReturn)): ?>
                    <p class="text-muted">No pending returns</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($changeOrdersToReturn as $co): ?>
                            <div class="list-group-item">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <strong><?php echo htmlspecialchars($co['show_name']); ?></strong>
                                        <div class="text-muted small">Change Order - <?php echo $co['items_to_remove']; ?> items to return</div>
                                    </div>
                                    <div class="col-auto">
                                        <span class="badge bg-danger">CO Return</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12 mb-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Active Shows</h3>
            </div>
            <div class="card-body">
                <?php if (empty($activeShows)): ?>
                    <p class="text-muted">No active shows</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach (array_slice($activeShows, 0, 5) as $show): ?>
                            <div class="list-group-item">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <strong><?php echo htmlspecialchars($show['name']); ?></strong>
                                        <div class="text-muted small">
                                            <?php echo htmlspecialchars($show['theatre_space_name'] ?? 'No space assigned'); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <a href="show_edit.php?id=<?php echo $show['id']; ?>" class="btn btn-sm btn-primary">
                                            View
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Quick Lookup functionality
document.getElementById('quickLookupInput')?.addEventListener('input', function(e) {
    const searchTerm = e.target.value.trim();
    const resultDiv = document.getElementById('quickLookupResult');
    
    if (searchTerm.length < 2) {
        resultDiv.innerHTML = '';
        return;
    }
    
    // Debounce the search
    clearTimeout(window.quickLookupTimeout);
    window.quickLookupTimeout = setTimeout(async () => {
        try {
            const response = await fetch('api_quick_lookup.php?q=' + encodeURIComponent(searchTerm));
            const data = await response.json();
            
            if (data.success && data.item) {
                const item = data.item;
                let photoHtml = '';
                if (item.photo_path) {
                    photoHtml = `<img src="uploads/items/${item.photo_path}" class="img-fluid mb-3" style="max-height: 200px; border-radius: 4px;" alt="${item.name}">`;
                }
                
                let locationHtml = '';
                if (item.location) {
                    locationHtml = `<p><strong>Location:</strong> ${item.location}</p>`;
                }
                
                resultDiv.innerHTML = `
                    <div class="card">
                        <div class="card-body">
                            ${photoHtml}
                            <h3>${item.name}</h3>
                            <p class="text-muted">${item.description || 'No description'}</p>
                            ${locationHtml}
                            <p><strong>Barcode:</strong> ${item.barcode}</p>
                            <p><strong>Category:</strong> ${item.category_name || 'N/A'}</p>
                            <p><strong>In Stock:</strong> <span class="badge bg-${item.in_stock_quantity > 0 ? 'success' : 'danger'}">${item.in_stock_quantity}</span> / ${item.total_quantity}</p>
                            <a href="item_edit.php?id=${item.id}" class="btn btn-primary">View/Edit Item</a>
                        </div>
                    </div>
                `;
            } else {
                resultDiv.innerHTML = '<div class="alert alert-warning">No item found</div>';
            }
        } catch (error) {
            resultDiv.innerHTML = '<div class="alert alert-danger">Error searching for item</div>';
        }
    }, 300);
});

// Reset modal when closed
document.getElementById('quickLookupModal')?.addEventListener('hidden.bs.modal', function() {
    document.getElementById('quickLookupInput').value = '';
    document.getElementById('quickLookupResult').innerHTML = '';
});

// Focus input when modal opens
document.getElementById('quickLookupModal')?.addEventListener('shown.bs.modal', function() {
    document.getElementById('quickLookupInput').focus();
});
</script>

<?php require_once 'includes/footer.php'; ?>
