<?php
/**
 * View Change Order
 * Display change order details with items and actions
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/helpers.php';

requireAuth();

$pageName = 'View Change Order';
$currentUser = getCurrentUser();
$userId = $currentUser['id'];
$userRole = $currentUser['role'];

$changeOrderId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$changeOrderId) {
    $_SESSION['error_message'] = 'Change order ID is required.';
    header('Location: index.php');
    exit;
}

// Get change order
$changeOrder = getChangeOrderById($changeOrderId);

if (!$changeOrder) {
    $_SESSION['error_message'] = 'Change order not found.';
    header('Location: index.php');
    exit;
}

// Get items
$items = getChangeOrderItems($changeOrderId);

// Separate items by action
$itemsToAdd = [];
$itemsToRemove = [];
foreach ($items as $item) {
    if ($item['action'] === 'add') {
        $itemsToAdd[] = $item;
    } else {
        $itemsToRemove[] = $item;
    }
}

// Success/error messages
$successMessage = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);
$errorMessage = $_SESSION['error_message'] ?? '';
unset($_SESSION['error_message']);

// Check permissions
$canEdit = canEditChangeOrder($changeOrderId, $userId, $userRole);
$canFinalize = $changeOrder['status'] === 'pending_approval' && canFinalizeChangeOrder($userRole);
$canDelete = $changeOrder['status'] === 'draft' && ($userRole === 'admin' || $changeOrder['created_by'] == $userId);
$canDownloadPDF = in_array($changeOrder['status'], ['approved', 'picked', 'returned']);

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="index.php">Change Orders</a></li>
                            <li class="breadcrumb-item active"><?php echo htmlspecialchars($changeOrder['barcode']); ?></li>
                        </ol>
                    </nav>
                </div>
                <h2 class="page-title">
                    Change Order: <?php echo htmlspecialchars($changeOrder['barcode']); ?>
                    <span class="ms-2"><?php echo getChangeOrderStatusBadge($changeOrder['status']); ?></span>
                </h2>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <?php if ($canEdit): ?>
                <a href="edit.php?id=<?php echo $changeOrderId; ?>" class="btn btn-primary">
                    <i class="ti ti-edit icon"></i>
                    Edit
                </a>
                <?php endif; ?>
                
                <?php if ($canDelete): ?>
                <a href="delete.php?id=<?php echo $changeOrderId; ?>" 
                   class="btn btn-danger"
                   onclick="return confirm('Are you sure you want to delete this change order?');">
                    <i class="ti ti-trash icon"></i>
                    Delete
                </a>
                <?php endif; ?>
                
                <?php if ($canFinalize): ?>
                <a href="finalize.php?id=<?php echo $changeOrderId; ?>&action=approve" 
                   class="btn btn-success"
                   onclick="return confirm('Approve this change order? This will apply the stock changes.');">
                    <i class="ti ti-check icon"></i>
                    Approve
                </a>
                <?php endif; ?>
                
                <?php if ($canDownloadPDF): ?>
                <a href="generate-pdf.php?id=<?php echo $changeOrderId; ?>" class="btn btn-secondary" target="_blank">
                    <i class="ti ti-download icon"></i>
                    Download PDF
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <?php if ($successMessage): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <div class="d-flex">
                <div><i class="ti ti-check icon me-2"></i></div>
                <div><?php echo htmlspecialchars($successMessage); ?></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <div class="d-flex">
                <div><i class="ti ti-alert-circle icon me-2"></i></div>
                <div><?php echo htmlspecialchars($errorMessage); ?></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-8">
                <!-- Change Order Information -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h3 class="card-title">Change Order Information</h3>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Barcode</label>
                                <div class="text-monospace fw-bold"><?php echo htmlspecialchars($changeOrder['barcode']); ?></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <div><?php echo getChangeOrderStatusBadge($changeOrder['status']); ?></div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label">Show</label>
                                <div>
                                    <?php if ($changeOrder['show_name']): ?>
                                    <span class="avatar avatar-sm me-2" style="background-color: <?php echo htmlspecialchars($changeOrder['show_color'] ?? '#3b82f6'); ?>"></span>
                                    <a href="<?php echo BASE_URL; ?>shows/view.php?id=<?php echo $changeOrder['show_id']; ?>">
                                        <?php echo htmlspecialchars($changeOrder['show_name']); ?>
                                    </a>
                                    <?php if ($changeOrder['theatre_space_name']): ?>
                                        <span class="text-muted">- <?php echo htmlspecialchars($changeOrder['theatre_space_name']); ?></span>
                                    <?php endif; ?>
                                    <?php else: ?>
                                    <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Created By</label>
                                <div>
                                    <?php 
                                    if ($changeOrder['creator_first'] && $changeOrder['creator_last']) {
                                        echo htmlspecialchars($changeOrder['creator_first'] . ' ' . $changeOrder['creator_last']);
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Created Date</label>
                                <div><?php echo date('M d, Y g:i A', strtotime($changeOrder['created_at'])); ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Items to Add -->
                <?php if (!empty($itemsToAdd)): ?>
                <div class="card mb-3">
                    <div class="card-header bg-success-lt">
                        <h3 class="card-title">
                            <i class="ti ti-plus icon me-2"></i>
                            Items to Add (<?php echo count($itemsToAdd); ?>)
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead>
                                    <tr>
                                        <th>Action</th>
                                        <th>Item</th>
                                        <th>Barcode</th>
                                        <th>Location</th>
                                        <th>Quantity</th>
                                        <th>Current Stock</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($itemsToAdd as $item): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-success">
                                                <i class="ti ti-plus icon"></i>
                                                Add
                                            </span>
                                        </td>
                                        <td>
                                            <a href="<?php echo BASE_URL; ?>inventory/view.php?id=<?php echo $item['item_id']; ?>">
                                                <?php echo htmlspecialchars($item['item_name']); ?>
                                            </a>
                                        </td>
                                        <td class="text-monospace"><?php echo htmlspecialchars($item['item_barcode']); ?></td>
                                        <td><?php echo htmlspecialchars($item['location'] ?? 'N/A'); ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td><?php echo $item['in_stock_quantity']; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Items to Remove -->
                <?php if (!empty($itemsToRemove)): ?>
                <div class="card mb-3">
                    <div class="card-header bg-danger-lt">
                        <h3 class="card-title">
                            <i class="ti ti-minus icon me-2"></i>
                            Items to Remove (<?php echo count($itemsToRemove); ?>)
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead>
                                    <tr>
                                        <th>Action</th>
                                        <th>Item</th>
                                        <th>Barcode</th>
                                        <th>Location</th>
                                        <th>Quantity</th>
                                        <th>Current Stock</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($itemsToRemove as $item): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-danger">
                                                <i class="ti ti-minus icon"></i>
                                                Remove
                                            </span>
                                        </td>
                                        <td>
                                            <a href="<?php echo BASE_URL; ?>inventory/view.php?id=<?php echo $item['item_id']; ?>">
                                                <?php echo htmlspecialchars($item['item_name']); ?>
                                            </a>
                                        </td>
                                        <td class="text-monospace"><?php echo htmlspecialchars($item['item_barcode']); ?></td>
                                        <td><?php echo htmlspecialchars($item['location'] ?? 'N/A'); ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td><?php echo $item['in_stock_quantity']; ?></td>
                                        <td>
                                            <?php if ($item['quantity'] <= $item['in_stock_quantity']): ?>
                                            <span class="badge bg-success">Available</span>
                                            <?php else: ?>
                                            <span class="badge bg-warning">Insufficient</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (empty($items)): ?>
                <div class="card">
                    <div class="card-body">
                        <div class="empty">
                            <div class="empty-icon">
                                <i class="ti ti-package icon"></i>
                            </div>
                            <p class="empty-title">No items</p>
                            <p class="empty-subtitle text-muted">This change order has no items.</p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="col-md-4">
                <!-- Status Timeline -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ti ti-timeline icon me-2"></i>
                            Timeline
                        </h3>
                    </div>
                    <div class="card-body">
                        <ul class="steps steps-vertical">
                            <li class="step-item <?php echo !empty($changeOrder['created_at']) ? 'active' : ''; ?>">
                                <div class="h4 m-0">Created</div>
                                <?php if ($changeOrder['created_at']): ?>
                                <div class="text-muted small">
                                    <?php echo date('M d, Y g:i A', strtotime($changeOrder['created_at'])); ?>
                                </div>
                                <div class="text-muted small">
                                    by <?php 
                                    if ($changeOrder['creator_first'] && $changeOrder['creator_last']) {
                                        echo htmlspecialchars($changeOrder['creator_first'] . ' ' . $changeOrder['creator_last']);
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </div>
                                <?php endif; ?>
                            </li>
                            
                            <?php if ($changeOrder['status'] !== 'draft'): ?>
                            <li class="step-item <?php echo in_array($changeOrder['status'], ['pending_approval', 'approved', 'picked', 'returned']) ? 'active' : ''; ?>">
                                <div class="h4 m-0">Submitted</div>
                                <?php if ($changeOrder['created_at']): ?>
                                <div class="text-muted small">
                                    <?php echo date('M d, Y g:i A', strtotime($changeOrder['created_at'])); ?>
                                </div>
                                <?php endif; ?>
                            </li>
                            <?php endif; ?>
                            
                            <?php if (in_array($changeOrder['status'], ['approved', 'picked', 'returned'])): ?>
                            <li class="step-item active">
                                <div class="h4 m-0">Approved</div>
                                <div class="text-muted small">
                                    <?php echo date('M d, Y g:i A', strtotime($changeOrder['updated_at'])); ?>
                                </div>
                                <div class="text-muted small">
                                    by <?php 
                                    if ($changeOrder['approver_first'] && $changeOrder['approver_last']) {
                                        echo htmlspecialchars($changeOrder['approver_first'] . ' ' . $changeOrder['approver_last']);
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </div>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ti ti-info-circle icon me-2"></i>
                            Quick Stats
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="text-muted small">Items to Add</div>
                            <div class="h3 mb-0 text-success"><?php echo count($itemsToAdd); ?></div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Items to Remove</div>
                            <div class="h3 mb-0 text-danger"><?php echo count($itemsToRemove); ?></div>
                        </div>
                        <div>
                            <div class="text-muted small">Total Items</div>
                            <div class="h3 mb-0"><?php echo count($items); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
