<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/helpers.php';

requireAuth();

// Allow GET for confirmation, POST for actual finalization
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
        $_SESSION['error_message'] = 'Invalid change order ID';
        header('Location: index.php');
        exit;
    }
    
    $changeOrderId = (int)$_POST['id'];
    $action = $_POST['action'] ?? 'finalize';
} else {
    // GET request - show confirmation
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        $_SESSION['error_message'] = 'Invalid change order ID';
        header('Location: index.php');
        exit;
    }
    
    $changeOrderId = (int)$_GET['id'];
    $action = $_GET['action'] ?? 'finalize';
}

$changeOrder = getChangeOrderById($changeOrderId);

if (!$changeOrder) {
    $_SESSION['error_message'] = 'Change order not found';
    header('Location: index.php');
    exit;
}

$userId = $_SESSION['user']['id'];
$userRole = $_SESSION['user']['role'];

// Only admins can finalize
if (!canFinalizeChangeOrder($userRole)) {
    $_SESSION['error_message'] = 'Only administrators can finalize change orders';
    header('Location: view.php?id=' . $changeOrderId);
    exit;
}

// Must be pending approval or draft
if ($changeOrder['status'] !== 'pending_approval' && $changeOrder['status'] !== 'draft') {
    $_SESSION['error_message'] = 'This change order cannot be finalized';
    header('Location: view.php?id=' . $changeOrderId);
    exit;
}

// If GET request, show confirmation page
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $pageName = 'Finalize Change Order';
    include dirname(__DIR__) . '/includes/header.php';
    ?>
    
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">Finalize Change Order</h2>
                </div>
            </div>
        </div>
    </div>
    
    <div class="page-body">
        <div class="container-xl">
            <div class="card">
                <div class="card-body">
                    <h3>Confirm Finalization</h3>
                    <p>Are you sure you want to finalize change order <strong><?php echo htmlspecialchars($changeOrder['barcode']); ?></strong> for show <strong><?php echo htmlspecialchars($changeOrder['show_name']); ?></strong>?</p>
                    <p class="text-muted">This will apply the stock changes and cannot be undone.</p>
                    
                    <?php
                    // Check stock availability for remove actions
                    $validation = validateChangeOrderStock($changeOrderId);
                    if (!$validation['valid']): ?>
                    <div class="alert alert-warning">
                        <h4 class="alert-title">Insufficient Stock Warning</h4>
                        <p>The following items have insufficient stock for removal:</p>
                        <ul>
                            <?php foreach ($validation['errors'] as $err): ?>
                            <li><?php echo htmlspecialchars($err['item_name']); ?> - Need: <?php echo $err['needed']; ?>, Available: <?php echo $err['available']; ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <p>You cannot finalize this change order until stock is available.</p>
                    </div>
                    <div class="d-flex">
                        <a href="view.php?id=<?php echo $changeOrderId; ?>" class="btn btn-secondary">Back to Change Order</a>
                    </div>
                    <?php else: ?>
                    <form method="POST">
                        <input type="hidden" name="id" value="<?php echo $changeOrderId; ?>">
                        <input type="hidden" name="action" value="finalize">
                        <div class="d-flex">
                            <a href="view.php?id=<?php echo $changeOrderId; ?>" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-success ms-auto">Finalize Change Order</button>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php
    include dirname(__DIR__) . '/includes/footer.php';
    exit;
}

// POST request - actually finalize
// Validate stock availability for remove actions
$validation = validateChangeOrderStock($changeOrderId);
if (!$validation['valid']) {
    $errorMsg = 'Insufficient stock for the following items: ';
    $errorItems = [];
    foreach ($validation['errors'] as $err) {
        $errorItems[] = $err['item_name'] . ' (need ' . $err['needed'] . ', have ' . $err['available'] . ')';
    }
    $errorMsg .= implode(', ', $errorItems);
    $_SESSION['error_message'] = $errorMsg;
    header('Location: view.php?id=' . $changeOrderId);
    exit;
}

// Apply stock changes
if (!applyChangeOrderStockChanges($changeOrderId)) {
    $_SESSION['error_message'] = 'Failed to apply stock changes';
    header('Location: view.php?id=' . $changeOrderId);
    exit;
}

// Update change order status
$finalizedAt = date('Y-m-d H:i:s');
$query = "UPDATE change_orders 
          SET status = 'finalized', 
              finalized_by = ?,
              finalized_at = ?
          WHERE id = ?";
$result = executeQuery($query, [$userId, $finalizedAt, $changeOrderId], 'isi');

if ($result) {
    $_SESSION['success_message'] = 'Change order finalized successfully';
} else {
    $_SESSION['error_message'] = 'Failed to finalize change order';
}
header('Location: view.php?id=' . $changeOrderId);
exit;
