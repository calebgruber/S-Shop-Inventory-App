<?php
/**
 * Dashboard - Main Landing Page
 * Shows navigation cards and cable color key
 */

require_once __DIR__ . '/config/config.php';

requireAuth();

$pageName = 'Dashboard';
$currentUser = getCurrentUser();

// Get statistics
$conn = getDbConnection();

// Total items
$totalItemsResult = executeQuery('SELECT COUNT(*) as count FROM items');
$totalItems = $totalItemsResult ? $totalItemsResult->fetch_assoc()['count'] : 0;

// Items in stock
$inStockResult = executeQuery('SELECT SUM(in_stock_quantity) as count FROM items');
$inStock = $inStockResult ? ($inStockResult->fetch_assoc()['count'] ?? 0) : 0;

// Active shows
$activeShowsResult = executeQuery('SELECT COUNT(*) as count FROM shows WHERE archived = 0');
$activeShows = $activeShowsResult ? $activeShowsResult->fetch_assoc()['count'] : 0;

// Pending requests (for admins)
$pendingRequests = 0;
if (hasRole('admin')) {
    $pendingRequestsResult = executeQuery('SELECT COUNT(*) as count FROM student_requests WHERE status = ?', ['pending'], 's');
    $pendingRequests = $pendingRequestsResult ? $pendingRequestsResult->fetch_assoc()['count'] : 0;
}

include __DIR__ . '/includes/header.php';
?>

<!-- Page Header -->
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">
                    Dashboard
                </h2>
                <div class="text-muted mt-1">Welcome back, <?php echo htmlspecialchars($currentUser['first_name']); ?>!</div>
            </div>
        </div>
    </div>
</div>

<!-- Page Body -->
<div class="page-body">
    <div class="container-xl">
        <!-- Statistics Cards -->
        <div class="row row-deck row-cards mb-3">
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="bg-primary text-white avatar">
                                    <i class="ti ti-package"></i>
                                </span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium">
                                    <?php echo number_format($totalItems); ?> Items
                                </div>
                                <div class="text-muted">
                                    Total inventory
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="bg-success text-white avatar">
                                    <i class="ti ti-checkbox"></i>
                                </span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium">
                                    <?php echo number_format($inStock); ?> Available
                                </div>
                                <div class="text-muted">
                                    Items in stock
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="bg-info text-white avatar">
                                    <i class="ti ti-theater"></i>
                                </span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium">
                                    <?php echo number_format($activeShows); ?> Shows
                                </div>
                                <div class="text-muted">
                                    Currently active
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if (hasRole('admin') && $pendingRequests > 0): ?>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="bg-warning text-white avatar">
                                    <i class="ti ti-bell"></i>
                                </span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium">
                                    <?php echo number_format($pendingRequests); ?> Requests
                                </div>
                                <div class="text-muted">
                                    Pending approval
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Main Navigation Cards -->
        <div class="row row-cards">
            <div class="col-12">
                <h3 class="mb-3">Quick Actions</h3>
            </div>
            
            <!-- Inventory -->
            <div class="col-sm-6 col-lg-4">
                <a href="<?php echo BASE_URL; ?>inventory/" class="card card-link card-link-pop">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="avatar avatar-lg" style="background-image: none; background-color: rgba(32, 107, 196, 0.1);">
                                    <i class="ti ti-package" style="font-size: 2rem; color: #206bc4;"></i>
                                </span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium h3 mb-1">Inventory</div>
                                <div class="text-muted">Browse and manage items</div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            
            <?php if (hasRole(['admin', 'designer', 'production_audio'])): ?>
            <!-- Shows -->
            <div class="col-sm-6 col-lg-4">
                <a href="<?php echo BASE_URL; ?>shows/" class="card card-link card-link-pop">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="avatar avatar-lg" style="background-image: none; background-color: rgba(94, 114, 228, 0.1);">
                                    <i class="ti ti-theater" style="font-size: 2rem; color: #5e72e4;"></i>
                                </span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium h3 mb-1">Shows</div>
                                <div class="text-muted">Manage productions</div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            
            <!-- Shop Orders -->
            <div class="col-sm-6 col-lg-4">
                <a href="<?php echo BASE_URL; ?>orders/" class="card card-link card-link-pop">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="avatar avatar-lg" style="background-image: none; background-color: rgba(0, 180, 216, 0.1);">
                                    <i class="ti ti-clipboard-list" style="font-size: 2rem; color: #00b4d8;"></i>
                                </span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium h3 mb-1">Shop Orders</div>
                                <div class="text-muted">Pull sheets and orders</div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            
            <!-- Change Orders -->
            <div class="col-sm-6 col-lg-4">
                <a href="<?php echo BASE_URL; ?>change-orders/" class="card card-link card-link-pop">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="avatar avatar-lg" style="background-image: none; background-color: rgba(249, 177, 21, 0.1);">
                                    <i class="ti ti-file-diff" style="font-size: 2rem; color: #f9b115;"></i>
                                </span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium h3 mb-1">Change Orders</div>
                                <div class="text-muted">Modify existing orders</div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <?php endif; ?>
            
            <?php if (hasRole(['admin', 'production_audio'])): ?>
            <!-- Pick Mode -->
            <div class="col-sm-6 col-lg-4">
                <a href="<?php echo BASE_URL; ?>pick.php" class="card card-link card-link-pop">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="avatar avatar-lg" style="background-image: none; background-color: rgba(45, 206, 137, 0.1);">
                                    <i class="ti ti-checkbox" style="font-size: 2rem; color: #2dce89;"></i>
                                </span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium h3 mb-1">Pick Mode</div>
                                <div class="text-muted">Scan and pick items</div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            
            <!-- Return Mode -->
            <div class="col-sm-6 col-lg-4">
                <a href="<?php echo BASE_URL; ?>return.php" class="card card-link card-link-pop">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="avatar avatar-lg" style="background-image: none; background-color: rgba(251, 99, 64, 0.1);">
                                    <i class="ti ti-arrow-back" style="font-size: 2rem; color: #fb6340;"></i>
                                </span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium h3 mb-1">Return Mode</div>
                                <div class="text-muted">Return checked out items</div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <?php endif; ?>
            
            <!-- Student Requests (not for admins per requirements) -->
            <?php if (!hasRole('admin')): ?>
            <div class="col-sm-6 col-lg-4">
                <a href="<?php echo BASE_URL; ?>requests/" class="card card-link card-link-pop">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="avatar avatar-lg" style="background-image: none; background-color: rgba(138, 43, 226, 0.1);">
                                    <i class="ti ti-mail" style="font-size: 2rem; color: #8a2be2;"></i>
                                </span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium h3 mb-1">Student Requests</div>
                                <div class="text-muted">Submit equipment requests</div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <?php endif; ?>
            
            <?php if (hasRole('admin')): ?>
            <!-- Reports -->
            <div class="col-sm-6 col-lg-4">
                <a href="<?php echo BASE_URL; ?>reports/" class="card card-link card-link-pop">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="avatar avatar-lg" style="background-image: none; background-color: rgba(102, 16, 242, 0.1);">
                                    <i class="ti ti-chart-bar" style="font-size: 2rem; color: #6610f2;"></i>
                                </span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium h3 mb-1">Reports</div>
                                <div class="text-muted">Analytics and reports</div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            
            <!-- Settings (not shown on dashboard per requirements, but accessible via menu)
            <div class="col-sm-6 col-lg-4">
                <a href="<?php echo BASE_URL; ?>settings/" class="card card-link card-link-pop">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="avatar avatar-lg" style="background-image: none; background-color: rgba(108, 117, 125, 0.1);">
                                    <i class="ti ti-settings" style="font-size: 2rem; color: #6c757d;"></i>
                                </span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium h3 mb-1">Settings</div>
                                <div class="text-muted">Configure system</div>
                            </div>
                        </div>
                    </div>
                </a>
            </div> -->
            <?php endif; ?>
        </div>
        
        <!-- Cable Color Key -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Cable Color Key</h3>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-6 col-sm-4 col-md-2">
                                <div class="d-flex align-items-center" style="cursor: pointer;" id="cable-red" title="Click for a surprise!">
                                    <div class="me-3" style="width: 40px; height: 40px; background-color: #dc3545; border-radius: 4px;"></div>
                                    <div>
                                        <div class="font-weight-medium">Red</div>
                                        <div class="text-muted small">5'</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-sm-4 col-md-2">
                                <div class="d-flex align-items-center">
                                    <div class="me-3" style="width: 40px; height: 40px; background-color: #6c757d; border-radius: 4px;"></div>
                                    <div>
                                        <div class="font-weight-medium">Gray</div>
                                        <div class="text-muted small">10'</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-sm-4 col-md-2">
                                <div class="d-flex align-items-center">
                                    <div class="me-3" style="width: 40px; height: 40px; background-color: #6f42c1; border-radius: 4px;"></div>
                                    <div>
                                        <div class="font-weight-medium">Purple</div>
                                        <div class="text-muted small">15'</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-sm-4 col-md-2">
                                <div class="d-flex align-items-center">
                                    <div class="me-3" style="width: 40px; height: 40px; background-color: #ffc107; border-radius: 4px;"></div>
                                    <div>
                                        <div class="font-weight-medium">Yellow</div>
                                        <div class="text-muted small">25'</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-sm-4 col-md-2">
                                <div class="d-flex align-items-center">
                                    <div class="me-3" style="width: 40px; height: 40px; background-color: #0d6efd; border-radius: 4px;"></div>
                                    <div>
                                        <div class="font-weight-medium">Blue</div>
                                        <div class="text-muted small">50'</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6 col-sm-4 col-md-2">
                                <div class="d-flex align-items-center">
                                    <div class="me-3" style="width: 40px; height: 40px; background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px;"></div>
                                    <div>
                                        <div class="font-weight-medium">White</div>
                                        <div class="text-muted small">100'</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
