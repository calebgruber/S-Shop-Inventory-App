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

// Determine current page based on directory structure
$scriptName = $_SERVER['PHP_SELF'];
$pathParts = explode('/', trim($scriptName, '/'));

// Check if we're in a subdirectory
if (count($pathParts) >= 2) {
    // Get the directory name (e.g., "items", "pullsheets", "operations")
    $directory = $pathParts[count($pathParts) - 2];
    $fileName = basename($scriptName, '.php');
    
    // Map directory/file combinations to page identifiers
    if ($directory === 'operations') {
        $currentPage = ($fileName === 'pick') ? 'pick_mode' : (($fileName === 'return') ? 'return_mode' : $fileName);
    } elseif ($directory === 'change-orders') {
        $currentPage = ($fileName === 'index') ? 'change_orders' : 'change_order_' . $fileName;
    } elseif ($directory === 'admin') {
        $currentPage = $fileName; // settings or approvals
    } elseif ($directory === 'tools') {
        $currentPage = str_replace('-', '_', $fileName);
    } elseif ($directory === 'api') {
        $currentPage = str_replace('-', '_', $fileName);
    } elseif ($directory === 'auth') {
        $currentPage = str_replace('-', '_', $fileName);
    } else {
        // For other directories (items, shows, pullsheets, etc.)
        if ($fileName === 'index') {
            $currentPage = $directory;
        } else {
            // For create, edit, view pages
            $currentPage = rtrim($directory, 's') . '_' . $fileName;
        }
    }
} else {
    // Root level file
    $currentPage = basename($scriptName, '.php');
}

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
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Tabler CSS -->
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta19/dist/css/tabler.min.css" rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" rel="stylesheet"/>
    
    <!-- Tabler JS in head for dropdown functionality -->
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/js/tabler.min.js"></script>
    
    <style>
        /* Modern UI with consistent border radius */
        :root {
            --modern-radius: 2px;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        
        /* Pink Mode - Secret Easter Egg - OVERLOADING PINK! 🎀 */
        body.pink-mode {
            --bs-primary: #ff69b4 !important;
            --bs-primary-rgb: 255, 105, 180 !important;
            --bs-link-color: #ff1493 !important;
            --bs-link-hover-color: #c71585 !important;
            background: linear-gradient(180deg, #ffe6f0 0%, #fff0f8 50%, #ffe6f0 100%) !important;
        }
        
        body.pink-mode .page-wrapper {
            background: linear-gradient(180deg, #ffe6f0 0%, #fff0f8 50%, #ffe6f0 100%) !important;
        }
        
        body.pink-mode .page-body {
            background: transparent !important;
        }
        
        body.pink-mode .navbar,
        body.pink-mode .card-header,
        body.pink-mode .btn-primary {
            background: linear-gradient(135deg, #ff69b4 0%, #ff1493 100%) !important;
            border-color: #ff69b4 !important;
            color: white !important;
        }
        
        body.pink-mode .navbar-brand img {
            opacity: 0.3;
            filter: sepia(100%) hue-rotate(280deg) saturate(500%);
        }
        
        body.pink-mode .navbar-brand::after {
            content: '🐱';
            position: absolute;
            font-size: 2rem;
            margin-left: -40px;
            opacity: 0.5;
        }
        
        body.pink-mode .btn-primary:hover {
            background: linear-gradient(135deg, #ff1493 0%, #c71585 100%) !important;
        }
        
        body.pink-mode .card {
            border-color: #ff69b4 !important;
            background: rgba(255, 255, 255, 0.9) !important;
            box-shadow: 0 2px 8px rgba(255, 105, 180, 0.2) !important;
        }
        
        body.pink-mode .card-header {
            border-bottom: 2px solid #ff69b4 !important;
        }
        
        body.pink-mode a:not(.btn) {
            color: #ff1493 !important;
        }
        
        body.pink-mode a:not(.btn):hover {
            color: #c71585 !important;
        }
        
        body.pink-mode .badge {
            background-color: #ff69b4 !important;
            color: white !important;
        }
        
        body.pink-mode .form-control:focus,
        body.pink-mode .form-select:focus {
            border-color: #ff69b4 !important;
            box-shadow: 0 0 0 0.25rem rgba(255, 105, 180, 0.25) !important;
        }
        
        body.pink-mode .btn-secondary,
        body.pink-mode .btn-info,
        body.pink-mode .btn-success {
            background: linear-gradient(135deg, #ffa6d2 0%, #ff69b4 100%) !important;
            border-color: #ff69b4 !important;
            color: white !important;
        }
        
        body.pink-mode .list-group-item {
            background: rgba(255, 230, 240, 0.3) !important;
            border-color: #ffb6d9 !important;
        }
        
        body.pink-mode .table {
            background: rgba(255, 255, 255, 0.8) !important;
        }
        
        body.pink-mode .table-striped tbody tr:nth-of-type(odd) {
            background: rgba(255, 182, 217, 0.1) !important;
        }
        
        /* Pink Mode - Floating Hearts Animation */
        @keyframes floatHearts {
            0% {
                transform: translateY(0) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100vh) rotate(360deg);
                opacity: 0;
            }
        }
        
        body.pink-mode .heart {
            position: fixed;
            font-size: 2rem;
            animation: floatHearts 8s linear infinite;
            pointer-events: none;
            z-index: 9999;
        }
        
        body.pink-mode .heart:nth-child(1) { left: 10%; animation-delay: 0s; font-size: 1.5rem; }
        body.pink-mode .heart:nth-child(2) { left: 20%; animation-delay: 1s; font-size: 2rem; }
        body.pink-mode .heart:nth-child(3) { left: 30%; animation-delay: 2s; font-size: 1.8rem; }
        body.pink-mode .heart:nth-child(4) { left: 40%; animation-delay: 3s; font-size: 1.6rem; }
        body.pink-mode .heart:nth-child(5) { left: 50%; animation-delay: 4s; font-size: 2.2rem; }
        body.pink-mode .heart:nth-child(6) { left: 60%; animation-delay: 5s; font-size: 1.7rem; }
        body.pink-mode .heart:nth-child(7) { left: 70%; animation-delay: 6s; font-size: 1.9rem; }
        body.pink-mode .heart:nth-child(8) { left: 80%; animation-delay: 7s; font-size: 2.1rem; }
        body.pink-mode .heart:nth-child(9) { left: 90%; animation-delay: 0.5s; font-size: 1.4rem; }
        
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
        
        /* Notification badge */
        .nav-link {
            position: relative;
        }
        
        .badge-notification {
            position: absolute;
            top: 0;
            right: 0;
            font-size: 0.625rem;
            padding: 0.25em 0.4em;
            min-width: 1.25rem;
        }
        
        .notification-item {
            cursor: pointer;
        }
        
        .notification-item:hover {
            background-color: var(--tblr-hover-bg);
        }
        
        /* Loading Screen - Super quick page transition */
        #pageLoadingScreen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--tblr-body-bg);
            z-index: 99999;
            display: none;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.15s ease-in-out;
        }
        
        #pageLoadingScreen.show {
            display: flex;
            opacity: 1;
        }
        
        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 3px solid rgba(var(--tblr-primary-rgb), 0.1);
            border-top-color: var(--tblr-primary);
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        [data-bs-theme="dark"] #pageLoadingScreen {
            background: var(--tblr-body-bg);
        }
    </style>
    
    <!-- Random Cursor Easter Egg - 1% chance -->
    <!-- Note: External image dependency - consider hosting locally for production -->
    <script>
        // 1 in 100 chance to show special cursor
        if (Math.random() < 0.01) {
            document.addEventListener('DOMContentLoaded', function() {
                // Set cursor to the image
                const cursorStyle = 'url(https://preview.tabler.io/static/avatars/000m.jpg), auto';
                document.body.style.cursor = cursorStyle;
                
                // Apply to all elements for consistency
                const style = document.createElement('style');
                style.textContent = `
                    * {
                        cursor: url(https://preview.tabler.io/static/avatars/000m.jpg), auto !important;
                    }
                `;
                document.head.appendChild(style);
                
                console.log('🎉 Special cursor Easter egg activated! (1% chance)');
            });
        }
    </script>
    
    <!-- Universal Form Reload Handler -->
    <script>
    // Loading screen function - show during page transitions
    function showLoading() {
        const loadingScreen = document.getElementById('pageLoadingScreen');
        if (loadingScreen) {
            loadingScreen.classList.add('show');
        }
    }
    
    // Hide loading screen when page loads
    window.addEventListener('load', function() {
        const loadingScreen = document.getElementById('pageLoadingScreen');
        if (loadingScreen) {
            loadingScreen.classList.remove('show');
        }
    });
    
    // Show loading on page unload (when navigating away)
    window.addEventListener('beforeunload', function() {
        showLoading();
    });
    
    // Ensure ALL forms reload page after submission
    document.addEventListener('DOMContentLoaded', function() {
        // Get all forms on the page
        const forms = document.querySelectorAll('form');
        
        forms.forEach(function(form) {
            // Skip forms explicitly marked as no-reload
            if (form.hasAttribute('data-no-reload')) {
                return;
            }
            
            // Add submit handler
            form.addEventListener('submit', function(e) {
                // Show loading screen
                showLoading();
                
                // Store form reference
                const thisForm = this;
                
                // Check if form uses AJAX (has data-ajax attribute)
                if (thisForm.hasAttribute('data-ajax')) {
                    // For AJAX forms, let them handle their own reload
                    return;
                }
                
                // For regular forms, schedule page reload after submission completes
                // The delay allows the POST request to complete before reload
                setTimeout(function() {
                    // Force page reload to show fresh data and alerts
                    window.location.href = window.location.href;
                }, 500);
            });
        });
    });
    </script>
    
    <!-- Real-Time Updates -->
    <!-- <script src="assets/js/realtime-updates.js"></script> -->
</head>
<body>
    <!-- Notification Sound -->
    <audio id="notificationSound" preload="auto">
        <source src="/assets/sounds/success.mp3" type="audio/mpeg">
    </audio>
    
    <!-- Loading Screen -->
    <div id="pageLoadingScreen">
        <div class="loading-spinner"></div>
    </div>
    
    <div class="page">
        <!-- Navbar -->
        <header class="navbar navbar-expand-md navbar-light d-print-none">
            <div class="container-xl">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <h1 class="navbar-brand navbar-brand-autodark d-none-navbar-horizontal pe-0 pe-md-3">
                    <a href="index">
                        <?php 
                        $logoPath = getSetting('logo_path');
                        $showLogo = false;
                        $logoSrc = '';
                        
                        if ($logoPath) {
                            // Validate logo path - should be just a filename, not a path
                            $logoFilename = basename($logoPath);
                            $fullPath = UPLOAD_DIR . $logoFilename;
                            $extension = strtolower(pathinfo($logoFilename, PATHINFO_EXTENSION));
                            $allowedExtensions = ['png', 'jpg', 'jpeg', 'gif', 'webp'];
                            
                            if (in_array($extension, $allowedExtensions) && file_exists($fullPath)) {
                                $showLogo = true;
                                $logoSrc = 'uploads/' . htmlspecialchars($logoFilename);
                            }
                        }
                        
                        if ($showLogo):
                        ?>
                            <img src="<?php echo $logoSrc; ?>" height="32" alt="<?php echo htmlspecialchars($appName); ?>">
                        <?php else: ?>
                            <?php echo htmlspecialchars($appName); ?>
                        <?php endif; ?>
                    </a>
                </h1>
                <div class="navbar-nav flex-row order-md-last">
                    <!-- Notifications Dropdown -->
                    <?php 
                    $unreadCount = getUnreadNotificationCount($currentUser['id']);
                    $notifications = getUserNotifications($currentUser['id'], true);
                    ?>
                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link px-0" data-bs-toggle="dropdown" aria-label="Notifications">
                            <i class="ti ti-bell icon"></i>
                            <?php if ($unreadCount > 0): ?>
                            <span class="badge bg-red badge-notification badge-pill"><?php echo $unreadCount; ?></span>
                            <?php endif; ?>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end dropdown-menu-card" style="width: 350px;">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h3 class="card-title">Notifications</h3>
                                    <?php if ($unreadCount > 0): ?>
                                    <a href="#" class="btn btn-sm" onclick="markAllAsRead(); return false;">
                                        Mark all read
                                    </a>
                                    <?php endif; ?>
                                </div>
                                <div class="list-group list-group-flush" style="max-height: 400px; overflow-y: auto;">
                                    <?php if (empty($notifications)): ?>
                                    <div class="list-group-item text-center text-muted py-3">
                                        <i class="ti ti-bell-off icon mb-2"></i>
                                        <div>No new notifications</div>
                                    </div>
                                    <?php else: ?>
                                        <?php foreach ($notifications as $notification): ?>
                                        <a href="<?php echo htmlspecialchars($notification['link'] ?? '#'); ?>" 
                                           class="list-group-item list-group-item-action notification-item" 
                                           data-notification-id="<?php echo $notification['id']; ?>"
                                           onclick="markNotificationRead(<?php echo $notification['id']; ?>)">
                                            <div class="d-flex">
                                                <div class="flex-fill">
                                                    <div class="font-weight-medium"><?php echo htmlspecialchars($notification['message']); ?></div>
                                                    <div class="text-muted small mt-1">
                                                        <?php echo timeAgo($notification['created_at']); ?>
                                                    </div>
                                                </div>
                                                <?php if (!$notification['is_read']): ?>
                                                <span class="badge bg-blue ms-2"></span>
                                                <?php endif; ?>
                                            </div>
                                        </a>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- User Menu -->
                    <div class="nav-item dropdown ms-2">
                        <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown" aria-label="User menu" aria-expanded="false">
                            <span class="avatar avatar-sm" style="background-image: url('<?php echo getUserAvatarUrl($currentUser); ?>')"></span>
                            <div class="d-none d-xl-block ps-2">
                                <div><?php echo htmlspecialchars($currentUser['name']); ?></div>
                                <div class="mt-1 small text-muted"><?php echo ucfirst($currentUser['role']); ?></div>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="/users/settings">
                                <i class="ti ti-settings icon me-2"></i>
                                Settings
                            </a></li>
                            <li><a class="dropdown-item" href="#" id="theme-toggle-dropdown">
                                <i class="ti ti-moon icon me-2"></i>
                                Toggle Dark Mode
                            </a></li>
                            <?php if (isAdmin()): ?>
                            <li><a class="dropdown-item" href="/users/">
                                <i class="ti ti-users icon me-2"></i>
                                User Management
                            </a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/logout">
                                <i class="ti ti-logout icon me-2"></i>
                                Logout
                            </a></li>
                        </ul>
                    </div>
                    
                    <!-- Theme Toggle -->
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
                                <a class="nav-link" href="/index">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-home"></i>
                                    </span>
                                    <span class="nav-link-title">Dashboard</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php if (hasPermission('items')): ?>
                            <li class="nav-item <?php echo $currentPage === 'items' || $currentPage === 'item_edit' ? 'active' : ''; ?>">
                                <a class="nav-link" href="/items/">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-package"></i>
                                    </span>
                                    <span class="nav-link-title">Inventory</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php if (isAdmin()): ?>
                            <li class="nav-item <?php echo $currentPage === 'shows' ? 'active' : ''; ?>">
                                <a class="nav-link" href="/shows/">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-theater"></i>
                                    </span>
                                    <span class="nav-link-title">Shows</span>
                                </a>
                            </li>
                            
                            <li class="nav-item <?php echo $currentPage === 'production_calendar' ? 'active' : ''; ?>">
                                <a class="nav-link" href="/calendar/">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-calendar-event"></i>
                                    </span>
                                    <span class="nav-link-title">Calendar</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php if ((hasPermission('pullsheets') || hasPermission('change_orders')) && !isStudent()): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle <?php echo in_array($currentPage, ['pullsheets', 'pullsheet_create', 'pullsheet_edit', 'pullsheet_view', 'change_orders', 'change_order_create', 'change_order_edit', 'change_order_view']) ? 'active' : ''; ?>" 
                                   href="#" data-bs-toggle="dropdown" aria-expanded="false" role="button">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-clipboard-list"></i>
                                    </span>
                                    <span class="nav-link-title">Orders</span>
                                </a>
                                <ul class="dropdown-menu">
                                    <?php if (hasPermission('pullsheets')): ?>
                                    <li><a class="dropdown-item <?php echo in_array($currentPage, ['pullsheets', 'pullsheet_create', 'pullsheet_edit', 'pullsheet_view']) ? 'active' : ''; ?>" href="/pullsheets/">
                                        <i class="ti ti-file-text me-2"></i>Shop Orders
                                    </a></li>
                                    <?php endif; ?>
                                    <?php if (hasPermission('change_orders')): ?>
                                    <li><a class="dropdown-item <?php echo in_array($currentPage, ['change_orders', 'change_order_create', 'change_order_edit', 'change_order_view']) ? 'active' : ''; ?>" href="/change-orders/">
                                        <i class="ti ti-exchange me-2"></i>Change Orders
                                    </a></li>
                                    <?php endif; ?>
                                </ul>
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
                                    <li><a class="dropdown-item" href="/operations/pick">Pick Mode</a></li>
                                    <?php endif; ?>
                                    <?php if (hasPermission('return_mode')): ?>
                                    <li><a class="dropdown-item" href="/operations/return">Return Mode</a></li>
                                    <?php endif; ?>
                                    <?php if (isAdmin()): ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="/admin/approvals">
                                        <i class="ti ti-signature me-2"></i>Admin Approvals
                                    </a></li>
                                    <?php endif; ?>
                                </ul>
                            </li>
                            <?php endif; ?>
                            
                            <?php if (hasPermission('reports') && !isStudent()): ?>
                            <li class="nav-item <?php echo $currentPage === 'reports' ? 'active' : ''; ?>">
                                <a class="nav-link" href="/reports/">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-report"></i>
                                    </span>
                                    <span class="nav-link-title">Reports</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php if (hasPermission('paperwork')): ?>
                            <li class="nav-item <?php echo $currentPage === 'paperwork' ? 'active' : ''; ?>">
                                <a class="nav-link" href="/paperwork">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-files"></i>
                                    </span>
                                    <span class="nav-link-title">Paperwork</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php if (hasPermission('repairs')): ?>
                            <li class="nav-item <?php echo $currentPage === 'repairs' ? 'active' : ''; ?>">
                                <a class="nav-link" href="/repairs/">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-tool"></i>
                                    </span>
                                    <span class="nav-link-title">Repairs</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php if (hasPermission('student_requests')): ?>
                            <li class="nav-item <?php echo $currentPage === 'student_requests' ? 'active' : ''; ?>">
                                <a class="nav-link" href="/student/">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-clipboard-list"></i>
                                    </span>
                                    <span class="nav-link-title">Requests</span>
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php if (isAdmin()): ?>
                            <li class="nav-item <?php echo $currentPage === 'settings' ? 'active' : ''; ?>">
                                <a class="nav-link" href="/settings/">
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
