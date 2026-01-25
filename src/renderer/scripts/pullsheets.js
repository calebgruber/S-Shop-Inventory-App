/**
 * Pull Sheets Module
 * Handles pull sheet creation, management, and barcode-driven workflows
 */

let currentPullSheets = [];

// Render pull sheets page
async function renderPullSheetsPage() {
  currentPullSheets = await window.api.pullsheets.getAll();
  
  return `
    <div class="row mb-3">
      <div class="col-md-8">
        <p class="text-muted">Equipment checkout lists for shows</p>
      </div>
      <div class="col-md-4 text-end">
        <button class="btn btn-success" id="createPullSheetBtn">
          <i class="ti ti-plus icon"></i> Create Pull Sheet
        </button>
      </div>
    </div>
    
    <div class="card">
      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>Pull Sheet</th>
              <th>Show</th>
              <th>Status</th>
              <th>Items</th>
              <th>Pulled Date</th>
              <th>Pulled By</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="pullSheetsTableBody">
            ${renderPullSheetRows(currentPullSheets)}
          </tbody>
        </table>
      </div>
    </div>
  `;
}

// Render pull sheet rows
function renderPullSheetRows(pullSheets) {
  if (pullSheets.length === 0) {
    return `
      <tr>
        <td colspan="7" class="text-center text-muted py-5">
          <div class="empty-state">
            <i class="ti ti-clipboard-off empty-state-icon"></i>
            <p>No pull sheets yet. Create one to start checking out equipment.</p>
          </div>
        </td>
      </tr>
    `;
  }
  
  return pullSheets.map(ps => {
    const statusBadge = getPullSheetStatusBadge(ps.status);
    const pulledDate = ps.pulled_date ? new Date(ps.pulled_date).toLocaleDateString() : '-';
    
    return `
      <tr>
        <td><strong>${ps.name || `Pull Sheet #${ps.id}`}</strong></td>
        <td>${ps.show_name}</td>
        <td>${statusBadge}</td>
        <td class="text-center">-</td>
        <td>${pulledDate}</td>
        <td>${ps.pulled_by || '-'}</td>
        <td>
          <button class="btn btn-sm btn-ghost-primary" onclick="viewPullSheet(${ps.id})">
            <i class="ti ti-eye icon"></i> View
          </button>
          <button class="btn btn-sm btn-ghost-success" onclick="generatePullSheetPDF(${ps.id})">
            <i class="ti ti-file-download icon"></i> PDF
          </button>
        </td>
      </tr>
    `;
  }).join('');
}

// Get pull sheet status badge
function getPullSheetStatusBadge(status) {
  const badges = {
    draft: '<span class="pullsheet-status draft">Draft</span>',
    ready: '<span class="pullsheet-status ready">Ready</span>',
    pulled: '<span class="pullsheet-status pulled">Pulled</span>',
    returned: '<span class="pullsheet-status returned">Returned</span>'
  };
  return badges[status] || badges.draft;
}

// Initialize pull sheets page
function initPullSheetsPage() {
  document.getElementById('createPullSheetBtn')?.addEventListener('click', () => {
    alert('Pull sheet creation with barcode scanning will be implemented.');
  });
}

// View pull sheet details
function viewPullSheet(pullSheetId) {
  console.log('Viewing pull sheet:', pullSheetId);
  alert('Pull sheet detail view with item scanning will be implemented.');
}

// Generate pull sheet PDF
async function generatePullSheetPDF(pullSheetId) {
  const result = await window.api.pdf.generatePullSheet(pullSheetId);
  if (result.success) {
    alert(`Pull sheet PDF generated: ${result.filePath}`);
  } else {
    alert('Failed to generate PDF: ' + result.error);
  }
}

// Make functions globally available
window.viewPullSheet = viewPullSheet;
window.generatePullSheetPDF = generatePullSheetPDF;

// Register page with navigation
window.navigation.registerPage('pullsheets', {
  render: renderPullSheetsPage,
  init: initPullSheetsPage
});
