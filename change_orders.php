<?php
require_once 'config.php';
require_once 'db.php';
require_once 'functions.php';

$db = getDB();

// Get all change orders with show names
$sql = "SELECT co.*, s.name as show_name 
        FROM change_orders co 
        INNER JOIN shows s ON co.show_id = s.id 
        ORDER BY co.created_at DESC";
$change_orders = $db->query($sql);

$pageTitle = "Change Orders - " . APP_NAME;
$pageHeader = "Change Orders";
$pageActions = '<a href="change_order_create.php" class="btn btn-primary"><i class="ti ti-plus"></i> Create Change Order</a>';

ob_start();
?>

<div class="row row-cards">
    <?php if ($change_orders && $change_orders->num_rows > 0): ?>
        <?php while ($co = $change_orders->fetch_assoc()): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card">
                <div class="card-status-top bg-<?php 
                    echo $co['status'] === 'finalized' ? 'blue' : 
                        ($co['status'] === 'picked' ? 'green' : 
                        ($co['status'] === 'completed' ? 'purple' : 'gray')); 
                ?>"></div>
                <div class="card-body">
                    <h3 class="card-title"><?php echo sanitize($co['barcode']); ?></h3>
                    <div class="text-muted mb-2">
                        <i class="ti ti-theater"></i> <?php echo sanitize($co['show_name']); ?>
                    </div>
                    <div class="mb-2">
                        <span class="badge badge-outline text-<?php 
                            echo $co['status'] === 'finalized' ? 'blue' : 
                                ($co['status'] === 'picked' ? 'green' : 
                                ($co['status'] === 'completed' ? 'purple' : 'gray')); 
                        ?>">
                            <?php echo ucfirst($co['status']); ?>
                        </span>
                    </div>
                    <?php if ($co['created_by']): ?>
                    <div class="text-muted mb-1">
                        <i class="ti ti-user"></i> <?php echo sanitize($co['created_by']); ?>
                    </div>
                    <?php endif; ?>
                    <div class="text-muted mt-2">
                        <small>Created: <?php echo formatDate($co['created_at']); ?></small>
                    </div>
                    <?php if ($co['finalized_at']): ?>
                    <div class="text-muted">
                        <small>Finalized: <?php echo formatDate($co['finalized_at']); ?></small>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer">
                    <div class="d-flex gap-2">
                        <a href="change_order_view.php?id=<?php echo $co['id']; ?>" class="btn btn-primary btn-sm flex-fill">
                            View
                        </a>
                        <?php if ($co['status'] === 'draft'): ?>
                        <a href="change_order_create.php?id=<?php echo $co['id']; ?>" class="btn btn-secondary btn-sm">
                            Edit
                        </a>
                        <?php endif; ?>
                        <?php if ($co['status'] !== 'draft'): ?>
                        <a href="pdf_generator.php?type=change_order&id=<?php echo $co['id']; ?>" class="btn btn-success btn-sm" target="_blank">
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
                    <i class="ti ti-repeat icon"></i>
                </div>
                <p class="empty-title">No change orders found</p>
                <p class="empty-subtitle text-muted">
                    Get started by creating your first change order
                </p>
                <div class="empty-action">
                    <a href="change_order_create.php" class="btn btn-primary">
                        <i class="ti ti-plus"></i> Create Change Order
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
