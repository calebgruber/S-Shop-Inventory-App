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
function viewShowDetails(showId) {
  // In full implementation, would show detailed view
  console.log('Viewing show details:', showId);
  alert('Show details view will be implemented with pull sheets and change orders.');
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

// Register page with navigation
window.navigation.registerPage('shows', {
  render: renderShowsPage,
  init: initShowsPage
});
