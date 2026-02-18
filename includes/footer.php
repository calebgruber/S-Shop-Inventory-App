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
        
        // Pink Mode Easter Egg - Triple click on logo
        let pinkModeActive = localStorage.getItem('pinkMode') === 'true';
        let logoClickCount = 0;
        let logoClickTimer = null;
        
        function togglePinkMode() {
            pinkModeActive = !pinkModeActive;
            localStorage.setItem('pinkMode', pinkModeActive);
            
            if (pinkModeActive) {
                document.body.classList.add('pink-mode');
                
                // Add floating hearts and cats
                for (let i = 0; i < 15; i++) {
                    setTimeout(() => {
                        const emoji = Math.random() > 0.5 ? '💖' : '🐱';
                        const floater = document.createElement('div');
                        floater.className = 'heart';
                        floater.textContent = emoji;
                        floater.style.left = Math.random() * 100 + '%';
                        floater.style.bottom = '-50px';
                        document.body.appendChild(floater);
                    }, i * 200);
                }
                
                // Play random meow sounds
                startRandomMeows();
                
                console.log('🐱 Pink mode activated! Meow! 🎀');
            } else {
                document.body.classList.remove('pink-mode');
                
                // Remove floating hearts and cats
                document.querySelectorAll('.heart').forEach(h => h.remove());
                
                // Stop meow sounds
                stopRandomMeows();
                
                console.log('Pink mode deactivated.');
            }
        }
        
        // Random meow sound system for pink mode
        let meowInterval = null;
        
        function playRandomMeow() {
            try {
                const audio = new Audio('assets/sounds/meow.mp3');
                audio.volume = 0.5;
                audio.play().catch(err => console.log('Meow sound failed:', err));
            } catch (err) {
                console.log('Meow Easter egg failed:', err);
            }
        }
        
        function startRandomMeows() {
            // Schedule next meow randomly between 5 seconds and 5 minutes
            function scheduleNextMeow() {
                if (!pinkModeActive) return;
                const delay = Math.random() * (300000 - 5000) + 5000; // 5 sec to 5 min
                meowInterval = setTimeout(() => {
                    playRandomMeow();
                    scheduleNextMeow();
                }, delay);
            }
            scheduleNextMeow();
        }
        
        function stopRandomMeows() {
            if (meowInterval) {
                clearTimeout(meowInterval);
                meowInterval = null;
            }
        }
        
        // Apply pink mode if saved
        if (pinkModeActive) {
            document.body.classList.add('pink-mode');
            
            // Add floating hearts and cats on page load if pink mode is active
            setTimeout(() => {
                for (let i = 0; i < 15; i++) {
                    setTimeout(() => {
                        const emoji = Math.random() > 0.5 ? '💖' : '🐱';
                        const floater = document.createElement('div');
                        floater.className = 'heart';
                        floater.textContent = emoji;
                        floater.style.left = Math.random() * 100 + '%';
                        floater.style.bottom = '-50px';
                        document.body.appendChild(floater);
                    }, i * 100);
                }
            }, 100);
            
            // Start random meows
            startRandomMeows();
        }
        
        // Attach triple-click handler to logo
        const logo = document.querySelector('.navbar-brand');
        if (logo) {
            logo.addEventListener('click', (e) => {
                e.preventDefault();
                logoClickCount++;
                
                if (logoClickTimer) {
                    clearTimeout(logoClickTimer);
                }
                
                if (logoClickCount === 3) {
                    togglePinkMode();
                    logoClickCount = 0;
                } else {
                    logoClickTimer = setTimeout(() => {
                        logoClickCount = 0;
                    }, 500); // Reset after 500ms
                }
            });
        }
        
        // Add cat icon to exit pink mode
        if (pinkModeActive && logo) {
            const catIcon = document.createElement('span');
            catIcon.innerHTML = ' 🐱';
            catIcon.style.cursor = 'pointer';
            catIcon.title = 'Exit pink mode';
            catIcon.addEventListener('click', (e) => {
                e.stopPropagation();
                togglePinkMode();
            });
            logo.appendChild(catIcon);
        }
        
        // Random Guy Easter Egg - 5% chance on page load
        if (Math.random() < 0.05) {
            document.body.style.backgroundImage = 'url(https://preview.tabler.io/static/avatars/000m.jpg)';
            document.body.style.backgroundSize = 'cover';
            document.body.style.backgroundPosition = 'center';
            document.body.style.backgroundAttachment = 'fixed';
            console.log('👨 Random guy appeared!');
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
            fetch('/api/notifications.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=mark_read&notification_id=' + notificationId
            }).then(response => response.json())
              .then(data => {
                  if (data.success) {
                      // Remove notification from DOM
                      const notifElement = document.querySelector(`[data-notification-id="${notificationId}"]`);
                      if (notifElement) {
                          notifElement.remove();
                      }
                      // Update badge count
                      updateNotificationBadge();
                  }
              })
              .catch(err => console.error('Error marking notification as read:', err));
        }
        
        function markAllAsRead() {
            fetch('/api/notifications.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=mark_all_read'
            }).then(() => {
                location.reload();
            });
        }
        
        function updateNotificationBadge() {
            fetch('/api/notifications.php?action=get_unread_count')
                .then(response => response.json())
                .then(data => {
                    const badge = document.querySelector('.notification-badge');
                    if (badge) {
                        if (data.count > 0) {
                            badge.textContent = data.count;
                            badge.style.display = '';
                        } else {
                            badge.style.display = 'none';
                        }
                    }
                })
                .catch(err => console.error('Error updating notification badge:', err));
        }
        
        window.markNotificationRead = markNotificationRead;
        window.markAllAsRead = markAllAsRead;
        window.updateNotificationBadge = updateNotificationBadge;
        
    </script>
    
    <?php if (isset($extraJS)): ?>
        <?php echo $extraJS; ?>
    <?php endif; ?>
</body>
</html>
