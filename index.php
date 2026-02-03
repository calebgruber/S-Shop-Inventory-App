<?php
requireLogin(); // Dashboard requires user to be logged in
$pageTitle = 'Dashboard';
require_once 'includes/header.php';

$stats = getDashboardStats();
$activeShows = getActiveShows();
$currentUser = getCurrentUser();
$isStudent = $currentUser['role'] === 'student';
$isDesigner = $currentUser['role'] === 'designer';
$isProductionAudio = $currentUser['role'] === 'production_audio';

// Get pending pullsheets (finalized but not picked) - filtered by show assignments for designers/production audio
if ($isDesigner || $isProductionAudio) {
    $assignedShows = getAssignedShows($currentUser['id']);
    $assignedShowIds = array_column($assignedShows, 'id');
    
    if (empty($assignedShowIds)) {
        $pendingPullsheets = [];
    } else {
        $placeholders = implode(',', array_fill(0, count($assignedShowIds), '?'));
        $pendingPullsheets = getDB()->fetchAll(
            "SELECT p.*, s.name as show_name 
             FROM pullsheets p 
             LEFT JOIN shows s ON p.show_id = s.id 
             WHERE p.status = 'finalized' AND p.show_id IN ($placeholders)
             ORDER BY p.created_at 
             LIMIT 5",
            $assignedShowIds
        );
    }
} else {
    $pendingPullsheets = getDB()->fetchAll(
        "SELECT p.*, s.name as show_name 
         FROM pullsheets p 
         LEFT JOIN shows s ON p.show_id = s.id 
         WHERE p.status = 'finalized'
         ORDER BY p.created_at 
         LIMIT 5"
    );
}

// Get pending change orders (finalized but not processed) - filtered by show assignments for designers/production audio
if ($isDesigner || $isProductionAudio) {
    if (empty($assignedShowIds)) {
        $pendingChangeOrders = [];
    } else {
        $placeholders = implode(',', array_fill(0, count($assignedShowIds), '?'));
        $pendingChangeOrders = getDB()->fetchAll(
            "SELECT co.*, s.name as show_name 
             FROM change_orders co 
             LEFT JOIN shows s ON co.show_id = s.id 
             WHERE co.status = 'finalized' AND co.show_id IN ($placeholders)
             ORDER BY co.created_at 
             LIMIT 5",
            $assignedShowIds
        );
    }
} else {
    $pendingChangeOrders = getDB()->fetchAll(
        "SELECT co.*, s.name as show_name 
         FROM change_orders co 
         LEFT JOIN shows s ON co.show_id = s.id 
         WHERE co.status = 'finalized'
         ORDER BY co.created_at 
         LIMIT 5"
    );
}

// Get student requests based on role
if ($isStudent) {
    // Students see only THEIR requests
    $studentRequests = getDB()->fetchAll(
        "SELECT sr.*, i.name as item_name, u.full_name as approved_by_name
         FROM student_requests sr
         LEFT JOIN items i ON sr.item_id = i.id
         LEFT JOIN users u ON sr.approved_by = u.id
         WHERE sr.student_id = ?
         ORDER BY sr.created_at DESC
         LIMIT 10",
        [$currentUser['id']]
    );
} elseif ($isDesigner) {
    // Designers see ALL student requests on dashboard
    $studentRequests = getDB()->fetchAll(
        "SELECT sr.*, i.name as item_name, u.full_name as student_name, u2.full_name as approved_by_name
         FROM student_requests sr
         LEFT JOIN items i ON sr.item_id = i.id
         LEFT JOIN users u ON sr.student_id = u.id
         LEFT JOIN users u2 ON sr.approved_by = u2.id
         ORDER BY sr.created_at DESC
         LIMIT 10"
    );
} else {
    // Admins see pending requests (existing behavior)
    $pendingStudentRequests = getDB()->fetchAll(
        "SELECT sr.*, i.name as item_name, u.full_name as student_name
         FROM student_requests sr
         LEFT JOIN items i ON sr.item_id = i.id
         LEFT JOIN users u ON sr.student_id = u.id
         WHERE sr.status = 'pending'
         ORDER BY sr.created_at DESC
         LIMIT 5"
    );
}

// Get change orders with items to add (need to be picked) - filtered by show assignments
if ($isDesigner || $isProductionAudio) {
    if (empty($assignedShowIds)) {
        $changeOrdersToPick = [];
    } else {
        $placeholders = implode(',', array_fill(0, count($assignedShowIds), '?'));
        $changeOrdersToPick = getDB()->fetchAll(
            "SELECT co.*, s.name as show_name, COUNT(coi.id) as items_to_add
             FROM change_orders co 
             LEFT JOIN shows s ON co.show_id = s.id 
             LEFT JOIN change_order_items coi ON co.id = coi.change_order_id 
             WHERE co.status = 'finalized' AND coi.type = 'add' AND co.show_id IN ($placeholders)
             GROUP BY co.id
             HAVING items_to_add > 0
             ORDER BY co.created_at 
             LIMIT 5",
            $assignedShowIds
        );
    }
} else {
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
}

// Get change orders with items to remove (need to be returned) - filtered by show assignments
if ($isDesigner || $isProductionAudio) {
    if (empty($assignedShowIds)) {
        $changeOrdersToReturn = [];
    } else {
        $placeholders = implode(',', array_fill(0, count($assignedShowIds), '?'));
        $changeOrdersToReturn = getDB()->fetchAll(
            "SELECT co.*, s.name as show_name, COUNT(coi.id) as items_to_remove
             FROM change_orders co 
             LEFT JOIN shows s ON co.show_id = s.id 
             LEFT JOIN change_order_items coi ON co.id = coi.change_order_id 
             WHERE co.status = 'finalized' AND coi.type = 'remove' AND co.show_id IN ($placeholders)
             GROUP BY co.id
             HAVING items_to_remove > 0
             ORDER BY co.created_at 
             LIMIT 5",
            $assignedShowIds
        );
    }
} else {
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
}
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
                    <input type="text" class="form-control" id="quickLookupInput" placeholder="Search items by name, barcode, or category..." autofocus>
                </div>
                <div class="list-group" id="quickLookupList" style="max-height: 400px; overflow-y: auto;">
                    <?php
                    $allItems = getAllItems();
                    foreach ($allItems as $item):
                    ?>
                    <?php if ($isStudent): ?>
                    <div class="list-group-item quick-lookup-item">
                        <div class="d-flex w-100 align-items-center gap-2">
                            <div class="flex-grow-1">
                                <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                <small class="text-muted"><?php echo htmlspecialchars($item['category_name'] ?? 'Uncategorized'); ?> - <?php echo htmlspecialchars($item['barcode']); ?></small>
                            </div>
                            <div class="text-end me-2">
                                <small class="<?php echo $item['in_stock_quantity'] > 0 ? 'text-success' : 'text-danger'; ?>">
                                    <?php echo $item['in_stock_quantity']; ?> / <?php echo $item['total_quantity']; ?>
                                </small>
                            </div>
                            <?php if (!empty($item['photo_path']) && file_exists(__DIR__ . '/uploads/items/' . $item['photo_path'])): ?>
                            <img src="uploads/items/<?php echo htmlspecialchars($item['photo_path']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php else: ?>
                    <a href="item_edit?id=<?php echo $item['id']; ?>" class="list-group-item list-group-item-action quick-lookup-item">
                        <div class="d-flex w-100 align-items-center gap-2">
                            <div class="flex-grow-1">
                                <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                <small class="text-muted"><?php echo htmlspecialchars($item['category_name'] ?? 'Uncategorized'); ?> - <?php echo htmlspecialchars($item['barcode']); ?></small>
                            </div>
                            <div class="text-end me-2">
                                <small class="<?php echo $item['in_stock_quantity'] > 0 ? 'text-success' : 'text-danger'; ?>">
                                    <?php echo $item['in_stock_quantity']; ?> / <?php echo $item['total_quantity']; ?>
                                </small>
                            </div>
                            <?php if (!empty($item['photo_path']) && file_exists(__DIR__ . '/uploads/items/' . $item['photo_path'])): ?>
                            <img src="uploads/items/<?php echo htmlspecialchars($item['photo_path']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                            <?php endif; ?>
                        </div>
                    </a>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </div>
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
                <div class="d-flex align-items-center" style="cursor: pointer;" id="redCableBlock">
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

<?php if (isAdmin()): ?>
<div class="row row-deck row-cards mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card stat-card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Active Shows</div>
                    <div class="ms-auto lh-1">
                        <i class="ti ti-theater text-muted dashboard-icon-bg"></i>
                    </div>
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
                    <div class="ms-auto lh-1">
                        <i class="ti ti-scan text-muted dashboard-icon-bg"></i>
                    </div>
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
                    <div class="ms-auto lh-1">
                        <i class="ti ti-arrow-back text-muted dashboard-icon-bg"></i>
                    </div>
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
                    <div class="ms-auto lh-1">
                        <i class="ti ti-package text-muted dashboard-icon-bg"></i>
                    </div>
                </div>
                <div class="h1 mb-0"><?php echo getDB()->fetchOne("SELECT COUNT(*) as count FROM items")['count']; ?></div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

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
    
    <!-- Student Requests (visible for students and designers, not admins) -->
    <?php if (hasPermission('student_requests') && !isAdmin()): ?>
    <div class="col-md-6 col-lg-3 mb-3">
        <a href="student_requests" class="btn btn-primary w-100 quick-action-btn d-flex flex-column justify-content-center align-items-center">
            <i class="ti ti-clipboard-list icon mb-2" style="font-size: 2rem;"></i>
            <span>Requests</span>
        </a>
    </div>
    <?php endif; ?>
    
    <?php if (!$isStudent): ?>
    <?php if (hasPermission('inventory')): ?>
    <!-- Inventory -->
    <div class="col-md-6 col-lg-3 mb-3">
        <a href="items" class="btn btn-secondary w-100 quick-action-btn d-flex flex-column justify-content-center align-items-center">
            <i class="ti ti-package icon mb-2" style="font-size: 2rem;"></i>
            <span>Inventory</span>
        </a>
    </div>
    <?php endif; ?>
    
    <?php if (hasPermission('reports')): ?>
    <!-- Show Reports -->
    <div class="col-md-6 col-lg-3 mb-3">
        <a href="reports" class="btn btn-info w-100 quick-action-btn d-flex flex-column justify-content-center align-items-center">
            <i class="ti ti-report icon mb-2" style="font-size: 2rem;"></i>
            <span>Show Reports</span>
        </a>
    </div>
    <?php endif; ?>
    
    <?php if (hasPermission('pullsheets')): ?>
    <!-- Create Pullsheet -->
    <div class="col-md-6 col-lg-3 mb-3">
        <a href="pullsheet_create" class="btn btn-success w-100 quick-action-btn d-flex flex-column justify-content-center align-items-center">
            <i class="ti ti-file-plus icon mb-2" style="font-size: 2rem;"></i>
            <span>Create Pullsheet</span>
        </a>
    </div>
    <?php endif; ?>
    
    <?php if (hasPermission('pick_mode')): ?>
    <!-- Pick Mode -->
    <div class="col-md-6 col-lg-3 mb-3">
        <a href="pick_mode" class="btn btn-primary w-100 quick-action-btn d-flex flex-column justify-content-center align-items-center">
            <i class="ti ti-scan icon mb-2" style="font-size: 2rem;"></i>
            <span>Pick Mode</span>
        </a>
    </div>
    <?php endif; ?>
    
    <?php if (hasPermission('return_mode')): ?>
    <!-- Return Mode -->
    <div class="col-md-6 col-lg-3 mb-3">
        <a href="return_mode" class="btn btn-warning w-100 quick-action-btn d-flex flex-column justify-content-center align-items-center">
            <i class="ti ti-arrow-back icon mb-2" style="font-size: 2rem;"></i>
            <span>Return Mode</span>
        </a>
    </div>
    <?php endif; ?>
    
    <?php if (hasPermission('shows') && !isProductionAudio()): ?>
    <!-- Shows -->
    <div class="col-md-6 col-lg-3 mb-3">
        <a href="shows" class="btn btn-purple w-100 quick-action-btn d-flex flex-column justify-content-center align-items-center">
            <i class="ti ti-theater icon mb-2" style="font-size: 2rem;"></i>
            <span>Shows</span>
        </a>
    </div>
    <?php endif; ?>
    
    <?php if (hasPermission('change_orders')): ?>
    <!-- Change Orders -->
    <div class="col-md-6 col-lg-3 mb-3">
        <a href="change_orders" class="btn btn-orange w-100 quick-action-btn d-flex flex-column justify-content-center align-items-center">
            <i class="ti ti-exchange icon mb-2" style="font-size: 2rem;"></i>
            <span>Change Orders</span>
        </a>
    </div>
    <?php endif; ?>
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
    
    <?php if (hasPermission('student_requests') && !empty($pendingStudentRequests)): ?>
    <div class="col-lg-6 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Student Requests</h3>
                <?php if ($stats['pending_student_requests'] > 0): ?>
                    <span class="badge bg-primary"><?php echo $stats['pending_student_requests']; ?> pending</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <?php foreach ($pendingStudentRequests as $request): ?>
                        <div class="list-group-item">
                            <div class="row align-items-center">
                                <div class="col">
                                    <strong><?php echo htmlspecialchars($request['item_name'] ?? 'Unknown Item'); ?></strong>
                                    <div class="text-muted small">By: <?php echo htmlspecialchars($request['student_name'] ?? 'Unknown'); ?> - <?php echo date('m/d/Y', strtotime($request['created_at'])); ?></div>
                                </div>
                                <div class="col-auto">
                                    <a href="student_requests" class="btn btn-sm btn-primary">View</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="text-center mt-3">
                    <a href="student_requests" class="btn btn-link">View All Requests</a>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
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
    <?php endif; ?>
</div>

<?php if (!$isStudent && !$isDesigner): ?>
<!-- Active Shows - Only for Admins -->
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
                                        <a href="show_edit?id=<?php echo $show['id']; ?>" class="btn btn-sm btn-primary">
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
<?php endif; ?>

<?php if ($isStudent || $isDesigner): ?>
<!-- Student/Designer Requests Table -->
<div class="row">
    <div class="col-lg-12 mb-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><?php echo $isStudent ? 'My Requests' : 'Student Requests'; ?></h3>
            </div>
            <div class="card-body">
                <?php if (empty($studentRequests)): ?>
                    <p class="text-muted"><?php echo $isStudent ? 'You have no requests' : 'No student requests'; ?></p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-vcenter">
                            <thead>
                                <tr>
                                    <?php if ($isDesigner): ?>
                                    <th>Student</th>
                                    <?php endif; ?>
                                    <th>Item</th>
                                    <th>Quantity</th>
                                    <th>Status</th>
                                    <th>Requested</th>
                                    <th>Approved By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($studentRequests as $request): ?>
                                <tr>
                                    <?php if ($isDesigner): ?>
                                    <td><?php echo htmlspecialchars($request['student_name'] ?? 'N/A'); ?></td>
                                    <?php endif; ?>
                                    <td><?php echo htmlspecialchars($request['item_name'] ?? 'N/A'); ?></td>
                                    <td><?php echo $request['quantity']; ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $request['status'] === 'approved' ? 'success' : 
                                                 ($request['status'] === 'rejected' ? 'danger' : 
                                                 ($request['status'] === 'fulfilled' ? 'info' : 'warning')); 
                                        ?>">
                                            <?php echo ucfirst($request['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('m/d/Y', strtotime($request['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($request['approved_by_name'] ?? '-'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
                <div class="text-center mt-3">
                    <a href="student_requests" class="btn btn-primary"><?php echo $isStudent ? 'View All My Requests' : 'View All Requests'; ?></a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// Quick Lookup functionality - filter list
document.getElementById('quickLookupInput')?.addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const items = document.querySelectorAll('#quickLookupList .quick-lookup-item');
    
    // Easter egg: Play whopper.mp3 at 300% volume if CHZ-BGR barcode is scanned
    if (searchTerm.includes('chz-bgr')) {
        try {
            const audio = new Audio('assets/sounds/whopper.mp3');
            audio.volume = 1.0; // Max volume (300% would require Web Audio API)
            // Using Web Audio API for 300% volume
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const source = audioContext.createMediaElementSource(audio);
            const gainNode = audioContext.createGain();
            gainNode.gain.value = 3.0; // 300% volume
            source.connect(gainNode);
            gainNode.connect(audioContext.destination);
            audio.play().catch(err => console.log('Audio play failed:', err));
        } catch (err) {
            console.log('Whopper Easter egg failed:', err);
        }
    }
    
    items.forEach(item => {
        const text = item.textContent.toLowerCase();
        if (text.includes(searchTerm)) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });
});

// Reset modal when closed
document.getElementById('quickLookupModal')?.addEventListener('hidden.bs.modal', function() {
    document.getElementById('quickLookupInput').value = '';
    const items = document.querySelectorAll('#quickLookupList .quick-lookup-item');
    items.forEach(item => {
        item.style.display = '';
    });
});

// Focus input when modal opens
document.getElementById('quickLookupModal')?.addEventListener('shown.bs.modal', function() {
    document.getElementById('quickLookupInput').focus();
});

// Easter egg: Bonk sound when red cable block is clicked
document.getElementById('redCableBlock')?.addEventListener('click', function() {
    try {
        const audio = new Audio('assets/sounds/bonk.mp3');
        audio.volume = 1.0;
        audio.play().catch(err => console.log('Bonk sound failed:', err));
    } catch (err) {
        console.log('Bonk Easter egg failed:', err);
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
