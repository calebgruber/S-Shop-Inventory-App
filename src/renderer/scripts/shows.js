/**
 * Shows Management Module
 * Handles show/production creation and management
 */

let currentShows = [];
let currentTheatres = [];

// Render shows page
async function renderShowsPage() {
  currentShows = await window.api.shows.getAll();
  currentTheatres = await window.api.theatres.getAll();
  
  return `
    <div class="row mb-3">
      <div class="col-md-8">
        <p class="text-muted">Manage theatre productions and their equipment requirements</p>
      </div>
      <div class="col-md-4 text-end">
        <button class="btn btn-success" id="addShowBtn">
          <i class="ti ti-plus icon"></i> Create Show
        </button>
      </div>
    </div>
    
    <div class="row row-deck row-cards">
      ${renderShowCards(currentShows)}
    </div>
    
    <!-- Add/Edit Show Modal -->
    <div class="modal modal-blur fade" id="showModal" tabindex="-1">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="showModalTitle">Create Show</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <form id="showForm">
              <input type="hidden" id="showId">
              <div class="mb-3">
                <label class="form-label required">Show Name</label>
                <input type="text" class="form-control" id="showName" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea class="form-control" id="showDescription" rows="2"></textarea>
              </div>
              <div class="mb-3">
                <label class="form-label">Theatre</label>
                <select class="form-select" id="showTheatre">
                  <option value="">Select a theatre...</option>
                  ${currentTheatres.map(t => `<option value="${t.id}">${t.name}</option>`).join('')}
                </select>
                <small class="form-hint">Optional - link this show to a specific theatre space</small>
              </div>
              <div class="mb-3">
                <label class="form-label">Venue</label>
                <input type="text" class="form-control" id="showVenue">
                <small class="form-hint">e.g., Main Stage, Black Box, Studio Theatre</small>
              </div>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label">Start Date</label>
                  <input type="date" class="form-control" id="showStartDate">
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label">End Date</label>
                  <input type="date" class="form-control" id="showEndDate">
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label">Status</label>
                <select class="form-select" id="showStatus">
                  <option value="planning">Planning</option>
                  <option value="active">Active</option>
                  <option value="running">Running</option>
                  <option value="closed">Closed</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label">Notes</label>
                <textarea class="form-control" id="showNotes" rows="2"></textarea>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn me-auto" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="saveShowBtn">Save Show</button>
          </div>
        </div>
      </div>
    </div>
  `;
}

// Render show cards
function renderShowCards(shows) {
  if (shows.length === 0) {
    return `
      <div class="col-12">
        <div class="empty-state">
          <i class="ti ti-theater empty-state-icon"></i>
          <h3>No Shows Yet</h3>
          <p>Create your first show to start managing equipment.</p>
          <button class="btn btn-primary" onclick="document.getElementById('addShowBtn').click()">
            <i class="ti ti-plus icon"></i> Create First Show
          </button>
        </div>
      </div>
    `;
  }
  
  return shows.map(show => {
    const statusClass = getShowStatusClass(show.status);
    const dates = formatShowDates(show.start_date, show.end_date);
    
    return `
      <div class="col-md-6 col-lg-4">
        <div class="card">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-3">
              <h3 class="card-title">${show.name}</h3>
              <span class="badge ${statusClass}">${show.status || 'planning'}</span>
            </div>
            ${show.description ? `<p class="text-muted">${show.description}</p>` : ''}
            <div class="mb-2">
              <strong>Venue:</strong> ${show.venue || 'Not specified'}
            </div>
            ${dates ? `<div class="mb-2"><strong>Dates:</strong> ${dates}</div>` : ''}
            <div class="btn-group w-100 mt-3">
              <button class="btn btn-primary" onclick="viewShowDetails(${show.id})">
                <i class="ti ti-eye icon"></i> View
              </button>
              <button class="btn btn-secondary" onclick="createPullSheet(${show.id})">
                <i class="ti ti-clipboard-list icon"></i> Pull Sheet
              </button>
              <button class="btn btn-ghost-primary" onclick="editShow(${show.id})">
                <i class="ti ti-edit icon"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    `;
  }).join('');
}

// Get show status CSS class
function getShowStatusClass(status) {
  const classes = {
    planning: 'bg-secondary',
    active: 'bg-info',
    running: 'bg-success',
    closed: 'bg-dark'
  };
  return classes[status] || classes.planning;
}

// Format show dates
function formatShowDates(startDate, endDate) {
  if (!startDate && !endDate) return '';
  
  const formatDate = (date) => {
    if (!date) return '';
    return new Date(date).toLocaleDateString();
  };
  
  if (startDate && endDate) {
    return `${formatDate(startDate)} - ${formatDate(endDate)}`;
  } else if (startDate) {
    return `From ${formatDate(startDate)}`;
  } else {
    return `Until ${formatDate(endDate)}`;
  }
}

// Initialize shows page
function initShowsPage() {
  // Add show button
  document.getElementById('addShowBtn')?.addEventListener('click', () => {
    openShowModal();
  });
  
  // Save show button
  document.getElementById('saveShowBtn')?.addEventListener('click', saveShow);
}

// Open show modal
async function openShowModal(show = null) {
  const modal = new bootstrap.Modal(document.getElementById('showModal'));
  const title = document.getElementById('showModalTitle');
  
  // Load theatres if not already loaded
  if (currentTheatres.length === 0) {
    currentTheatres = await window.api.theatres.getAll();
  }
  
  if (show) {
    title.textContent = 'Edit Show';
    document.getElementById('showId').value = show.id;
    document.getElementById('showName').value = show.name || '';
    document.getElementById('showDescription').value = show.description || '';
    document.getElementById('showTheatre').value = show.theatre_id || '';
    document.getElementById('showVenue').value = show.venue || '';
    document.getElementById('showStartDate').value = show.start_date || '';
    document.getElementById('showEndDate').value = show.end_date || '';
    document.getElementById('showStatus').value = show.status || 'planning';
    document.getElementById('showNotes').value = show.notes || '';
  } else {
    title.textContent = 'Create Show';
    document.getElementById('showForm').reset();
    document.getElementById('showId').value = '';
  }
  
  modal.show();
}

// Edit show
async function editShow(showId) {
  const show = await window.api.shows.getById(showId);
  if (show) {
    openShowModal(show);
  }
}

// Save show
async function saveShow() {
  const theatreValue = document.getElementById('showTheatre').value;
  const showData = {
    name: document.getElementById('showName').value,
    description: document.getElementById('showDescription').value,
    theatre_id: theatreValue ? parseInt(theatreValue) : null,
    venue: document.getElementById('showVenue').value,
    start_date: document.getElementById('showStartDate').value,
    end_date: document.getElementById('showEndDate').value,
    status: document.getElementById('showStatus').value,
    notes: document.getElementById('showNotes').value
  };
  
  const showId = document.getElementById('showId').value;
  
  try {
    if (showId) {
      await window.api.shows.update(showId, showData);
    } else {
      await window.api.shows.create(showData);
    }
    
    // Close modal
    bootstrap.Modal.getInstance(document.getElementById('showModal')).hide();
    
    // Refresh shows list
    currentShows = await window.api.shows.getAll();
    document.getElementById('page-content').innerHTML = await renderShowsPage();
    initShowsPage();
  } catch (error) {
    alert('Error saving show: ' + error.message);
  }
}

// View show details
async function viewShowDetails(showId) {
  const show = await window.api.shows.getById(showId);
  if (!show) {
    alert('Show not found');
    return;
  }
  
  const pullSheets = await window.api.pullsheets.getByShow(showId);
  const changeOrders = await window.api.changeOrders.getByShow(showId);
  
  // Create modal content
  const modalHtml = `
    <div class="modal modal-blur fade" id="showDetailModal" tabindex="-1">
      <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">${show.name}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="row mb-3">
              <div class="col-md-6">
                <p><strong>Status:</strong> <span class="badge ${getShowStatusClass(show.status)}">${show.status}</span></p>
                ${show.description ? `<p><strong>Description:</strong> ${show.description}</p>` : ''}
                ${show.venue ? `<p><strong>Venue:</strong> ${show.venue}</p>` : ''}
              </div>
              <div class="col-md-6">
                ${show.start_date ? `<p><strong>Start Date:</strong> ${new Date(show.start_date).toLocaleDateString()}</p>` : ''}
                ${show.end_date ? `<p><strong>End Date:</strong> ${new Date(show.end_date).toLocaleDateString()}</p>` : ''}
                <p><strong>Created:</strong> ${new Date(show.created_at).toLocaleString()}</p>
              </div>
            </div>
            
            <div class="card mb-3">
              <div class="card-header">
                <h3 class="card-title">Pull Sheets (${pullSheets.length})</h3>
                <div class="card-actions">
                  <button class="btn btn-sm btn-primary" onclick="createPullSheet(${showId})">
                    <i class="ti ti-plus icon"></i> New Pull Sheet
                  </button>
                </div>
              </div>
              <div class="table-responsive">
                <table class="table table-sm">
                  <thead>
                    <tr>
                      <th>Name</th>
                      <th>Status</th>
                      <th>Pulled Date</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    ${pullSheets.length === 0 ? `
                      <tr><td colspan="4" class="text-center text-muted">No pull sheets yet</td></tr>
                    ` : pullSheets.map(ps => `
                      <tr>
                        <td>${ps.name || `Pull Sheet #${ps.id}`}</td>
                        <td>${getPullSheetStatusBadge(ps.status)}</td>
                        <td>${ps.pulled_date ? new Date(ps.pulled_date).toLocaleDateString() : '-'}</td>
                        <td>
                          <button class="btn btn-sm btn-ghost-primary" onclick="viewPullSheetFromShow(${ps.id})">
                            <i class="ti ti-eye icon"></i>
                          </button>
                        </td>
                      </tr>
                    `).join('')}
                  </tbody>
                </table>
              </div>
            </div>
            
            ${changeOrders.length > 0 ? `
              <div class="card">
                <div class="card-header">
                  <h3 class="card-title">Change Orders (${changeOrders.length})</h3>
                </div>
                <div class="table-responsive">
                  <table class="table table-sm">
                    <thead>
                      <tr>
                        <th>Type</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Created</th>
                      </tr>
                    </thead>
                    <tbody>
                      ${changeOrders.map(co => `
                        <tr>
                          <td>${co.type}</td>
                          <td>${co.description || '-'}</td>
                          <td><span class="badge">${co.status}</span></td>
                          <td>${new Date(co.created_at).toLocaleDateString()}</td>
                        </tr>
                      `).join('')}
                    </tbody>
                  </table>
                </div>
              </div>
            ` : ''}
          </div>
          <div class="modal-footer">
            <button type="button" class="btn me-auto" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary" onclick="editShow(${showId}); bootstrap.Modal.getInstance(document.getElementById('showDetailModal')).hide();">
              <i class="ti ti-edit icon"></i> Edit Show
            </button>
            <button type="button" class="btn btn-danger" onclick="deleteShowWithConfirm(${showId})">
              <i class="ti ti-trash icon"></i> Delete
            </button>
          </div>
        </div>
      </div>
    </div>
  `;
  
  // Remove old modal if exists
  const oldModal = document.getElementById('showDetailModal');
  if (oldModal) {
    oldModal.remove();
  }
  
  // Add modal to body
  document.body.insertAdjacentHTML('beforeend', modalHtml);
  
  // Show modal
  const modal = new bootstrap.Modal(document.getElementById('showDetailModal'));
  modal.show();
  
  // Remove modal from DOM when closed
  document.getElementById('showDetailModal').addEventListener('hidden.bs.modal', () => {
    document.getElementById('showDetailModal').remove();
  });
}

// Helper to get pull sheet status badge
function getPullSheetStatusBadge(status) {
  const badges = {
    draft: '<span class="badge bg-secondary">Draft</span>',
    finalized: '<span class="badge bg-info">Finalized</span>',
    pulled: '<span class="badge bg-primary">Active</span>',
    returned: '<span class="badge bg-success">Returned</span>'
  };
  return badges[status] || badges.draft;
}

// View pull sheet from show detail
function viewPullSheetFromShow(pullSheetId) {
  bootstrap.Modal.getInstance(document.getElementById('showDetailModal')).hide();
  window.navigation.loadPage('pullsheets', () => {
    if (window.viewPullSheet) {
      window.viewPullSheet(pullSheetId);
    }
  });
}

// Delete show with confirmation
async function deleteShowWithConfirm(showId) {
  const show = currentShows.find(s => s.id === showId);
  if (!show) return;
  
  const result = await window.api.dialog.showMessage({
    type: 'warning',
    title: 'Delete Show',
    message: `Delete "${show.name}"?`,
    detail: 'This will also delete all pull sheets and change orders for this show. This action cannot be undone.',
    buttons: ['Cancel', 'Delete'],
    defaultId: 0
  });
  
  if (result.response === 1) {
    try {
      await window.api.shows.delete(showId);
      
      // Close modal
      const modal = document.getElementById('showDetailModal');
      if (modal) {
        bootstrap.Modal.getInstance(modal).hide();
      }
      
      // Refresh list
      currentShows = await window.api.shows.getAll();
      document.getElementById('page-content').innerHTML = await renderShowsPage();
      initShowsPage();
    } catch (error) {
      alert('Error deleting show: ' + error.message);
    }
  }
}

// Create pull sheet for show
function createPullSheet(showId) {
  // Navigate to pull sheets page and create new
  window.navigation.loadPage('pullsheets');
  // In full implementation, would open pull sheet creation for this show
  console.log('Creating pull sheet for show:', showId);
}

// Make functions globally available
window.editShow = editShow;
window.viewShowDetails = viewShowDetails;
window.createPullSheet = createPullSheet;
window.viewPullSheetFromShow = viewPullSheetFromShow;
window.deleteShowWithConfirm = deleteShowWithConfirm;

// Register page with navigation
window.navigation.registerPage('shows', {
  render: renderShowsPage,
  init: initShowsPage
});
