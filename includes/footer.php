                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="footer footer-transparent d-print-none">
        <div class="container-xl">
            <div class="row text-center align-items-center">
                <div class="col-12 col-lg-auto mt-3 mt-lg-0">
                    <ul class="list-inline list-inline-dots mb-0">
                        <li class="list-inline-item">
                            Made with <span style="color: #e74c3c;">❤</span> by 
                            <a href="https://www.calebgruber.me" target="_blank" class="link-secondary" rel="noopener">Caleb Gruber</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JS (required for modals and other components) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Tabler JS -->
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta19/dist/js/tabler.min.js"></script>
    
    <!-- Audio for scanning -->
    <audio id="successSound" preload="auto">
        <source src="data:audio/wav;base64,UklGRhIAAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YU4AAACAgICAgICAgH9/f39/f35+fn5+fn19fX19fHx8fHx8e3t7e3t6enp6enl5eXl5eHh4eHh3d3d3d3Z2dnZ2dXV1dXV0dHR0dHNzc3Nzcg==" type="audio/wav">
    </audio>
    <audio id="errorSound" preload="auto">
        <source src="data:audio/wav;base64,UklGRhIAAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YU4AAACAgH5+fHp4dnRyb21qZ2RhXlpWU09LSERAOzYyLiojHxsXEw8LBwMAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=" type="audio/wav">
    </audio>
    
    <script>
        // Dark mode toggle
        const themeToggle = document.getElementById('theme-toggle');
        const themeToggleDropdown = document.getElementById('theme-toggle-dropdown');
        const html = document.documentElement;
        
        // Get current theme (already set by header inline script)
        const currentSavedTheme = localStorage.getItem('theme') || 'light';
        if (currentSavedTheme === 'dark') {
            if (themeToggle) themeToggle.innerHTML = '<i class="ti ti-sun icon"></i>';
        }
        
        function toggleTheme(e) {
            e.preventDefault();
            const currentTheme = html.getAttribute('data-bs-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            html.setAttribute('data-bs-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
            if (newTheme === 'dark') {
                if (themeToggle) themeToggle.innerHTML = '<i class="ti ti-sun icon"></i>';
            } else {
                if (themeToggle) themeToggle.innerHTML = '<i class="ti ti-moon icon"></i>';
            }
        }
        
        if (themeToggle) {
            themeToggle.addEventListener('click', toggleTheme);
        }
        
        if (themeToggleDropdown) {
            themeToggleDropdown.addEventListener('click', toggleTheme);
        }
        

        
        // Sound effects
        function playSuccessSound() {
            const sound = document.getElementById('successSound');
            if (sound) {
                sound.currentTime = 0;
                sound.play().catch(() => {});
            }
        }
        
        function playErrorSound() {
            const sound = document.getElementById('errorSound');
            if (sound) {
                sound.currentTime = 0;
                sound.play().catch(() => {});
            }
        }
        
        // Make functions globally available
        window.playSuccessSound = playSuccessSound;
        window.playErrorSound = playErrorSound;
        
        // Notifications
        function markNotificationRead(notificationId) {
            fetch('api_notifications.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=mark_read&notification_id=' + notificationId
            });
        }
        
        function markAllAsRead() {
            fetch('api_notifications.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=mark_all_read'
            }).then(() => {
                location.reload();
            });
        }
        
        window.markNotificationRead = markNotificationRead;
        window.markAllAsRead = markAllAsRead;
        
        // Hotkey System
        <?php 
        $userHotkeys = getUserHotkeys($currentUser['id']);
        $hotkeyActions = [
            'quick_lookup' => 'showQuickLookup',
            'pick_mode' => 'goToPage("pick_mode.php")',
            'return_mode' => 'goToPage("return_mode.php")',
            'create_pullsheet' => 'goToPage("pullsheet_create.php")',
            'inventory' => 'goToPage("items.php")',
            'reports' => 'goToPage("reports.php")',
            'shows' => 'goToPage("shows.php")'
        ];
        ?>
        
        const userHotkeys = <?php echo json_encode($userHotkeys); ?>;
        
        function normalizeHotkey(e) {
            const parts = [];
            if (e.ctrlKey || e.metaKey) parts.push('Ctrl');
            if (e.altKey) parts.push('Alt');
            if (e.shiftKey) parts.push('Shift');
            parts.push(e.key.toUpperCase());
            return parts.join('+');
        }
        
        function goToPage(url) {
            window.location.href = url;
        }
        
        function showQuickLookup() {
            // Try to focus search field or redirect to inventory
            const searchField = document.querySelector('[name="search"], #quick-search');
            if (searchField) {
                searchField.focus();
                searchField.select();
            } else {
                window.location.href = 'items.php';
            }
        }
        
        window.showQuickLookup = showQuickLookup;
        window.goToPage = goToPage;
        
        document.addEventListener('keydown', function(e) {
            // Don't trigger hotkeys when typing in input fields
            const isInTextField = e.target && 
                (e.target.tagName === 'INPUT' || 
                 e.target.tagName === 'TEXTAREA' || 
                 e.target.contentEditable === 'true');
            
            if (isInTextField) {
                // Exception: allow Ctrl+K even in input fields for quick lookup
                const hotkey = normalizeHotkey(e);
                if (userHotkeys['quick_lookup'] && hotkey === userHotkeys['quick_lookup']) {
                    e.preventDefault();
                    showQuickLookup();
                }
                return;
            }
            
            const hotkey = normalizeHotkey(e);
            
            // Check each action
            <?php foreach ($hotkeyActions as $action => $jsFunction): ?>
            if (userHotkeys['<?php echo $action; ?>'] && hotkey === userHotkeys['<?php echo $action; ?>']) {
                e.preventDefault();
                <?php echo $jsFunction; ?>;
            }
            <?php endforeach; ?>
        });
    </script>
    
    <?php if (isset($extraJS)): ?>
        <?php echo $extraJS; ?>
    <?php endif; ?>
</body>
</html>
