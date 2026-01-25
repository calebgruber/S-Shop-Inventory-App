/**
 * Navigation Module
 * Handles page routing and navigation
 */

const pageModules = {};

// Register page modules
function registerPage(name, module) {
  pageModules[name] = module;
}

// Load and display a page
async function loadPage(pageName, onLoadComplete) {
  const pageTitle = document.getElementById('page-title');
  const pageContent = document.getElementById('page-content');
  
  // Update active nav link
  document.querySelectorAll('.nav-link').forEach(link => {
    link.classList.remove('active');
  });
  
  const activeLink = document.querySelector(`[data-page="${pageName}"]`);
  if (activeLink) {
    activeLink.classList.add('active');
  }
  
  // Update page title
  const titles = {
    dashboard: 'Dashboard',
    inventory: 'Inventory Management',
    shows: 'Shows & Productions',
    pullsheets: 'Pull Sheets',
    returns: 'Returns',
    reports: 'Reports & Analytics',
    settings: 'Settings'
  };
  
  pageTitle.textContent = titles[pageName] || 'Dashboard';
  
  // Load page content
  if (pageModules[pageName] && typeof pageModules[pageName].render === 'function') {
    try {
      const content = await pageModules[pageName].render();
      pageContent.innerHTML = content;
      
      // Initialize page if it has an init function
      if (typeof pageModules[pageName].init === 'function') {
        pageModules[pageName].init();
      }
      
      // Call onLoadComplete callback if provided
      if (typeof onLoadComplete === 'function') {
        // Wait for next tick to ensure DOM is fully ready
        await new Promise(resolve => setTimeout(resolve, 0));
        onLoadComplete();
      }
    } catch (error) {
      console.error(`Error loading page ${pageName}:`, error);
      pageContent.innerHTML = `
        <div class="alert alert-danger">
          <h4 class="alert-title">Error Loading Page</h4>
          <p>${error.message}</p>
        </div>
      `;
    }
  } else {
    pageContent.innerHTML = `
      <div class="empty-state">
        <div class="empty-state-icon">
          <i class="ti ti-file-unknown"></i>
        </div>
        <h3>Page Not Found</h3>
        <p>The requested page "${pageName}" is not available.</p>
      </div>
    `;
  }
}

// Initialize navigation
function initNavigation() {
  // Handle navigation clicks
  document.querySelectorAll('[data-page]').forEach(link => {
    link.addEventListener('click', (e) => {
      e.preventDefault();
      const pageName = link.getAttribute('data-page');
      loadPage(pageName);
    });
  });
  
  // Handle special actions
  document.querySelectorAll('[data-action]').forEach(button => {
    button.addEventListener('click', async (e) => {
      e.preventDefault();
      const action = button.getAttribute('data-action');
      
      switch (action) {
        case 'backup':
          await handleBackup();
          break;
        case 'export':
          await handleExport();
          break;
        case 'checkUpdates':
          await handleCheckUpdates();
          break;
      }
    });
  });
  
  // Load dashboard by default
  loadPage('dashboard');
}

// Handle backup action
async function handleBackup() {
  const result = await window.api.dialog.showMessage({
    type: 'info',
    title: 'Database Backup',
    message: 'Would you like to create a backup of the database?',
    buttons: ['Cancel', 'Create Backup'],
    defaultId: 1
  });
  
  if (result.response === 1) {
    // In a full implementation, this would call a backup IPC handler
    alert('Backup functionality will be implemented in the backup utility.');
  }
}

// Handle export action
async function handleExport() {
  const result = await window.api.dialog.showMessage({
    type: 'info',
    title: 'Export Data',
    message: 'Choose what to export:',
    buttons: ['Cancel', 'Export Inventory', 'Export Database'],
    defaultId: 2
  });
  
  if (result.response === 1) {
    // Export inventory report
    const pdfResult = await window.api.pdf.generateInventoryReport({});
    if (pdfResult.success) {
      alert(`Inventory report exported to: ${pdfResult.filePath}`);
    }
  } else if (result.response === 2) {
    // Export entire database
    const exportResult = await window.api.backup.export();
    if (exportResult.success) {
      alert(`Database exported to: ${exportResult.path}`);
    } else if (!exportResult.canceled) {
      alert('Failed to export database: ' + exportResult.error);
    }
  }
}

// Handle check for updates action
async function handleCheckUpdates() {
  await window.api.update.check();
}

// Export functions
window.navigation = {
  registerPage,
  loadPage,
  initNavigation
};
