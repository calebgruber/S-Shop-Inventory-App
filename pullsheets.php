<?php
$pageTitle = 'Pullsheets';
require_once 'includes/header.php';

// Handle alert query parameters
if (isset($_GET['saved'])) {
    setAlert('Draft saved successfully', 'success');
}
if (isset($_GET['finalized'])) {
    setAlert('Pullsheet finalized successfully', 'success');
}

$pullsheets = getAllPullsheets();
?>

<div class="row mb-3">
    <div class="col">
        <a href="show_create.php" class="btn btn-primary">
            <i class="ti ti-plus"></i> Create New Show First
        </a>
    </div>
</div>

<div class="row">
    <?php foreach ($pullsheets as $pullsheet): ?>
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><?php echo htmlspecialchars($pullsheet['show_name']); ?></h3>
                    <div class="card-actions">
                        <?php
                        $badgeClass = [
                            'draft' => 'bg-secondary',
                            'finalized' => 'bg-warning',
                            'picked' => 'bg-info',
                            'completed' => 'bg-success'
                        ][$pullsheet['status']] ?? 'bg-secondary';
                        ?>
                        <span class="badge <?php echo $badgeClass; ?>">
                            <?php echo ucfirst($pullsheet['status']); ?>
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <small class="text-muted">Created by:</small>
                        <div><?php echo htmlspecialchars($pullsheet['created_by'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Created:</small>
                        <div><?php echo date('m/d/Y g:i A', strtotime($pullsheet['created_at'])); ?></div>
                    </div>
                    <?php if ($pullsheet['picked_by']): ?>
                        <div class="mb-2">
                            <small class="text-muted">Picked by:</small>
                            <div><?php echo htmlspecialchars($pullsheet['picked_by']); ?></div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer">
                    <div class="d-flex gap-2">
                        <a href="pullsheet_view.php?id=<?php echo $pullsheet['id']; ?>" class="btn btn-sm btn-primary">
                            <i class="ti ti-eye"></i> View
                        </a>
                        <?php if ($pullsheet['status'] === 'draft'): ?>
                            <a href="pullsheet_edit.php?id=<?php echo $pullsheet['id']; ?>" class="btn btn-sm btn-info">
                                <i class="ti ti-edit"></i> Edit
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    
    <?php if (empty($pullsheets)): ?>
        <div class="col-12">
            <div class="empty">
                <div class="empty-icon">
                    <i class="ti ti-file-text icon"></i>
                </div>
                <p class="empty-title">No pullsheets yet</p>
                <p class="empty-subtitle text-muted">Create a show first, then create a pullsheet for it</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
