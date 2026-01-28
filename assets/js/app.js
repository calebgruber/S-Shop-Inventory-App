/**
 * S-Shop Inventory System - Main JavaScript
 */

(function() {
    'use strict';
    
    // Global variables
    let barcodeBuffer = '';
    let barcodeTimeout = null;
    let pinkModeActive = false;
    let pinkModeTripleClickCount = 0;
    let pinkModeTripleClickTimeout = null;
    let meowInterval = null;
    
    // Initialize on DOM ready
    document.addEventListener('DOMContentLoaded', function() {
        initThemeToggle();
        initBarcodeScanning();
        initQuickLookup();
        initNotifications();
        initEasterEggs();
        initHotkeys();
    });
    
    /**
     * Theme Toggle (Dark/Light Mode)
     */
    function initThemeToggle() {
        const themeToggle = document.querySelectorAll('#theme-toggle');
        
        themeToggle.forEach(toggle => {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                
                const currentTheme = document.body.getAttribute('data-bs-theme');
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                
                document.body.setAttribute('data-bs-theme', newTheme);
                
                // Save preference
                fetch('/api/set-theme.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ theme: newTheme })
                });
            });
        });
    }
    
    /**
     * Barcode Scanning
     * Detects rapid keyboard input (barcode scanner simulation)
     */
    function initBarcodeScanning() {
        document.addEventListener('keypress', function(e) {
            // Ignore if typing in an input field (unless it's the quick lookup)
            if (document.activeElement.tagName === 'INPUT' && 
                document.activeElement.id !== 'quick-lookup-input') {
                return;
            }
            
            if (document.activeElement.tagName === 'TEXTAREA') {
                return;
            }
            
            // Add character to buffer
            barcodeBuffer += e.key;
            
            // Clear existing timeout
            if (barcodeTimeout) {
                clearTimeout(barcodeTimeout);
            }
            
            // Set new timeout (barcode scanners are fast, so short timeout)
            barcodeTimeout = setTimeout(function() {
                if (barcodeBuffer.length > 3) {
                    handleBarcodeScanned(barcodeBuffer);
                }
                barcodeBuffer = '';
            }, 100);
        });
    }
    
    /**
     * Handle barcode scanned
     */
    function handleBarcodeScanned(barcode) {
        console.log('Barcode scanned:', barcode);
        
        // Check for cheeseburger easter egg
        if (barcode.toUpperCase() === 'CHZ-BGR') {
            playSound('whopper.mp3');
            return;
        }
        
        // If on dashboard, open quick lookup
        if (window.location.pathname.endsWith('dashboard.php') || 
            window.location.pathname.endsWith('/')) {
            openQuickLookup(barcode);
        }
        
        // If in pick/return mode, handle differently
        if (window.location.pathname.endsWith('pick.php') || 
            window.location.pathname.endsWith('return.php')) {
            handlePickReturnScan(barcode);
        }
    }
    
    /**
     * Quick Item Lookup
     */
    function initQuickLookup() {
        const modal = document.getElementById('quick-lookup-modal');
        const input = document.getElementById('quick-lookup-input');
        const result = document.getElementById('quick-lookup-result');
        const itemDisplay = document.getElementById('quick-lookup-item');
        
        if (!modal || !input) return;
        
        // Auto-focus input when modal opens
        modal.addEventListener('shown.bs.modal', function() {
            input.focus();
            input.select();
        });
        
        // Search on enter
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchItem(input.value.trim());
            }
        });
        
        // Debounced search on input
        let searchTimeout;
        input.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(function() {
                if (input.value.trim().length > 2) {
                    searchItem(input.value.trim());
                }
            }, 500);
        });
    }
    
    /**
     * Open quick lookup with barcode
     */
    function openQuickLookup(barcode) {
        const modal = document.getElementById('quick-lookup-modal');
        const input = document.getElementById('quick-lookup-input');
        
        if (modal && input) {
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
            
            input.value = barcode;
            searchItem(barcode);
        }
    }
    
    /**
     * Search for item
     */
    function searchItem(query) {
        const result = document.getElementById('quick-lookup-result');
        const itemDisplay = document.getElementById('quick-lookup-item');
        
        if (!result || !itemDisplay) return;
        
        // Show loading
        result.style.display = 'block';
        itemDisplay.style.display = 'none';
        
        // Fetch item
        fetch('/api/search-item.php?q=' + encodeURIComponent(query))
            .then(response => response.json())
            .then(data => {
                result.style.display = 'none';
                
                if (data.success && data.item) {
                    displayItem(data.item);
                } else {
                    itemDisplay.innerHTML = '<div class="alert alert-warning">Item not found</div>';
                    itemDisplay.style.display = 'block';
                }
            })
            .catch(error => {
                console.error('Error searching item:', error);
                result.style.display = 'none';
                itemDisplay.innerHTML = '<div class="alert alert-danger">Error searching for item</div>';
                itemDisplay.style.display = 'block';
            });
    }
    
    /**
     * Display item in quick lookup
     */
    function displayItem(item) {
        const itemDisplay = document.getElementById('quick-lookup-item');
        
        const html = `
            <div class="card">
                <div class="row g-0">
                    ${item.photo_path ? `
                    <div class="col-auto">
                        <img src="${item.photo_path}" class="rounded-start" style="width: 200px; height: 200px; object-fit: cover;">
                    </div>
                    ` : ''}
                    <div class="col">
                        <div class="card-body">
                            <h3 class="card-title">${escapeHtml(item.name)}</h3>
                            <div class="text-muted mb-2">${escapeHtml(item.barcode)}</div>
                            <div class="mb-3">
                                <strong>In Stock / Total:</strong> 
                                <span class="badge bg-${item.in_stock_quantity > 0 ? 'success' : 'danger'} badge-lg">
                                    ${item.in_stock_quantity} / ${item.total_quantity}
                                </span>
                            </div>
                            ${item.description ? `<p class="text-muted">${escapeHtml(item.description)}</p>` : ''}
                            ${item.location ? `<div><strong>Location:</strong> ${escapeHtml(item.location)}</div>` : ''}
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        itemDisplay.innerHTML = html;
        itemDisplay.style.display = 'block';
    }
    
    /**
     * Initialize notifications
     */
    function initNotifications() {
        // Poll for notifications every 30 seconds
        loadNotifications();
        setInterval(loadNotifications, 30000);
    }
    
    /**
     * Load notifications
     */
    function loadNotifications() {
        fetch('/api/notifications.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.notifications) {
                    updateNotificationBadge(data.unread_count);
                    updateNotificationList(data.notifications);
                }
            })
            .catch(error => {
                console.error('Error loading notifications:', error);
            });
    }
    
    /**
     * Update notification badge
     */
    function updateNotificationBadge(count) {
        const badge = document.getElementById('notification-count');
        if (badge) {
            if (count > 0) {
                badge.textContent = count;
                badge.style.display = 'inline-block';
            } else {
                badge.style.display = 'none';
            }
        }
    }
    
    /**
     * Update notification list
     */
    function updateNotificationList(notifications) {
        const list = document.getElementById('notifications-list');
        if (!list) return;
        
        if (notifications.length === 0) {
            list.innerHTML = '<div class="list-group-item"><div class="text-muted">No new notifications</div></div>';
            return;
        }
        
        list.innerHTML = notifications.map(notification => `
            <div class="list-group-item">
                <div class="row align-items-center">
                    <div class="col">
                        <div class="text-truncate">${escapeHtml(notification.message)}</div>
                        <div class="text-muted small">${notification.created_at}</div>
                    </div>
                </div>
            </div>
        `).join('');
    }
    
    /**
     * Easter Eggs
     */
    function initEasterEggs() {
        // Pink Mode - Triple click logo
        const logoLink = document.getElementById('site-logo-link');
        const siteName = document.getElementById('site-name');
        
        if (logoLink) {
            logoLink.addEventListener('click', handlePinkModeClick);
        }
        if (siteName) {
            siteName.addEventListener('click', handlePinkModeClick);
        }
        
        // Random guy mode (5% chance on page load)
        if (Math.random() < 0.05) {
            activateRandomGuyMode();
        }
        
        // Cable red bonk sound
        const cableRed = document.getElementById('cable-red');
        if (cableRed) {
            cableRed.addEventListener('click', function() {
                playSound('bonk.mp3');
            });
        }
    }
    
    /**
     * Handle pink mode triple click
     */
    function handlePinkModeClick(e) {
        pinkModeTripleClickCount++;
        
        if (pinkModeTripleClickTimeout) {
            clearTimeout(pinkModeTripleClickTimeout);
        }
        
        pinkModeTripleClickTimeout = setTimeout(() => {
            pinkModeTripleClickCount = 0;
        }, 500);
        
        if (pinkModeTripleClickCount === 3) {
            e.preventDefault();
            togglePinkMode();
            pinkModeTripleClickCount = 0;
        }
    }
    
    /**
     * Toggle pink mode
     */
    function togglePinkMode() {
        pinkModeActive = !pinkModeActive;
        
        if (pinkModeActive) {
            document.body.classList.add('pink-mode');
            showPinkModeElements();
            startFlyingElements();
            startRandomMeow();
        } else {
            document.body.classList.remove('pink-mode');
            hidePinkModeElements();
            stopFlyingElements();
            stopRandomMeow();
        }
    }
    
    /**
     * Show pink mode elements
     */
    function showPinkModeElements() {
        const catIcon = document.getElementById('pink-mode-cat-icon');
        if (catIcon) {
            catIcon.style.display = 'block';
            catIcon.addEventListener('click', togglePinkMode);
        }
    }
    
    /**
     * Hide pink mode elements
     */
    function hidePinkModeElements() {
        const catIcon = document.getElementById('pink-mode-cat-icon');
        if (catIcon) {
            catIcon.style.display = 'none';
        }
    }
    
    /**
     * Start flying hearts and cats
     */
    function startFlyingElements() {
        const container = document.getElementById('flying-elements-container');
        if (!container) return;
        
        window.flyingElementInterval = setInterval(() => {
            const element = document.createElement('div');
            element.className = 'flying-element';
            element.innerHTML = Math.random() > 0.5 ? '❤️' : '🐱';
            element.style.top = Math.random() * window.innerHeight + 'px';
            element.style.left = '-50px';
            
            container.appendChild(element);
            
            setTimeout(() => {
                element.remove();
            }, 10000);
        }, 2000);
    }
    
    /**
     * Stop flying elements
     */
    function stopFlyingElements() {
        if (window.flyingElementInterval) {
            clearInterval(window.flyingElementInterval);
        }
        const container = document.getElementById('flying-elements-container');
        if (container) {
            container.innerHTML = '';
        }
    }
    
    /**
     * Start random meow sounds
     */
    function startRandomMeow() {
        function scheduleNextMeow() {
            const delay = Math.random() * 5 * 60 * 1000; // Random up to 5 minutes
            meowInterval = setTimeout(() => {
                playSound('meow.mp3');
                scheduleNextMeow();
            }, delay);
        }
        scheduleNextMeow();
    }
    
    /**
     * Stop random meow sounds
     */
    function stopRandomMeow() {
        if (meowInterval) {
            clearTimeout(meowInterval);
        }
    }
    
    /**
     * Activate random guy mode
     */
    function activateRandomGuyMode() {
        const bg = document.getElementById('random-guy-background');
        if (bg) {
            bg.style.display = 'block';
        }
        document.body.classList.add('random-guy-active');
    }
    
    /**
     * Play sound file
     */
    function playSound(filename) {
        const audio = new Audio('/assets/sounds/' + filename);
        audio.play().catch(error => {
            console.log('Could not play sound:', error);
        });
    }
    
    /**
     * Initialize user hotkeys
     */
    function initHotkeys() {
        // This would load user-specific hotkeys from settings
        // For now, just set up F24 for quick lookup
        document.addEventListener('keydown', function(e) {
            if (e.key === 'F24' || (e.key === 'k' && (e.ctrlKey || e.metaKey))) {
                e.preventDefault();
                const modal = document.getElementById('quick-lookup-modal');
                if (modal) {
                    const bsModal = new bootstrap.Modal(modal);
                    bsModal.show();
                }
            }
        });
    }
    
    /**
     * Handle pick/return mode scanning
     */
    function handlePickReturnScan(barcode) {
        // This will be implemented in pick.php and return.php
        console.log('Pick/Return scan:', barcode);
    }
    
    /**
     * Utility: Escape HTML
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    /**
     * Make functions available globally for inline event handlers
     */
    window.SShop = {
        openQuickLookup: openQuickLookup,
        playSound: playSound
    };
    
})();
