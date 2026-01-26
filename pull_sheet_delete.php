<?php
require_once 'config.php';
require_once 'db.php';
require_once 'functions.php';

$db = getDB();

// Get pull sheet ID
$pullSheetId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($pullSheetId <= 0) {
    redirectTo('pull_sheets.php');
}

// Check if confirmed
if (isset($_POST['confirm_delete'])) {
    // Get pull sheet info to check status
    $stmt = $db->prepare("SELECT status FROM pull_sheets WHERE id = ?");
    $stmt->bind_param("i", $pullSheetId);
    $stmt->execute();
    $ps = $stmt->get_result()->fetch_assoc();
    
    if ($ps && in_array($ps['status'], ['draft', 'finalized'])) {
        // Delete the pull sheet (CASCADE will handle related records)
        $stmt = $db->prepare("DELETE FROM pull_sheets WHERE id = ?");
        $stmt->bind_param("i", $pullSheetId);
        
        if ($stmt->execute()) {
            redirectTo('pull_sheets.php');
        } else {
            $error = "Failed to delete pull sheet. Please try again.";
        }
    } else {
        $error = "Cannot delete pull sheets that have been picked or completed. Status: " . $ps['status'];
    }
}

// Get pull sheet details for confirmation
$stmt = $db->prepare("SELECT ps.*, s.name as show_name 
                      FROM pull_sheets ps 
                      LEFT JOIN shows s ON ps.show_id = s.id 
                      WHERE ps.id = ?");
$stmt->bind_param("i", $pullSheetId);
$stmt->execute();
$result = $stmt->get_result();
$pullSheet = $result->fetch_assoc();

if (!$pullSheet) {
    redirectTo('pull_sheets.php');
}

// Get items count
$stmt = $db->prepare("SELECT COUNT(*) as count FROM pull_sheet_items WHERE pull_sheet_id = ?");
$stmt->bind_param("i", $pullSheetId);
$stmt->execute();
$itemsCount = $stmt->get_result()->fetch_assoc()['count'];

$pageTitle = "Delete Pull Sheet - " . APP_NAME;
$pageHeader = "Delete Pull Sheet";
$pageActions = '<a href="pull_sheet_view.php?id=' . $pullSheetId . '" class="btn btn-secondary"><i class="ti ti-arrow-left"></i> Cancel</a>';

ob_start();
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-danger text-white">
                <h3 class="card-title">
                    <i class="ti ti-alert-triangle me-2"></i>Confirm Deletion
                </h3>
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <?php echo sanitize($error); ?>
                </div>
                <?php endif; ?>
                
                <div class="alert alert-warning">
                    <h4 class="alert-title">Warning!</h4>
                    <p class="mb-0">
                        You are about to permanently delete this pull sheet. This action cannot be undone.
                    </p>
                </div>
                
                <div class="mb-4">
                    <h4>Pull Sheet to be deleted:</h4>
                    <div class="card bg-light">
                        <div class="card-body">
                            <h3><?php echo sanitize($pullSheet['barcode']); ?></h3>
                            <div class="mb-1">
                                <strong>Show:</strong> <?php echo sanitize($pullSheet['show_name']); ?>
                            </div>
                            <div class="mb-1">
                                <strong>Status:</strong> 
                                <span class="badge badge-outline text-<?php 
                                    echo $pullSheet['status'] === 'finalized' ? 'blue' : 
                                        ($pullSheet['status'] === 'picked' ? 'green' : 'gray'); 
                                ?>">
                                    <?php echo ucfirst($pullSheet['status']); ?>
                                </span>
                            </div>
                            <?php if ($pullSheet['is_main']): ?>
                            <div class="mb-1">
                                <span class="badge bg-primary">Main Pull Sheet</span>
                            </div>
                            <?php endif; ?>
                            <div class="mb-1">
                                <strong>Items:</strong> <?php echo $itemsCount; ?>
                            </div>
                            <div class="mt-2">
                                <small class="text-muted">Created: <?php echo formatDate($pullSheet['created_at']); ?></small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if (!in_array($pullSheet['status'], ['draft', 'finalized'])): ?>
                <div class="alert alert-info">
                    <h4 class="alert-title">Note</h4>
                    <p class="mb-0">
                        Pull sheets that have been picked or completed cannot be deleted to maintain inventory accuracy.
                    </p>
                </div>
                <?php endif; ?>
                
                <form method="POST" onsubmit="return confirm('Are you absolutely sure you want to delete this pull sheet? This cannot be undone.');">
                    <div class="d-flex justify-content-end gap-2">
                        <a href="pull_sheet_view.php?id=<?php echo $pullSheetId; ?>" class="btn btn-secondary">
                            <i class="ti ti-x"></i> Cancel
                        </a>
                        <?php if (in_array($pullSheet['status'], ['draft', 'finalized'])): ?>
                        <button type="submit" name="confirm_delete" class="btn btn-danger">
                            <i class="ti ti-trash"></i> Delete Pull Sheet
                        </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require 'layout.php';
?>
