<?php
/**
 * Inventory Module - Coming Soon
 */

require_once dirname(__DIR__) . '/config/config.php';

requireAuth();

$pageName = 'Inventory';

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">
                    <i class="ti ti-package me-2"></i>
                    Inventory
                </h2>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="empty">
            <div class="empty-icon">
                <i class="ti ti-package icon"></i>
            </div>
            <p class="empty-title">Inventory Module - Coming Soon</p>
            <p class="empty-subtitle text-muted">
                This module will allow you to browse, search, and manage inventory items.
            </p>
        </div>
    </div>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
