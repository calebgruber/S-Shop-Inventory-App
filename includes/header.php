<?php
/**
 * Header Include
 * Provides consistent header with Tabler UI framework
 */

if (!defined('BASE_PATH')) {
    require_once dirname(__DIR__) . '/config/config.php';
}

$currentUser = getCurrentUser();
$pageName = $pageName ?? 'S-Shop Inventory';
$siteName = getSetting('site_name', 'S-Shop Inventory System');
$siteLogo = getSetting('site_logo', null);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageName); ?> - <?php echo htmlspecialchars($siteName); ?></title>
    
    <!-- Tabler CSS -->
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/css/tabler.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.44.0/tabler-icons.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?php echo BASE_URL; ?>assets/css/custom.css" rel="stylesheet">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/images/favicon.png">
</head>
<body class="<?php echo isset($bodyClass) ? htmlspecialchars($bodyClass) : ''; ?>" data-bs-theme="<?php echo $_SESSION['theme'] ?? 'light'; ?>">
    <div class="page">
        <?php if (isLoggedIn() && !isset($hideNav)): ?>
        <!-- Navigation Header -->
        <header class="navbar navbar-expand-md d-print-none">
            <div class="container-xl">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <h1 class="navbar-brand navbar-brand-autodark d-none-navbar-horizontal pe-0 pe-md-3">
                    <a href="<?php echo BASE_URL; ?>dashboard.php" id="site-logo-link">
                        <?php if ($siteLogo && file_exists(UPLOADS_PATH . '/logos/' . $siteLogo)): ?>
                            <img src="<?php echo BASE_URL . 'uploads/logos/' . htmlspecialchars($siteLogo); ?>" 
                                 height="36" alt="<?php echo htmlspecialchars($siteName); ?>" 
                                 class="navbar-brand-image">
                        <?php else: ?>
                            <span id="site-name"><?php echo htmlspecialchars($siteName); ?></span>
                        <?php endif; ?>
                    </a>
                </h1>
                
                <div class="navbar-nav flex-row order-md-last">
                    <!-- Theme Toggle -->
                    <div class="nav-item d-none d-md-flex me-3">
                        <div class="btn-list">
                            <a href="#" class="nav-link px-0 hide-theme-dark" id="theme-toggle" title="Enable dark mode" data-bs-toggle="tooltip" data-bs-placement="bottom">
                                <i class="ti ti-moon"></i>
                            </a>
                            <a href="#" class="nav-link px-0 hide-theme-light" id="theme-toggle" title="Enable light mode" data-bs-toggle="tooltip" data-bs-placement="bottom">
                                <i class="ti ti-sun"></i>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Notifications -->
                    <div class="nav-item dropdown d-none d-md-flex me-3">
                        <a href="#" class="nav-link px-0" data-bs-toggle="dropdown" tabindex="-1" aria-label="Show notifications">
                            <i class="ti ti-bell"></i>
                            <span class="badge bg-red" id="notification-count" style="display: none;"></span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-arrow dropdown-menu-end dropdown-menu-card">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Notifications</h3>
                                </div>
                                <div class="list-group list-group-flush list-group-hoverable" id="notifications-list">
                                    <div class="list-group-item">
                                        <div class="text-muted">No new notifications</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- User Menu -->
                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown" aria-label="Open user menu">
                            <span class="avatar avatar-sm" style="background-image: url(https://api.dicebear.com/7.x/thumbs/svg?seed=<?php echo urlencode($currentUser['avatar_seed'] ?? $currentUser['username']); ?>&backgroundColor=<?php echo rand(0, 1) ? 'b6e3f4,c0aede,d1d4f9' : 'transparent'; ?>)"></span>
                            <div class="d-none d-xl-block ps-2">
                                <div><?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?></div>
                                <div class="mt-1 small text-muted"><?php echo ucfirst(htmlspecialchars(str_replace('_', ' ', $currentUser['role']))); ?></div>
                            </div>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                            <a href="<?php echo BASE_URL; ?>profile.php" class="dropdown-item">
                                <i class="ti ti-user me-2"></i>Profile
                            </a>
                            <a href="<?php echo BASE_URL; ?>settings.php" class="dropdown-item">
                                <i class="ti ti-settings me-2"></i>Settings
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="<?php echo BASE_URL; ?>logout.php" class="dropdown-item">
                                <i class="ti ti-logout me-2"></i>Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Main Navigation -->
        <header class="navbar-expand-md">
            <div class="collapse navbar-collapse" id="navbar-menu">
                <div class="navbar">
                    <div class="container-xl">
                        <ul class="navbar-nav">
                            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>dashboard.php">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-home"></i>
                                    </span>
                                    <span class="nav-link-title">Dashboard</span>
                                </a>
                            </li>
                            <li class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], 'inventory') !== false ? 'active' : ''; ?>">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>inventory/">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-package"></i>
                                    </span>
                                    <span class="nav-link-title">Inventory</span>
                                </a>
                            </li>
                            <?php if (hasRole(['admin', 'designer', 'production_audio'])): ?>
                            <li class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], 'shows') !== false ? 'active' : ''; ?>">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>shows/">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-theater"></i>
                                    </span>
                                    <span class="nav-link-title">Shows</span>
                                </a>
                            </li>
                            <li class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], 'orders') !== false ? 'active' : ''; ?>">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>orders/">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-clipboard-list"></i>
                                    </span>
                                    <span class="nav-link-title">Shop Orders</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if (hasRole(['admin'])): ?>
                            <li class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], 'repairs') !== false ? 'active' : ''; ?>">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>repairs/">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-tool"></i>
                                    </span>
                                    <span class="nav-link-title">Repairs</span>
                                </a>
                            </li>
                            <li class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], 'reports') !== false ? 'active' : ''; ?>">
                                <a class="nav-link" href="<?php echo BASE_URL; ?>reports/">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-chart-bar"></i>
                                    </span>
                                    <span class="nav-link-title">Reports</span>
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </header>
        <?php endif; ?>
        
        <div class="page-wrapper">
