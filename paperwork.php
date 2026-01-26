<?php
$pageTitle = 'Paperwork';
require_once 'includes/header.php';

// Check permissions
if (!hasPermission('paperwork')) {
    setAlert('You do not have permission to access paperwork', 'danger');
    redirect('index.php');
}

$db = getDB();

// Get selected show
$selectedShowId = $_GET['show_id'] ?? null;
$categoryFilter = $_GET['category'] ?? 'all';
$typeFilter = $_GET['type'] ?? 'all';

// Get all shows for dropdown
$shows = $db->fetchAll(
    "SELECT s.*, t.name as theatre_space_name 
     FROM shows s 
     LEFT JOIN theatre_spaces t ON s.theatre_space_id = t.id 
     ORDER BY s.created_at DESC"
);

$pullsheets = [];
$changeOrders = [];
$categories = getAllCategories();

if ($selectedShowId) {
    // Get pullsheets for this show
    $pullsheetQuery = "SELECT p.*, COUNT(pi.id) as item_count
                       FROM pullsheets p
                       LEFT JOIN pullsheet_items pi ON p.id = pi.pullsheet_id
                       WHERE p.show_id = ?
                       GROUP BY p.id
                       ORDER BY p.created_at DESC";
    
    $pullsheets = $db->fetchAll($pullsheetQuery, [$selectedShowId]);
    
    // Get change orders for this show
    $changeOrderQuery = "SELECT co.*, COUNT(coi.id) as item_count
                         FROM change_orders co
                         LEFT JOIN change_order_items coi ON co.id = coi.change_order_id
                         WHERE co.show_id = ?
                         GROUP BY co.id
                         ORDER BY co.created_at DESC";
    
    $changeOrders = $db->fetchAll($changeOrderQuery, [$selectedShowId]);
    
    // Apply category filter if needed
    if ($categoryFilter !== 'all') {
        // Filter pullsheets
        $filteredPullsheets = [];
        foreach ($pullsheets as $ps) {
            $hasCategory = $db->fetchOne(
                "SELECT COUNT(*) as count FROM pullsheet_items pi
                 LEFT JOIN items i ON pi.item_id = i.id
                 WHERE pi.pullsheet_id = ? AND i.category_id = ?",
                [$ps['id'], $categoryFilter]
            );
            if ($hasCategory['count'] > 0) {
                $filteredPullsheets[] = $ps;
            }
        }
        $pullsheets = $filteredPullsheets;
        
        // Filter change orders
        $filteredChangeOrders = [];
        foreach ($changeOrders as $co) {
            $hasCategory = $db->fetchOne(
                "SELECT COUNT(*) as count FROM change_order_items coi
                 LEFT JOIN items i ON coi.item_id = i.id
                 WHERE coi.change_order_id = ? AND i.category_id = ?",
                [$co['id'], $categoryFilter]
            );
            if ($hasCategory['count'] > 0) {
                $filteredChangeOrders[] = $co;
            }
        }
        $changeOrders = $filteredChangeOrders;
    }
    
    // Apply type filter
    if ($typeFilter === 'pullsheets') {
        $changeOrders = [];
    } elseif ($typeFilter === 'change_orders') {
        $pullsheets = [];
    }
}
?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Select Show</h3>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Show</label>
                        <select class="form-select" name="show_id" onchange="this.form.submit()" required>
                            <option value="">-- Select a Show --</option>
                            <?php foreach ($shows as $show): ?>
                            <option value="<?php echo $show['id']; ?>" <?php echo $selectedShowId == $show['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($show['name']); ?>
                                (<?php echo htmlspecialchars($show['theatre_space_name'] ?? 'No space'); ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <?php if ($selectedShowId): ?>
                    <div class="col-md-3">
                        <label class="form-label">Type</label>
                        <select class="form-select" name="type" onchange="this.form.submit()">
                            <option value="all" <?php echo $typeFilter === 'all' ? 'selected' : ''; ?>>All Types</option>
                            <option value="pullsheets" <?php echo $typeFilter === 'pullsheets' ? 'selected' : ''; ?>>Pullsheets</option>
                            <option value="change_orders" <?php echo $typeFilter === 'change_orders' ? 'selected' : ''; ?>>Change Orders</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
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
                    
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <a href="paperwork.php?show_id=<?php echo $selectedShowId; ?>" class="btn btn-secondary w-100">Clear Filters</a>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>

<?php if ($selectedShowId): ?>
<?php 
$selectedShow = null;
foreach ($shows as $show) {
    if ($show['id'] == $selectedShowId) {
        $selectedShow = $show;
        break;
    }
}
?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Show Information</h3>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-3">Show Name:</dt>
                    <dd class="col-sm-9"><?php echo htmlspecialchars($selectedShow['name']); ?></dd>
                    
                    <dt class="col-sm-3">Theatre Space:</dt>
                    <dd class="col-sm-9"><?php echo htmlspecialchars($selectedShow['theatre_space_name'] ?? 'N/A'); ?></dd>
                    
                    <dt class="col-sm-3">Shop Lead:</dt>
                    <dd class="col-sm-9"><?php echo htmlspecialchars($selectedShow['shop_lead'] ?? 'N/A'); ?></dd>
                    
                    <dt class="col-sm-3">Designer:</dt>
                    <dd class="col-sm-9"><?php echo htmlspecialchars($selectedShow['designer'] ?? 'N/A'); ?></dd>
                    
                    <dt class="col-sm-3">Status:</dt>
                    <dd class="col-sm-9">
                        <span class="badge bg-<?php echo $selectedShow['status'] === 'active' ? 'success' : 'secondary'; ?>">
                            <?php echo ucfirst($selectedShow['status']); ?>
                        </span>
                    </dd>
                </dl>
            </div>
        </div>
    </div>
</div>

<!-- Pullsheets -->
<?php if (($typeFilter === 'all' || $typeFilter === 'pullsheets') && !empty($pullsheets)): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Pullsheets (<?php echo count($pullsheets); ?>)</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Barcode</th>
                            <th>Items</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th>Created</th>
                            <th>Finalized</th>
                            <th class="w-1">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pullsheets as $ps): ?>
                        <tr>
                            <td><code><?php echo htmlspecialchars($ps['barcode']); ?></code></td>
                            <td><?php echo $ps['item_count']; ?> items</td>
                            <td>
                                <?php
                                $statusColors = [
                                    'draft' => 'secondary',
                                    'finalized' => 'warning',
                                    'picked' => 'info',
                                    'completed' => 'success'
                                ];
                                ?>
                                <span class="badge bg-<?php echo $statusColors[$ps['status']] ?? 'secondary'; ?>">
                                    <?php echo ucfirst($ps['status']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($ps['created_by'] ?? 'N/A'); ?></td>
                            <td><?php echo date('m/d/Y', strtotime($ps['created_at'])); ?></td>
                            <td><?php echo $ps['finalized_at'] ? date('m/d/Y', strtotime($ps['finalized_at'])) : 'N/A'; ?></td>
                            <td>
                                <div class="btn-group">
                                    <a href="pullsheet_view.php?id=<?php echo $ps['id']; ?>" class="btn btn-sm btn-primary" target="_blank">
                                        <i class="ti ti-eye icon"></i> View
                                    </a>
                                    <button onclick="printPullsheet(<?php echo $ps['id']; ?>)" class="btn btn-sm btn-info">
                                        <i class="ti ti-printer icon"></i> Print
                                    </button>
                                    <a href="pullsheet_edit.php?id=<?php echo $ps['id']; ?>" class="btn btn-sm btn-secondary">
                                        <i class="ti ti-edit icon"></i> Edit
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Change Orders -->
<?php if (($typeFilter === 'all' || $typeFilter === 'change_orders') && !empty($changeOrders)): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Change Orders (<?php echo count($changeOrders); ?>)</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Barcode</th>
                            <th>Items</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th>Created</th>
                            <th>Finalized</th>
                            <th class="w-1">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($changeOrders as $co): ?>
                        <tr>
                            <td><code><?php echo htmlspecialchars($co['barcode']); ?></code></td>
                            <td><?php echo $co['item_count']; ?> items</td>
                            <td>
                                <?php
                                $statusColors = [
                                    'draft' => 'secondary',
                                    'finalized' => 'warning',
                                    'processed' => 'info',
                                    'completed' => 'success'
                                ];
                                ?>
                                <span class="badge bg-<?php echo $statusColors[$co['status']] ?? 'secondary'; ?>">
                                    <?php echo ucfirst($co['status']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($co['created_by'] ?? 'N/A'); ?></td>
                            <td><?php echo date('m/d/Y', strtotime($co['created_at'])); ?></td>
                            <td><?php echo $co['finalized_at'] ? date('m/d/Y', strtotime($co['finalized_at'])) : 'N/A'; ?></td>
                            <td>
                                <div class="btn-group">
                                    <a href="change_order_edit.php?id=<?php echo $co['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="ti ti-eye icon"></i> View
                                    </a>
                                    <button onclick="printChangeOrder(<?php echo $co['id']; ?>)" class="btn btn-sm btn-info">
                                        <i class="ti ti-printer icon"></i> Print
                                    </button>
                                    <a href="change_order_edit.php?id=<?php echo $co['id']; ?>" class="btn btn-sm btn-secondary">
                                        <i class="ti ti-edit icon"></i> Edit
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (empty($pullsheets) && empty($changeOrders)): ?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body text-center text-muted">
                <i class="ti ti-file-off icon mb-3" style="font-size: 3rem;"></i>
                <p>No paperwork found for this show with the selected filters.</p>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php else: ?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body text-center text-muted">
                <i class="ti ti-file-search icon mb-3" style="font-size: 3rem;"></i>
                <p>Select a show to view paperwork</p>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function printPullsheet(id) {
    // Open pullsheet in new window and trigger print
    const win = window.open('pullsheet_view.php?id=' + id, '_blank');
    win.onload = function() {
        win.print();
    };
}

function printChangeOrder(id) {
    // Open change order in new window and trigger print
    const win = window.open('change_order_edit.php?id=' + id + '&print=1', '_blank');
    win.onload = function() {
        win.print();
    };
}
</script>

<?php require_once 'includes/footer.php'; ?>
