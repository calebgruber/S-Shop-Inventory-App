/**
 * Dashboard Module - COMPLETE IMPLEMENTATION
 * Real-time statistics and overview
 */

let dashboardStats = null;
let recentActivity = [];
let shortages = [];

// Render dashboard page
async function renderDashboardPage() {
  // Fetch all data
  dashboardStats = await window.api.reports.getDashboardStats();
  recentActivity = await window.api.reports.getActivityLog({ limit: 10 });
  shortages = await window.api.reports.getShortages();
  
  return `
    <!-- Statistics Cards -->
    <div class="row row-deck row-cards mb-3">
      <div class="col-sm-6 col-lg-3">
        <div class="card">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="subheader">Total Items</div>
            </div>
            <div class="h1 mb-0">${dashboardStats.totalItems}</div>
            <div class="text-muted mt-1">
              <i class="ti ti-box icon"></i> All inventory items
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-sm-6 col-lg-3">
        <div class="card">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="subheader">Available</div>
            </div>
            <div class="h1 mb-0 text-success">${dashboardStats.availableItems}</div>
            <div class="text-muted mt-1">
              <i class="ti ti-check icon"></i> Ready to use
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-sm-6 col-lg-3">
        <div class="card">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="subheader">Checked Out</div>
            </div>
            <div class="h1 mb-0 text-info">${dashboardStats.itemsOut}</div>
            <div class="text-muted mt-1">
              <i class="ti ti-arrow-up-right icon"></i> Currently in use
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-sm-6 col-lg-3">
        <div class="card">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="subheader">Active Shows</div>
            </div>
            <div class="h1 mb-0 text-primary">${dashboardStats.activeShows}</div>
            <div class="text-muted mt-1">
              <i class="ti ti-theater icon"></i> Running productions
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Shortages Alert -->
    ${shortages.length > 0 ? `
      <div class="alert alert-warning alert-dismissible mb-3" role="alert">
        <div class="d-flex">
          <div>
            <i class="ti ti-alert-triangle icon alert-icon"></i>
          </div>
          <div>
            <h4 class="alert-title">Shortages Detected!</h4>
            <div class="text-muted">
              ${shortages.length} ${shortages.length === 1 ? 'item has' : 'items have'} low or negative availability.
              <a href="#" onclick="event.preventDefault(); window.navigation.loadPage('reports');" class="alert-link">View Reports</a>
            </div>
          </div>
        </div>
        <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
      </div>
    ` : ''}
    
    <!-- Pending Returns Alert -->
    ${dashboardStats.pendingReturns > 0 ? `
      <div class="alert alert-info alert-dismissible mb-3" role="alert">
        <div class="d-flex">
          <div>
            <i class="ti ti-arrow-back icon alert-icon"></i>
          </div>
          <div>
            <h4 class="alert-title">Pending Returns</h4>
            <div class="text-muted">
              ${dashboardStats.pendingReturns} ${dashboardStats.pendingReturns === 1 ? 'return is' : 'returns are'} waiting to be completed.
              <a href="#" onclick="event.preventDefault(); window.navigation.loadPage('returns');" class="alert-link">Process Returns</a>
            </div>
          </div>
        </div>
        <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
      </div>
    ` : ''}
    
    <!-- Main Content -->
    <div class="row row-deck row-cards">
      <!-- Quick Actions -->
      <div class="col-md-4">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Quick Actions</h3>
          </div>
          <div class="card-body">
            <div class="list-group list-group-flush">
              <a href="#" class="list-group-item list-group-item-action" onclick="event.preventDefault(); quickScanBarcode()">
                <div class="d-flex align-items-center">
                  <i class="ti ti-scan icon me-2"></i>
                  <div>
                    <div class="text-truncate">Scan Barcode</div>
                    <small class="text-muted">Scan item or pull sheet</small>
                  </div>
                </div>
              </a>
              <a href="#" class="list-group-item list-group-item-action" onclick="event.preventDefault(); window.navigation.loadPage('inventory')">
                <div class="d-flex align-items-center">
                  <i class="ti ti-plus icon me-2"></i>
                  <div>
                    <div class="text-truncate">Add Inventory Item</div>
                    <small class="text-muted">Create new item</small>
                  </div>
                </div>
              </a>
              <a href="#" class="list-group-item list-group-item-action" onclick="event.preventDefault(); window.navigation.loadPage('shows')">
                <div class="d-flex align-items-center">
                  <i class="ti ti-theater icon me-2"></i>
                  <div>
                    <div class="text-truncate">Create Show</div>
                    <small class="text-muted">New production</small>
                  </div>
                </div>
              </a>
              <a href="#" class="list-group-item list-group-item-action" onclick="event.preventDefault(); window.navigation.loadPage('pullsheets')">
                <div class="d-flex align-items-center">
                  <i class="ti ti-clipboard-list icon me-2"></i>
                  <div>
                    <div class="text-truncate">Create Pull Sheet</div>
                    <small class="text-muted">Equipment checkout</small>
                  </div>
                </div>
              </a>
              <a href="#" class="list-group-item list-group-item-action" onclick="event.preventDefault(); window.navigation.loadPage('reports')">
                <div class="d-flex align-items-center">
                  <i class="ti ti-chart-bar icon me-2"></i>
                  <div>
                    <div class="text-truncate">View Reports</div>
                    <small class="text-muted">Analytics & exports</small>
                  </div>
                </div>
              </a>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Recent Activity -->
      <div class="col-md-8">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Recent Activity</h3>
            <div class="card-actions">
              <a href="#" onclick="event.preventDefault(); window.navigation.loadPage('reports')" class="btn btn-sm btn-primary">
                View All
              </a>
            </div>
          </div>
          <div class="card-body">
            ${renderRecentActivity(recentActivity)}
          </div>
        </div>
        
        <!-- Shortages Summary -->
        ${shortages.length > 0 ? `
          <div class="card mt-3">
            <div class="card-header">
              <h3 class="card-title">Shortage Alerts</h3>
              <div class="card-actions">
                <a href="#" onclick="event.preventDefault(); window.navigation.loadPage('reports')" class="btn btn-sm btn-warning">
                  View All
                </a>
              </div>
            </div>
            <div class="card-body">
              ${renderShortagesSummary(shortages.slice(0, 5))}
            </div>
          </div>
        ` : ''}
      </div>
    </div>
    
    <!-- Quick Scan Modal -->
    <div class="modal modal-blur fade" id="quickScanModal" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Quick Scan</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Scan or Enter Barcode</label>
              <input type="text" class="form-control form-control-lg text-mono" id="quickScanInput" placeholder="Scan barcode..." autofocus>
            </div>
            <div id="quickScanResult"></div>
          </div>
        </div>
      </div>
    </div>
  `;
}

// Render recent activity
function renderRecentActivity(activities) {
  if (activities.length === 0) {
    return '<p class="text-muted">No recent activity</p>';
  }
  
  return `
    <div class="list-group list-group-flush">
      ${activities.map(activity => {
        const time = new Date(activity.created_at).toLocaleString();
        const icon = getActivityIcon(activity.action_type);
        const color = getActivityColor(activity.action_type);
        
        return `
          <div class="list-group-item">
            <div class="d-flex align-items-center">
              <div class="me-3">
                <span class="avatar avatar-sm bg-${color}">
                  <i class="ti ${icon}"></i>
                </span>
              </div>
              <div class="flex-fill">
                <div class="text-truncate">
                  <strong>${activity.action_type}</strong>: ${activity.description}
                </div>
                <small class="text-muted">${time}</small>
              </div>
            </div>
          </div>
        `;
      }).join('')}
    </div>
  `;
}

// Get activity icon
function getActivityIcon(actionType) {
  const icons = {
    'INVENTORY': 'ti-box',
    'SHOW': 'ti-theater',
    'PULLSHEET': 'ti-clipboard-list',
    'RETURN': 'ti-arrow-back',
    'CHANGEORDER': 'ti-edit',
    'SYSTEM': 'ti-settings',
    'PDF': 'ti-file-download',
    'BARCODE': 'ti-barcode'
  };
  return icons[actionType] || 'ti-circle-dot';
}

// Get activity color
function getActivityColor(actionType) {
  const colors = {
    'INVENTORY': 'primary',
    'SHOW': 'info',
    'PULLSHEET': 'warning',
    'RETURN': 'success',
    'CHANGEORDER': 'orange',
    'SYSTEM': 'secondary',
    'PDF': 'purple',
    'BARCODE': 'cyan'
  };
  return colors[actionType] || 'secondary';
}

// Render shortages summary
function renderShortagesSummary(shortages) {
  return `
    <div class="list-group list-group-flush">
      ${shortages.map(item => {
        const severity = item.quantity_available < 0 ? 'danger' : 'warning';
        
        return `
          <div class="list-group-item">
            <div class="d-flex align-items-center">
              <div class="me-3">
                <i class="ti ti-alert-triangle text-${severity}"></i>
              </div>
              <div class="flex-fill">
                <div class="text-truncate">
                  <strong>${item.name}</strong>
                </div>
                <small class="text-muted">
                  Available: <span class="text-${severity}">${item.quantity_available}</span> | 
                  Out: ${item.quantity_out}
                </small>
              </div>
            </div>
          </div>
        `;
      }).join('')}
    </div>
  `;
}

// Initialize dashboard page
function initDashboardPage() {
  // Setup quick scan if modal exists
  const quickScanModal = document.getElementById('quickScanModal');
  if (quickScanModal) {
    quickScanModal.addEventListener('shown.bs.modal', () => {
      document.getElementById('quickScanInput')?.focus();
    });
    
    const quickScanInput = document.getElementById('quickScanInput');
    if (quickScanInput) {
      quickScanInput.addEventListener('keypress', async (e) => {
        if (e.key === 'Enter') {
          await processQuickScan();
        }
      });
    }
  }
}

// Quick scan barcode
function quickScanBarcode() {
  const modal = new bootstrap.Modal(document.getElementById('quickScanModal'));
  document.getElementById('quickScanInput').value = '';
  document.getElementById('quickScanResult').innerHTML = '';
  modal.show();
}

// Process quick scan
async function processQuickScan() {
  const barcode = document.getElementById('quickScanInput').value.trim();
  const resultDiv = document.getElementById('quickScanResult');
  
  if (!barcode) {
    resultDiv.innerHTML = '<div class="alert alert-warning">Please enter a barcode</div>';
    return;
  }
  
  try {
    // Check if it's a pull sheet barcode
    if (barcode.startsWith('PULL-')) {
      const pullSheet = await window.api.pullsheets.getByBarcode(barcode);
      if (pullSheet) {
        resultDiv.innerHTML = `
          <div class="alert alert-success">
            <strong>Pull Sheet Found:</strong> ${pullSheet.name || `Pull Sheet #${pullSheet.id}`}<br>
            <strong>Show:</strong> ${pullSheet.show_name}<br>
            <strong>Status:</strong> ${pullSheet.status}
          </div>
          <div class="btn-group w-100">
            <button class="btn btn-primary" onclick="viewPullSheetFromDashboard(${pullSheet.id})">View Details</button>
            ${pullSheet.status === 'finalized' ? `
              <button class="btn btn-success" onclick="startReturnFromDashboard(${pullSheet.id})">Start Return</button>
            ` : ''}
          </div>
        `;
        return;
      }
    }
    
    // Check if it's an item barcode
    const item = await window.api.inventory.getByBarcode(barcode);
    if (item) {
      const availabilityClass = item.quantity_available > 0 ? 'success' : 'danger';
      resultDiv.innerHTML = `
        <div class="alert alert-info">
          <strong>Item Found:</strong> ${item.name}<br>
          <strong>Category:</strong> ${item.category || 'N/A'}<br>
          <strong>Available:</strong> <span class="text-${availabilityClass}">${item.quantity_available} / ${item.quantity_total}</span><br>
          <strong>Status:</strong> ${item.status}
        </div>
        <button class="btn btn-primary w-100" onclick="viewItemFromDashboard(${item.id})">View Details</button>
      `;
      return;
    }
    
    // Not found
    resultDiv.innerHTML = '<div class="alert alert-danger">Barcode not found</div>';
  } catch (error) {
    resultDiv.innerHTML = `<div class="alert alert-danger">Error: ${error.message}</div>`;
  }
}

// View pull sheet from dashboard
function viewPullSheetFromDashboard(pullSheetId) {
  bootstrap.Modal.getInstance(document.getElementById('quickScanModal')).hide();
  window.navigation.loadPage('pullsheets', () => {
    if (window.viewPullSheet) {
      window.viewPullSheet(pullSheetId);
    }
  });
}

// Start return from dashboard
function startReturnFromDashboard(pullSheetId) {
  bootstrap.Modal.getInstance(document.getElementById('quickScanModal')).hide();
  window.navigation.loadPage('returns', () => {
    if (window.startReturnProcess) {
      window.startReturnProcess(pullSheetId);
    }
  });
}

// View item from dashboard
function viewItemFromDashboard(itemId) {
  bootstrap.Modal.getInstance(document.getElementById('quickScanModal')).hide();
  window.navigation.loadPage('inventory', () => {
    if (window.editInventoryItem) {
      window.editInventoryItem(itemId);
    }
  });
}

// Make functions globally available
window.quickScanBarcode = quickScanBarcode;
window.viewPullSheetFromDashboard = viewPullSheetFromDashboard;
window.startReturnFromDashboard = startReturnFromDashboard;
window.viewItemFromDashboard = viewItemFromDashboard;

// Register page with navigation
window.navigation.registerPage('dashboard', {
  render: renderDashboardPage,
  init: initDashboardPage
});
