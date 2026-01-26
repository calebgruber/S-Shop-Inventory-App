<?php
require_once 'config.php';
require_once 'db.php';
require_once 'functions.php';

$db = getDB();

// Get show ID
$showId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($showId <= 0) {
    redirectTo('shows.php');
}

// Check if confirmed
if (isset($_POST['confirm_delete'])) {
    // Delete the show (CASCADE will handle related records)
    $stmt = $db->prepare("DELETE FROM shows WHERE id = ?");
    $stmt->bind_param("i", $showId);
    
    if ($stmt->execute()) {
        redirectTo('shows.php');
    } else {
        $error = "Failed to delete show. Please try again.";
    }
}

// Get show details for confirmation
$stmt = $db->prepare("SELECT s.*, ts.name as theatre_space_name 
                      FROM shows s 
                      LEFT JOIN theatre_spaces ts ON s.theatre_space_id = ts.id 
                      WHERE s.id = ?");
$stmt->bind_param("i", $showId);
$stmt->execute();
$result = $stmt->get_result();
$show = $result->fetch_assoc();

if (!$show) {
    redirectTo('shows.php');
}

// Get counts of related items
$stmt = $db->prepare("SELECT COUNT(*) as count FROM pull_sheets WHERE show_id = ?");
$stmt->bind_param("i", $showId);
$stmt->execute();
$pullSheetsCount = $stmt->get_result()->fetch_assoc()['count'];

$stmt = $db->prepare("SELECT COUNT(*) as count FROM change_orders WHERE show_id = ?");
$stmt->bind_param("i", $showId);
$stmt->execute();
$changeOrdersCount = $stmt->get_result()->fetch_assoc()['count'];

$pageTitle = "Delete Show - " . APP_NAME;
$pageHeader = "Delete Show";
$pageActions = '<a href="show_view.php?id=' . $showId . '" class="btn btn-secondary"><i class="ti ti-arrow-left"></i> Cancel</a>';

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
                        You are about to permanently delete this show and all related data. This action cannot be undone.
                    </p>
                </div>
                
                <div class="mb-4">
                    <h4>Show to be deleted:</h4>
                    <div class="card bg-light">
                        <div class="card-body">
                            <h3><?php echo sanitize($show['name']); ?></h3>
                            <?php if ($show['designer']): ?>
                            <div class="mb-1">
                                <strong>Designer:</strong> <?php echo sanitize($show['designer']); ?>
                            </div>
                            <?php endif; ?>
                            <?php if ($show['shop_lead']): ?>
                            <div class="mb-1">
                                <strong>Shop Lead:</strong> <?php echo sanitize($show['shop_lead']); ?>
                            </div>
                            <?php endif; ?>
                            <?php if ($show['theatre_space_name']): ?>
                            <div class="mb-1">
                                <strong>Theatre Space:</strong> <?php echo sanitize($show['theatre_space_name']); ?>
                            </div>
                            <?php endif; ?>
                            <div class="mt-2">
                                <span class="badge badge-outline text-<?php 
                                    echo $show['status'] === 'active' ? 'green' : 
                                        ($show['status'] === 'completed' ? 'blue' : 'red'); 
                                ?>">
                                    <?php echo ucfirst($show['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if ($pullSheetsCount > 0 || $changeOrdersCount > 0): ?>
                <div class="alert alert-info">
                    <h4 class="alert-title">Related Items</h4>
                    <p>The following will also be deleted:</p>
                    <ul class="mb-0">
                        <?php if ($pullSheetsCount > 0): ?>
                        <li><?php echo $pullSheetsCount; ?> Pull Sheet<?php echo $pullSheetsCount > 1 ? 's' : ''; ?></li>
                        <?php endif; ?>
                        <?php if ($changeOrdersCount > 0): ?>
                        <li><?php echo $changeOrdersCount; ?> Change Order<?php echo $changeOrdersCount > 1 ? 's' : ''; ?></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <form method="POST" onsubmit="return confirm('Are you absolutely sure you want to delete this show? This cannot be undone.');">
                    <div class="d-flex justify-content-end gap-2">
                        <a href="show_view.php?id=<?php echo $showId; ?>" class="btn btn-secondary">
                            <i class="ti ti-x"></i> Cancel
                        </a>
                        <button type="submit" name="confirm_delete" class="btn btn-danger">
                            <i class="ti ti-trash"></i> Delete Show
                        </button>
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
