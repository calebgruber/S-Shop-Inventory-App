<?php
/**
 * Help / Documentation
 */

require_once __DIR__ . '/config/config.php';

requireAuth();

$pageName = 'Help';

include __DIR__ . '/includes/header.php';
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">
                    <i class="ti ti-help me-2"></i>
                    Help & Documentation
                </h2>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h3>Getting Started</h3>
                        <p>Welcome to the S-Shop Inventory System. This system helps manage theatre sound shop inventory, orders, and workflows.</p>
                        
                        <h4 class="mt-4">Quick Tips</h4>
                        <ul>
                            <li>Use <kbd>Ctrl+K</kbd> or <kbd>⌘K</kbd> to open quick item lookup</li>
                            <li>Scan barcodes anywhere to quickly look up items</li>
                            <li>Triple-click the logo for a surprise 😊</li>
                            <li>Click the sun/moon icon to toggle dark/light mode</li>
                        </ul>
                        
                        <h4 class="mt-4">User Roles</h4>
                        <dl>
                            <dt>Admin</dt>
                            <dd>Full system access including approvals and settings</dd>
                            
                            <dt>Designer</dt>
                            <dd>Create orders, read-only inventory access</dd>
                            
                            <dt>Production Audio</dt>
                            <dd>Create orders, pick/return items with signature requirement</dd>
                            
                            <dt>Student</dt>
                            <dd>Read-only inventory, create equipment requests</dd>
                        </dl>
                        
                        <h4 class="mt-4">Cable Color Key</h4>
                        <p>Remember the cable length by color:</p>
                        <ul>
                            <li>🔴 Red = 5'</li>
                            <li>⚫ Gray = 10'</li>
                            <li>🟣 Purple = 15'</li>
                            <li>🟡 Yellow = 25'</li>
                            <li>🔵 Blue = 50'</li>
                            <li>⚪ White = 100'</li>
                        </ul>
                        
                        <h4 class="mt-4">Need More Help?</h4>
                        <p>Contact your system administrator for additional assistance.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
