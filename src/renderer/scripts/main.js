/**
 * Main Application Script
 * Initializes the application and coordinates all modules
 */

// Initialize application when DOM is ready
document.addEventListener('DOMContentLoaded', async () => {
  console.log('S-Shop Inventory Application starting...');
  
  try {
    // Initialize navigation system
    window.navigation.initNavigation();
    
    // Initialize barcode scanning
    window.barcode.init();
    
    console.log('Application initialized successfully');
    
    // Show welcome message
    showWelcomeMessage();
    
  } catch (error) {
    console.error('Application initialization error:', error);
    showErrorMessage('Failed to initialize application: ' + error.message);
  }
});

// Show welcome message
function showWelcomeMessage() {
  // Check if this is first run
  const hasSeenWelcome = localStorage.getItem('hasSeenWelcome');
  
  if (!hasSeenWelcome) {
    setTimeout(() => {
      const welcomeModal = `
        <div class="modal modal-blur fade show" id="welcomeModal" tabindex="-1" style="display: block;" aria-modal="true">
          <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Welcome to S-Shop Inventory</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="closeWelcomeModal()"></button>
              </div>
              <div class="modal-body">
                <h3 class="mb-3">Theatre Sound Equipment Management System</h3>
                <p>This application helps you manage your theatre sound shop inventory with:</p>
                <ul>
                  <li><strong>Barcode-driven workflows</strong> - Press <kbd>F1</kbd> to scan items with your Zebra DS-series scanner</li>
                  <li><strong>Show management</strong> - Create shows and build pull sheets by scanning equipment</li>
                  <li><strong>Serialized tracking</strong> - Track individual items like microphones and transmitters</li>
                  <li><strong>Change orders & returns</strong> - Manage equipment throughout the show lifecycle</li>
                  <li><strong>Offline operation</strong> - All data is stored locally in SQLite</li>
                  <li><strong>PDF generation</strong> - Print pull sheets with barcodes for quick recall</li>
                </ul>
                <div class="alert alert-info mt-3">
                  <h4 class="alert-title">Getting Started</h4>
                  <ol class="mb-0">
                    <li>Add your inventory items (Equipment → Inventory)</li>
                    <li>Create a show (Shows & Productions)</li>
                    <li>Build a pull sheet by scanning items</li>
                    <li>Generate a PDF with show barcode</li>
                    <li>Process returns when show closes</li>
                  </ol>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="closeWelcomeModal()">Get Started</button>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-backdrop fade show"></div>
      `;
      
      document.body.insertAdjacentHTML('beforeend', welcomeModal);
      localStorage.setItem('hasSeenWelcome', 'true');
    }, 500);
  }
}

// Close welcome modal
window.closeWelcomeModal = function() {
  const modal = document.getElementById('welcomeModal');
  const backdrop = document.querySelector('.modal-backdrop');
  if (modal) modal.remove();
  if (backdrop) backdrop.remove();
};

// Show error message
function showErrorMessage(message) {
  const errorDiv = document.createElement('div');
  errorDiv.className = 'alert alert-danger alert-dismissible position-fixed top-0 start-50 translate-middle-x mt-3';
  errorDiv.style.zIndex = '9999';
  errorDiv.innerHTML = `
    <div class="d-flex">
      <div>
        <i class="ti ti-alert-circle icon"></i>
      </div>
      <div>
        <h4 class="alert-title">Error</h4>
        <div class="text-muted">${message}</div>
      </div>
    </div>
    <a class="btn-close" data-bs-dismiss="alert"></a>
  `;
  
  document.body.appendChild(errorDiv);
  
  setTimeout(() => {
    errorDiv.remove();
  }, 5000);
}

// Show success message
window.showSuccessMessage = function(message) {
  const successDiv = document.createElement('div');
  successDiv.className = 'alert alert-success alert-dismissible position-fixed top-0 start-50 translate-middle-x mt-3';
  successDiv.style.zIndex = '9999';
  successDiv.innerHTML = `
    <div class="d-flex">
      <div>
        <i class="ti ti-check icon"></i>
      </div>
      <div>
        <h4 class="alert-title">Success</h4>
        <div class="text-muted">${message}</div>
      </div>
    </div>
    <a class="btn-close" data-bs-dismiss="alert"></a>
  `;
  
  document.body.appendChild(successDiv);
  
  setTimeout(() => {
    successDiv.remove();
  }, 3000);
};

// Handle unhandled errors
window.addEventListener('error', (event) => {
  console.error('Unhandled error:', event.error);
});

window.addEventListener('unhandledrejection', (event) => {
  console.error('Unhandled promise rejection:', event.reason);
});

// Export utility functions
window.app = {
  showSuccess: window.showSuccessMessage,
  showError: showErrorMessage
};

console.log('Main script loaded');
