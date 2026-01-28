<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
requireAuth();

$pageName = 'Shows';
$successMessage = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);

// Get all shows
$shows = getAllShows(true); // Include archived

// Separate active and archived shows
$activeShows = array_filter($shows, function($show) { return !$show['archived']; });
$archivedShows = array_filter($shows, function($show) { return $show['archived']; });

// Calculate statistics
$totalShows = count($shows);
$activeCount = count($activeShows);
$archivedCount = count($archivedShows);

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">Production Management</div>
                <h2 class="page-title">Shows</h2>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <?php if (hasPermission('admin')): ?>
                <a href="add.php" class="btn btn-primary">
                    <i class="ti ti-plus icon"></i>
                    Add Show
                </a>
                <a href="calendar.php" class="btn btn-secondary">
                    <i class="ti ti-calendar icon"></i>
                    Calendar
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <?php if ($successMessage): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="ti ti-check icon"></i>
            <?php echo htmlspecialchars($successMessage); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row row-cards mb-3">
            <div class="col-sm-6 col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Total Shows</div>
                        </div>
                        <div class="h1 mb-0"><?php echo $totalShows; ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Active Shows</div>
                        </div>
                        <div class="h1 mb-0 text-success"><?php echo $activeCount; ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Archived Shows</div>
                        </div>
                        <div class="h1 mb-0 text-muted"><?php echo $archivedCount; ?></div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (empty($shows)): ?>
        <!-- Empty State -->
        <div class="empty">
            <div class="empty-icon">
                <i class="ti ti-award icon"></i>
            </div>
            <p class="empty-title">No shows yet</p>
            <p class="empty-subtitle text-muted">
                Get started by creating your first show.
            </p>
            <?php if (hasPermission('admin')): ?>
            <div class="empty-action">
                <a href="add.php" class="btn btn-primary">
                    <i class="ti ti-plus icon"></i>
                    Add Show
                </a>
            </div>
            <?php endif; ?>
        </div>
        <?php else: ?>
        
        <!-- Active Shows -->
        <?php if (!empty($activeShows)): ?>
        <h3 class="mb-3">Active Shows</h3>
        <div class="row row-cards mb-4">
            <?php foreach ($activeShows as $show): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="avatar rounded" style="background-color: <?php echo htmlspecialchars($show['color']); ?>">
                                    <i class="ti ti-award icon text-white"></i>
                                </span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium">
                                    <a href="view.php?id=<?php echo $show['id']; ?>" class="text-reset">
                                        <?php echo htmlspecialchars($show['name']); ?>
                                    </a>
                                </div>
                                <div class="text-muted">
                                    <small>
                                        <?php if ($show['theatre_space_name']): ?>
                                            <i class="ti ti-building icon"></i>
                                            <?php echo htmlspecialchars($show['theatre_space_name']); ?>
                                        <?php else: ?>
                                            No space assigned
                                        <?php endif; ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="row">
                                <div class="col-6">
                                    <div class="text-muted small">Designer</div>
                                    <div class="text-truncate">
                                        <?php 
                                        if ($show['designer_first_name']) {
                                            echo htmlspecialchars($show['designer_first_name'] . ' ' . $show['designer_last_name']);
                                        } else {
                                            echo '<span class="text-muted">Not assigned</span>';
                                        }
                                        ?>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted small">Production Audio</div>
                                    <div class="text-truncate">
                                        <?php 
                                        if ($show['production_audio_first_name']) {
                                            echo htmlspecialchars($show['production_audio_first_name'] . ' ' . $show['production_audio_last_name']);
                                        } else {
                                            echo '<span class="text-muted">Not assigned</span>';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="btn-list">
                                <a href="view.php?id=<?php echo $show['id']; ?>" class="btn btn-sm btn-secondary">
                                    <i class="ti ti-eye icon"></i>
                                    View
                                </a>
                                <?php if (hasPermission('admin')): ?>
                                <a href="edit.php?id=<?php echo $show['id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="ti ti-edit icon"></i>
                                    Edit
                                </a>
                                <button class="btn btn-sm btn-warning" onclick="archiveShow(<?php echo $show['id']; ?>, '<?php echo htmlspecialchars($show['name'], ENT_QUOTES); ?>')">
                                    <i class="ti ti-archive icon"></i>
                                    Archive
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Archived Shows -->
        <?php if (!empty($archivedShows)): ?>
        <h3 class="mb-3 text-muted">Archived Shows</h3>
        <div class="row row-cards">
            <?php foreach ($archivedShows as $show): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card bg-light">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="avatar rounded" style="background-color: <?php echo htmlspecialchars($show['color']); ?>; opacity: 0.5;">
                                    <i class="ti ti-award icon text-white"></i>
                                </span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium text-muted">
                                    <?php echo htmlspecialchars($show['name']); ?>
                                </div>
                                <div class="text-muted">
                                    <small>
                                        <i class="ti ti-archive icon"></i>
                                        Archived
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="btn-list">
                                <a href="view.php?id=<?php echo $show['id']; ?>" class="btn btn-sm btn-secondary">
                                    <i class="ti ti-eye icon"></i>
                                    View
                                </a>
                                <?php if (hasPermission('admin')): ?>
                                <button class="btn btn-sm btn-success" onclick="unarchiveShow(<?php echo $show['id']; ?>, '<?php echo htmlspecialchars($show['name'], ENT_QUOTES); ?>')">
                                    <i class="ti ti-refresh icon"></i>
                                    Unarchive
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php endif; ?>
    </div>
</div>

<script>
function archiveShow(showId, showName) {
    if (confirm(`Are you sure you want to archive "${showName}"?\n\nThis will also archive all associated pull sheets and change orders.`)) {
        // Create form and submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'archive.php';
        
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'show_id';
        idInput.value = showId;
        form.appendChild(idInput);
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'archive';
        form.appendChild(actionInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

function unarchiveShow(showId, showName) {
    if (confirm(`Are you sure you want to unarchive "${showName}"?`)) {
        // Create form and submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'archive.php';
        
        const idInput = document.createElement('input');
        idInput.type = 'hidden';
        idInput.name = 'show_id';
        idInput.value = showId;
        form.appendChild(idInput);
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'unarchive';
        form.appendChild(actionInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
