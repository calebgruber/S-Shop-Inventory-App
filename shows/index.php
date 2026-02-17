<?php
require_once '../includes/functions.php';

// Check permissions - only admins can view shows
requireRole('admin');

$pageTitle = 'Shows';
require_once '../includes/header.php';

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    try {
        $deleteId = (int)$_POST['delete_id'];
        getDB()->query("DELETE FROM shows WHERE id = ?", [$deleteId]);
        setAlert('Show deleted successfully');
        redirect();
    } catch (Exception $e) {
        setAlert('Error: ' . $e->getMessage(), 'danger');
    }
}

// Get all shows
$shows = getDB()->fetchAll("SELECT s.*, t.name as theatre_space_name 
    FROM shows s 
    LEFT JOIN theatre_spaces t ON s.theatre_space_id = t.id 
    ORDER BY s.created_at DESC");
?>

<div class="row mb-3">
    <div class="col">
        <a href="show_create" class="btn btn-primary">
            <i class="ti ti-plus"></i> Create New Show
        </a>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><?php echo ($showArchived ?? false) ? 'Archived Shows' : 'Active Shows'; ?></h3>
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
                                        <a href="/shows/tracker?id=<?php echo $show['id']; ?>" class="btn btn-sm btn-success" title="View Workflow Tracker">
                                            <i class="ti ti-timeline"></i>
                                        </a>
                                        <a href="/shows/edit?id=<?php echo $show['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="ti ti-edit"></i>
                                        </a>
                                        <a href="/pullsheets/create?show_id=<?php echo $show['id']; ?>" class="btn btn-sm btn-info" title="Create Pullsheet">
                                            <i class="ti ti-file-text"></i>
                                        </a>
                                        <a href="/change-orders/create?show_id=<?php echo $show['id']; ?>" class="btn btn-sm btn-warning" title="Create Change Order">
                                            <i class="ti ti-exchange"></i>
                                        </a>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this show? This cannot be undone.');">
                                            <input type="hidden" name="delete_id" value="<?php echo $show['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" title="Delete Show">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </form>
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

<?php require_once '../includes/footer.php'; ?>
