<?php
require_once 'config.php';
require_once 'db.php';
require_once 'functions.php';

$db = getDB();
$pullSheetId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($pullSheetId <= 0) {
    redirectTo('pull_sheets.php');
}

// Get pull sheet details
$stmt = $db->prepare("SELECT ps.*, s.name as show_name, s.id as show_id
                      FROM pull_sheets ps 
                      INNER JOIN shows s ON ps.show_id = s.id 
                      WHERE ps.id = ?");
$stmt->bind_param("i", $pullSheetId);
$stmt->execute();
$result = $stmt->get_result();
$pullSheet = $result->fetch_assoc();

if (!$pullSheet) {
    redirectTo('pull_sheets.php');
}

// Get items
$stmt = $db->prepare("SELECT psi.*, i.name, i.barcode, i.available_quantity 
                      FROM pull_sheet_items psi 
                      INNER JOIN items i ON psi.item_id = i.id 
                      WHERE psi.pull_sheet_id = ?");
$stmt->bind_param("i", $pullSheetId);
$stmt->execute();
$items = $stmt->get_result();

$pageTitle = "Pull Sheet: " . sanitize($pullSheet['barcode']) . " - " . APP_NAME;
$pageHeader = "Pull Sheet Details";
$pageActions = '<a href="pull_sheets.php" class="btn btn-secondary"><i class="ti ti-arrow-left"></i> Back</a>';

ob_start();
?>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">Pull Sheet Information</h3>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Barcode</label>
                        <div class="font-weight-medium"><?php echo sanitize($pullSheet['barcode']); ?></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Status</label>
                        <div>
                            <span class="badge badge-outline text-<?php 
                                echo $pullSheet['status'] === 'finalized' ? 'blue' : 
                                    ($pullSheet['status'] === 'picked' ? 'green' : 
                                    ($pullSheet['status'] === 'completed' ? 'purple' : 'gray')); 
                            ?>">
                                <?php echo ucfirst($pullSheet['status']); ?>
                            </span>
                            <?php if ($pullSheet['is_main']): ?>
                            <span class="badge bg-primary ms-2">Main</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Show</label>
                        <div>
                            <a href="show_view.php?id=<?php echo $pullSheet['show_id']; ?>">
                                <?php echo sanitize($pullSheet['show_name']); ?>
                            </a>
                        </div>
                    </div>
                    <?php if ($pullSheet['created_by']): ?>
                    <div class="col-md-6">
                        <label class="form-label">Created By</label>
                        <div><?php echo sanitize($pullSheet['created_by']); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label">Created</label>
                        <div><?php echo formatDate($pullSheet['created_at']); ?></div>
                    </div>
                    <?php if ($pullSheet['finalized_at']): ?>
                    <div class="col-md-6">
                        <label class="form-label">Finalized</label>
                        <div><?php echo formatDate($pullSheet['finalized_at']); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Items</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Barcode</th>
                            <th>Needed</th>
                            <th>Picked</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($item = $items->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo sanitize($item['name']); ?></td>
                            <td><?php echo sanitize($item['barcode']); ?></td>
                            <td><?php echo $item['quantity_needed']; ?></td>
                            <td><?php echo $item['quantity_picked']; ?></td>
                            <td>
                                <?php if ($item['quantity_picked'] >= $item['quantity_needed']): ?>
                                <span class="badge bg-success">Complete</span>
                                <?php elseif ($item['quantity_picked'] > 0): ?>
                                <span class="badge bg-warning">Partial</span>
                                <?php else: ?>
                                <span class="badge bg-secondary">Pending</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">Barcode</h3>
            </div>
            <div class="card-body text-center">
                <img src="barcode_generator.php?type=pdf417&data=<?php echo urlencode($pullSheet['barcode']); ?>" 
                     alt="Barcode" style="max-width: 100%;">
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Actions</h3>
            </div>
            <div class="list-group list-group-flush">
                <?php if ($pullSheet['status'] !== 'draft'): ?>
                <a href="pdf_generator.php?type=pull_sheet&id=<?php echo $pullSheetId; ?>" 
                   class="list-group-item list-group-item-action" target="_blank">
                    <i class="ti ti-download me-2"></i> Download PDF
                </a>
                <a href="pdf_generator.php?type=labels&pull_sheet_id=<?php echo $pullSheetId; ?>" 
                   class="list-group-item list-group-item-action" target="_blank">
                    <i class="ti ti-tag me-2"></i> Print Labels (Avery 8195)
                </a>
                <?php endif; ?>
                <?php if ($pullSheet['status'] === 'finalized'): ?>
                <a href="pick_mode.php?pull_sheet_id=<?php echo $pullSheetId; ?>" 
                   class="list-group-item list-group-item-action">
                    <i class="ti ti-scan me-2"></i> Start Picking
                </a>
                <?php endif; ?>
                <a href="show_view.php?id=<?php echo $pullSheet['show_id']; ?>" 
                   class="list-group-item list-group-item-action">
                    <i class="ti ti-theater me-2"></i> View Show
                </a>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require 'layout.php';
?>
