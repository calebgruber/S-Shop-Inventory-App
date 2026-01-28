<?php
/**
 * View Pull Sheet
 * Display pull sheet details with items and actions
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/helpers.php';

requireAuth();

$pageName = 'View Pull Sheet';
$currentUser = getCurrentUser();
$userId = $currentUser['id'];
$userRole = $currentUser['role'];

$pullsheetId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$pullsheetId) {
    $_SESSION['error_message'] = 'Pull sheet ID is required.';
    header('Location: index.php');
    exit;
}

// Get pull sheet
$pullsheet = getPullSheetById($pullsheetId);

if (!$pullsheet) {
    $_SESSION['error_message'] = 'Pull sheet not found.';
    header('Location: index.php');
    exit;
}

// Get items
$items = getPullSheetItems($pullsheetId);

// Group items by category
$groupedItems = [];
foreach ($items as $item) {
    $category = $item['category_name'] ?? 'Uncategorized';
    $subcategory = $item['subcategory_name'] ?? 'N/A';
    
    if (!isset($groupedItems[$category])) {
        $groupedItems[$category] = [];
    }
    if (!isset($groupedItems[$category][$subcategory])) {
        $groupedItems[$category][$subcategory] = [];
    }
    $groupedItems[$category][$subcategory][] = $item;
}

// Success/error messages
$successMessage = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);
$errorMessage = $_SESSION['error_message'] ?? '';
unset($_SESSION['error_message']);

// Check permissions
$canEdit = canEditPullSheet($pullsheetId, $userId, $userRole);
$canApprove = $pullsheet['status'] === 'pending_approval' && canApprovePullSheet($userRole);
$canDelete = $pullsheet['status'] === 'draft' && ($userRole === 'admin' || $pullsheet['created_by'] == $userId);
$canDownloadPDF = in_array($pullsheet['status'], ['approved', 'picked', 'returned']);

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="index.php">Pull Sheets</a></li>
                            <li class="breadcrumb-item active"><?php echo htmlspecialchars($pullsheet['barcode']); ?></li>
                        </ol>
                    </nav>
                </div>
                <h2 class="page-title">
                    Pull Sheet: <?php echo htmlspecialchars($pullsheet['barcode']); ?>
                    <span class="ms-2"><?php echo getPullSheetStatusBadge($pullsheet['status']); ?></span>
                </h2>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <?php if ($canEdit): ?>
                <a href="edit.php?id=<?php echo $pullsheetId; ?>" class="btn btn-primary">
                    <i class="ti ti-edit icon"></i>
                    Edit
                </a>
                <?php endif; ?>
                
                <?php if ($canDelete): ?>
                <a href="delete.php?id=<?php echo $pullsheetId; ?>" 
                   class="btn btn-danger"
                   onclick="return confirm('Are you sure you want to delete this pull sheet?');">
                    <i class="ti ti-trash icon"></i>
                    Delete
                </a>
                <?php endif; ?>
                
                <?php if ($canApprove): ?>
                <a href="finalize.php?id=<?php echo $pullsheetId; ?>&action=approve" 
                   class="btn btn-success"
                   onclick="return confirm('Approve this pull sheet? This will reserve the items.');">
                    <i class="ti ti-check icon"></i>
                    Approve
                </a>
                <?php endif; ?>
                
                <?php if ($canDownloadPDF): ?>
                <a href="generate-pdf.php?id=<?php echo $pullsheetId; ?>" class="btn btn-secondary" target="_blank">
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
                <!-- Pull Sheet Information -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h3 class="card-title">Pull Sheet Information</h3>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Barcode</label>
                                <div class="text-monospace fw-bold"><?php echo htmlspecialchars($pullsheet['barcode']); ?></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <div><?php echo getPullSheetStatusBadge($pullsheet['status']); ?></div>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label">Show</label>
                                <div>
                                    <?php if ($pullsheet['show_name']): ?>
                                    <span class="avatar avatar-sm me-2" style="background-color: <?php echo htmlspecialchars($pullsheet['show_color'] ?? '#3b82f6'); ?>"></span>
                                    <a href="<?php echo BASE_URL; ?>shows/view.php?id=<?php echo $pullsheet['show_id']; ?>">
                                        <?php echo htmlspecialchars($pullsheet['show_name']); ?>
                                    </a>
                                    <?php if ($pullsheet['theatre_space_name']): ?>
                                        <span class="text-muted">- <?php echo htmlspecialchars($pullsheet['theatre_space_name']); ?></span>
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
                                    if ($pullsheet['creator_first'] && $pullsheet['creator_last']) {
                                        echo htmlspecialchars($pullsheet['creator_first'] . ' ' . $pullsheet['creator_last']);
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Created Date</label>
                                <div><?php echo date('M d, Y g:i A', strtotime($pullsheet['created_at'])); ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Items List -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Items (<?php echo count($items); ?>)</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($items)): ?>
                        <div class="empty">
                            <div class="empty-icon">
                                <i class="ti ti-package icon"></i>
                            </div>
                            <p class="empty-title">No items</p>
                            <p class="empty-subtitle text-muted">This pull sheet has no items.</p>
                        </div>
                        <?php else: ?>
                            <?php foreach ($groupedItems as $category => $subcategories): ?>
                            <div class="mb-4">
                                <h4 class="mb-3"><?php echo htmlspecialchars($category); ?></h4>
                                <?php foreach ($subcategories as $subcategory => $categoryItems): ?>
                                <div class="mb-3">
                                    <h5 class="text-muted mb-2"><?php echo htmlspecialchars($subcategory); ?></h5>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Item</th>
                                                    <th>Barcode</th>
                                                    <th>Location</th>
                                                    <th>Qty Needed</th>
                                                    <th>In Stock</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($categoryItems as $item): ?>
                                                <tr>
                                                    <td>
                                                        <a href="<?php echo BASE_URL; ?>inventory/view.php?id=<?php echo $item['item_id']; ?>">
                                                            <?php echo htmlspecialchars($item['item_name']); ?>
                                                        </a>
                                                    </td>
                                                    <td class="text-monospace"><?php echo htmlspecialchars($item['item_barcode']); ?></td>
                                                    <td><?php echo htmlspecialchars($item['location'] ?? 'N/A'); ?></td>
                                                    <td><?php echo $item['quantity_needed']; ?></td>
                                                    <td><?php echo $item['in_stock_quantity']; ?></td>
                                                    <td>
                                                        <?php if ($item['quantity_needed'] <= $item['in_stock_quantity']): ?>
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
                                <?php endforeach; ?>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
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
                            <li class="step-item <?php echo !empty($pullsheet['created_at']) ? 'active' : ''; ?>">
                                <div class="h4 m-0">Created</div>
                                <?php if ($pullsheet['created_at']): ?>
                                <div class="text-muted small">
                                    <?php echo date('M d, Y g:i A', strtotime($pullsheet['created_at'])); ?>
                                </div>
                                <div class="text-muted small">
                                    by <?php 
                                    if ($pullsheet['creator_first'] && $pullsheet['creator_last']) {
                                        echo htmlspecialchars($pullsheet['creator_first'] . ' ' . $pullsheet['creator_last']);
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </div>
                                <?php endif; ?>
                            </li>
                            
                            <?php if ($pullsheet['status'] !== 'draft'): ?>
                            <li class="step-item <?php echo in_array($pullsheet['status'], ['pending_approval', 'approved', 'picked', 'returned']) ? 'active' : ''; ?>">
                                <div class="h4 m-0">Submitted</div>
                                <?php if ($pullsheet['created_at']): ?>
                                <div class="text-muted small">
                                    <?php echo date('M d, Y g:i A', strtotime($pullsheet['created_at'])); ?>
                                </div>
                                <?php endif; ?>
                            </li>
                            <?php endif; ?>
                            
                            <?php if (in_array($pullsheet['status'], ['approved', 'picked', 'returned'])): ?>
                            <li class="step-item active">
                                <div class="h4 m-0">Approved</div>
                                <?php if ($pullsheet['approved_at']): ?>
                                <div class="text-muted small">
                                    <?php echo date('M d, Y g:i A', strtotime($pullsheet['approved_at'])); ?>
                                </div>
                                <div class="text-muted small">
                                    by <?php 
                                    if ($pullsheet['approver_first'] && $pullsheet['approver_last']) {
                                        echo htmlspecialchars($pullsheet['approver_first'] . ' ' . $pullsheet['approver_last']);
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </div>
                                <?php endif; ?>
                            </li>
                            <?php endif; ?>
                            
                            <?php if (in_array($pullsheet['status'], ['picked', 'returned'])): ?>
                            <li class="step-item active">
                                <div class="h4 m-0">Picked</div>
                                <?php if ($pullsheet['picked_at']): ?>
                                <div class="text-muted small">
                                    <?php echo date('M d, Y g:i A', strtotime($pullsheet['picked_at'])); ?>
                                </div>
                                <div class="text-muted small">
                                    by <?php 
                                    if ($pullsheet['picker_first'] && $pullsheet['picker_last']) {
                                        echo htmlspecialchars($pullsheet['picker_first'] . ' ' . $pullsheet['picker_last']);
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </div>
                                <?php endif; ?>
                            </li>
                            <?php endif; ?>
                            
                            <?php if ($pullsheet['status'] === 'returned'): ?>
                            <li class="step-item active">
                                <div class="h4 m-0">Returned</div>
                                <?php if ($pullsheet['returned_at']): ?>
                                <div class="text-muted small">
                                    <?php echo date('M d, Y g:i A', strtotime($pullsheet['returned_at'])); ?>
                                </div>
                                <?php endif; ?>
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
                            <div class="text-muted small">Total Items</div>
                            <div class="h3 mb-0"><?php echo count($items); ?></div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Total Quantity</div>
                            <div class="h3 mb-0">
                                <?php 
                                $totalQty = 0;
                                foreach ($items as $item) {
                                    $totalQty += $item['quantity_needed'];
                                }
                                echo $totalQty;
                                ?>
                            </div>
                        </div>
                        <div>
                            <div class="text-muted small">Items with Insufficient Stock</div>
                            <div class="h3 mb-0">
                                <?php 
                                $insufficientCount = 0;
                                foreach ($items as $item) {
                                    if ($item['quantity_needed'] > $item['in_stock_quantity']) {
                                        $insufficientCount++;
                                    }
                                }
                                echo $insufficientCount;
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
