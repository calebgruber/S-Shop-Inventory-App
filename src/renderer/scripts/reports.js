/**
 * Reports Module
 * Handles reporting and analytics
 */

let currentTheatres = [];
let currentLocationItems = [];

// Render reports page
async function renderReportsPage() {
  const shortages = await window.api.reports.getShortages();
  const activityLog = await window.api.reports.getActivityLog({ limit: 50 });
  currentTheatres = await window.api.theatres.getAll();
  
  return `
    <div class="row mb-3">
      <div class="col-12">
        <p class="text-muted">Analytics, shortages, and activity logs</p>
      </div>
    </div>
    
    <div class="row row-deck row-cards mb-3">
      <div class="col-md-6">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Shortages & Alerts</h3>
          </div>
          <div class="card-body">
            ${renderShortages(shortages)}
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">Quick Stats</h3>
          </div>
          <div class="card-body">
            <div id="quickStats">Loading statistics...</div>
          </div>
        </div>
      </div>
    </div>
    
    <div class="card mb-3">
      <div class="card-header">
        <h3 class="card-title">Items by Location</h3>
        <div class="card-actions">
          <button class="btn btn-primary btn-sm" id="viewLocationReportBtn">
            <i class="ti ti-map-pin icon"></i> View Location Report
          </button>
        </div>
      </div>
      <div class="card-body">
        <p class="text-muted">View items currently in use at each theatre location</p>
      </div>
    </div>
    
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Recent Activity</h3>
      </div>
      <div class="table-responsive">
        <table class="table table-sm">
          <thead>
            <tr>
              <th>Time</th>
              <th>Action</th>
              <th>Description</th>
            </tr>
          </thead>
          <tbody>
            ${renderActivityLog(activityLog)}
          </tbody>
        </table>
      </div>
    </div>
    
    <!-- Location Report Modal -->
    <div class="modal modal-blur fade" id="locationReportModal" tabindex="-1">
      <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Items by Location</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Filter by Theatre</label>
              <select class="form-select" id="locationTheatreFilter">
                <option value="">All Theatres</option>
                ${currentTheatres.map(t => `<option value="${t.id}">${t.name}</option>`).join('')}
              </select>
            </div>
            <div id="locationReportContent">Loading...</div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn me-auto" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary" id="exportLocationPDFBtn">
              <i class="ti ti-download icon"></i> Export to PDF
            </button>
          </div>
        </div>
      </div>
    </div>
  `;
}

// Render shortages
function renderShortages(shortages) {
  if (shortages.length === 0) {
    return `
      <div class="text-center text-success py-3">
        <i class="ti ti-check icon icon-lg"></i>
        <p class="mb-0">No shortages detected</p>
      </div>
    `;
  }
  
  return shortages.map(item => `
    <div class="shortage-alert alert alert-warning mb-2">
      <strong>${item.name}</strong><br>
      <small>Available: ${item.quantity_in_stock} | Out: ${item.quantity_out}</small>
    </div>
  `).join('');
}

// Render activity log
function renderActivityLog(logs) {
  if (logs.length === 0) {
    return `
      <tr>
        <td colspan="3" class="text-center text-muted">No recent activity</td>
      </tr>
    `;
  }
  
  return logs.map(log => {
    const time = new Date(log.created_at).toLocaleString();
    return `
      <tr>
        <td class="text-muted"><small>${time}</small></td>
        <td><span class="badge">${log.action_type}</span></td>
        <td>${log.description}</td>
      </tr>
    `;
  }).join('');
}

// Initialize reports page
async function initReportsPage() {
  // Load quick stats
  const allItems = await window.api.inventory.getAll();
  const allShows = await window.api.shows.getAll();
  const allPullSheets = await window.api.pullsheets.getAll();
  
  const totalItems = allItems.length;
  const totalAvailable = allItems.filter(i => i.quantity_available > 0).length;
  const activeShows = allShows.filter(s => s.status === 'active' || s.status === 'running').length;
  const activePullSheets = allPullSheets.filter(ps => ps.status === 'pulled').length;
  
  const statsDiv = document.getElementById('quickStats');
  if (statsDiv) {
    statsDiv.innerHTML = `
      <div class="row">
        <div class="col-6 mb-3">
          <div class="text-center">
            <div class="h2 mb-0">${totalItems}</div>
            <div class="text-muted">Total Items</div>
          </div>
        </div>
        <div class="col-6 mb-3">
          <div class="text-center">
            <div class="h2 mb-0 text-success">${totalAvailable}</div>
            <div class="text-muted">Available</div>
          </div>
        </div>
        <div class="col-6">
          <div class="text-center">
            <div class="h2 mb-0 text-info">${activeShows}</div>
            <div class="text-muted">Active Shows</div>
          </div>
        </div>
        <div class="col-6">
          <div class="text-center">
            <div class="h2 mb-0 text-primary">${activePullSheets}</div>
            <div class="text-muted">Active Pulls</div>
          </div>
        </div>
      </div>
    `;
  }
  
  // Location report button
  const viewLocationBtn = document.getElementById('viewLocationReportBtn');
  if (viewLocationBtn) {
    viewLocationBtn.addEventListener('click', openLocationReport);
  }
  
  // Theatre filter change
  const theatreFilter = document.getElementById('locationTheatreFilter');
  if (theatreFilter) {
    theatreFilter.addEventListener('change', loadLocationReport);
  }
  
  // Export PDF button
  const exportPDFBtn = document.getElementById('exportLocationPDFBtn');
  if (exportPDFBtn) {
    exportPDFBtn.addEventListener('click', exportLocationPDF);
  }
}

// Open location report modal
async function openLocationReport() {
  const modal = new bootstrap.Modal(document.getElementById('locationReportModal'));
  modal.show();
  await loadLocationReport();
}

// Load location report data
async function loadLocationReport() {
  const theatreFilter = document.getElementById('locationTheatreFilter');
  const theatreId = theatreFilter ? theatreFilter.value : null;
  
  const contentDiv = document.getElementById('locationReportContent');
  if (!contentDiv) return;
  
  contentDiv.innerHTML = '<div class="text-center py-3"><div class="spinner-border" role="status"></div></div>';
  
  try {
    currentLocationItems = await window.api.reports.getItemsByLocation(theatreId || null);
    contentDiv.innerHTML = renderLocationReport(currentLocationItems);
  } catch (error) {
    console.error('Error loading location report:', error);
    contentDiv.innerHTML = `<div class="alert alert-danger">Failed to load location report: ${error.message}</div>`;
  }
}

// Render location report
function renderLocationReport(items) {
  if (!items || items.length === 0) {
    return `
      <div class="empty-state py-4">
        <i class="ti ti-map-pin empty-state-icon"></i>
        <h3>No Items Found</h3>
        <p>No items are currently in use at the selected location(s).</p>
      </div>
    `;
  }
  
  // Group items by theatre
  const groupedByTheatre = {};
  items.forEach(item => {
    if (!item.theatre_id) return;
    
    if (!groupedByTheatre[item.theatre_id]) {
      groupedByTheatre[item.theatre_id] = {
        theatre_name: item.theatre_name,
        items: []
      };
    }
    
    if (item.item_id) {
      groupedByTheatre[item.theatre_id].items.push(item);
    }
  });
  
  let html = '';
  let grandTotalItems = 0;
  let grandTotalQuantity = 0;
  
  for (const theatreId in groupedByTheatre) {
    const theatre = groupedByTheatre[theatreId];
    const theatreTotal = theatre.items.reduce((sum, item) => sum + (item.quantity || 0), 0);
    grandTotalItems += theatre.items.length;
    grandTotalQuantity += theatreTotal;
    
    html += `
      <div class="card mb-3">
        <div class="card-header">
          <h4 class="card-title">${theatre.theatre_name}</h4>
          <div class="card-subtitle">
            ${theatre.items.length} item(s) | Total Qty: ${theatreTotal}
          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-sm table-hover mb-0">
            <thead>
              <tr>
                <th>Item Name</th>
                <th>Show</th>
                <th>Quantity</th>
                <th>Serial Number</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              ${theatre.items.map(item => `
                <tr>
                  <td><strong>${item.item_name}</strong></td>
                  <td>${item.show_name || 'N/A'}</td>
                  <td><span class="badge bg-primary">${item.quantity || 0}</span></td>
                  <td>${item.serial_number || '-'}</td>
                  <td><span class="badge bg-${item.status === 'available' ? 'success' : 'warning'}">${item.status || 'N/A'}</span></td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        </div>
      </div>
    `;
  }
  
  // Add summary
  html = `
    <div class="alert alert-info mb-3">
      <strong>Summary:</strong> ${grandTotalItems} unique item(s) in ${Object.keys(groupedByTheatre).length} theatre(s) | Total Quantity: ${grandTotalQuantity}
    </div>
  ` + html;
  
  return html;
}

// Export location report to PDF
async function exportLocationPDF() {
  const theatreFilter = document.getElementById('locationTheatreFilter');
  const theatreId = theatreFilter ? theatreFilter.value : null;
  
  try {
    const result = await window.api.pdf.generateLocationReport(theatreId || null);
    if (result.success) {
      alert('Location report exported successfully!');
    } else {
      alert('Failed to export location report: ' + result.error);
    }
  } catch (error) {
    console.error('Error exporting location PDF:', error);
    alert('Failed to export location report: ' + error.message);
  }
}

// Register page with navigation
window.navigation.registerPage('reports', {
  render: renderReportsPage,
  init: initReportsPage
});
