<?php
require_once 'includes/functions.php';

// Validate and check permissions BEFORE including header
$currentUser = getCurrentUser();
$isDesigner = $currentUser['role'] === 'designer';

$showId = $_GET['show_id'] ?? null;
$show = null;

if ($showId) {
    $show = getShowById($showId);
    if (!$show) {
        setAlert('Show not found', 'danger');
        redirect('shows.php');
    }
    
    // Check permission for designers
    if ($isDesigner && !canAccessShow($currentUser['id'], $showId)) {
        setAlert('You do not have permission to create pullsheet for this show', 'danger');
        redirect('pullsheets.php');
    }
    
    // Check if pullsheet already exists
    $existing = getDB()->fetchOne("SELECT id FROM pullsheets WHERE show_id = ?", [$showId]);
    if ($existing) {
        setAlert('A pullsheet already exists for this show', 'warning');
        redirect('pullsheet_edit.php?id=' . $existing['id']);
    }
}

$pageTitle = 'Create Pullsheet';
require_once 'includes/header.php';

// Get shows filtered by permission
if ($isDesigner) {
    $activeShows = getAssignedShows($currentUser['id']);
} else {
    $activeShows = getActiveShows();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $postShowId = $_POST['show_id'] ?? null;
        if (!$postShowId) {
            throw new Exception('Please select a show');
        }
        
        // Check permission for designers
        if ($isDesigner && !canAccessShow($currentUser['id'], $postShowId)) {
            throw new Exception('You do not have permission to create pullsheet for this show');
        }
        
        $barcode = generateUniqueBarcode('PS');
        $createdBy = $_POST['created_by'] ?? 'Unknown';
        
        // Check if pullsheet already exists
        $existing = getDB()->fetchOne("SELECT id FROM pullsheets WHERE show_id = ?", [$postShowId]);
        if ($existing) {
            setAlert('A pullsheet already exists for this show', 'warning');
            redirect('pullsheet_edit.php?id=' . $existing['id']);
        }
        
        getDB()->query(
            "INSERT INTO pullsheets (show_id, barcode, created_by, status) VALUES (?, ?, ?, 'draft')",
            [$postShowId, $barcode, $createdBy]
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
                <h3 class="card-title">Create Pullsheet<?php echo $show ? ' for ' . htmlspecialchars($show['name']) : ''; ?></h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <?php if (!$showId): ?>
                    <div class="mb-3">
                        <label class="form-label required">Select Show</label>
                        <select class="form-select" name="show_id" required>
                            <option value="">-- Select a Show --</option>
                            <?php foreach ($activeShows as $s): ?>
                            <option value="<?php echo $s['id']; ?>">
                                <?php echo htmlspecialchars($s['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php else: ?>
                    <input type="hidden" name="show_id" value="<?php echo $showId; ?>">
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label class="form-label">Your Name</label>
                        <input type="text" class="form-control" name="created_by" placeholder="Enter your name" <?php echo $showId ? 'autofocus' : ''; ?>>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="ti ti-info-circle"></i>
                        You'll be able to add items to the pullsheet on the next screen.
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-check"></i> Create Pullsheet
                        </button>
                        <a href="<?php echo $showId ? 'show_edit.php?id=' . $showId : 'shows.php'; ?>" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
