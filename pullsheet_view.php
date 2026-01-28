<?php
// Handle PDF download BEFORE any output
if (isset($_GET['download_pdf']) && isset($_GET['id'])) {
    require_once __DIR__ . '/includes/functions.php';
    require_once __DIR__ . '/includes/config.php';
    
    $pullsheetId = $_GET['id'];
    $pullsheet = getPullsheetById($pullsheetId);
    
    if ($pullsheet) {
        $pdf = generatePullsheetPDF($pullsheetId);
        $showName = $pullsheet['show_name'] ?? 'Student_Requests';
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="pullsheet_' . $showName . '.pdf"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
        exit;
    }
}

require_once 'includes/functions.php';

// Check permissions
if (!hasPermission('pullsheets')) {
    setAlert('You do not have permission to access pullsheets', 'danger');
    redirect('index.php');
}

// Handle approval actions (Admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAdmin()) {
    $pullsheetId = $_POST['pullsheet_id'] ?? null;
    $action = $_POST['approval_action'] ?? null;
    
    if ($pullsheetId && $action) {
        $currentUser = getCurrentUser();
        if ($action === 'approve') {
            if (approvePullsheet($pullsheetId, $currentUser['id'])) {
                setAlert('Pullsheet approved successfully', 'success');
            } else {
                setAlert('Error approving pullsheet', 'danger');
            }
        } elseif ($action === 'reject') {
            if (rejectPullsheet($pullsheetId, $currentUser['id'])) {
                setAlert('Pullsheet rejected', 'warning');
            } else {
                setAlert('Error rejecting pullsheet', 'danger');
            }
        }
        redirect('pullsheet_view.php?id=' . $pullsheetId);
    }
}

$pageTitle = 'View Pullsheet';
require_once 'includes/header.php';

$pullsheetId = $_GET['id'] ?? null;
if (!$pullsheetId) {
    redirect('pullsheets.php');
}

$pullsheet = getPullsheetById($pullsheetId);
if (!$pullsheet) {
    setAlert('Pullsheet not found', 'danger');
    redirect('pullsheets.php');
}

// Handle alert query parameters
if (isset($_GET['finalized'])) {
    setAlert('Pullsheet finalized successfully', 'success');
}

$items = getPullsheetItems($pullsheetId);
?>

<div class="row mb-3">
    <div class="col">
        <a href="pullsheets.php" class="btn btn-secondary">
            <i class="ti ti-arrow-left"></i> Back to Pullsheets
        </a>
        <?php if ($pullsheet['status'] === 'finalized'): ?>
            <a href="?id=<?php echo $pullsheetId; ?>&download_pdf=1" class="btn btn-info">
                <i class="ti ti-download"></i> Download PDF
            </a>
        <?php endif; ?>
        
        <?php if (isAdmin() && $pullsheet['requires_approval'] && $pullsheet['approval_status'] === 'pending'): ?>
            <form method="POST" class="d-inline ms-2">
                <input type="hidden" name="pullsheet_id" value="<?php echo $pullsheetId; ?>">
                <button type="submit" name="approval_action" value="approve" class="btn btn-success">
                    <i class="ti ti-check"></i> Approve
                </button>
                <button type="submit" name="approval_action" value="reject" class="btn btn-danger" 
                        onclick="return confirm('Are you sure you want to reject this pullsheet?');">
                    <i class="ti ti-x"></i> Reject
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php if ($pullsheet['requires_approval']): ?>
<div class="row mb-3">
    <div class="col">
        <div class="alert alert-<?php 
            echo $pullsheet['approval_status'] === 'approved' ? 'success' : 
                 ($pullsheet['approval_status'] === 'rejected' ? 'danger' : 'warning'); 
        ?>">
            <strong>Approval Status:</strong> <?php echo ucfirst($pullsheet['approval_status']); ?>
            <?php if ($pullsheet['approved_by']): ?>
                <br><small>by Admin at <?php echo date('m/d/Y g:i A', strtotime($pullsheet['approved_at'])); ?></small>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Pullsheet for <?php echo htmlspecialchars($pullsheet['show_name'] ?? 'Student Requests'); ?></h3>
            </div>
            <div class="card-body">
                <?php if ($pullsheet['status'] === 'finalized' || $pullsheet['status'] === 'picked'): ?>
                    <div class="mb-4 text-center">
                        <img src="data:image/png;base64,<?php echo base64_encode(generatePDF417Barcode($pullsheet['barcode'])); ?>" 
                             alt="Pullsheet Barcode" style="max-width: 400px;">
                        <div class="mt-2"><code><?php echo htmlspecialchars($pullsheet['barcode']); ?></code></div>
                    </div>
                <?php endif; ?>
                
                <div class="table-responsive">
                    <table class="table table-vcenter">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Barcode</th>
                                <th>Qty Needed</th>
                                <th>Qty Picked</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                    <td><code><?php echo htmlspecialchars($item['item_barcode']); ?></code></td>
                                    <td><?php echo $item['quantity_needed']; ?></td>
                                    <td><?php echo $item['quantity_picked']; ?></td>
                                    <td>
                                        <?php if ($item['quantity_picked'] >= $item['quantity_needed']): ?>
                                            <span class="badge bg-success">Complete</span>
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
                            'picked' => 'bg-info',
                            'completed' => 'bg-success'
                        ][$pullsheet['status']] ?? 'bg-secondary';
                        ?>
                        <span class="badge <?php echo $badgeClass; ?>">
                            <?php echo ucfirst($pullsheet['status']); ?>
                        </span>
                    </div>
                </div>
                
                <div class="mb-3">
                    <small class="text-muted">Created By</small>
                    <div><?php echo htmlspecialchars($pullsheet['created_by'] ?? 'N/A'); ?></div>
                </div>
                
                <div class="mb-3">
                    <small class="text-muted">Created</small>
                    <div><?php echo date('m/d/Y g:i A', strtotime($pullsheet['created_at'])); ?></div>
                </div>
                
                <?php if ($pullsheet['picked_by']): ?>
                    <div class="mb-3">
                        <small class="text-muted">Picked By</small>
                        <div><?php echo htmlspecialchars($pullsheet['picked_by']); ?></div>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Picked At</small>
                        <div><?php echo date('m/d/Y g:i A', strtotime($pullsheet['picked_at'])); ?></div>
                    </div>
                <?php endif; ?>
                
                <hr>
                
                <div class="mb-3">
                    <small class="text-muted">Show Details</small>
                    <div><strong><?php echo htmlspecialchars($pullsheet['show_name'] ?? 'Student Requests'); ?></strong></div>
                    <div>Shop Lead: <?php echo htmlspecialchars($pullsheet['shop_lead'] ?? 'N/A'); ?></div>
                    <div>Designer: <?php echo htmlspecialchars($pullsheet['designer'] ?? 'N/A'); ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
