<?php
$pageTitle = 'Shows';
require_once 'includes/header.php';

// Handle archive/unarchive request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['archive_id'])) {
    try {
        $archiveId = (int)$_POST['archive_id'];
        $archiveValue = isset($_POST['unarchive']) ? 0 : 1;
        getDB()->query("UPDATE shows SET archived = ? WHERE id = ?", [$archiveValue, $archiveId]);
        setAlert($archiveValue ? 'Show archived successfully' : 'Show restored successfully');
        redirect('index.php');
    } catch (Exception $e) {
        setAlert('Error: ' . $e->getMessage(), 'danger');
    }
}

// Check if user wants to see archived shows
$showArchived = isset($_GET['show_archived']) && $_GET['show_archived'] === '1';

// Get shows based on archived filter
if ($showArchived) {
    $shows = getDB()->fetchAll("SELECT s.*, t.name as theatre_space_name 
        FROM shows s 
        LEFT JOIN theatre_spaces t ON s.theatre_space_id = t.id 
        WHERE s.archived = 1
        ORDER BY s.created_at DESC");
} else {
    $shows = getDB()->fetchAll("SELECT s.*, t.name as theatre_space_name 
        FROM shows s 
        LEFT JOIN theatre_spaces t ON s.theatre_space_id = t.id 
        WHERE s.archived = 0
        ORDER BY s.created_at DESC");
}
?>

<div class="row mb-3">
    <div class="col">
        <a href="show_create.php" class="btn btn-primary">
            <i class="ti ti-plus"></i> Create New Show
        </a>
        <?php if ($showArchived): ?>
            <a href="shows.php" class="btn btn-secondary">
                <i class="ti ti-eye"></i> Show Active Shows
            </a>
        <?php else: ?>
            <a href="shows.php?show_archived=1" class="btn btn-secondary">
                <i class="ti ti-archive"></i> Show Archived Shows
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><?php echo $showArchived ? 'Archived Shows' : 'Active Shows'; ?></h3>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Shop Lead</th>
                            <th>Designer</th>
                            <th>Theatre Space</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th class="w-1">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($shows as $show): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($show['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($show['shop_lead'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($show['designer'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($show['theatre_space_name'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php
                                    $badgeClass = [
                                        'active' => 'bg-success',
                                        'completed' => 'bg-secondary',
                                        'cancelled' => 'bg-danger'
                                    ][$show['status']] ?? 'bg-secondary';
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?>">
                                        <?php echo ucfirst($show['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('m/d/Y', strtotime($show['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="show_tracker.php?id=<?php echo $show['id']; ?>" class="btn btn-sm btn-success" title="View Workflow Tracker">
                                            <i class="ti ti-timeline"></i>
                                        </a>
                                        <a href="show_edit.php?id=<?php echo $show['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="ti ti-edit"></i>
                                        </a>
                                        <?php if (!$showArchived): ?>
                                            <a href="pullsheet_create.php?show_id=<?php echo $show['id']; ?>" class="btn btn-sm btn-info" title="Create Pullsheet">
                                                <i class="ti ti-file-text"></i>
                                            </a>
                                            <a href="change_order_create.php?show_id=<?php echo $show['id']; ?>" class="btn btn-sm btn-warning" title="Create Change Order">
                                                <i class="ti ti-exchange"></i>
                                            </a>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to archive this show?');">
                                                <input type="hidden" name="archive_id" value="<?php echo $show['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-secondary" title="Archive Show">
                                                    <i class="ti ti-archive"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to restore this show?');">
                                                <input type="hidden" name="archive_id" value="<?php echo $show['id']; ?>">
                                                <input type="hidden" name="unarchive" value="1">
                                                <button type="submit" class="btn btn-sm btn-success" title="Restore Show">
                                                    <i class="ti ti-archive-off"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
