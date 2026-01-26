/**
 * Pull Sheets Module - COMPLETE IMPLEMENTATION
 * Handles pull sheet creation, management, and barcode-driven workflows
 */

(function() {
'use strict';

let currentPullSheets = [];
let currentShows = [];
let currentPullSheet = null;
let currentItems = [];

// Render pull sheets page
async function renderPullSheetsPage() {
  currentPullSheets = await window.api.pullsheets.getAll();
  currentShows = await window.api.shows.getAll();
  
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
    
    <div class="row mb-3">
      <div class="col-md-12">
        <div class="btn-group" role="group">
          <input type="radio" class="btn-check" name="psStatusFilter" id="psFilterAll" value="all" checked>
          <label class="btn btn-outline-primary" for="psFilterAll">All</label>
          
          <input type="radio" class="btn-check" name="psStatusFilter" id="psFilterDraft" value="draft">
          <label class="btn btn-outline-secondary" for="psFilterDraft">Draft</label>
          
          <input type="radio" class="btn-check" name="psStatusFilter" id="psFilterFinalized" value="finalized">
          <label class="btn btn-outline-info" for="psFilterFinalized">Finalized</label>
          
          <input type="radio" class="btn-check" name="psStatusFilter" id="psFilterReturned" value="returned">
          <label class="btn btn-outline-success" for="psFilterReturned">Returned</label>
        </div>
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
    
    <!-- Create Pull Sheet Modal -->
    <div class="modal modal-blur fade" id="createPullSheetModal" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Create Pull Sheet</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <form id="createPullSheetForm">
              <div class="mb-3">
                <label class="form-label required">Show</label>
                <select class="form-select" id="pullSheetShow" required>
                  <option value="">Select a show...</option>
                  ${currentShows.filter(s => s.status !== 'closed').map(s => 
                    `<option value="${s.id}">${s.name}</option>`
                  ).join('')}
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label">Pull Sheet Name</label>
                <input type="text" class="form-control" id="pullSheetName" placeholder="Optional - leave blank for auto-naming">
              </div>
              <div class="mb-3">
                <label class="form-label">Created By</label>
                <input type="text" class="form-control" id="pullSheetCreatedBy" placeholder="Your name">
                <small class="form-hint">Person creating this pull sheet</small>
              </div>
              <div class="mb-3">
                <label class="form-label">Notes</label>
                <textarea class="form-control" id="pullSheetNotes" rows="2"></textarea>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn me-auto" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="savePullSheetBtn">Create</button>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Pull Sheet Detail Modal -->
    <div class="modal modal-blur fade" id="pullSheetDetailModal" tabindex="-1">
      <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="pullSheetDetailTitle">Pull Sheet Details</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div id="pullSheetDetailContent">Loading...</div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn me-auto" data-bs-dismiss="modal">Close</button>
            <div class="btn-group" id="pullSheetActions"></div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Add Items Modal -->
    <div class="modal modal-blur fade" id="addItemsModal" tabindex="-1">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Add Items to Pull Sheet</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Scan Barcode or Search</label>
              <div class="input-group">
                <input type="text" class="form-control" id="itemSearchInput" placeholder="Scan barcode or search by name...">
                <button class="btn btn-primary" id="searchItemBtn">
                  <i class="ti ti-search icon"></i>
                </button>
              </div>
            </div>
            <div id="searchResults"></div>
          </div>
        </div>
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
    const itemCount = ps.items ? ps.items.length : 0;
    
    return `
      <tr>
        <td><strong>${ps.name || `Pull Sheet #${ps.id}`}</strong></td>
        <td>${ps.show_name}</td>
        <td>${statusBadge}</td>
        <td class="text-center">${itemCount}</td>
        <td>${pulledDate}</td>
        <td>${ps.pulled_by || '-'}</td>
        <td>
          <button class="btn btn-sm btn-ghost-primary" onclick="viewPullSheet(${ps.id})">
            <i class="ti ti-eye icon"></i> View
          </button>
          <button class="btn btn-sm btn-ghost-success" onclick="generatePullSheetPDF(${ps.id})">
            <i class="ti ti-file-download icon"></i> PDF
          </button>
          ${ps.status === 'draft' ? `
            <button class="btn btn-sm btn-ghost-danger" onclick="deletePullSheet(${ps.id})">
              <i class="ti ti-trash icon"></i>
            </button>
          ` : ''}
        </td>
      </tr>
    `;
  }).join('');
}

// Get pull sheet status badge
function getPullSheetStatusBadge(status) {
  const badges = {
    draft: '<span class="badge bg-secondary">Draft</span>',
    finalized: '<span class="badge bg-info">Finalized</span>',
    pulled: '<span class="badge bg-primary">Active</span>',
    returned: '<span class="badge bg-success">Returned</span>'
  };
  return badges[status] || badges.draft;
}

// Initialize pull sheets page
function initPullSheetsPage() {
  // Create button
  document.getElementById('createPullSheetBtn')?.addEventListener('click', () => {
    openCreatePullSheetModal();
  });
  
  // Save pull sheet
  document.getElementById('savePullSheetBtn')?.addEventListener('click', createPullSheet);
  
  // Filter buttons
  document.querySelectorAll('[name="psStatusFilter"]').forEach(radio => {
    radio.addEventListener('change', async (e) => {
      const filter = e.target.value;
      let filtered = currentPullSheets;
      
      if (filter !== 'all') {
        filtered = currentPullSheets.filter(ps => ps.status === filter);
      }
      
      document.getElementById('pullSheetsTableBody').innerHTML = renderPullSheetRows(filtered);
    });
  });
}

// Open create pull sheet modal
function openCreatePullSheetModal() {
  const modal = new bootstrap.Modal(document.getElementById('createPullSheetModal'));
  document.getElementById('createPullSheetForm').reset();
  modal.show();
}

// Create pull sheet
async function createPullSheet() {
  const showId = document.getElementById('pullSheetShow').value;
  
  if (!showId) {
    alert('Please select a show');
    return;
  }
  
  const pullSheetData = {
    show_id: parseInt(showId),
    name: document.getElementById('pullSheetName').value || null,
    status: 'draft',
    notes: document.getElementById('pullSheetNotes').value || null,
    pulled_by: document.getElementById('pullSheetCreatedBy').value || null
  };
  
  try {
    const result = await window.api.pullsheets.create(pullSheetData);
    
    // Close modal
    bootstrap.Modal.getInstance(document.getElementById('createPullSheetModal')).hide();
    
    // Refresh list
    await refreshPullSheets();
    
    // Open detail view to add items
    viewPullSheet(result.id);
  } catch (error) {
    alert('Error creating pull sheet: ' + error.message);
  }
}

// View pull sheet details
async function viewPullSheet(pullSheetId) {
  currentPullSheet = await window.api.pullsheets.getById(pullSheetId);
  
  if (!currentPullSheet) {
    alert('Pull sheet not found');
    return;
  }
  
  // If it's a draft, open in full-screen mode for easier item addition
  if (currentPullSheet.status === 'draft') {
    openFullScreenPullSheet();
  } else {
    // For finalized/returned pull sheets, use the regular modal
    const modal = new bootstrap.Modal(document.getElementById('pullSheetDetailModal'));
    document.getElementById('pullSheetDetailTitle').textContent = currentPullSheet.name || `Pull Sheet #${currentPullSheet.id}`;
    
    renderPullSheetDetail();
    modal.show();
  }
}

// Open pull sheet in full-screen mode for item addition
function openFullScreenPullSheet() {
  // Create or get full-screen modal
  let fullScreenModal = document.getElementById('pullSheetFullScreenModal');
  
  if (!fullScreenModal) {
    fullScreenModal = document.createElement('div');
    fullScreenModal.id = 'pullSheetFullScreenModal';
    fullScreenModal.className = 'modal modal-blur fade';
    fullScreenModal.setAttribute('data-bs-backdrop', 'static');
    fullScreenModal.setAttribute('data-bs-keyboard', 'false');
    fullScreenModal.innerHTML = `
      <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
          <div class="modal-header bg-primary text-white">
            <h3 class="modal-title" id="fullScreenPullSheetTitle"></h3>
            <button type="button" class="btn-close btn-close-white" onclick="closeFullScreenPullSheet()"></button>
          </div>
          <div class="modal-body" id="fullScreenPullSheetBody">
          </div>
          <div class="modal-footer">
            <div id="fullScreenPullSheetActions"></div>
          </div>
        </div>
      </div>
    `;
    document.body.appendChild(fullScreenModal);
  }
  
  document.getElementById('fullScreenPullSheetTitle').textContent = currentPullSheet.name || `Pull Sheet #${currentPullSheet.id}`;
  renderFullScreenPullSheet();
  
  const modal = new bootstrap.Modal(fullScreenModal);
  modal.show();
}

// Render full-screen pull sheet interface
function renderFullScreenPullSheet() {
  const items = currentPullSheet.items || [];
  
  const body = document.getElementById('fullScreenPullSheetBody');
  body.innerHTML = `
    <div class="container-fluid">
      <div class="row mb-4">
        <div class="col-md-6">
          <h4>Show: ${currentPullSheet.show_name}</h4>
          <p class="text-muted">Status: ${getPullSheetStatusBadge(currentPullSheet.status)}</p>
          ${currentPullSheet.pulled_by ? `<p class="text-muted">Created by: ${currentPullSheet.pulled_by}</p>` : ''}
        </div>
        <div class="col-md-6 text-end">
          <div class="h2 mb-0">${items.length} Items</div>
          <div class="text-muted">in pull sheet</div>
        </div>
      </div>
      
      <!-- Search/Scan Section -->
      <div class="row mb-4">
        <div class="col-12">
          <div class="card bg-light">
            <div class="card-body">
              <h4 class="mb-3"><i class="ti ti-scan icon"></i> Add Items to Pull Sheet</h4>
              <div class="input-group input-group-lg">
                <span class="input-group-text bg-primary text-white">
                  <i class="ti ti-barcode icon"></i>
                </span>
                <input type="text" class="form-control form-control-lg" id="fullScreenSearchInput" 
                       placeholder="Scan barcode or type to search for items..." autofocus>
                <button class="btn btn-primary btn-lg" id="fullScreenSearchBtn">
                  <i class="ti ti-search icon"></i> Search
                </button>
              </div>
              <div id="fullScreenSearchResults" class="mt-3"></div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Items List -->
      <div class="row">
        <div class="col-12">
          <div class="card">
            <div class="card-header">
              <h3 class="card-title">Pull Sheet Items</h3>
            </div>
            <div class="table-responsive">
              <table class="table table-lg table-hover">
                <thead>
                  <tr>
                    <th>Item</th>
                    <th>Barcode</th>
                    <th>Location</th>
                    <th class="text-center">Requested</th>
                    <th class="text-center">Available</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  ${items.length === 0 ? `
                    <tr>
                      <td colspan="6" class="text-center text-muted py-5">
                        <i class="ti ti-list-search icon icon-lg mb-2"></i>
                        <p>No items added yet. Use the search above to add items.</p>
                      </td>
                    </tr>
                  ` : items.map(item => {
                    const availabilityClass = item.quantity_available >= item.quantity_requested ? 'text-success' : 'text-danger';
                    return `
                    <tr>
                      <td>
                        <div><strong class="h4">${item.name}</strong></div>
                        ${item.description ? `<div class="text-muted">${item.description}</div>` : ''}
                      </td>
                      <td><span class="text-mono h5">${item.barcode || '-'}</span></td>
                      <td><span class="h5">${item.location || '-'}</span></td>
                      <td class="text-center"><span class="badge bg-primary badge-lg">${item.quantity_requested}</span></td>
                      <td class="text-center ${availabilityClass}"><strong class="h4">${item.quantity_available || 0}</strong></td>
                      <td>
                        <button class="btn btn-ghost-danger" onclick="removeItemFromPullSheet(${item.item_id})">
                          <i class="ti ti-trash icon"></i> Remove
                        </button>
                      </td>
                    </tr>
                  `;
                  }).join('')}
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  `;
  
  // Setup search functionality
  setTimeout(() => setupFullScreenSearch(), 100);
  
  // Update actions
  const actions = document.getElementById('fullScreenPullSheetActions');
  actions.innerHTML = items.length > 0 ? `
    <button class="btn btn-outline-secondary me-auto" onclick="closeFullScreenPullSheet()">Close</button>
    <button class="btn btn-success btn-lg" onclick="finalizePullSheet()">
      <i class="ti ti-check icon"></i> Finalize & Generate PDF
    </button>
  ` : `
    <button class="btn btn-outline-secondary" onclick="closeFullScreenPullSheet()">Close</button>
    <span class="text-muted">Add items to enable finalization</span>
  `;
}

// Setup full-screen search functionality
function setupFullScreenSearch() {
  const input = document.getElementById('fullScreenSearchInput');
  const searchBtn = document.getElementById('fullScreenSearchBtn');
  
  if (!input || !searchBtn) return;
  
  // Use requestAnimationFrame to ensure focus happens after DOM is fully rendered
  requestAnimationFrame(() => {
    requestAnimationFrame(() => {
      input.focus();
      input.select();
    });
  });
  
  // Select all on focus
  input.addEventListener('focus', () => {
    input.select();
  });
  
  // Handle Enter key
  input.addEventListener('keypress', async (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      await fullScreenSearch();
    }
  });
  
  // Handle search button
  searchBtn.addEventListener('click', async () => {
    await fullScreenSearch();
  });
}

// Perform full-screen search
async function fullScreenSearch() {
  const query = document.getElementById('fullScreenSearchInput').value.trim();
  const resultsDiv = document.getElementById('fullScreenSearchResults');
  
  if (!query) {
    resultsDiv.innerHTML = '';
    return;
  }
  
  try {
    // Try barcode lookup first
    const item = await window.api.inventory.getByBarcode(query);
    
    if (item) {
      showQuantityModal(item);
      return;
    }
    
    // Search by name if not found by barcode
    const items = await window.api.inventory.search(query);
    
    if (items.length === 0) {
      resultsDiv.innerHTML = `<div class="alert alert-warning">No items found for "${query}"</div>`;
      return;
    }
    
    // Show search results
    resultsDiv.innerHTML = `
      <div class="card">
        <div class="list-group list-group-flush">
          ${items.map(item => `
            <div class="list-group-item">
              <div class="row align-items-center">
                <div class="col">
                  <h4 class="mb-1">${item.name}</h4>
                  <p class="text-muted mb-0">
                    <span class="me-3">${item.barcode || 'No barcode'}</span>
                    <span class="me-3">${item.category || 'Uncategorized'}</span>
                    <span class="me-3">${item.location || 'No location'}</span>
                  </p>
                </div>
                <div class="col-auto">
                  <span class="badge bg-${item.quantity_available > 0 ? 'success' : 'danger'} badge-lg me-3">
                    ${item.quantity_available} available
                  </span>
                  <button class="btn btn-primary btn-lg" onclick='showQuantityModal(${JSON.stringify(item)})'>
                    <i class="ti ti-plus icon"></i> Add to Pull Sheet
                  </button>
                </div>
              </div>
            </div>
          `).join('')}
        </div>
      </div>
    `;
  } catch (error) {
    resultsDiv.innerHTML = `<div class="alert alert-danger">Error: ${error.message}</div>`;
  }
}

// Show quantity selection modal
window.showQuantityModal = function(item) {
  const modal = document.createElement('div');
  modal.innerHTML = `
    <div class="modal modal-blur fade show" style="display: block;" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
          <div class="modal-header bg-primary text-white">
            <h3 class="modal-title">Select Quantity</h3>
          </div>
          <div class="modal-body">
            <h4>${item.name}</h4>
            <p class="text-muted">${item.description || ''}</p>
            <div class="row mb-4">
              <div class="col-6">
                <div class="text-muted">Available in Stock</div>
                <div class="h1 ${item.quantity_available > 0 ? 'text-success' : 'text-danger'}">
                  ${item.quantity_available}
                </div>
              </div>
              <div class="col-6">
                <div class="text-muted">Location</div>
                <div class="h3">${item.location || 'N/A'}</div>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label h4">Quantity to Add</label>
              <input type="number" class="form-control form-control-lg" id="quantityInput" 
                     value="1" min="1" max="${item.quantity_available}" autofocus>
              <small class="form-hint">Maximum: ${item.quantity_available}</small>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn" onclick="this.closest('.modal').remove()">Cancel</button>
            <button type="button" class="btn btn-primary btn-lg" id="confirmQuantityBtn">
              <i class="ti ti-check icon"></i> Add to Pull Sheet
            </button>
          </div>
        </div>
      </div>
    </div>
    <div class="modal-backdrop fade show"></div>
  `;
  document.body.appendChild(modal);
  
  // Focus and select the input using requestAnimationFrame for reliability
  requestAnimationFrame(() => {
    requestAnimationFrame(() => {
      const input = document.getElementById('quantityInput');
      if (input) {
        input.focus();
        input.select();
      }
    });
  });
  
  // Handle Enter key
  document.getElementById('quantityInput').addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
      document.getElementById('confirmQuantityBtn').click();
    }
  });
  
  // Handle confirm
  document.getElementById('confirmQuantityBtn').addEventListener('click', async () => {
    const quantity = parseInt(document.getElementById('quantityInput').value) || 1;
    
    if (quantity > item.quantity_available) {
      alert(`Only ${item.quantity_available} available in stock`);
      return;
    }
    
    modal.remove();
    await addItemToPullSheet(item.id, quantity);
    
    // Clear search and refocus
    const searchInput = document.getElementById('fullScreenSearchInput');
    const resultsDiv = document.getElementById('fullScreenSearchResults');
    
    if (searchInput) searchInput.value = '';
    if (resultsDiv) {
      resultsDiv.innerHTML = `
        <div class="alert alert-success alert-dismissible fade show">
          <strong>${item.name}</strong> added to pull sheet (Quantity: ${quantity})
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      `;
    }
    
    // Use requestAnimationFrame for reliable focus
    requestAnimationFrame(() => {
      requestAnimationFrame(() => {
        if (searchInput) {
          searchInput.focus();
          searchInput.select();
        }
      });
    });
  });
};

// Close full-screen pull sheet
window.closeFullScreenPullSheet = function() {
  const modal = document.getElementById('pullSheetFullScreenModal');
  if (modal) {
    const bsModal = bootstrap.Modal.getInstance(modal);
    if (bsModal) {
      bsModal.hide();
    }
  }
};

// Render pull sheet detail
function renderPullSheetDetail() {
  if (!currentPullSheet) return;
  
  const items = currentPullSheet.items || [];
  const isDraft = currentPullSheet.status === 'draft';
  const isFinalized = currentPullSheet.status === 'finalized';
  const isReturned = currentPullSheet.status === 'returned';
  
  let content = `
    <div class="row mb-3">
      <div class="col-md-6">
        <p><strong>Show:</strong> ${currentPullSheet.show_name}</p>
        <p><strong>Status:</strong> ${getPullSheetStatusBadge(currentPullSheet.status)}</p>
      </div>
      <div class="col-md-6">
        <p><strong>Created:</strong> ${new Date(currentPullSheet.created_at).toLocaleString()}</p>
        ${currentPullSheet.pulled_date ? `<p><strong>Pulled:</strong> ${new Date(currentPullSheet.pulled_date).toLocaleString()}</p>` : ''}
        ${currentPullSheet.pulled_by ? `<p><strong>Pulled By:</strong> ${currentPullSheet.pulled_by}</p>` : ''}
      </div>
    </div>
    
    ${isDraft ? `
      <div class="alert alert-info">
        <i class="ti ti-info-circle icon"></i>
        This pull sheet is in draft mode. Add items using barcode scanner or search below.
      </div>
      <div class="mb-3">
        <div class="row">
          <div class="col-12">
            <label class="form-label">Scan Barcode or Search for Items</label>
            <div class="input-group input-group-lg">
              <span class="input-group-text">
                <i class="ti ti-barcode icon"></i>
              </span>
              <input type="text" class="form-control" id="quickAddItemInput" placeholder="Scan barcode or type to search..." autofocus>
              <button class="btn btn-primary" id="quickAddSearchBtn">
                <i class="ti ti-search icon"></i> Search
              </button>
            </div>
          </div>
        </div>
        <div id="quickAddResults" class="mt-2"></div>
      </div>
    ` : ''}
    
    ${isFinalized ? `
      <div class="alert alert-success">
        <i class="ti ti-check icon"></i>
        This pull sheet has been finalized. Equipment has been checked out.
      </div>
    ` : ''}
    
    <div class="card">
      <div class="table-responsive">
        <table class="table table-sm">
          <thead>
            <tr>
              <th>Item</th>
              <th>Barcode</th>
              <th>Location</th>
              <th>Requested</th>
              <th>Available</th>
              ${isDraft ? '<th>Actions</th>' : ''}
            </tr>
          </thead>
          <tbody>
            ${items.length === 0 ? `
              <tr>
                <td colspan="${isDraft ? '6' : '5'}" class="text-center text-muted">
                  No items added yet. Use the search above to add items.
                </td>
              </tr>
            ` : items.map(item => {
              const availabilityClass = item.quantity_available >= item.quantity_requested ? 'text-success' : 'text-danger';
              return `
              <tr>
                <td><strong>${item.name}</strong>${item.description ? `<br><small class="text-muted">${item.description}</small>` : ''}</td>
                <td><span class="text-mono">${item.barcode || '-'}</span></td>
                <td>${item.location || '-'}</td>
                <td class="text-center">${item.quantity_requested}</td>
                <td class="text-center ${availabilityClass}"><strong>${item.quantity_available || 0}</strong></td>
                ${isDraft ? `
                  <td>
                    <button class="btn btn-sm btn-ghost-danger" onclick="removeItemFromPullSheet(${item.item_id})">
                      <i class="ti ti-trash icon"></i>
                    </button>
                  </td>
                ` : ''}
              </tr>
            `;
            }).join('')}
          </tbody>
        </table>
      </div>
    </div>
  `;
  
  document.getElementById('pullSheetDetailContent').innerHTML = content;
  
  // Setup quick add functionality if in draft mode
  if (isDraft) {
    setupQuickAddListeners();
  }
  
  // Update actions
  let actions = '';
  if (isDraft && items.length > 0) {
    actions = `
      <button class="btn btn-success" onclick="finalizePullSheet()">
        <i class="ti ti-check icon"></i> Finalize & Generate PDF
      </button>
    `;
  } else if (isFinalized) {
    actions = `
      <button class="btn btn-info" onclick="openPickMode(${currentPullSheet.id})">
        <i class="ti ti-scan icon"></i> Start Pick Mode
      </button>
      <button class="btn btn-primary" onclick="startReturn(${currentPullSheet.id})">
        <i class="ti ti-arrow-back icon"></i> Start Return
      </button>
      <button class="btn btn-secondary" onclick="generatePullSheetPDF(${currentPullSheet.id})">
        <i class="ti ti-file-download icon"></i> Download PDF
      </button>
    `;
  }
  
  document.getElementById('pullSheetActions').innerHTML = actions;
}

// Open add items modal
async function openAddItemsModal() {
  const modal = new bootstrap.Modal(document.getElementById('addItemsModal'));
  document.getElementById('itemSearchInput').value = '';
  document.getElementById('searchResults').innerHTML = '';
  modal.show();
  
  // Focus on search input
  document.getElementById('itemSearchInput').focus();
  
  // Setup search
  const searchBtn = document.getElementById('searchItemBtn');
  const searchInput = document.getElementById('itemSearchInput');
  
  searchBtn.onclick = searchItems;
  searchInput.onkeypress = (e) => {
    if (e.key === 'Enter') {
      searchItems();
    }
  };
}

// Search items
async function searchItems() {
  const query = document.getElementById('itemSearchInput').value.trim();
  
  if (!query) {
    document.getElementById('searchResults').innerHTML = '';
    return;
  }
  
  try {
    // Check if it's a barcode
    const item = await window.api.inventory.getByBarcode(query);
    
    if (item) {
      // Add directly
      await addItemToPullSheet(item.id, 1);
      document.getElementById('itemSearchInput').value = '';
      return;
    }
    
    // Search by name
    const items = await window.api.inventory.search(query);
    
    if (items.length === 0) {
      document.getElementById('searchResults').innerHTML = `
        <div class="alert alert-warning">No items found</div>
      `;
      return;
    }
    
    // Show results
    const html = `
      <div class="list-group">
        ${items.map(item => `
          <a href="#" class="list-group-item list-group-item-action" onclick="event.preventDefault(); addItemToPullSheet(${item.id}, 1)">
            <div class="d-flex w-100 justify-content-between">
              <h5 class="mb-1">${item.name}</h5>
              <span class="badge bg-${item.quantity_available > 0 ? 'success' : 'danger'}">${item.quantity_available} available</span>
            </div>
            <p class="mb-1"><small class="text-muted">${item.barcode || 'No barcode'} | ${item.category || 'Uncategorized'}</small></p>
          </a>
        `).join('')}
      </div>
    `;
    
    document.getElementById('searchResults').innerHTML = html;
  } catch (error) {
    alert('Error searching items: ' + error.message);
  }
}

// Add item to pull sheet
async function addItemToPullSheet(itemId, quantity) {
  if (!currentPullSheet) return;
  
  try {
    // Check if item already exists
    const exists = currentPullSheet.items?.find(i => i.item_id === itemId);
    if (exists) {
      // Update quantity
      await window.api.pullsheets.updateItem(currentPullSheet.id, itemId, exists.quantity_requested + quantity);
    } else {
      // Add new
      await window.api.pullsheets.addItem(currentPullSheet.id, itemId, quantity);
    }
    
    // Refresh detail
    currentPullSheet = await window.api.pullsheets.getById(currentPullSheet.id);
    
    // Check if we're in full-screen mode or regular modal
    if (document.getElementById('pullSheetFullScreenModal')?.classList.contains('show')) {
      renderFullScreenPullSheet();
    } else {
      renderPullSheetDetail();
      
      // Clear search (for old modal)
      const searchInput = document.getElementById('itemSearchInput');
      const searchResults = document.getElementById('searchResults');
      if (searchInput) searchInput.value = '';
      if (searchResults) {
        searchResults.innerHTML = '<div class="alert alert-success">Item added!</div>';
        setTimeout(() => {
          searchResults.innerHTML = '';
        }, 2000);
      }
    }
  } catch (error) {
    alert('Error adding item: ' + error.message);
  }
}

// Remove item from pull sheet
async function removeItemFromPullSheet(itemId) {
  if (!currentPullSheet) return;
  
  const confirmed = await window.api.dialog.showMessage({
    type: 'question',
    title: 'Remove Item',
    message: 'Remove this item from the pull sheet?',
    buttons: ['Cancel', 'Remove'],
    defaultId: 1
  });
  
  if (confirmed.response === 1) {
    try {
      await window.api.pullsheets.removeItem(currentPullSheet.id, itemId);
      currentPullSheet = await window.api.pullsheets.getById(currentPullSheet.id);
      
      // Check if we're in full-screen mode or regular modal
      if (document.getElementById('pullSheetFullScreenModal')?.classList.contains('show')) {
        renderFullScreenPullSheet();
      } else {
        renderPullSheetDetail();
      }
    } catch (error) {
      alert('Error removing item: ' + error.message);
    }
  }
}

// Finalize pull sheet
async function finalizePullSheet() {
  if (!currentPullSheet) return;
  
  // Use the pulled_by field that was set during creation, or prompt if not set
  let pulledBy = currentPullSheet.pulled_by;
  
  if (!pulledBy) {
    // If no name was provided during creation, ask for it now
    const modal = document.createElement('div');
    modal.innerHTML = `
      <div class="modal modal-blur fade show" style="display: block;" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Finalize Pull Sheet</h5>
            </div>
            <div class="modal-body">
              <label class="form-label required">Your Name</label>
              <input type="text" class="form-control" id="pulledByInput" placeholder="Enter your name" autofocus>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn" onclick="this.closest('.modal').remove()">Cancel</button>
              <button type="button" class="btn btn-primary" id="confirmFinalizeBtn">Continue</button>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-backdrop fade show"></div>
    `;
    document.body.appendChild(modal);
    
    // Focus the input
    setTimeout(() => document.getElementById('pulledByInput').focus(), 100);
    
    // Handle Enter key
    document.getElementById('pulledByInput').addEventListener('keypress', (e) => {
      if (e.key === 'Enter') {
        document.getElementById('confirmFinalizeBtn').click();
      }
    });
    
    // Handle confirm - make it return a promise
    await new Promise((resolve) => {
      document.getElementById('confirmFinalizeBtn').addEventListener('click', () => {
        const value = document.getElementById('pulledByInput').value.trim();
        if (!value) {
          alert('Please enter your name');
          return;
        }
        pulledBy = value;
        modal.remove();
        resolve();
      });
    });
  }
  
  // Confirm finalization
  const confirmed = await window.api.dialog.showMessage({
    type: 'question',
    title: 'Finalize Pull Sheet',
    message: 'Finalize this pull sheet? This will check out all items and update inventory availability.',
    buttons: ['Cancel', 'Finalize'],
    defaultId: 1
  });
  
  if (confirmed.response === 1) {
    try {
      await window.api.pullsheets.finalize(currentPullSheet.id, pulledBy);
      
      alert('Pull sheet finalized! Equipment has been checked out.');
      
      // Close modal and refresh
      bootstrap.Modal.getInstance(document.getElementById('pullSheetDetailModal')).hide();
      await refreshPullSheets();
    } catch (error) {
      alert('Error finalizing pull sheet: ' + error.message);
    }
  }
}

// Start return process
async function startReturn(pullSheetId) {
  // Navigate to returns page and start process
  window.navigation.loadPage('returns');
  
  // Wait a bit for page to load, then trigger return
  setTimeout(() => {
    if (window.startReturnProcess) {
      window.startReturnProcess(pullSheetId);
    }
  }, 500);
}

// Create change order
async function createChangeOrder(pullSheetId) {
  alert('Change order functionality will open a modal to add/remove items from active pull sheet');
  // This would open a change order modal
}

// Generate pull sheet PDF
async function generatePullSheetPDF(pullSheetId) {
  try {
    const result = await window.api.pdf.generatePullSheet(pullSheetId);
    if (result.success) {
      alert(`Pull sheet PDF generated: ${result.filePath}`);
    } else {
      alert('Failed to generate PDF: ' + result.error);
    }
  } catch (error) {
    alert('Error generating PDF: ' + error.message);
  }
}

// Delete pull sheet
async function deletePullSheet(pullSheetId) {
  const confirmed = await window.api.dialog.showMessage({
    type: 'warning',
    title: 'Delete Pull Sheet',
    message: 'Delete this draft pull sheet? This cannot be undone.',
    buttons: ['Cancel', 'Delete'],
    defaultId: 0
  });
  
  if (confirmed.response === 1) {
    try {
      await window.api.pullsheets.delete(pullSheetId);
      await refreshPullSheets();
    } catch (error) {
      alert('Error deleting pull sheet: ' + error.message);
    }
  }
}

// Refresh pull sheets list
async function refreshPullSheets() {
  currentPullSheets = await window.api.pullsheets.getAll();
  document.getElementById('pullSheetsTableBody').innerHTML = renderPullSheetRows(currentPullSheets);
}

// Setup quick add listeners for inline item addition
function setupQuickAddListeners() {
  const input = document.getElementById('quickAddItemInput');
  const searchBtn = document.getElementById('quickAddSearchBtn');
  
  if (!input || !searchBtn) return;
  
  // Auto-focus and select all text on focus
  setTimeout(() => {
    input.focus();
    input.select();
  }, 100);
  
  // Select all on focus
  input.addEventListener('focus', () => {
    input.select();
  });
  
  // Handle barcode scan (Enter key)
  input.addEventListener('keypress', async (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      await quickAddItem();
    }
  });
  
  // Handle search button click
  searchBtn.addEventListener('click', async () => {
    await quickAddItem();
  });
}

// Quick add item from inline search
async function quickAddItem() {
  const query = document.getElementById('quickAddItemInput').value.trim();
  const resultsDiv = document.getElementById('quickAddResults');
  
  if (!query) {
    resultsDiv.innerHTML = '';
    return;
  }
  
  try {
    // Try barcode lookup first
    const item = await window.api.inventory.getByBarcode(query);
    
    if (item) {
      // Show quantity selector for this item
      resultsDiv.innerHTML = `
        <div class="card">
          <div class="card-body">
            <h4>${item.name}</h4>
            <p class="text-muted">${item.description || ''}</p>
            <p><strong>Available:</strong> <span class="badge bg-${item.quantity_available > 0 ? 'success' : 'danger'}">${item.quantity_available}</span></p>
            <div class="input-group">
              <span class="input-group-text">Quantity:</span>
              <input type="number" class="form-control" id="quickAddQuantity" value="1" min="1" max="${item.quantity_available}">
              <button class="btn btn-success" onclick="confirmQuickAdd(${item.id})">
                <i class="ti ti-plus icon"></i> Add to Pull Sheet
              </button>
            </div>
          </div>
        </div>
      `;
      document.getElementById('quickAddQuantity').focus();
      return;
    }
    
    // Search by name if not found by barcode
    const items = await window.api.inventory.search(query);
    
    if (items.length === 0) {
      resultsDiv.innerHTML = `<div class="alert alert-warning">No items found for "${query}"</div>`;
      return;
    }
    
    // Show search results
    resultsDiv.innerHTML = `
      <div class="list-group">
        ${items.map(item => `
          <div class="list-group-item">
            <div class="row align-items-center">
              <div class="col">
                <h5 class="mb-1">${item.name}</h5>
                <p class="mb-0"><small class="text-muted">${item.barcode || 'No barcode'} | ${item.category || 'Uncategorized'}</small></p>
              </div>
              <div class="col-auto">
                <span class="badge bg-${item.quantity_available > 0 ? 'success' : 'danger'} me-2">${item.quantity_available} available</span>
                <button class="btn btn-sm btn-primary" onclick="selectQuickAddItem(${item.id}, '${item.name.replace(/'/g, "\\'")}', ${item.quantity_available})">
                  <i class="ti ti-plus icon"></i> Add
                </button>
              </div>
            </div>
          </div>
        `).join('')}
      </div>
    `;
  } catch (error) {
    resultsDiv.innerHTML = `<div class="alert alert-danger">Error: ${error.message}</div>`;
  }
}

// Select item from search results
window.selectQuickAddItem = function(itemId, itemName, available) {
  const resultsDiv = document.getElementById('quickAddResults');
  resultsDiv.innerHTML = `
    <div class="card">
      <div class="card-body">
        <h4>${itemName}</h4>
        <p><strong>Available:</strong> <span class="badge bg-${available > 0 ? 'success' : 'danger'}">${available}</span></p>
        <div class="input-group">
          <span class="input-group-text">Quantity:</span>
          <input type="number" class="form-control" id="quickAddQuantity" value="1" min="1" max="${available}">
          <button class="btn btn-success" onclick="confirmQuickAdd(${itemId})">
            <i class="ti ti-plus icon"></i> Add to Pull Sheet
          </button>
        </div>
      </div>
    </div>
  `;
  document.getElementById('quickAddQuantity').focus();
};

// Confirm and add item
window.confirmQuickAdd = async function(itemId) {
  const quantity = parseInt(document.getElementById('quickAddQuantity').value) || 1;
  
  await addItemToPullSheet(itemId, quantity);
  
  // Clear input and results
  document.getElementById('quickAddItemInput').value = '';
  document.getElementById('quickAddResults').innerHTML = `
    <div class="alert alert-success alert-dismissible fade show">
      Item added successfully!
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  `;
  
  setTimeout(() => {
    document.getElementById('quickAddResults').innerHTML = '';
    document.getElementById('quickAddItemInput').focus();
  }, 2000);
};

// Open pick mode for a finalized pull sheet
window.openPickMode = async function(pullSheetId) {
  const pullSheet = await window.api.pullsheets.getById(pullSheetId);
  
  if (!pullSheet || pullSheet.status !== 'finalized') {
    alert('Pull sheet must be finalized to start pick mode');
    return;
  }
  
  // Close detail modal
  const detailModal = bootstrap.Modal.getInstance(document.getElementById('pullSheetDetailModal'));
  if (detailModal) {
    detailModal.hide();
  }
  
  // Open pick mode in a new full-screen modal
  currentPullSheet = pullSheet;
  renderPickMode();
};

// Render pick mode interface
function renderPickMode() {
  // Create a full-screen pick mode modal if it doesn't exist
  let pickModal = document.getElementById('pickModeModal');
  
  if (!pickModal) {
    pickModal = document.createElement('div');
    pickModal.id = 'pickModeModal';
    pickModal.className = 'modal modal-blur fade';
    pickModal.setAttribute('data-bs-backdrop', 'static');
    pickModal.setAttribute('data-bs-keyboard', 'false');
    pickModal.innerHTML = `
      <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
          <div class="modal-header bg-primary text-white">
            <h3 class="modal-title">Pick Mode - ${currentPullSheet.name || `Pull Sheet #${currentPullSheet.id}`}</h3>
            <button type="button" class="btn-close btn-close-white" onclick="closePickMode()"></button>
          </div>
          <div class="modal-body" id="pickModeBody">
          </div>
          <div class="modal-footer">
            <div id="pickModeActions"></div>
          </div>
        </div>
      </div>
    `;
    document.body.appendChild(pickModal);
  }
  
  // Initialize pick state
  if (!window.pickState) {
    window.pickState = {
      items: (currentPullSheet.items || []).map(item => ({
        ...item,
        scanned: 0,
        status: 'pending'
      }))
    };
  }
  
  updatePickModeDisplay();
  
  const modal = new bootstrap.Modal(pickModal);
  modal.show();
}

// Update pick mode display
function updatePickModeDisplay() {
  const body = document.getElementById('pickModeBody');
  const actions = document.getElementById('pickModeActions');
  
  if (!window.pickState) return;
  
  const allComplete = window.pickState.items.every(item => item.scanned >= item.quantity_requested);
  
  body.innerHTML = `
    <div class="container-fluid">
      <div class="row mb-3">
        <div class="col-12">
          <div class="input-group input-group-lg">
            <span class="input-group-text bg-primary text-white">
              <i class="ti ti-scan icon"></i>
            </span>
            <input type="text" class="form-control form-control-lg" id="pickScanInput" placeholder="Scan item barcode..." autofocus>
          </div>
        </div>
      </div>
      <div class="row g-3">
        ${window.pickState.items.map((item, index) => {
          const isComplete = item.scanned >= item.quantity_requested;
          const isOver = item.scanned > item.quantity_requested;
          const cardClass = isOver ? 'border-danger bg-danger-lt' : isComplete ? 'border-success bg-success-lt' : 'border-secondary';
          
          if (isComplete && !isOver) return ''; // Hide completed items
          
          return `
            <div class="col-md-6 col-lg-4">
              <div class="card ${cardClass} h-100">
                <div class="card-body">
                  <h3 class="card-title">${item.name}</h3>
                  <p class="text-muted">${item.barcode || 'No barcode'}</p>
                  <div class="row text-center mt-3">
                    <div class="col-6">
                      <div class="text-muted small">Scanned</div>
                      <div class="display-6 ${isOver ? 'text-danger' : isComplete ? 'text-success' : 'text-primary'}">${item.scanned}</div>
                    </div>
                    <div class="col-6">
                      <div class="text-muted small">Needed</div>
                      <div class="display-6">${item.quantity_requested}</div>
                    </div>
                  </div>
                  ${isOver ? `
                    <div class="alert alert-danger mt-3">
                      <strong>Over-scanned by ${item.scanned - item.quantity_requested}</strong>
                      <div class="mt-2">
                        <button class="btn btn-sm btn-danger" onclick="correctOverScan(${index})">
                          Remove Excess
                        </button>
                      </div>
                    </div>
                  ` : ''}
                </div>
              </div>
            </div>
          `;
        }).join('')}
      </div>
    </div>
  `;
  
  actions.innerHTML = allComplete ? `
    <button class="btn btn-lg btn-success" onclick="completePickMode()">
      <i class="ti ti-check icon"></i> Complete Pick & Checkout
    </button>
  ` : `
    <span class="text-muted">Scan all items to complete the pick</span>
    <button class="btn btn-outline-secondary ms-2" onclick="closePickMode()">Cancel</button>
  `;
  
  // Setup scan listener
  const scanInput = document.getElementById('pickScanInput');
  if (scanInput) {
    scanInput.focus();
    scanInput.select();
    
    // Select all on focus
    scanInput.addEventListener('focus', () => {
      scanInput.select();
    });
    
    scanInput.addEventListener('keypress', handlePickScan);
  }
}

// Handle barcode scan in pick mode
function handlePickScan(e) {
  if (e.key === 'Enter') {
    e.preventDefault();
    const barcode = e.target.value.trim();
    
    if (!barcode) return;
    
    // Find item with this barcode
    const itemIndex = window.pickState.items.findIndex(item => item.barcode === barcode);
    
    if (itemIndex >= 0) {
      window.pickState.items[itemIndex].scanned++;
      e.target.value = '';
      updatePickModeDisplay();
    } else {
      alert(`Item with barcode "${barcode}" not found in this pull sheet`);
      e.target.value = '';
    }
  }
}

// Correct over-scan
window.correctOverScan = function(itemIndex) {
  const item = window.pickState.items[itemIndex];
  const excess = item.scanned - item.quantity_requested;
  
  // Show modal to get correction amount
  const modal = document.createElement('div');
  modal.innerHTML = `
    <div class="modal modal-blur fade show" style="display: block;" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-danger text-white">
            <h5 class="modal-title">Correct Over-Scan</h5>
          </div>
          <div class="modal-body">
            <p><strong>${item.name}</strong></p>
            <p class="text-danger">Over-scanned by ${excess} items</p>
            <label class="form-label required">Remove how many items?</label>
            <input type="number" class="form-control form-control-lg" id="removeCountInput" value="${excess}" min="0" max="${excess}" autofocus>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn" onclick="this.closest('.modal').remove()">Cancel</button>
            <button type="button" class="btn btn-danger" id="confirmRemoveBtn">Remove</button>
          </div>
        </div>
      </div>
    </div>
    <div class="modal-backdrop fade show"></div>
  `;
  document.body.appendChild(modal);
  
  // Focus and select the input
  setTimeout(() => {
    const input = document.getElementById('removeCountInput');
    input.focus();
    input.select();
  }, 100);
  
  // Handle Enter key
  document.getElementById('removeCountInput').addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
      document.getElementById('confirmRemoveBtn').click();
    }
  });
  
  // Handle confirm
  document.getElementById('confirmRemoveBtn').addEventListener('click', () => {
    const removeCount = parseInt(document.getElementById('removeCountInput').value) || 0;
    window.pickState.items[itemIndex].scanned -= removeCount;
    modal.remove();
    updatePickModeDisplay();
  });
};

// Complete pick mode
window.completePickMode = async function() {
  if (!window.pickState) return;
  
  try {
    // Update pull sheet status and generate PDF
    await window.api.pullsheets.finalize(currentPullSheet.id, 'Picker');
    
    // Generate PDF
    const result = await window.api.pdf.generatePullSheet(currentPullSheet.id);
    
    if (result.success) {
      alert(`Pick completed! PDF generated: ${result.filePath}`);
    }
    
    closePickMode();
    await refreshPullSheets();
  } catch (error) {
    alert('Error completing pick: ' + error.message);
  }
};

// Close pick mode
window.closePickMode = function() {
  const pickModal = document.getElementById('pickModeModal');
  if (pickModal) {
    const modal = bootstrap.Modal.getInstance(pickModal);
    if (modal) {
      modal.hide();
    }
  }
  window.pickState = null;
};

// Make functions globally available
window.viewPullSheet = viewPullSheet;
window.generatePullSheetPDF = generatePullSheetPDF;
window.deletePullSheet = deletePullSheet;
window.openAddItemsModal = openAddItemsModal;
window.addItemToPullSheet = addItemToPullSheet;
window.removeItemFromPullSheet = removeItemFromPullSheet;
window.finalizePullSheet = finalizePullSheet;
window.startReturn = startReturn;
window.createChangeOrder = createChangeOrder;

// Register page with navigation
window.navigation.registerPage('pullsheets', {
  render: renderPullSheetsPage,
  init: initPullSheetsPage
});

})();
