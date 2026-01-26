                </div>
            </div>
        </div>
    </div>
    
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
        const html = document.documentElement;
        
        // Load saved theme
        const savedTheme = localStorage.getItem('theme') || 'light';
        if (savedTheme === 'dark') {
            html.setAttribute('data-bs-theme', 'dark');
            themeToggle.innerHTML = '<i class="ti ti-sun icon"></i>';
        }
        
        themeToggle.addEventListener('click', function(e) {
            e.preventDefault();
            const currentTheme = html.getAttribute('data-bs-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            html.setAttribute('data-bs-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
            if (newTheme === 'dark') {
                themeToggle.innerHTML = '<i class="ti ti-sun icon"></i>';
            } else {
                themeToggle.innerHTML = '<i class="ti ti-moon icon"></i>';
            }
        });
        
        // Auto-focus barcode/search fields
        document.addEventListener('DOMContentLoaded', function() {
            const autoFocusField = document.querySelector('.barcode-autofocus, [data-autofocus]');
            if (autoFocusField) {
                autoFocusField.focus();
                autoFocusField.select();
            }
        });
        
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
    </script>
    
    <?php if (isset($extraJS)): ?>
        <?php echo $extraJS; ?>
    <?php endif; ?>
</body>
</html>
