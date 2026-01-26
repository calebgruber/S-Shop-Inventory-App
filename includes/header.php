<?php
require_once __DIR__ . '/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Require login for all pages except login.php
if (basename($_SERVER['PHP_SELF']) !== 'login.php') {
    requireLogin();
}

$pageTitle = $pageTitle ?? 'Dashboard';
$appName = getSetting('app_name', 'Sound Shop Inventory');
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle . ' - ' . $appName); ?></title>
    
    <!-- Apply theme immediately to prevent flash -->
    <script>
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-bs-theme', savedTheme);
    </script>
    
    <!-- Tabler CSS -->
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta19/dist/css/tabler.min.css" rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" rel="stylesheet"/>
    
    <style>
        /* Modern UI with consistent border radius */
        :root {
            --modern-radius: 2px;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        
        /* Apply consistent border radius */
        .card,
        .btn,
        .form-control,
        .form-select,
        .input-group,
        .modal-content,
        .alert,
        .badge,
        .dropdown-menu {
            border-radius: var(--modern-radius) !important;
        }
        
        .input-group .btn,
        .input-group .form-control {
            border-radius: 0 !important;
        }
        
        .input-group .btn:first-child,
        .input-group .form-control:first-child {
            border-top-left-radius: var(--modern-radius) !important;
            border-bottom-left-radius: var(--modern-radius) !important;
        }
        
        .input-group .btn:last-child,
        .input-group .form-control:last-child {
            border-top-right-radius: var(--modern-radius) !important;
            border-bottom-right-radius: var(--modern-radius) !important;
        }
        
        .barcode-autofocus {
            font-size: 1.2rem;
            padding: 0.75rem;
        }
        
        /* Pick/Return mode card states */
        .item-card-incomplete {
            border-left: 4px solid #d63939;
        }
        .item-card-complete {
            border-left: 4px solid #2fb344;
        }
        .item-card-overage {
            border-left: 4px solid #f59f00;
        }
        
        /* Fullscreen mode */
        .fullscreen-mode {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            background: var(--tblr-body-bg);
            z-index: 9999;
            overflow-y: auto;
            padding: 2rem;
        }
        
        .fullscreen-mode .container-fluid {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        /* Modern card hover effects */
        .stat-card {
            transition: transform 0.2s, box-shadow 0.2s;
            position: relative;
            overflow: hidden;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .dashboard-icon-bg {
            font-size: 3rem;
            opacity: 0.1;
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
        }
        
        .quick-action-btn {
            min-height: 100px;
            font-size: 1.05rem;
        }
        
        /* Item cards in pick/return mode */
        .item-scan-card {
            transition: all 0.3s ease;
        }
        
        .item-scan-card:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        
        /* Alert dark mode support */
        [data-bs-theme="dark"] .alert-success {
            background-color: rgba(47, 179, 68, 0.15);
            border-color: rgba(47, 179, 68, 0.3);
            color: #2fb344;
        }
        
        [data-bs-theme="dark"] .alert-danger {
            background-color: rgba(214, 57, 57, 0.15);
            border-color: rgba(214, 57, 57, 0.3);
            color: #d63939;
        }
        
        [data-bs-theme="dark"] .alert-warning {
            background-color: rgba(245, 159, 0, 0.15);
            border-color: rgba(245, 159, 0, 0.3);
            color: #f59f00;
        }
        
        [data-bs-theme="dark"] .alert-info {
            background-color: rgba(66, 153, 225, 0.15);
            border-color: rgba(66, 153, 225, 0.3);
            color: #4299e1;
        }
    </style>
</head>
<body>
    <div class="page">
        <!-- Navbar -->
        <header class="navbar navbar-expand-md navbar-light d-print-none">
            <div class="container-xl">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <h1 class="navbar-brand navbar-brand-autodark d-none-navbar-horizontal pe-0 pe-md-3">
                    <a href="index.php">
                        <?php echo htmlspecialchars($appName); ?>
                    </a>
                </h1>
                <div class="navbar-nav flex-row order-md-last">
                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown" aria-label="User menu" aria-expanded="false">
                            <span class="avatar avatar-sm" style="background-image: url('<?php echo getUserAvatarUrl($currentUser); ?>')"></span>
                            <div class="d-none d-xl-block ps-2">
                                <div><?php echo htmlspecialchars($currentUser['name']); ?></div>
                                <div class="mt-1 small text-muted"><?php echo ucfirst($currentUser['role']); ?></div>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#" id="theme-toggle-dropdown">
                                <i class="ti ti-moon icon me-2"></i>
                                Toggle Dark Mode
                            </a></li>
                            <?php if (isAdmin()): ?>
                            <li><a class="dropdown-item" href="user_management.php">
                                <i class="ti ti-users icon me-2"></i>
                                User Management
                            </a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">
                                <i class="ti ti-logout icon me-2"></i>
                                Logout
                            </a></li>
                        </ul>
                    </div>
                    <div class="nav-item ms-2">
                        <a href="#" class="nav-link px-0" id="theme-toggle" title="Toggle dark mode">
                            <i class="ti ti-moon icon"></i>
                        </a>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Navigation -->
        <header class="navbar-expand-md">
            <div class="collapse navbar-collapse" id="navbar-menu">
                <div class="navbar navbar-light">
                    <div class="container-xl">
                        <ul class="navbar-nav">
                            <?php if (hasPermission('dashboard')): ?>
                            <li class="nav-item <?php echo $currentPage === 'index' ? 'active' : ''; ?>">
                                <a class="nav-link" href="index.php">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-home"></i>
                                    </span>
                                    <span class="nav-link-title">Dashboard</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php if (hasPermission('inventory')): ?>
                            <li class="nav-item <?php echo $currentPage === 'items' || $currentPage === 'item_edit' ? 'active' : ''; ?>">
                                <a class="nav-link" href="items.php">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-package"></i>
                                    </span>
                                    <span class="nav-link-title">Inventory</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php if (hasPermission('shows')): ?>
                            <li class="nav-item <?php echo $currentPage === 'shows' ? 'active' : ''; ?>">
                                <a class="nav-link" href="shows.php">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-theater"></i>
                                    </span>
                                    <span class="nav-link-title">Shows</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php if (hasPermission('pullsheets')): ?>
                            <li class="nav-item <?php echo $currentPage === 'pullsheets' ? 'active' : ''; ?>">
                                <a class="nav-link" href="pullsheets.php">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-file-text"></i>
                                    </span>
                                    <span class="nav-link-title">Pullsheets</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php if (hasPermission('change_orders')): ?>
                            <li class="nav-item <?php echo $currentPage === 'change_orders' ? 'active' : ''; ?>">
                                <a class="nav-link" href="change_orders.php">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-exchange"></i>
                                    </span>
                                    <span class="nav-link-title">Change Orders</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php if (hasPermission('pick_mode') || hasPermission('return_mode')): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle <?php echo in_array($currentPage, ['pick_mode', 'return_mode']) ? 'active' : ''; ?>" 
                                   href="#" data-bs-toggle="dropdown" aria-expanded="false" role="button">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-scan"></i>
                                    </span>
                                    <span class="nav-link-title">Operations</span>
                                </a>
                                <ul class="dropdown-menu">
                                    <?php if (hasPermission('pick_mode')): ?>
                                    <li><a class="dropdown-item" href="pick_mode.php">Pick Mode</a></li>
                                    <?php endif; ?>
                                    <?php if (hasPermission('return_mode')): ?>
                                    <li><a class="dropdown-item" href="return_mode.php">Return Mode</a></li>
                                    <?php endif; ?>
                                </ul>
                            </li>
                            <?php endif; ?>
                            
                            <?php if (hasPermission('reports')): ?>
                            <li class="nav-item <?php echo $currentPage === 'reports' ? 'active' : ''; ?>">
                                <a class="nav-link" href="reports.php">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-report"></i>
                                    </span>
                                    <span class="nav-link-title">Reports</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php if (hasPermission('paperwork')): ?>
                            <li class="nav-item <?php echo $currentPage === 'paperwork' ? 'active' : ''; ?>">
                                <a class="nav-link" href="paperwork.php">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-files"></i>
                                    </span>
                                    <span class="nav-link-title">Paperwork</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php if (hasPermission('repairs')): ?>
                            <li class="nav-item <?php echo $currentPage === 'repairs' ? 'active' : ''; ?>">
                                <a class="nav-link" href="repairs.php">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-tool"></i>
                                    </span>
                                    <span class="nav-link-title">Repairs</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php if (hasPermission('student_requests')): ?>
                            <li class="nav-item <?php echo $currentPage === 'student_requests' ? 'active' : ''; ?>">
                                <a class="nav-link" href="student_requests.php">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-clipboard-list"></i>
                                    </span>
                                    <span class="nav-link-title">Requests</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php if (isAdmin()): ?>
                            <li class="nav-item <?php echo $currentPage === 'settings' ? 'active' : ''; ?>">
                                <a class="nav-link" href="settings.php">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-settings"></i>
                                    </span>
                                    <span class="nav-link-title">Settings</span>
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </header>
        
        <div class="page-wrapper">
            <div class="page-header d-print-none">
                <div class="container-xl">
                    <div class="row g-2 align-items-center">
                        <div class="col">
                            <h2 class="page-title">
                                <?php echo htmlspecialchars($pageTitle); ?>
                            </h2>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="page-body">
                <div class="container-xl">
                    <?php
                    $alert = getAlert();
                    if ($alert):
                    ?>
                    <div class="alert alert-<?php echo $alert['type']; ?> alert-dismissible" role="alert">
                        <div class="d-flex">
                            <div>
                                <?php echo htmlspecialchars($alert['message']); ?>
                            </div>
                        </div>
                        <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
                    </div>
                    <?php endif; ?>
