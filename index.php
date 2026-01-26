<?php
require_once 'config.php';
require_once 'db.php';
require_once 'functions.php';

$pageTitle = 'Dashboard - ' . APP_NAME;
$pageHeader = 'Dashboard';

// Get pending tasks
$db = getDB();

// Get incomplete pull sheets
$incompletePullSheets = $db->query("
    SELECT ps.*, s.name as show_name 
    FROM pull_sheets ps 
    JOIN shows s ON ps.show_id = s.id 
    WHERE ps.status IN ('finalized', 'picked') 
    AND ps.status != 'completed'
    ORDER BY ps.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

// Get incomplete change orders
$incompleteChangeOrders = $db->query("
    SELECT co.*, s.name as show_name 
    FROM change_orders co 
    JOIN shows s ON co.show_id = s.id 
    WHERE co.status IN ('finalized', 'picked', 'returned') 
    AND co.status != 'completed'
    ORDER BY co.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

// Get active shows count
$activeShowsCount = $db->query("SELECT COUNT(*) as count FROM shows WHERE status = 'active'")->fetch_assoc()['count'];

// Get total items count
$totalItemsCount = $db->query("SELECT COUNT(*) as count FROM items")->fetch_assoc()['count'];

// Get items checked out
$checkedOutCount = $db->query("
    SELECT SUM(quantity) as total 
    FROM item_locations 
    WHERE location_type = 'theatre'
")->fetch_assoc()['total'] ?? 0;

ob_start();
?>

<div class="row row-deck row-cards">
    <!-- Quick Stats -->
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Active Shows</div>
                </div>
                <div class="h1 mb-3"><?php echo $activeShowsCount; ?></div>
                <div class="d-flex mb-2">
                    <a href="shows.php" class="btn btn-primary btn-sm w-100">View Shows</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Total Items</div>
                </div>
                <div class="h1 mb-3"><?php echo $totalItemsCount; ?></div>
                <div class="d-flex mb-2">
                    <a href="inventory.php" class="btn btn-primary btn-sm w-100">View Inventory</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Items Checked Out</div>
                </div>
                <div class="h1 mb-3"><?php echo $checkedOutCount; ?></div>
                <div class="d-flex mb-2">
                    <a href="reports.php?type=checked_out" class="btn btn-primary btn-sm w-100">View Report</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="subheader">Pending Tasks</div>
                </div>
                <div class="h1 mb-3"><?php echo count($incompletePullSheets) + count($incompleteChangeOrders); ?></div>
                <div class="d-flex mb-2">
                    <a href="#pending-tasks" class="btn btn-primary btn-sm w-100">View Below</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Access Buttons -->
<div class="row row-deck row-cards mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Quick Access</h3>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <a href="pick_mode.php" class="btn btn-lg btn-success w-100 dashboard-card">
                            <i class="ti ti-scan icon mb-2"></i><br>
                            Pick Mode
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="return_mode.php" class="btn btn-lg btn-info w-100 dashboard-card">
                            <i class="ti ti-arrow-back-up icon mb-2"></i><br>
                            Return Mode
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="shows.php" class="btn btn-lg btn-primary w-100 dashboard-card">
                            <i class="ti ti-theater icon mb-2"></i><br>
                            Shows
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="reports.php" class="btn btn-lg btn-secondary w-100 dashboard-card">
                            <i class="ti ti-file-analytics icon mb-2"></i><br>
                            Reports
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Pending Tasks -->
<?php if (count($incompletePullSheets) > 0 || count($incompleteChangeOrders) > 0): ?>
<div class="row row-deck row-cards mt-4" id="pending-tasks">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Pending Tasks</h3>
            </div>
            <div class="card-body">
                <?php if (count($incompletePullSheets) > 0): ?>
                <h4>Incomplete Pull Sheets</h4>
                <div class="list-group mb-3">
                    <?php foreach ($incompletePullSheets as $ps): ?>
                    <div class="list-group-item">
                        <div class="row align-items-center">
                            <div class="col">
                                <strong><?php echo sanitize($ps['show_name']); ?></strong><br>
                                <small class="text-muted">Barcode: <?php echo sanitize($ps['barcode']); ?></small><br>
                                <small class="text-muted">Status: <?php echo ucfirst($ps['status']); ?></small>
                            </div>
                            <div class="col-auto">
                                <?php if ($ps['status'] === 'finalized'): ?>
                                <a href="pick_mode.php?barcode=<?php echo urlencode($ps['barcode']); ?>" class="btn btn-success btn-sm">
                                    Start Picking
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                
                <?php if (count($incompleteChangeOrders) > 0): ?>
                <h4>Incomplete Change Orders</h4>
                <div class="list-group">
                    <?php foreach ($incompleteChangeOrders as $co): ?>
                    <div class="list-group-item">
                        <div class="row align-items-center">
                            <div class="col">
                                <strong><?php echo sanitize($co['show_name']); ?></strong><br>
                                <small class="text-muted">Barcode: <?php echo sanitize($co['barcode']); ?></small><br>
                                <small class="text-muted">Status: <?php echo ucfirst($co['status']); ?></small>
                            </div>
                            <div class="col-auto">
                                <?php if ($co['status'] === 'finalized'): ?>
                                <a href="pick_mode.php?barcode=<?php echo urlencode($co['barcode']); ?>" class="btn btn-success btn-sm">
                                    Process
                                </a>
                                <?php elseif ($co['status'] === 'picked'): ?>
                                <a href="return_mode.php?barcode=<?php echo urlencode($co['barcode']); ?>" class="btn btn-info btn-sm">
                                    Complete Returns
                                </a>
                                <?php endif; ?>
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

<?php
$content = ob_get_clean();
include 'layout.php';
?>
