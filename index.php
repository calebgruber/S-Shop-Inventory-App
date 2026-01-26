<?php
$pageTitle = 'Dashboard';
require_once 'includes/header.php';

$stats = getDashboardStats();
$activeShows = getActiveShows();

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
     WHERE co.status = 'finalized' AND coi.action = 'add'
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
     WHERE co.status = 'finalized' AND coi.action = 'remove'
     GROUP BY co.id
     HAVING items_to_remove > 0
     ORDER BY co.created_at 
     LIMIT 5"
);
?>

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
    <div class="col-md-6 col-lg-4 mb-3">
        <a href="pick_mode.php" class="btn btn-primary w-100 quick-action-btn d-flex flex-column justify-content-center align-items-center">
            <i class="ti ti-scan icon mb-2" style="font-size: 2rem;"></i>
            <span>Pick Mode</span>
        </a>
    </div>
    <div class="col-md-6 col-lg-4 mb-3">
        <a href="return_mode.php" class="btn btn-success w-100 quick-action-btn d-flex flex-column justify-content-center align-items-center">
            <i class="ti ti-arrow-back icon mb-2" style="font-size: 2rem;"></i>
            <span>Return Mode</span>
        </a>
    </div>
    <div class="col-md-6 col-lg-4 mb-3">
        <a href="pullsheets.php" class="btn btn-info w-100 quick-action-btn d-flex flex-column justify-content-center align-items-center">
            <i class="ti ti-file-text icon mb-2" style="font-size: 2rem;"></i>
            <span>Pullsheets</span>
        </a>
    </div>
    <div class="col-md-6 col-lg-4 mb-3">
        <a href="change_orders.php" class="btn btn-warning w-100 quick-action-btn d-flex flex-column justify-content-center align-items-center">
            <i class="ti ti-exchange icon mb-2" style="font-size: 2rem;"></i>
            <span>Change Orders</span>
        </a>
    </div>
    <div class="col-md-6 col-lg-4 mb-3">
        <a href="reports.php" class="btn btn-secondary w-100 quick-action-btn d-flex flex-column justify-content-center align-items-center">
            <i class="ti ti-report icon mb-2" style="font-size: 2rem;"></i>
            <span>Reports</span>
        </a>
    </div>
    <div class="col-md-6 col-lg-4 mb-3">
        <a href="shows.php" class="btn btn-purple w-100 quick-action-btn d-flex flex-column justify-content-center align-items-center">
            <i class="ti ti-theater icon mb-2" style="font-size: 2rem;"></i>
            <span>Shows</span>
        </a>
    </div>
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

<?php require_once 'includes/footer.php'; ?>
