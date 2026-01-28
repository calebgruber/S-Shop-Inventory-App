<?php
/**
 * Change Orders List
 * Display all change orders with search, filter, and pagination
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/helpers.php';

requireAuth();

$pageName = 'Change Orders';
$currentUser = getCurrentUser();
$userId = $currentUser['id'];
$userRole = $currentUser['role'];

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 30;
$offset = ($page - 1) * $perPage;

// Search and filters
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$showFilter = $_GET['show'] ?? '';

// Build query
$where = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where[] = '(co.barcode LIKE ? OR s.name LIKE ?)';
    $searchParam = '%' . $search . '%';
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= 'ss';
}

if (!empty($statusFilter)) {
    $where[] = 'co.status = ?';
    $params[] = $statusFilter;
    $types .= 's';
}

if (!empty($showFilter)) {
    $where[] = 'co.show_id = ?';
    $params[] = $showFilter;
    $types .= 'i';
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$countQuery = "SELECT COUNT(*) as total FROM change_orders co $whereClause";
$countResult = executeQuery($countQuery, $params, $types);
$totalItems = 0;
if ($countResult) {
    $countRow = fetchAssoc($countResult);
    $totalItems = $countRow['total'];
}

$totalPages = ceil($totalItems / $perPage);

// Get change orders
$query = "SELECT co.*, 
          s.name as show_name, s.color as show_color,
          u.username as creator_username, u.first_name as creator_first, u.last_name as creator_last
          FROM change_orders co
          LEFT JOIN shows s ON co.show_id = s.id
          LEFT JOIN users u ON co.created_by = u.id
          $whereClause
          ORDER BY co.created_at DESC
          LIMIT ? OFFSET ?";

$params[] = $perPage;
$params[] = $offset;
$types .= 'ii';

$changeOrdersResult = executeQuery($query, $params, $types);

$changeOrders = [];
if ($changeOrdersResult) {
    while ($row = fetchAssoc($changeOrdersResult)) {
        $changeOrders[] = $row;
    }
}

// Get all shows for filter dropdown
$shows = getShowsForUser($userId, $userRole);

// Get statistics
$statsWhere = '';
$statsParams = [];
$statsTypes = '';

$statsQuery = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending_approval' THEN 1 ELSE 0 END) as pending_approval,
    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft
    FROM change_orders co";

if ($userRole !== 'admin') {
    $statsQuery .= " LEFT JOIN shows s ON co.show_id = s.id 
                     WHERE (s.designer_id = ? OR s.production_audio_id = ?)";
    $statsParams = [$userId, $userId];
    $statsTypes = 'ii';
}

$statsResult = executeQuery($statsQuery, $statsParams, $statsTypes);
$stats = $statsResult ? fetchAssoc($statsResult) : ['total' => 0, 'pending_approval' => 0, 'draft' => 0];

// Success/error messages
$successMessage = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);
$errorMessage = $_SESSION['error_message'] ?? '';
unset($_SESSION['error_message']);

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">
                    <i class="ti ti-arrows-exchange me-2"></i>
                    Change Orders
                </h2>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <a href="add.php" class="btn btn-primary">
                    <i class="ti ti-plus icon"></i>
                    Add Change Order
                </a>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <?php if ($successMessage): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <div class="d-flex">
                <div><i class="ti ti-check icon me-2"></i></div>
                <div><?php echo htmlspecialchars($successMessage); ?></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <div class="d-flex">
                <div><i class="ti ti-alert-circle icon me-2"></i></div>
                <div><?php echo htmlspecialchars($errorMessage); ?></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-3">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Total Change Orders</div>
                        </div>
                        <div class="h1 mb-0"><?php echo $stats['total']; ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Pending Approval</div>
                        </div>
                        <div class="h1 mb-0"><?php echo $stats['pending_approval']; ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Drafts</div>
                        </div>
                        <div class="h1 mb-0"><?php echo $stats['draft']; ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" class="row g-2">
                    <div class="col-md-4">
                        <label class="form-label">Search</label>
                        <input type="text" class="form-control" name="search" 
                               placeholder="Search by barcode or show..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="">All Statuses</option>
                            <option value="draft" <?php echo $statusFilter === 'draft' ? 'selected' : ''; ?>>Draft</option>
                            <option value="pending_approval" <?php echo $statusFilter === 'pending_approval' ? 'selected' : ''; ?>>Pending Approval</option>
                            <option value="approved" <?php echo $statusFilter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Show</label>
                        <select class="form-select" name="show">
                            <option value="">All Shows</option>
                            <?php foreach ($shows as $show): ?>
                            <option value="<?php echo $show['id']; ?>" 
                                    <?php echo $showFilter == $show['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($show['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="ti ti-search icon"></i>
                            Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Change Orders Table -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Change Orders List</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Barcode</th>
                            <th>Show</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th>Created Date</th>
                            <th class="w-1">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($changeOrders)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                <div class="empty">
                                    <div class="empty-icon">
                                        <i class="ti ti-arrows-exchange icon"></i>
                                    </div>
                                    <p class="empty-title">No change orders found</p>
                                    <p class="empty-subtitle text-muted">
                                        <?php if (!empty($search) || !empty($statusFilter) || !empty($showFilter)): ?>
                                            Try adjusting your filters or search terms.
                                        <?php else: ?>
                                            Get started by creating your first change order.
                                        <?php endif; ?>
                                    </p>
                                    <?php if (empty($search) && empty($statusFilter) && empty($showFilter)): ?>
                                    <div class="empty-action">
                                        <a href="add.php" class="btn btn-primary">
                                            <i class="ti ti-plus icon"></i>
                                            Add Change Order
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($changeOrders as $changeOrder): ?>
                            <tr>
                                <td>
                                    <span class="text-monospace"><?php echo htmlspecialchars($changeOrder['barcode']); ?></span>
                                </td>
                                <td>
                                    <?php if ($changeOrder['show_name']): ?>
                                    <span class="avatar avatar-sm me-2" style="background-color: <?php echo htmlspecialchars($changeOrder['show_color'] ?? '#3b82f6'); ?>"></span>
                                    <a href="<?php echo BASE_URL; ?>shows/view.php?id=<?php echo $changeOrder['show_id']; ?>">
                                        <?php echo htmlspecialchars($changeOrder['show_name']); ?>
                                    </a>
                                    <?php else: ?>
                                    <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo getChangeOrderStatusBadge($changeOrder['status']); ?>
                                </td>
                                <td>
                                    <?php 
                                    if ($changeOrder['creator_first'] && $changeOrder['creator_last']) {
                                        echo htmlspecialchars($changeOrder['creator_first'] . ' ' . $changeOrder['creator_last']);
                                    } else {
                                        echo htmlspecialchars($changeOrder['creator_username']);
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php echo date('M d, Y g:i A', strtotime($changeOrder['created_at'])); ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="view.php?id=<?php echo $changeOrder['id']; ?>" 
                                           class="btn btn-sm btn-secondary" 
                                           title="View">
                                            <i class="ti ti-eye icon"></i>
                                        </a>
                                        <?php if ($changeOrder['status'] === 'draft' && ($userRole === 'admin' || $changeOrder['created_by'] == $userId)): ?>
                                        <a href="edit.php?id=<?php echo $changeOrder['id']; ?>" 
                                           class="btn btn-sm btn-primary" 
                                           title="Edit">
                                            <i class="ti ti-edit icon"></i>
                                        </a>
                                        <a href="delete.php?id=<?php echo $changeOrder['id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           title="Delete"
                                           onclick="return confirm('Are you sure you want to delete this change order?');">
                                            <i class="ti ti-trash icon"></i>
                                        </a>
                                        <?php endif; ?>
                                        <?php if ($changeOrder['status'] === 'pending_approval' && $userRole === 'admin'): ?>
                                        <a href="finalize.php?id=<?php echo $changeOrder['id']; ?>&action=approve" 
                                           class="btn btn-sm btn-success" 
                                           title="Approve"
                                           onclick="return confirm('Approve this change order?');">
                                            <i class="ti ti-check icon"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($totalPages > 1): ?>
            <div class="card-footer d-flex align-items-center">
                <p class="m-0 text-muted">
                    Showing <span><?php echo $offset + 1; ?></span> to 
                    <span><?php echo min($offset + $perPage, $totalItems); ?></span> of 
                    <span><?php echo $totalItems; ?></span> entries
                </p>
                <ul class="pagination m-0 ms-auto">
                    <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($statusFilter) ? '&status=' . urlencode($statusFilter) : ''; ?><?php echo !empty($showFilter) ? '&show=' . urlencode($showFilter) : ''; ?>">
                            <i class="ti ti-chevron-left"></i> prev
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($statusFilter) ? '&status=' . urlencode($statusFilter) : ''; ?><?php echo !empty($showFilter) ? '&show=' . urlencode($showFilter) : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($statusFilter) ? '&status=' . urlencode($statusFilter) : ''; ?><?php echo !empty($showFilter) ? '&show=' . urlencode($showFilter) : ''; ?>">
                            next <i class="ti ti-chevron-right"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
