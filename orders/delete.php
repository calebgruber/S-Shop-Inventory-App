<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/helpers.php';

requireAuth();

// Allow GET for confirmation, POST for actual deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
        $_SESSION['error_message'] = 'Invalid pull sheet ID';
        header('Location: index.php');
        exit;
    }
    
    $pullsheetId = (int)$_POST['id'];
} else {
    // GET request - show confirmation
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        $_SESSION['error_message'] = 'Invalid pull sheet ID';
        header('Location: index.php');
        exit;
    }
    
    $pullsheetId = (int)$_GET['id'];
}

$pullsheet = getPullSheetById($pullsheetId);

if (!$pullsheet) {
    $_SESSION['error_message'] = 'Pull sheet not found';
    header('Location: index.php');
    exit;
}

$userId = $_SESSION['user']['id'];
$userRole = $_SESSION['user']['role'];

// Check permissions: only owner or admin can delete drafts
if ($pullsheet['status'] !== 'draft') {
    $_SESSION['error_message'] = 'Only draft pull sheets can be deleted';
    header('Location: view.php?id=' . $pullsheetId);
    exit;
}

if ($userRole !== 'admin' && $pullsheet['created_by'] != $userId) {
    $_SESSION['error_message'] = 'You do not have permission to delete this pull sheet';
    header('Location: view.php?id=' . $pullsheetId);
    exit;
}

// If GET request, show confirmation page
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $pageName = 'Delete Pull Sheet';
    include dirname(__DIR__) . '/includes/header.php';
    ?>
    
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">Delete Pull Sheet</h2>
                </div>
            </div>
        </div>
    </div>
    
    <div class="page-body">
        <div class="container-xl">
            <div class="card">
                <div class="card-body">
                    <h3>Confirm Deletion</h3>
                    <p>Are you sure you want to delete pull sheet <strong><?php echo htmlspecialchars($pullsheet['barcode']); ?></strong>?</p>
                    <p class="text-muted">This action cannot be undone.</p>
                    
                    <form method="POST">
                        <input type="hidden" name="id" value="<?php echo $pullsheetId; ?>">
                        <div class="d-flex">
                            <a href="view.php?id=<?php echo $pullsheetId; ?>" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-danger ms-auto">Delete Pull Sheet</button>
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
// Release any reserved items (though drafts shouldn't have reserved items)
releaseItemsForPullSheet($pullsheetId);

// Delete the pull sheet (cascade will delete items)
$query = "DELETE FROM pullsheets WHERE id = ?";
$result = executeQuery($query, [$pullsheetId], 'i');

if ($result) {
    // Delete PDF if exists
    if (!empty($pullsheet['pdf_path']) && file_exists($pullsheet['pdf_path'])) {
        @unlink($pullsheet['pdf_path']);
    }
    
    $_SESSION['success_message'] = 'Pull sheet deleted successfully';
} else {
    $_SESSION['error_message'] = 'Failed to delete pull sheet';
}
header('Location: index.php');
exit;
