        </div><!-- End page-wrapper -->
        
        <!-- Footer -->
        <footer class="footer footer-transparent d-print-none">
            <div class="container-xl">
                <div class="row text-center align-items-center flex-row-reverse">
                    <div class="col-lg-auto ms-lg-auto">
                        <ul class="list-inline list-inline-dots mb-0">
                            <li class="list-inline-item">
                                <a href="<?php echo BASE_URL; ?>help.php" class="link-secondary">Help</a>
                            </li>
                            <li class="list-inline-item">
                                Version 1.0.0
                            </li>
                        </ul>
                    </div>
                    <div class="col-12 col-lg-auto mt-3 mt-lg-0">
                        <ul class="list-inline list-inline-dots mb-0">
                            <li class="list-inline-item">
                                &copy; <?php echo date('Y'); ?> 
                                <a href="<?php echo BASE_URL; ?>" class="link-secondary"><?php echo htmlspecialchars(getSetting('site_name', 'S-Shop Inventory System')); ?></a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </footer>
    </div><!-- End page -->
    
    <!-- Quick Item Lookup Modal -->
    <?php if (isLoggedIn()): ?>
    <div class="modal modal-blur fade" id="quick-lookup-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Quick Item Lookup</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Scan or enter barcode</label>
                        <input type="text" class="form-control form-control-lg" id="quick-lookup-input" placeholder="Scan barcode or search item...">
                    </div>
                    <div id="quick-lookup-result" class="empty" style="display: none;">
                        <div class="empty-icon">
                            <i class="ti ti-search icon"></i>
                        </div>
                        <p class="empty-title">Searching...</p>
                    </div>
                    <div id="quick-lookup-item" style="display: none;">
                        <!-- Item details will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Pink Mode Easter Egg Elements -->
    <div id="pink-mode-cat-icon" style="display: none; position: fixed; top: 10px; left: 10px; z-index: 9999; cursor: pointer;">
        <i class="ti ti-cat" style="font-size: 2rem; color: #ff69b4;"></i>
    </div>
    <div id="flying-elements-container" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 9998;"></div>
    <?php endif; ?>
    
    <!-- Random Guy Easter Egg -->
    <div id="random-guy-background" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-image: url('https://preview.tabler.io/static/avatars/000m.jpg'); background-size: cover; background-position: center; z-index: -1; opacity: 0.3;"></div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta17/dist/js/tabler.min.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/app.js"></script>
    
    <?php if (isset($additionalScripts)): ?>
        <?php foreach ($additionalScripts as $script): ?>
            <script src="<?php echo BASE_URL . htmlspecialchars($script); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
