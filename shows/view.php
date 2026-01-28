<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
requireAuth();

$pageName = 'View Show';
$showId = intval($_GET['id'] ?? 0);

if (!$showId) {
    $_SESSION['error_message'] = 'Show ID is required.';
    header('Location: index.php');
    exit;
}

// Get show details
$show = getShowById($showId);

if (!$show) {
    $_SESSION['error_message'] = 'Show not found.';
    header('Location: index.php');
    exit;
}

$successMessage = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);

// Get pull sheets for this show
$pullSheetsQuery = "SELECT ps.*, u.username as created_by_username, u.first_name, u.last_name 
                    FROM pullsheets ps
                    LEFT JOIN users u ON ps.created_by = u.id
                    WHERE ps.show_id = ?
                    ORDER BY ps.created_at DESC";
$pullSheetsResult = executeQuery($pullSheetsQuery, [$showId]);
$pullSheets = [];
if ($pullSheetsResult && numRows($pullSheetsResult) > 0) {
    while ($row = fetchAssoc($pullSheetsResult)) {
        $pullSheets[] = $row;
    }
}

// Get change orders for this show
$changeOrdersQuery = "SELECT co.*, u.username as created_by_username, u.first_name, u.last_name 
                      FROM change_orders co
                      LEFT JOIN users u ON co.created_by = u.id
                      WHERE co.show_id = ?
                      ORDER BY co.created_at DESC";
$changeOrdersResult = executeQuery($changeOrdersQuery, [$showId]);
$changeOrders = [];
if ($changeOrdersResult && numRows($changeOrdersResult) > 0) {
    while ($row = fetchAssoc($changeOrdersResult)) {
        $changeOrders[] = $row;
    }
}

// Get calendar events for this show
$calendarEvents = getCalendarEvents($showId);

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="index.php">Shows</a></li>
                            <li class="breadcrumb-item active"><?php echo htmlspecialchars($show['name']); ?></li>
                        </ol>
                    </nav>
                </div>
                <h2 class="page-title">
                    <span class="avatar me-2" style="background-color: <?php echo htmlspecialchars($show['color']); ?>">
                        <i class="ti ti-award icon text-white"></i>
                    </span>
                    <?php echo htmlspecialchars($show['name']); ?>
                    <?php if ($show['archived']): ?>
                        <span class="badge bg-secondary ms-2">Archived</span>
                    <?php endif; ?>
                </h2>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <?php if (hasPermission('admin')): ?>
                <a href="edit.php?id=<?php echo $showId; ?>" class="btn btn-primary">
                    <i class="ti ti-edit icon"></i>
                    Edit Show
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

        <div class="row">
            <!-- Show Information -->
            <div class="col-md-8">
                <div class="card mb-3">
                    <div class="card-header">
                        <h3 class="card-title">Show Information</h3>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label text-muted">Theatre Space</label>
                                    <div>
                                        <?php if ($show['theatre_space_name']): ?>
                                            <i class="ti ti-building icon"></i>
                                            <?php echo htmlspecialchars($show['theatre_space_name']); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Not assigned</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label text-muted">Calendar Color</label>
                                    <div>
                                        <span class="badge" style="background-color: <?php echo htmlspecialchars($show['color']); ?>">
                                            <?php echo htmlspecialchars($show['color']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label text-muted">Designer</label>
                                    <div>
                                        <?php 
                                        if ($show['designer_first_name']) {
                                            echo htmlspecialchars($show['designer_first_name'] . ' ' . $show['designer_last_name']);
                                        } else {
                                            echo '<span class="text-muted">Not assigned</span>';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label text-muted">Production Audio</label>
                                    <div>
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
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-0">
                                    <label class="form-label text-muted">Created</label>
                                    <div><?php echo date('M d, Y g:i A', strtotime($show['created_at'])); ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-0">
                                    <label class="form-label text-muted">Last Updated</label>
                                    <div><?php echo date('M d, Y g:i A', strtotime($show['updated_at'])); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pull Sheets -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h3 class="card-title">Pull Sheets (Shop Orders)</h3>
                    </div>
                    <?php if (empty($pullSheets)): ?>
                    <div class="card-body">
                        <div class="empty">
                            <p class="empty-title">No pull sheets yet</p>
                            <p class="empty-subtitle text-muted">Pull sheets will appear here once created.</p>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table card-table table-vcenter">
                            <thead>
                                <tr>
                                    <th>Status</th>
                                    <th>Created By</th>
                                    <th>Created Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pullSheets as $ps): ?>
                                <tr>
                                    <td>
                                        <?php 
                                        $statusClass = 'secondary';
                                        $statusText = 'Draft';
                                        if ($ps['status'] === 'approved') {
                                            $statusClass = 'info';
                                            $statusText = 'Approved';
                                        } elseif ($ps['status'] === 'picked') {
                                            $statusClass = 'success';
                                            $statusText = 'Picked';
                                        } elseif ($ps['status'] === 'returned') {
                                            $statusClass = 'dark';
                                            $statusText = 'Returned';
                                        } elseif ($ps['archived']) {
                                            $statusClass = 'secondary';
                                            $statusText = 'Archived';
                                        }
                                        ?>
                                        <span class="badge bg-<?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($ps['first_name']) {
                                            echo htmlspecialchars($ps['first_name'] . ' ' . $ps['last_name']);
                                        } else {
                                            echo htmlspecialchars($ps['created_by_username'] ?? 'Unknown');
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($ps['created_at'])); ?></td>
                                    <td>
                                        <a href="../orders/view.php?id=<?php echo $ps['id']; ?>" class="btn btn-sm btn-secondary">
                                            View
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Change Orders -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Change Orders</h3>
                    </div>
                    <?php if (empty($changeOrders)): ?>
                    <div class="card-body">
                        <div class="empty">
                            <p class="empty-title">No change orders yet</p>
                            <p class="empty-subtitle text-muted">Change orders will appear here once created.</p>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table card-table table-vcenter">
                            <thead>
                                <tr>
                                    <th>Status</th>
                                    <th>Created By</th>
                                    <th>Created Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($changeOrders as $co): ?>
                                <tr>
                                    <td>
                                        <?php 
                                        $statusClass = 'secondary';
                                        $statusText = 'Draft';
                                        if ($co['status'] === 'approved') {
                                            $statusClass = 'info';
                                            $statusText = 'Approved';
                                        } elseif ($co['status'] === 'picked') {
                                            $statusClass = 'success';
                                            $statusText = 'Picked';
                                        } elseif ($co['status'] === 'returned') {
                                            $statusClass = 'dark';
                                            $statusText = 'Returned';
                                        } elseif ($co['archived']) {
                                            $statusClass = 'secondary';
                                            $statusText = 'Archived';
                                        }
                                        ?>
                                        <span class="badge bg-<?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($co['first_name']) {
                                            echo htmlspecialchars($co['first_name'] . ' ' . $co['last_name']);
                                        } else {
                                            echo htmlspecialchars($co['created_by_username'] ?? 'Unknown');
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($co['created_at'])); ?></td>
                                    <td>
                                        <a href="../change-orders/view.php?id=<?php echo $co['id']; ?>" class="btn btn-sm btn-secondary">
                                            View
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-md-4">
                <!-- Quick Stats -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h3 class="card-title">Quick Stats</h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="text-muted small">Pull Sheets</div>
                            <div class="h3 mb-0"><?php echo count($pullSheets); ?></div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Change Orders</div>
                            <div class="h3 mb-0"><?php echo count($changeOrders); ?></div>
                        </div>
                        <div class="mb-0">
                            <div class="text-muted small">Calendar Events</div>
                            <div class="h3 mb-0"><?php echo count($calendarEvents); ?></div>
                        </div>
                    </div>
                </div>

                <!-- Calendar Events -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Upcoming Events</h3>
                    </div>
                    <?php if (empty($calendarEvents)): ?>
                    <div class="card-body">
                        <div class="text-muted text-center py-3">
                            <small>No events scheduled</small>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php 
                        $displayedEvents = array_slice($calendarEvents, 0, 5);
                        foreach ($displayedEvents as $event): 
                        ?>
                        <div class="list-group-item">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <span class="avatar rounded" style="background-color: <?php echo htmlspecialchars($show['color']); ?>">
                                        <i class="ti ti-calendar icon text-white"></i>
                                    </span>
                                </div>
                                <div class="col text-truncate">
                                    <div class="text-reset d-block text-truncate">
                                        <?php echo htmlspecialchars($event['title']); ?>
                                    </div>
                                    <div class="text-muted text-truncate mt-n1">
                                        <small><?php echo date('M d, Y', strtotime($event['start_date'])); ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($calendarEvents) > 5): ?>
                    <div class="card-footer">
                        <a href="calendar.php?show_id=<?php echo $showId; ?>" class="btn btn-sm btn-secondary w-100">
                            View All Events
                        </a>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
