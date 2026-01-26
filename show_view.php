<?php
require_once 'config.php';
require_once 'db.php';
require_once 'functions.php';

$db = getDB();

// Get show ID
$showId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($showId <= 0) {
    redirectTo('shows.php');
}

// Get show details
$stmt = $db->prepare("SELECT s.*, ts.name as theatre_space_name 
                      FROM shows s 
                      LEFT JOIN theatre_spaces ts ON s.theatre_space_id = ts.id 
                      WHERE s.id = ?");
$stmt->bind_param("i", $showId);
$stmt->execute();
$result = $stmt->get_result();
$show = $result->fetch_assoc();

if (!$show) {
    redirectTo('shows.php');
}

// Get pull sheets for this show
$stmt = $db->prepare("SELECT * FROM pull_sheets WHERE show_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $showId);
$stmt->execute();
$pull_sheets = $stmt->get_result();

// Get change orders for this show
$stmt = $db->prepare("SELECT * FROM change_orders WHERE show_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $showId);
$stmt->execute();
$change_orders = $stmt->get_result();

$pageTitle = sanitize($show['name']) . " - " . APP_NAME;
$pageHeader = sanitize($show['name']);
$pageActions = '<a href="shows.php" class="btn btn-secondary"><i class="ti ti-arrow-left"></i> Back to Shows</a>
                <a href="show_delete.php?id=' . $showId . '" class="btn btn-danger"><i class="ti ti-trash"></i> Delete Show</a>';

ob_start();
?>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">Show Details</h3>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <div>
                            <span class="badge badge-outline text-<?php 
                                echo $show['status'] === 'active' ? 'green' : 
                                    ($show['status'] === 'completed' ? 'blue' : 'red'); 
                            ?>">
                                <?php echo ucfirst($show['status']); ?>
                            </span>
                        </div>
                    </div>
                    <?php if ($show['theatre_space_name']): ?>
                    <div class="col-md-6">
                        <label class="form-label">Theatre Space</label>
                        <div><?php echo sanitize($show['theatre_space_name']); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="row mb-3">
                    <?php if ($show['designer']): ?>
                    <div class="col-md-6">
                        <label class="form-label">Designer</label>
                        <div><?php echo sanitize($show['designer']); ?></div>
                    </div>
                    <?php endif; ?>
                    <?php if ($show['shop_lead']): ?>
                    <div class="col-md-6">
                        <label class="form-label">Shop Lead</label>
                        <div><?php echo sanitize($show['shop_lead']); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label">Created</label>
                        <div><?php echo formatDate($show['created_at']); ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Last Updated</label>
                        <div><?php echo formatDate($show['updated_at']); ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">Pull Sheets</h3>
                <div class="card-actions">
                    <a href="pull_sheet_create.php?show_id=<?php echo $showId; ?>" class="btn btn-primary btn-sm">
                        <i class="ti ti-plus"></i> Create Pull Sheet
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php if ($pull_sheets->num_rows > 0): ?>
                <div class="list-group list-group-flush">
                    <?php while ($ps = $pull_sheets->fetch_assoc()): ?>
                    <div class="list-group-item">
                        <div class="row align-items-center">
                            <div class="col">
                                <div class="font-weight-medium">
                                    <?php echo sanitize($ps['barcode']); ?>
                                    <?php if ($ps['is_main']): ?>
                                    <span class="badge bg-primary ms-2">Main</span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-muted small">
                                    Status: <span class="badge badge-outline text-<?php 
                                        echo $ps['status'] === 'finalized' ? 'blue' : 
                                            ($ps['status'] === 'picked' ? 'green' : 
                                            ($ps['status'] === 'completed' ? 'purple' : 'gray')); 
                                    ?>"><?php echo ucfirst($ps['status']); ?></span>
                                    | Created: <?php echo formatDate($ps['created_at']); ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <a href="pull_sheet_view.php?id=<?php echo $ps['id']; ?>" class="btn btn-sm btn-primary">
                                    View
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                <?php else: ?>
                <div class="text-muted text-center py-4">
                    No pull sheets created yet
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Change Orders</h3>
                <div class="card-actions">
                    <a href="change_order_create.php?show_id=<?php echo $showId; ?>" class="btn btn-primary btn-sm">
                        <i class="ti ti-plus"></i> Create Change Order
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php if ($change_orders->num_rows > 0): ?>
                <div class="list-group list-group-flush">
                    <?php while ($co = $change_orders->fetch_assoc()): ?>
                    <div class="list-group-item">
                        <div class="row align-items-center">
                            <div class="col">
                                <div class="font-weight-medium">
                                    <?php echo sanitize($co['barcode']); ?>
                                </div>
                                <div class="text-muted small">
                                    Status: <span class="badge badge-outline text-<?php 
                                        echo $co['status'] === 'finalized' ? 'blue' : 
                                            ($co['status'] === 'picked' ? 'green' : 
                                            ($co['status'] === 'completed' ? 'purple' : 'gray')); 
                                    ?>"><?php echo ucfirst($co['status']); ?></span>
                                    | Created: <?php echo formatDate($co['created_at']); ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <a href="change_order_view.php?id=<?php echo $co['id']; ?>" class="btn btn-sm btn-primary">
                                    View
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                <?php else: ?>
                <div class="text-muted text-center py-4">
                    No change orders created yet
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Quick Actions</h3>
            </div>
            <div class="list-group list-group-flush">
                <a href="pull_sheet_create.php?show_id=<?php echo $showId; ?>" class="list-group-item list-group-item-action">
                    <i class="ti ti-list-check me-2"></i> Create Pull Sheet
                </a>
                <a href="change_order_create.php?show_id=<?php echo $showId; ?>" class="list-group-item list-group-item-action">
                    <i class="ti ti-repeat me-2"></i> Create Change Order
                </a>
                <a href="reports.php?show_id=<?php echo $showId; ?>" class="list-group-item list-group-item-action">
                    <i class="ti ti-file-analytics me-2"></i> View Reports
                </a>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require 'layout.php';
?>
