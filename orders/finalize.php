<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/helpers.php';

requireAuth();

// Allow GET for confirmation, POST for actual finalization
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
        $_SESSION['error_message'] = 'Invalid pull sheet ID';
        header('Location: index.php');
        exit;
    }
    
    $pullsheetId = (int)$_POST['id'];
    $action = $_POST['action'] ?? 'approve';
} else {
    // GET request - show confirmation
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        $_SESSION['error_message'] = 'Invalid pull sheet ID';
        header('Location: index.php');
        exit;
    }
    
    $pullsheetId = (int)$_GET['id'];
    $action = $_GET['action'] ?? 'approve';
}

$pullsheet = getPullSheetById($pullsheetId);

if (!$pullsheet) {
    $_SESSION['error_message'] = 'Pull sheet not found';
    header('Location: index.php');
    exit;
}

$userId = $_SESSION['user']['id'];
$userRole = $_SESSION['user']['role'];

// Only admins can approve
if (!canApprovePullSheet($userRole)) {
    $_SESSION['error_message'] = 'Only administrators can approve pull sheets';
    header('Location: view.php?id=' . $pullsheetId);
    exit;
}

// Must be pending approval
if ($pullsheet['status'] !== 'pending_approval' && $pullsheet['status'] !== 'draft') {
    $_SESSION['error_message'] = 'This pull sheet cannot be approved';
    header('Location: view.php?id=' . $pullsheetId);
    exit;
}

// If GET request, show confirmation page
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $pageName = 'Approve Pull Sheet';
    include dirname(__DIR__) . '/includes/header.php';
    ?>
    
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">Approve Pull Sheet</h2>
                </div>
            </div>
        </div>
    </div>
    
    <div class="page-body">
        <div class="container-xl">
            <div class="card">
                <div class="card-body">
                    <h3>Confirm Approval</h3>
                    <p>Are you sure you want to approve pull sheet <strong><?php echo htmlspecialchars($pullsheet['barcode']); ?></strong> for show <strong><?php echo htmlspecialchars($pullsheet['show_name']); ?></strong>?</p>
                    <p class="text-muted">This will reserve the items and allow the pull sheet to be processed.</p>
                    
                    <?php
                    // Check stock availability
                    $validation = validatePullSheetStock($pullsheetId);
                    if (!$validation['valid']): ?>
                    <div class="alert alert-warning">
                        <h4 class="alert-title">Insufficient Stock Warning</h4>
                        <p>The following items have insufficient stock:</p>
                        <ul>
                            <?php foreach ($validation['errors'] as $err): ?>
                            <li><?php echo htmlspecialchars($err['item_name']); ?> - Need: <?php echo $err['needed']; ?>, Available: <?php echo $err['available']; ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <p>You cannot approve this pull sheet until stock is available.</p>
                    </div>
                    <div class="d-flex">
                        <a href="view.php?id=<?php echo $pullsheetId; ?>" class="btn btn-secondary">Back to Pull Sheet</a>
                    </div>
                    <?php else: ?>
                    <form method="POST">
                        <input type="hidden" name="id" value="<?php echo $pullsheetId; ?>">
                        <input type="hidden" name="action" value="approve">
                        <div class="d-flex">
                            <a href="view.php?id=<?php echo $pullsheetId; ?>" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-success ms-auto">Approve Pull Sheet</button>
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

// POST request - actually approve
// Validate stock availability
$validation = validatePullSheetStock($pullsheetId);
if (!$validation['valid']) {
    $errorMsg = 'Insufficient stock for the following items: ';
    $errorItems = [];
    foreach ($validation['errors'] as $err) {
        $errorItems[] = $err['item_name'] . ' (need ' . $err['needed'] . ', have ' . $err['available'] . ')';
    }
    $errorMsg .= implode(', ', $errorItems);
    $_SESSION['error_message'] = $errorMsg;
    header('Location: view.php?id=' . $pullsheetId);
    exit;
}

// Reserve items (update stock)
if (!reserveItemsForPullSheet($pullsheetId)) {
    $_SESSION['error_message'] = 'Failed to reserve items';
    header('Location: view.php?id=' . $pullsheetId);
    exit;
}

// Update pull sheet status
$approvedAt = date('Y-m-d H:i:s');
$query = "UPDATE pullsheets 
          SET status = 'approved', 
              approved_by = ?,
              approved_at = ?
          WHERE id = ?";
$result = executeQuery($query, [$userId, $approvedAt, $pullsheetId], 'isi');

if ($result) {
    $_SESSION['success_message'] = 'Pull sheet approved successfully';
} else {
    // Release items if approval failed
    releaseItemsForPullSheet($pullsheetId);
    $_SESSION['error_message'] = 'Failed to approve pull sheet';
}
header('Location: view.php?id=' . $pullsheetId);
exit;
