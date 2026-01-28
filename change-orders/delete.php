<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/helpers.php';

requireAuth();

// Allow GET for confirmation, POST for actual deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
        $_SESSION['error_message'] = 'Invalid change order ID';
        header('Location: index.php');
        exit;
    }
    
    $changeOrderId = (int)$_POST['id'];
} else {
    // GET request - show confirmation
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        $_SESSION['error_message'] = 'Invalid change order ID';
        header('Location: index.php');
        exit;
    }
    
    $changeOrderId = (int)$_GET['id'];
}

$changeOrder = getChangeOrderById($changeOrderId);

if (!$changeOrder) {
    $_SESSION['error_message'] = 'Change order not found';
    header('Location: index.php');
    exit;
}

$userId = $_SESSION['user']['id'];
$userRole = $_SESSION['user']['role'];

// Check permissions: only owner or admin can delete drafts
if ($changeOrder['status'] !== 'draft') {
    $_SESSION['error_message'] = 'Only draft change orders can be deleted';
    header('Location: view.php?id=' . $changeOrderId);
    exit;
}

if ($userRole !== 'admin' && $changeOrder['created_by'] != $userId) {
    $_SESSION['error_message'] = 'You do not have permission to delete this change order';
    header('Location: view.php?id=' . $changeOrderId);
    exit;
}

// If GET request, show confirmation page
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $pageName = 'Delete Change Order';
    include dirname(__DIR__) . '/includes/header.php';
    ?>
    
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">Delete Change Order</h2>
                </div>
            </div>
        </div>
    </div>
    
    <div class="page-body">
        <div class="container-xl">
            <div class="card">
                <div class="card-body">
                    <h3>Confirm Deletion</h3>
                    <p>Are you sure you want to delete change order <strong><?php echo htmlspecialchars($changeOrder['barcode']); ?></strong>?</p>
                    <p class="text-muted">This action cannot be undone.</p>
                    
                    <form method="POST">
                        <input type="hidden" name="id" value="<?php echo $changeOrderId; ?>">
                        <div class="d-flex">
                            <a href="view.php?id=<?php echo $changeOrderId; ?>" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-danger ms-auto">Delete Change Order</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <?php
    include dirname(__DIR__) . '/includes/footer.php';
    exit;
}

// POST request - actually delete
// Delete the change order (cascade will delete items)
$query = "DELETE FROM change_orders WHERE id = ?";
$result = executeQuery($query, [$changeOrderId], 'i');

if ($result) {
    // Delete PDF if exists
    if (!empty($changeOrder['pdf_path']) && file_exists($changeOrder['pdf_path'])) {
        @unlink($changeOrder['pdf_path']);
    }
    
    $_SESSION['success_message'] = 'Change order deleted successfully';
} else {
    $_SESSION['error_message'] = 'Failed to delete change order';
}
header('Location: index.php');
exit;
