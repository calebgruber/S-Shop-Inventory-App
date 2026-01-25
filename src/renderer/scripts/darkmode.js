/**
 * Dark Mode Module
 * Handles dark mode toggle and persistence
 */

// Initialize dark mode
function initDarkMode() {
  // Load saved preference from localStorage
  const isDarkMode = localStorage.getItem('darkMode') === 'true';
  
  // Apply dark mode if saved
  if (isDarkMode) {
    enableDarkMode();
  }
  
  // Setup toggle button
  const toggleBtn = document.getElementById('dark-mode-toggle');
  if (toggleBtn) {
    toggleBtn.addEventListener('click', (e) => {
      e.preventDefault();
      toggleDarkMode();
    });
  }
}

// Toggle dark mode
function toggleDarkMode() {
  const htmlElement = document.documentElement;
  const isDarkMode = htmlElement.classList.contains('theme-dark');
  
  if (isDarkMode) {
    disableDarkMode();
  } else {
    enableDarkMode();
  }
  
  // Save preference
  localStorage.setItem('darkMode', !isDarkMode);
}

// Enable dark mode
function enableDarkMode() {
  const htmlElement = document.documentElement;
  const navbar = document.querySelector('.navbar');
  const icon = document.getElementById('dark-mode-icon');
  
  // Add dark theme class to html
  htmlElement.classList.add('theme-dark');
  
  // Update navbar
  if (navbar) {
    navbar.classList.remove('navbar-dark', 'bg-dark');
    navbar.classList.add('navbar-dark');
    navbar.style.backgroundColor = '#1e293b';
  }
  
  // Update icon to sun
  if (icon) {
    icon.classList.remove('ti-moon');
    icon.classList.add('ti-sun');
  }
}

// Disable dark mode
function disableDarkMode() {
  const htmlElement = document.documentElement;
  const navbar = document.querySelector('.navbar');
  const icon = document.getElementById('dark-mode-icon');
  
  // Remove dark theme class from html
  htmlElement.classList.remove('theme-dark');
  
  // Restore navbar background color (classes should already exist)
  if (navbar) {
    navbar.style.backgroundColor = '';
  }
  
  // Update icon to moon
  if (icon) {
    icon.classList.remove('ti-sun');
    icon.classList.add('ti-moon');
  }
}

// Export functions
window.darkMode = {
  init: initDarkMode,
  toggle: toggleDarkMode,
  enable: enableDarkMode,
  disable: disableDarkMode
};
