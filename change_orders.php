<?php
$pageTitle = 'Change Orders';
require_once 'includes/header.php';

$changeOrders = getAllChangeOrders();
?>

<div class="row mb-3">
    <div class="col">
        <a href="show_create.php" class="btn btn-primary">
            <i class="ti ti-plus"></i> Create Show First
        </a>
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
                    <a href="change_order_edit.php?id=<?php echo $co['id']; ?>" class="btn btn-sm btn-primary">
                        <i class="ti ti-edit"></i> View/Edit
                    </a>
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
