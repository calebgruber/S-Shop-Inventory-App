<?php
$pageTitle = 'Shows';
require_once 'includes/header.php';

$shows = getAllShows();
?>

<div class="row mb-3">
    <div class="col">
        <a href="show_create.php" class="btn btn-primary">
            <i class="ti ti-plus"></i> Create New Show
        </a>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">All Shows</h3>
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
                                        <a href="pullsheet_create.php?show_id=<?php echo $show['id']; ?>" class="btn btn-sm btn-info" title="Create Pullsheet">
                                            <i class="ti ti-file-text"></i>
                                        </a>
                                        <a href="change_order_create.php?show_id=<?php echo $show['id']; ?>" class="btn btn-sm btn-warning" title="Create Change Order">
                                            <i class="ti ti-exchange"></i>
                                        </a>
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
