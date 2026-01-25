/**
 * Reports Module
 * Handles reporting and analytics
 */

// Render reports page
async function renderReportsPage() {
  const shortages = await window.api.reports.getShortages();
  const activityLog = await window.api.reports.getActivityLog({ limit: 50 });
  
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
}

// Register page with navigation
window.navigation.registerPage('reports', {
  render: renderReportsPage,
  init: initReportsPage
});
