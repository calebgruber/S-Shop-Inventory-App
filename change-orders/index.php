<?php
require_once dirname(__DIR__) . '/config/config.php';
requireAuth();
$moduleName = ucwords(str_replace('-', ' ', basename(__DIR__)));
$pageName = $moduleName;
include dirname(__DIR__) . '/includes/header.php';
?>
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title"><?php echo htmlspecialchars($moduleName); ?></h2>
            </div>
        </div>
    </div>
</div>
<div class="page-body">
    <div class="container-xl">
        <div class="empty">
            <div class="empty-icon"><i class="ti ti-package icon"></i></div>
            <p class="empty-title"><?php echo htmlspecialchars($moduleName); ?> Module - Coming Soon</p>
            <p class="empty-subtitle text-muted">This module is under development.</p>
        </div>
    </div>
</div>
<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
