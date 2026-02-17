<?php
require_once '../includes/functions.php';

// Only admins can create shows
requireRole('admin');

$pageTitle = 'Create Show';
require_once '../includes/header.php';

$theatreSpaces = getAllTheatreSpaces();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        validateRequired($_POST['name'], 'Show name');
        
        getDB()->query(
            "INSERT INTO shows (name, shop_lead, designer, theatre_space_id, status) VALUES (?, ?, ?, ?, 'active')",
            [$_POST['name'], $_POST['shop_lead'], $_POST['designer'], $_POST['theatre_space_id'] ?: null]
        );
        
        setAlert('Show created successfully');
        redirect();
    } catch (Exception $e) {
        setAlert($e->getMessage(), 'danger');
    }
}
?>

<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Create New Show</h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label required">Show Name</label>
                        <input type="text" class="form-control" name="name" required autofocus>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Shop Lead</label>
                        <input type="text" class="form-control" name="shop_lead">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Designer</label>
                        <input type="text" class="form-control" name="designer">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Theatre Space</label>
                        <select class="form-select" name="theatre_space_id">
                            <option value="">-- Select Theatre Space --</option>
                            <?php foreach ($theatreSpaces as $space): ?>
                                <option value="<?php echo $space['id']; ?>">
                                    <?php echo htmlspecialchars($space['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-check"></i> Create Show
                        </button>
                        <a href="shows" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
