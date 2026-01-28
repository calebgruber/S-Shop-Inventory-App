<?php
// Handle PDF download BEFORE any output
if (isset($_GET['download_pdf']) && isset($_GET['id'])) {
    require_once __DIR__ . '/includes/functions.php';
    require_once __DIR__ . '/includes/config.php';
    
    $changeOrderId = $_GET['id'];
    $changeOrder = getChangeOrderById($changeOrderId);
    
    if ($changeOrder) {
        $pdf = generateChangeOrderPDF($changeOrderId);
        $showName = $changeOrder['show_name'] ?? 'Unknown';
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="change_order_' . $showName . '.pdf"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
        exit;
    }
}

require_once 'includes/functions.php';

// Check permissions
if (!hasPermission('change_orders')) {
    setAlert('You do not have permission to access change orders', 'danger');
    redirect('index.php');
}

// Handle approval actions (Admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAdmin()) {
    $changeOrderId = $_POST['change_order_id'] ?? null;
    $action = $_POST['approval_action'] ?? null;
    
    if ($changeOrderId && $action) {
        $currentUser = getCurrentUser();
        if ($action === 'approve') {
            if (approveChangeOrder($changeOrderId, $currentUser['id'])) {
                setAlert('Change order approved successfully', 'success');
            } else {
                setAlert('Error approving change order', 'danger');
            }
        } elseif ($action === 'reject') {
            if (rejectChangeOrder($changeOrderId, $currentUser['id'])) {
                setAlert('Change order rejected', 'warning');
            } else {
                setAlert('Error rejecting change order', 'danger');
            }
        }
        redirect('change_order_view.php?id=' . $changeOrderId);
    }
}

$pageTitle = 'View Change Order';
require_once 'includes/header.php';

$changeOrderId = $_GET['id'] ?? null;
if (!$changeOrderId) {
    redirect('change_orders.php');
}

$changeOrder = getChangeOrderById($changeOrderId);
if (!$changeOrder) {
    setAlert('Change order not found', 'danger');
    redirect('change_orders.php');
}

// Handle alert query parameters
if (isset($_GET['finalized'])) {
    setAlert('Change order finalized successfully', 'success');
}

$items = getChangeOrderItems($changeOrderId);
?>

<div class="row mb-3">
    <div class="col">
        <a href="change_orders.php" class="btn btn-secondary">
            <i class="ti ti-arrow-left"></i> Back to Change Orders
        </a>
        <?php if ($changeOrder['status'] === 'finalized' || $changeOrder['status'] === 'processed' || $changeOrder['status'] === 'completed'): ?>
            <a href="?id=<?php echo $changeOrderId; ?>&download_pdf=1" class="btn btn-info">
                <i class="ti ti-download"></i> Download PDF
            </a>
        <?php endif; ?>
        
        <?php if (isAdmin() && $changeOrder['requires_approval'] && $changeOrder['approval_status'] === 'pending'): ?>
            <form method="POST" class="d-inline ms-2">
                <input type="hidden" name="change_order_id" value="<?php echo $changeOrderId; ?>">
                <button type="submit" name="approval_action" value="approve" class="btn btn-success">
                    <i class="ti ti-check"></i> Approve
                </button>
                <button type="submit" name="approval_action" value="reject" class="btn btn-danger" 
                        onclick="return confirm('Are you sure you want to reject this change order?');">
                    <i class="ti ti-x"></i> Reject
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php if ($changeOrder['requires_approval']): ?>
<div class="row mb-3">
    <div class="col">
        <div class="alert alert-<?php 
            echo $changeOrder['approval_status'] === 'approved' ? 'success' : 
                 ($changeOrder['approval_status'] === 'rejected' ? 'danger' : 'warning'); 
        ?>">
            <strong>Approval Status:</strong> <?php echo ucfirst($changeOrder['approval_status']); ?>
            <?php if ($changeOrder['approved_by']): ?>
                <br><small>by Admin at <?php echo date('m/d/Y g:i A', strtotime($changeOrder['approved_at'])); ?></small>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Change Order for <?php echo htmlspecialchars($changeOrder['show_name'] ?? 'Unknown Show'); ?></h3>
            </div>
            <div class="card-body">
                <?php if (($changeOrder['status'] === 'finalized' || $changeOrder['status'] === 'processed' || $changeOrder['status'] === 'completed') && !empty($changeOrder['barcode'])): ?>
                    <div class="mb-4 text-center">
                        <img src="data:image/png;base64,<?php echo base64_encode(generatePDF417Barcode($changeOrder['barcode'])); ?>" 
                             alt="Change Order Barcode" style="max-width: 400px;">
                        <div class="mt-2"><code><?php echo htmlspecialchars($changeOrder['barcode']); ?></code></div>
                    </div>
                <?php endif; ?>
                
                <div class="table-responsive">
                    <table class="table table-vcenter">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Barcode</th>
                                <th>Type</th>
                                <th>Quantity</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                    <td><code><?php echo htmlspecialchars($item['item_barcode']); ?></code></td>
                                    <td>
                                        <span class="badge bg-<?php echo $item['type'] === 'add' ? 'success' : 'danger'; ?>">
                                            <?php echo ucfirst($item['type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo abs($item['quantity_change']); ?></td>
                                    <td>
                                        <?php if ($item['quantity_processed'] >= abs($item['quantity_change'])): ?>
                                            <span class="badge bg-success">Complete</span>
                                        <?php elseif ($item['quantity_processed'] > 0): ?>
                                            <span class="badge bg-info">In Progress (<?php echo $item['quantity_processed']; ?>/<?php echo abs($item['quantity_change']); ?>)</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Details</h3>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <small class="text-muted">Status</small>
                    <div>
                        <?php
                        $badgeClass = [
                            'draft' => 'bg-secondary',
                            'finalized' => 'bg-warning',
                            'processed' => 'bg-info',
                            'completed' => 'bg-success'
                        ][$changeOrder['status']] ?? 'bg-secondary';
                        ?>
                        <span class="badge <?php echo $badgeClass; ?>">
                            <?php echo ucfirst($changeOrder['status']); ?>
                        </span>
                    </div>
                </div>
                
                <div class="mb-3">
                    <small class="text-muted">Created By</small>
                    <div><?php echo htmlspecialchars($changeOrder['created_by'] ?? 'N/A'); ?></div>
                </div>
                
                <div class="mb-3">
                    <small class="text-muted">Created</small>
                    <div><?php echo date('m/d/Y g:i A', strtotime($changeOrder['created_at'])); ?></div>
                </div>
                
                <?php if ($changeOrder['finalized_at']): ?>
                    <div class="mb-3">
                        <small class="text-muted">Finalized</small>
                        <div><?php echo date('m/d/Y g:i A', strtotime($changeOrder['finalized_at'])); ?></div>
                    </div>
                <?php endif; ?>
                
                <?php if ($changeOrder['processed_by']): ?>
                    <div class="mb-3">
                        <small class="text-muted">Processed By</small>
                        <div><?php echo htmlspecialchars($changeOrder['processed_by']); ?></div>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Processed At</small>
                        <div><?php echo date('m/d/Y g:i A', strtotime($changeOrder['processed_at'])); ?></div>
                    </div>
                <?php endif; ?>
                
                <hr>
                
                <div class="mb-3">
                    <small class="text-muted">Show Details</small>
                    <div><strong><?php echo htmlspecialchars($changeOrder['show_name'] ?? 'Unknown Show'); ?></strong></div>
                    <div>Shop Lead: <?php echo htmlspecialchars($changeOrder['shop_lead'] ?? 'N/A'); ?></div>
                    <div>Designer: <?php echo htmlspecialchars($changeOrder['designer'] ?? 'N/A'); ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
