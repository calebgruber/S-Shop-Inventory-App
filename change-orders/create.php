<?php
require_once '../includes/functions.php';
requirePermission('change_orders');

// Validate show and permissions
$currentUser = getCurrentUser();
$isDesigner = $currentUser['role'] === 'designer';

$showId = $_GET['show_id'] ?? null;
if (!$showId) redirect('shows');

$show = getShowById($showId);
if (!$show) {
    setAlert('Show not found', 'danger');
    redirect('shows');
}

// Check permission for designers
if ($isDesigner && !canAccessShow($currentUser['id'], $showId)) {
    setAlert('You do not have permission to create change order for this show', 'danger');
    redirect('change_orders');
}

$pageTitle = 'Create Change Order';
require_once '../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $barcode = generateUniqueBarcode('CO');
        $createdBy = $currentUser['name'] ?? 'Unknown'; // Use logged-in user's name with fallback
        
        getDB()->query(
            "INSERT INTO change_orders (show_id, barcode, created_by, status) VALUES (?, ?, ?, 'draft')",
            [$showId, $barcode, $createdBy]
        );
        
        setAlert('Change order created');
        redirect('change_order_edit.php?id=' . getDB()->lastInsertId());
    } catch (Exception $e) {
        setAlert($e->getMessage(), 'danger');
    }
}
?>

<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Create Change Order for <?php echo htmlspecialchars($show['name']); ?></h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="alert alert-info">
                        <i class="ti ti-info-circle"></i>
                        You'll be able to add items to the change order on the next screen.
                    </div>
                    <button type="submit" class="btn btn-primary">Create Change Order</button>
                    <a href="show_edit?id=<?php echo $showId; ?>" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
