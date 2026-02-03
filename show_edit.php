<?php
requirePermission('shows');
require_once 'includes/functions.php';

// Validate show BEFORE including header
$showId = $_GET['id'] ?? null;
if (!$showId) {
    redirect('shows');
}

$show = getShowById($showId);
if (!$show) {
    setAlert('Show not found', 'danger');
    redirect('shows');
}

$pageTitle = 'Edit Show';
require_once 'includes/header.php';

$theatreSpaces = getAllTheatreSpaces();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            if ($_POST['action'] === 'delete') {
                getDB()->query("DELETE FROM shows WHERE id = ?", [$showId]);
                setAlert('Show deleted successfully');
                redirect();
            }
        } else {
            validateRequired($_POST['name'], 'Show name');
            
            getDB()->query(
                "UPDATE shows SET name = ?, shop_lead = ?, designer = ?, theatre_space_id = ?, status = ? WHERE id = ?",
                [$_POST['name'], $_POST['shop_lead'], $_POST['designer'], $_POST['theatre_space_id'] ?: null, $_POST['status'], $showId]
            );
            
            // If show is archived, archive associated pullsheets and change orders
            if ($_POST['status'] === 'archived') {
                getDB()->query("UPDATE pullsheets SET status = 'archived' WHERE show_id = ?", [$showId]);
                getDB()->query("UPDATE change_orders SET status = 'archived' WHERE show_id = ?", [$showId]);
            }
            
            setAlert('Show updated successfully');
            redirect();
        }
    } catch (Exception $e) {
        setAlert($e->getMessage(), 'danger');
    }
}

// Get pullsheets and change orders for this show
$pullsheets = getDB()->fetchAll("SELECT * FROM pullsheets WHERE show_id = ? ORDER BY created_at DESC", [$showId]);
$changeOrders = getDB()->fetchAll("SELECT * FROM change_orders WHERE show_id = ? ORDER BY created_at DESC", [$showId]);
?>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">Edit Show</h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label required">Show Name</label>
                        <input type="text" class="form-control" name="name" value="<?php echo htmlspecialchars($show['name']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Shop Lead</label>
                        <input type="text" class="form-control" name="shop_lead" value="<?php echo htmlspecialchars($show['shop_lead'] ?? ''); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Designer</label>
                        <input type="text" class="form-control" name="designer" value="<?php echo htmlspecialchars($show['designer'] ?? ''); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Theatre Space</label>
                        <select class="form-select" name="theatre_space_id">
                            <option value="">-- Select Theatre Space --</option>
                            <?php foreach ($theatreSpaces as $space): ?>
                                <option value="<?php echo $space['id']; ?>" <?php echo $show['theatre_space_id'] == $space['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($space['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="active" <?php echo $show['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="completed" <?php echo $show['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $show['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            <option value="archived" <?php echo $show['status'] === 'archived' ? 'selected' : ''; ?>>Archived</option>
                        </select>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-check"></i> Save Changes
                        </button>
                        <a href="shows" class="btn btn-secondary">Back to Shows</a>
                    </div>
                </form>
                
                <hr class="my-4">
                
                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this show? This cannot be undone.')">
                    <input type="hidden" name="action" value="delete">
                    <button type="submit" class="btn btn-danger">
                        <i class="ti ti-trash"></i> Delete Show
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">Quick Actions</h3>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="pullsheet_create.php?show_id=<?php echo $showId; ?>" class="btn btn-info">
                        <i class="ti ti-file-text"></i> Create Pullsheet
                    </a>
                    <a href="change_order_create.php?show_id=<?php echo $showId; ?>" class="btn btn-warning">
                        <i class="ti ti-exchange"></i> Create Change Order
                    </a>
                </div>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">Pullsheets</h3>
            </div>
            <div class="card-body">
                <?php if (empty($pullsheets)): ?>
                    <p class="text-muted">No pullsheets yet</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($pullsheets as $pullsheet): ?>
                            <a href="pullsheet_view?id=<?php echo $pullsheet['id']; ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>Pullsheet #<?php echo $pullsheet['id']; ?></span>
                                    <span class="badge bg-<?php echo $pullsheet['status'] === 'draft' ? 'secondary' : ($pullsheet['status'] === 'finalized' ? 'warning' : 'success'); ?>">
                                        <?php echo ucfirst($pullsheet['status']); ?>
                                    </span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Change Orders</h3>
            </div>
            <div class="card-body">
                <?php if (empty($changeOrders)): ?>
                    <p class="text-muted">No change orders yet</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($changeOrders as $changeOrder): ?>
                            <a href="change_order_edit?id=<?php echo $changeOrder['id']; ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>Change Order #<?php echo $changeOrder['id']; ?></span>
                                    <span class="badge bg-<?php echo $changeOrder['status'] === 'draft' ? 'secondary' : 'success'; ?>">
                                        <?php echo ucfirst($changeOrder['status']); ?>
                                    </span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
