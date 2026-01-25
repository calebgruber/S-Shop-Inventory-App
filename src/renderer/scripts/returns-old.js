/**
 * Returns Module
 * Handles equipment return workflows
 */

let currentReturns = [];

// Render returns page
async function renderReturnsPage() {
  currentReturns = await window.api.returns.getAll();
  
  return `
    <div class="row mb-3">
      <div class="col-md-8">
        <p class="text-muted">Process equipment returns after show closes</p>
      </div>
      <div class="col-md-4 text-end">
        <button class="btn btn-success" id="createReturnBtn">
          <i class="ti ti-arrow-back icon"></i> Start Return
        </button>
      </div>
    </div>
    
    <div class="card">
      <div class="table-responsive">
        <table class="table table-hover">
          <thead>
            <tr>
              <th>Return ID</th>
              <th>Pull Sheet</th>
              <th>Show</th>
              <th>Return Date</th>
              <th>Returned By</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="returnsTableBody">
            ${renderReturnRows(currentReturns)}
          </tbody>
        </table>
      </div>
    </div>
  `;
}

// Render return rows
function renderReturnRows(returns) {
  if (returns.length === 0) {
    return `
      <tr>
        <td colspan="7" class="text-center text-muted py-5">
          <div class="empty-state">
            <i class="ti ti-arrow-back-up empty-state-icon"></i>
            <p>No returns in progress. Start a return when a show closes.</p>
          </div>
        </td>
      </tr>
    `;
  }
  
  return returns.map(ret => {
    const returnDate = new Date(ret.return_date).toLocaleDateString();
    const statusBadge = ret.status === 'completed' 
      ? '<span class="badge bg-success">Completed</span>' 
      : '<span class="badge bg-warning">Pending</span>';
    
    return `
      <tr>
        <td><strong>#${ret.id}</strong></td>
        <td>${ret.pull_sheet_name || 'N/A'}</td>
        <td>${ret.show_name}</td>
        <td>${returnDate}</td>
        <td>${ret.returned_by || '-'}</td>
        <td>${statusBadge}</td>
        <td>
          <button class="btn btn-sm btn-ghost-primary" onclick="viewReturn(${ret.id})">
            <i class="ti ti-eye icon"></i> View
          </button>
          ${ret.status !== 'completed' ? `
            <button class="btn btn-sm btn-ghost-success" onclick="completeReturn(${ret.id})">
              <i class="ti ti-check icon"></i> Complete
            </button>
          ` : ''}
        </td>
      </tr>
    `;
  }).join('');
}

// Initialize returns page
function initReturnsPage() {
  document.getElementById('createReturnBtn')?.addEventListener('click', () => {
    alert('Return workflow with item scanning will be implemented.');
  });
}

// View return details
function viewReturn(returnId) {
  console.log('Viewing return:', returnId);
  alert('Return detail view will be implemented.');
}

// Complete return
async function completeReturn(returnId) {
  const result = await window.api.dialog.showMessage({
    type: 'question',
    title: 'Complete Return',
    message: 'Mark this return as completed? All items should be accounted for.',
    buttons: ['Cancel', 'Complete'],
    defaultId: 1
  });
  
  if (result.response === 1) {
    await window.api.returns.complete(returnId);
    currentReturns = await window.api.returns.getAll();
    document.getElementById('page-content').innerHTML = await renderReturnsPage();
    initReturnsPage();
  }
}

// Make functions globally available
window.viewReturn = viewReturn;
window.completeReturn = completeReturn;

// Register page with navigation
window.navigation.registerPage('returns', {
  render: renderReturnsPage,
  init: initReturnsPage
});
