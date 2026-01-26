<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? APP_NAME; ?></title>
    
    <!-- Tabler CSS -->
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css" rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" rel="stylesheet"/>
    
    <style>
        [data-bs-theme="dark"] {
            color-scheme: dark;
        }
        
        .auto-focus {
            /* Field will be auto-focused on page load */
        }
        
        .fullscreen-mode {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 9999;
            background: var(--tblr-body-bg);
            overflow-y: auto;
        }
        
        .item-card-red {
            border-left: 4px solid #d63939;
        }
        
        .item-card-green {
            border-left: 4px solid #2fb344;
        }
        
        .item-card-yellow {
            border-left: 4px solid #f59f00;
        }
        
        .barcode-container {
            text-align: center;
            margin: 20px 0;
        }
        
        .dashboard-card {
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
    </style>
    
    <?php if (isset($additionalCSS)) echo $additionalCSS; ?>
</head>
<body data-bs-theme="<?php echo getThemeMode(); ?>">
    <div class="page">
        <?php if (!isset($hideHeader) || !$hideHeader): ?>
        <!-- Header -->
        <header class="navbar navbar-expand-md navbar-light d-print-none">
            <div class="container-xl">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <h1 class="navbar-brand navbar-brand-autodark d-none-navbar-horizontal pe-0 pe-md-3">
                    <a href="index.php">
                        <?php echo APP_NAME; ?>
                    </a>
                </h1>
                <div class="navbar-nav flex-row order-md-last">
                    <div class="nav-item">
                        <a href="settings.php" class="nav-link">
                            <i class="ti ti-settings"></i>
                        </a>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Navigation -->
        <div class="navbar-expand-md">
            <div class="collapse navbar-collapse" id="navbar-menu">
                <div class="navbar navbar-light">
                    <div class="container-xl">
                        <ul class="navbar-nav">
                            <li class="nav-item">
                                <a class="nav-link" href="index.php">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-home"></i>
                                    </span>
                                    <span class="nav-link-title">Dashboard</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="shows.php">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-theater"></i>
                                    </span>
                                    <span class="nav-link-title">Shows</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="inventory.php">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-package"></i>
                                    </span>
                                    <span class="nav-link-title">Inventory</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="pull_sheets.php">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-list-check"></i>
                                    </span>
                                    <span class="nav-link-title">Pull Sheets</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="change_orders.php">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-repeat"></i>
                                    </span>
                                    <span class="nav-link-title">Change Orders</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="reports.php">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-file-analytics"></i>
                                    </span>
                                    <span class="nav-link-title">Reports</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="pick_mode.php">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-scan"></i>
                                    </span>
                                    <span class="nav-link-title">Pick Mode</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="return_mode.php">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-arrow-back-up"></i>
                                    </span>
                                    <span class="nav-link-title">Return Mode</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Page content -->
        <div class="page-wrapper">
            <?php if (isset($pageHeader)): ?>
            <div class="page-header d-print-none">
                <div class="container-xl">
                    <div class="row g-2 align-items-center">
                        <div class="col">
                            <h2 class="page-title">
                                <?php echo $pageHeader; ?>
                            </h2>
                        </div>
                        <?php if (isset($pageActions)): ?>
                        <div class="col-auto ms-auto d-print-none">
                            <?php echo $pageActions; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="page-body">
                <div class="container-xl">
                    <?php echo $content ?? ''; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tabler JS -->
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js"></script>
    
    <!-- Audio for scanning feedback -->
    <audio id="success-sound" preload="auto">
        <source src="data:audio/wav;base64,UklGRhQAAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQAAAAA=" type="audio/wav">
    </audio>
    <audio id="error-sound" preload="auto">
        <source src="data:audio/wav;base64,UklGRhQAAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQAAAAA=" type="audio/wav">
    </audio>
    
    <script>
        // Auto-focus functionality
        document.addEventListener('DOMContentLoaded', function() {
            const autoFocusElement = document.querySelector('.auto-focus');
            if (autoFocusElement) {
                autoFocusElement.focus();
                autoFocusElement.select();
            }
        });
        
        // Play success sound
        function playSuccessSound() {
            const sound = document.getElementById('success-sound');
            if (sound) {
                sound.currentTime = 0;
                sound.play().catch(e => console.log('Audio play failed:', e));
            }
        }
        
        // Play error sound
        function playErrorSound() {
            const sound = document.getElementById('error-sound');
            if (sound) {
                sound.currentTime = 0;
                sound.play().catch(e => console.log('Audio play failed:', e));
            }
        }
    </script>
    
    <?php if (isset($additionalJS)) echo $additionalJS; ?>
</body>
</html>
