<?php
require_once __DIR__ . '/functions.php';

$pageTitle = $pageTitle ?? 'Dashboard';
$appName = getSetting('app_name', 'Sound Shop Inventory');
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle . ' - ' . $appName); ?></title>
    
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
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
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
                    <div class="nav-item">
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
                            <li class="nav-item <?php echo $currentPage === 'index' ? 'active' : ''; ?>">
                                <a class="nav-link" href="index.php">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-home"></i>
                                    </span>
                                    <span class="nav-link-title">Dashboard</span>
                                </a>
                            </li>
                            <li class="nav-item <?php echo $currentPage === 'items' ? 'active' : ''; ?>">
                                <a class="nav-link" href="items.php">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-package"></i>
                                    </span>
                                    <span class="nav-link-title">Items</span>
                                </a>
                            </li>
                            <li class="nav-item <?php echo $currentPage === 'shows' ? 'active' : ''; ?>">
                                <a class="nav-link" href="shows.php">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-theater"></i>
                                    </span>
                                    <span class="nav-link-title">Shows</span>
                                </a>
                            </li>
                            <li class="nav-item <?php echo $currentPage === 'pullsheets' ? 'active' : ''; ?>">
                                <a class="nav-link" href="pullsheets.php">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-file-text"></i>
                                    </span>
                                    <span class="nav-link-title">Pullsheets</span>
                                </a>
                            </li>
                            <li class="nav-item <?php echo $currentPage === 'change_orders' ? 'active' : ''; ?>">
                                <a class="nav-link" href="change_orders.php">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-exchange"></i>
                                    </span>
                                    <span class="nav-link-title">Change Orders</span>
                                </a>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle <?php echo in_array($currentPage, ['pick_mode', 'return_mode']) ? 'active' : ''; ?>" 
                                   href="#" data-bs-toggle="dropdown">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-scan"></i>
                                    </span>
                                    <span class="nav-link-title">Operations</span>
                                </a>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item" href="pick_mode.php">Pick Mode</a>
                                    <a class="dropdown-item" href="return_mode.php">Return Mode</a>
                                </div>
                            </li>
                            <li class="nav-item <?php echo $currentPage === 'reports' ? 'active' : ''; ?>">
                                <a class="nav-link" href="reports.php">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-report"></i>
                                    </span>
                                    <span class="nav-link-title">Reports</span>
                                </a>
                            </li>
                            <li class="nav-item <?php echo $currentPage === 'settings' ? 'active' : ''; ?>">
                                <a class="nav-link" href="settings.php">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-settings"></i>
                                    </span>
                                    <span class="nav-link-title">Settings</span>
                                </a>
                            </li>
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
