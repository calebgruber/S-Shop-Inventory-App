<?php
$pageTitle = 'Change Orders';
require_once 'includes/header.php';

// Handle archive/unarchive request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['archive_id'])) {
    try {
        $archiveId = (int)$_POST['archive_id'];
        $archiveValue = isset($_POST['unarchive']) ? 0 : 1;
        getDB()->query("UPDATE change_orders SET archived = ? WHERE id = ?", [$archiveValue, $archiveId]);
        setAlert($archiveValue ? 'Change order archived successfully' : 'Change order restored successfully');
        redirect('index.php');
    } catch (Exception $e) {
        setAlert('Error: ' . $e->getMessage(), 'danger');
    }
}

// Handle alert query parameters
if (isset($_GET['saved'])) {
    setAlert('Draft saved successfully', 'success');
}
if (isset($_GET['finalized'])) {
    setAlert('Change order finalized successfully', 'success');
}

// Check if user wants to see archived change orders
$showArchived = isset($_GET['show_archived']) && $_GET['show_archived'] === '1';

// Get change orders based on archived filter
if ($showArchived) {
    $changeOrders = getDB()->fetchAll("SELECT co.*, s.name as show_name 
        FROM change_orders co 
        LEFT JOIN shows s ON co.show_id = s.id 
        WHERE co.archived = 1
        ORDER BY co.created_at DESC");
} else {
    $changeOrders = getDB()->fetchAll("SELECT co.*, s.name as show_name 
        FROM change_orders co 
        LEFT JOIN shows s ON co.show_id = s.id 
        WHERE co.archived = 0
        ORDER BY co.created_at DESC");
}
?>

<div class="row mb-3">
    <div class="col">
        <a href="show_create.php" class="btn btn-primary">
            <i class="ti ti-plus"></i> Create Show First
        </a>
        <?php if ($showArchived): ?>
            <a href="change_orders.php" class="btn btn-secondary">
                <i class="ti ti-eye"></i> Show Active Change Orders
            </a>
        <?php else: ?>
            <a href="change_orders.php?show_archived=1" class="btn btn-secondary">
                <i class="ti ti-archive"></i> Show Archived Change Orders
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="row">
    <?php foreach ($changeOrders as $co): ?>
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><?php echo htmlspecialchars($co['show_name']); ?></h3>
                    <div class="card-actions">
                        <span class="badge bg-<?php echo $co['status'] === 'draft' ? 'secondary' : 'success'; ?>">
                            <?php echo ucfirst($co['status']); ?>
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <small class="text-muted">Created by:</small>
                        <div><?php echo htmlspecialchars($co['created_by'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Created:</small>
                        <div><?php echo date('m/d/Y', strtotime($co['created_at'])); ?></div>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="d-flex gap-2">
                        <a href="change_order_edit.php?id=<?php echo $co['id']; ?>" class="btn btn-sm btn-primary">
                            <i class="ti ti-edit"></i> View/Edit
                        </a>
                        <?php if (!$showArchived): ?>
                            <form method="POST" class="d-inline ms-auto" onsubmit="return confirm('Are you sure you want to archive this change order?');">
                                <input type="hidden" name="archive_id" value="<?php echo $co['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-secondary" title="Archive">
                                    <i class="ti ti-archive"></i>
                                </button>
                            </form>
                        <?php else: ?>
                            <form method="POST" class="d-inline ms-auto" onsubmit="return confirm('Are you sure you want to restore this change order?');">
                                <input type="hidden" name="archive_id" value="<?php echo $co['id']; ?>">
                                <input type="hidden" name="unarchive" value="1">
                                <button type="submit" class="btn btn-sm btn-success" title="Restore">
                                    <i class="ti ti-archive-off"></i>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    
    <?php if (empty($changeOrders)): ?>
        <div class="col-12">
            <div class="empty">
                <p class="empty-title">No change orders yet</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
