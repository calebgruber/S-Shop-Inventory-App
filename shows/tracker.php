<?php
require_once '../includes/functions.php';
requirePermission('shows');

// Validate show
$showId = $_GET['id'] ?? null;
if (!$showId) {
    redirect('shows');
}

$show = getShowById($showId);
if (!$show) {
    setAlert('Show not found', 'danger');
    redirect('shows');
}

$pageTitle = 'Show Tracker';
require_once '../includes/header.php';

// Get pullsheet status
$pullsheet = getDB()->fetchOne(
    "SELECT * FROM pullsheets WHERE show_id = ? ORDER BY created_at DESC LIMIT 1",
    [$showId]
);

// Get change orders
$changeOrders = getDB()->fetchAll(
    "SELECT * FROM change_orders WHERE show_id = ? ORDER BY created_at",
    [$showId]
);

// Determine workflow stages
$stages = [
    [
        'id' => 'show_created',
        'title' => 'Show Created',
        'icon' => 'ti-star',
        'status' => 'complete',
        'date' => $show['created_at']
    ],
    [
        'id' => 'pullsheet_created',
        'title' => 'Shop Order Created',
        'icon' => 'ti-file-text',
        'status' => $pullsheet ? 'complete' : 'pending',
        'date' => $pullsheet['created_at'] ?? null
    ],
    [
        'id' => 'pullsheet_finalized',
        'title' => 'Shop Order Finalized',
        'icon' => 'ti-check',
        'status' => ($pullsheet && $pullsheet['status'] !== 'draft') ? 'complete' : 'pending',
        'date' => $pullsheet['finalized_at'] ?? null
    ],
    [
        'id' => 'items_picked',
        'title' => 'Items Picked',
        'icon' => 'ti-scan',
        'status' => ($pullsheet && $pullsheet['status'] === 'picked') ? 'complete' : (($pullsheet && $pullsheet['status'] === 'finalized') ? 'current' : 'pending'),
        'date' => $pullsheet['picked_at'] ?? null
    ],
    [
        'id' => 'show_active',
        'title' => 'Show Active',
        'icon' => 'ti-player-play',
        'status' => ($pullsheet && $pullsheet['status'] === 'picked') ? 'current' : 'pending',
        'date' => null
    ],
    [
        'id' => 'items_returned',
        'title' => 'Items Returned',
        'icon' => 'ti-arrow-back-up',
        'status' => ($pullsheet && $pullsheet['status'] === 'completed') ? 'complete' : 'pending',
        'date' => null
    ]
];
?>

<style>
    .workflow-tracker {
        max-width: 900px;
        margin: 2rem auto;
        padding: 2rem;
    }
    
    .tracker-stage {
        position: relative;
        display: flex;
        align-items: center;
        margin-bottom: 2rem;
    }
    
    .tracker-stage:not(:last-child)::after {
        content: '';
        position: absolute;
        left: 30px;
        top: 60px;
        width: 3px;
        height: calc(100% + 0.5rem);
        background: var(--tblr-border-color);
    }
    
    .tracker-stage.complete::after {
        background: #2fb344;
    }
    
    .tracker-stage.current::after {
        background: linear-gradient(to bottom, #2fb344 0%, var(--tblr-border-color) 100%);
    }
    
    .stage-icon {
        width: 60px;
        height: 60px;
        border-radius: 2px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        background: var(--tblr-bg-surface);
        border: 3px solid var(--tblr-border-color);
        position: relative;
        z-index: 1;
        transition: all 0.3s ease;
    }
    
    .tracker-stage.complete .stage-icon {
        background: #2fb344;
        border-color: #2fb344;
        color: white;
    }
    
    .tracker-stage.current .stage-icon {
        background: #206bc4;
        border-color: #206bc4;
        color: white;
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0%, 100% {
            box-shadow: 0 0 0 0 rgba(32, 107, 196, 0.7);
        }
        50% {
            box-shadow: 0 0 0 10px rgba(32, 107, 196, 0);
        }
    }
    
    .stage-content {
        flex: 1;
        margin-left: 1.5rem;
    }
    
    .stage-title {
        font-size: 1.2rem;
        font-weight: 600;
        margin-bottom: 0.25rem;
    }
    
    .stage-date {
        color: var(--tblr-secondary);
        font-size: 0.875rem;
    }
    
    .stage-badge {
        padding: 0.25rem 0.75rem;
        border-radius: 2px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .badge-complete {
        background: #2fb344;
        color: white;
    }
    
    .badge-current {
        background: #206bc4;
        color: white;
    }
    
    .badge-pending {
        background: var(--tblr-border-color);
        color: var(--tblr-secondary);
    }
    
    .change-orders-section {
        margin-top: 3rem;
        padding-top: 2rem;
        border-top: 2px dashed var(--tblr-border-color);
    }
    
    .co-item {
        padding: 1rem;
        background: var(--tblr-bg-surface);
        border-radius: 2px;
        margin-bottom: 1rem;
        border-left: 3px solid var(--tblr-border-color);
    }
    
    .co-item.processed {
        border-left-color: #2fb344;
    }
</style>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="ti ti-timeline"></i>
                    Show Workflow Tracker - <?php echo htmlspecialchars($show['name']); ?>
                </h3>
                <div class="card-actions">
                    <a href="shows" class="btn btn-secondary">
                        <i class="ti ti-arrow-left"></i> Back to Shows
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="workflow-tracker">
                    <?php foreach ($stages as $stage): ?>
                        <div class="tracker-stage <?php echo $stage['status']; ?>">
                            <div class="stage-icon">
                                <i class="ti <?php echo $stage['icon']; ?>"></i>
                            </div>
                            <div class="stage-content">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="stage-title"><?php echo $stage['title']; ?></div>
                                        <?php if ($stage['date']): ?>
                                            <div class="stage-date">
                                                <?php echo date('M j, Y g:i A', strtotime($stage['date'])); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <span class="stage-badge badge-<?php echo $stage['status']; ?>">
                                            <?php echo ucfirst($stage['status']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if (!empty($changeOrders)): ?>
                    <div class="change-orders-section">
                        <h4 class="mb-3">
                            <i class="ti ti-exchange"></i>
                            Change Orders (<?php echo count($changeOrders); ?>)
                        </h4>
                        <?php foreach ($changeOrders as $co): ?>
                            <div class="co-item <?php echo $co['status'] === 'completed' ? 'processed' : ''; ?>">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <strong>Change Order #<?php echo $co['id']; ?></strong>
                                        <br>
                                        <small class="text-muted">
                                            Created: <?php echo date('M j, Y g:i A', strtotime($co['created_at'])); ?>
                                        </small>
                                        <?php if ($co['status'] === 'finalized'): ?>
                                            <br>
                                            <small class="text-muted">
                                                Finalized: <?php echo date('M j, Y g:i A', strtotime($co['finalized_at'])); ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <span class="badge bg-<?php 
                                            echo $co['status'] === 'completed' ? 'success' : 
                                                ($co['status'] === 'processed' ? 'info' : 
                                                ($co['status'] === 'finalized' ? 'primary' : 'secondary')); 
                                        ?>">
                                            <?php echo ucfirst($co['status']); ?>
                                        </span>
                                        <a href="change_order_edit?id=<?php echo $co['id']; ?>" class="btn btn-sm btn-outline-primary ms-2">
                                            <i class="ti ti-eye"></i> View
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <div class="mt-4 text-center">
                    <p class="text-muted">
                        <small>
                            <i class="ti ti-info-circle"></i>
                            This tracker shows the workflow progress for this show from creation to completion.
                        </small>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
