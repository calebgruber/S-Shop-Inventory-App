<?php
require_once 'config.php';
require_once 'db.php';
require_once 'functions.php';

$db = getDB();

// Get all pull sheets with show names
$sql = "SELECT ps.*, s.name as show_name 
        FROM pull_sheets ps 
        INNER JOIN shows s ON ps.show_id = s.id 
        ORDER BY ps.created_at DESC";
$pull_sheets = $db->query($sql);

$pageTitle = "Pull Sheets - " . APP_NAME;
$pageHeader = "Pull Sheets";
$pageActions = '<a href="pull_sheet_create.php" class="btn btn-primary"><i class="ti ti-plus"></i> Create Pull Sheet</a>';

ob_start();
?>

<div class="row row-cards">
    <?php if ($pull_sheets && $pull_sheets->num_rows > 0): ?>
        <?php while ($ps = $pull_sheets->fetch_assoc()): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card">
                <div class="card-status-top bg-<?php 
                    echo $ps['status'] === 'finalized' ? 'blue' : 
                        ($ps['status'] === 'picked' ? 'green' : 
                        ($ps['status'] === 'completed' ? 'purple' : 'gray')); 
                ?>"></div>
                <div class="card-body">
                    <h3 class="card-title">
                        <?php echo sanitize($ps['barcode']); ?>
                        <?php if ($ps['is_main']): ?>
                        <span class="badge bg-primary ms-2">Main</span>
                        <?php endif; ?>
                    </h3>
                    <div class="text-muted mb-2">
                        <i class="ti ti-theater"></i> <?php echo sanitize($ps['show_name']); ?>
                    </div>
                    <div class="mb-2">
                        <span class="badge badge-outline text-<?php 
                            echo $ps['status'] === 'finalized' ? 'blue' : 
                                ($ps['status'] === 'picked' ? 'green' : 
                                ($ps['status'] === 'completed' ? 'purple' : 'gray')); 
                        ?>">
                            <?php echo ucfirst($ps['status']); ?>
                        </span>
                    </div>
                    <?php if ($ps['created_by']): ?>
                    <div class="text-muted mb-1">
                        <i class="ti ti-user"></i> <?php echo sanitize($ps['created_by']); ?>
                    </div>
                    <?php endif; ?>
                    <div class="text-muted mt-2">
                        <small>Created: <?php echo formatDate($ps['created_at']); ?></small>
                    </div>
                    <?php if ($ps['finalized_at']): ?>
                    <div class="text-muted">
                        <small>Finalized: <?php echo formatDate($ps['finalized_at']); ?></small>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer">
                    <div class="d-flex gap-2">
                        <a href="pull_sheet_view.php?id=<?php echo $ps['id']; ?>" class="btn btn-primary btn-sm flex-fill">
                            View
                        </a>
                        <?php if ($ps['status'] === 'draft'): ?>
                        <a href="pull_sheet_create.php?id=<?php echo $ps['id']; ?>" class="btn btn-secondary btn-sm">
                            Edit
                        </a>
                        <?php endif; ?>
                        <?php if ($ps['status'] !== 'draft'): ?>
                        <a href="pdf_generator.php?type=pull_sheet&id=<?php echo $ps['id']; ?>" class="btn btn-success btn-sm" target="_blank">
                            <i class="ti ti-download"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="col-12">
            <div class="empty">
                <div class="empty-icon">
                    <i class="ti ti-list-check icon"></i>
                </div>
                <p class="empty-title">No pull sheets found</p>
                <p class="empty-subtitle text-muted">
                    Get started by creating your first pull sheet
                </p>
                <div class="empty-action">
                    <a href="pull_sheet_create.php" class="btn btn-primary">
                        <i class="ti ti-plus"></i> Create Pull Sheet
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require 'layout.php';
?>
