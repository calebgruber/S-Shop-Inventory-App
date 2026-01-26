<?php
$pageTitle = 'Create Pullsheet';
require_once 'includes/header.php';

$showId = $_GET['show_id'] ?? null;
if (!$showId) {
    redirect('shows.php');
}

$show = getShowById($showId);
if (!$show) {
    setAlert('Show not found', 'danger');
    redirect('shows.php');
}

// Check if pullsheet already exists
$existing = getDB()->fetchOne("SELECT id FROM pullsheets WHERE show_id = ?", [$showId]);
if ($existing) {
    setAlert('A pullsheet already exists for this show', 'warning');
    redirect('pullsheet_edit.php?id=' . $existing['id']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $barcode = generateUniqueBarcode('PS');
        $createdBy = $_POST['created_by'] ?? 'Unknown';
        
        getDB()->query(
            "INSERT INTO pullsheets (show_id, barcode, created_by, status) VALUES (?, ?, ?, 'draft')",
            [$showId, $barcode, $createdBy]
        );
        
        $pullsheetId = getDB()->lastInsertId();
        
        setAlert('Pullsheet created successfully');
        redirect('pullsheet_edit.php?id=' . $pullsheetId);
    } catch (Exception $e) {
        setAlert($e->getMessage(), 'danger');
    }
}
?>

<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Create Pullsheet for <?php echo htmlspecialchars($show['name']); ?></h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Your Name</label>
                        <input type="text" class="form-control" name="created_by" placeholder="Enter your name" autofocus>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="ti ti-info-circle"></i>
                        You'll be able to add items to the pullsheet on the next screen.
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-check"></i> Create Pullsheet
                        </button>
                        <a href="show_edit.php?id=<?php echo $showId; ?>" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
