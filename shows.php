<?php
require_once 'config.php';
require_once 'db.php';
require_once 'functions.php';

$db = getDB();

// Get all shows with theatre space names
$sql = "SELECT s.*, ts.name as theatre_space_name 
        FROM shows s 
        LEFT JOIN theatre_spaces ts ON s.theatre_space_id = ts.id 
        ORDER BY s.created_at DESC";
$shows = $db->query($sql);

$pageTitle = "Shows - " . APP_NAME;
$pageHeader = "Shows";
$pageActions = '<a href="show_create.php" class="btn btn-primary"><i class="ti ti-plus"></i> Create Show</a>';

ob_start();
?>

<div class="row row-cards">
    <?php if ($shows && $shows->num_rows > 0): ?>
        <?php while ($show = $shows->fetch_assoc()): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h3 class="card-title"><?php echo sanitize($show['name']); ?></h3>
                    <div class="mb-2">
                        <span class="badge badge-outline text-<?php 
                            echo $show['status'] === 'active' ? 'green' : 
                                ($show['status'] === 'completed' ? 'blue' : 'red'); 
                        ?>">
                            <?php echo ucfirst($show['status']); ?>
                        </span>
                    </div>
                    <?php if ($show['designer']): ?>
                    <div class="text-muted mb-1">
                        <i class="ti ti-user"></i> Designer: <?php echo sanitize($show['designer']); ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($show['shop_lead']): ?>
                    <div class="text-muted mb-1">
                        <i class="ti ti-user-star"></i> Shop Lead: <?php echo sanitize($show['shop_lead']); ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($show['theatre_space_name']): ?>
                    <div class="text-muted mb-1">
                        <i class="ti ti-building"></i> <?php echo sanitize($show['theatre_space_name']); ?>
                    </div>
                    <?php endif; ?>
                    <div class="text-muted mt-2">
                        <small>Created: <?php echo formatDate($show['created_at']); ?></small>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="btn-group w-100">
                        <a href="show_view.php?id=<?php echo $show['id']; ?>" class="btn btn-primary">
                            View Details
                        </a>
                        <a href="show_delete.php?id=<?php echo $show['id']; ?>" 
                           class="btn btn-danger" 
                           onclick="return confirm('Are you sure you want to delete this show?');">
                            <i class="ti ti-trash"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="col-12">
            <div class="empty">
                <div class="empty-icon">
                    <i class="ti ti-theater icon"></i>
                </div>
                <p class="empty-title">No shows found</p>
                <p class="empty-subtitle text-muted">
                    Get started by creating your first show
                </p>
                <div class="empty-action">
                    <a href="show_create.php" class="btn btn-primary">
                        <i class="ti ti-plus"></i> Create Show
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require 'layout.php';
?>
