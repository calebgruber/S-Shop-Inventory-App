/**
 * Complete Reports Module
 * Comprehensive reporting system with all report types, filters, and PDF export
 */

(function() {
'use strict';

let currentTheatres = [];
let currentCategories = [];
let currentReport = null;
let currentReportType = null;
let activityLogPage = 1;
const activityLogPerPage = 50;

// ===== MAIN PAGE RENDER =====

async function renderReportsPage() {
  const shortages = await window.api.reports.getShortages();
  const activityLog = await window.api.reports.getActivityLog({ limit: 10 });
  const stats = await window.api.reports.getDashboardStats();
  currentTheatres = await window.api.theatres.getAll();
  
  // Get unique categories from inventory
  const allItems = await window.api.inventory.getAll();
  const categorySet = new Set();
  allItems.forEach(item => {
    if (item.category) categorySet.add(item.category);
  });
  currentCategories = Array.from(categorySet).sort();
  
  return `
    <div class="row mb-3">
      <div class="col-12">
        <p class="text-muted">Analytics, reports, and activity tracking for inventory management</p>
      </div>
    </div>
    
    <!-- Quick Stats Cards -->
    <div class="row row-deck row-cards mb-3">
      <div class="col-sm-6 col-lg-3">
        <div class="card">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="subheader">Total Items</div>
              <div class="ms-auto lh-1">
                <i class="ti ti-box icon text-muted"></i>
              </div>
            </div>
            <div class="h1 mb-0">${stats?.totalItems || 0}</div>
            <div class="text-muted">${stats?.uniqueItems || 0} unique types</div>
          </div>
        </div>
      </div>
      
      <div class="col-sm-6 col-lg-3">
        <div class="card">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="subheader">Available</div>
              <div class="ms-auto lh-1">
                <i class="ti ti-check icon text-success"></i>
              </div>
            </div>
            <div class="h1 mb-0 text-success">${stats?.availableItems || 0}</div>
            <div class="text-muted">${stats?.availablePercent || 0}% availability</div>
          </div>
        </div>
      </div>
      
      <div class="col-sm-6 col-lg-3">
        <div class="card">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="subheader">Checked Out</div>
              <div class="ms-auto lh-1">
                <i class="ti ti-arrow-right icon text-info"></i>
              </div>
            </div>
            <div class="h1 mb-0 text-info">${stats?.itemsOut || 0}</div>
            <div class="text-muted">at ${stats?.activeLocations || 0} locations</div>
          </div>
        </div>
      </div>
      
      <div class="col-sm-6 col-lg-3">
        <div class="card">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="subheader">Shortages</div>
              <div class="ms-auto lh-1">
                <i class="ti ti-alert-triangle icon text-warning"></i>
              </div>
            </div>
            <div class="h1 mb-0 ${shortages.length > 0 ? 'text-warning' : 'text-success'}">${shortages.length}</div>
            <div class="text-muted">${shortages.length > 0 ? 'Needs attention' : 'All good'}</div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Shortages & Alerts -->
    ${shortages.length > 0 ? `
      <div class="row mb-3">
        <div class="col-12">
          <div class="card border-warning">
            <div class="card-header">
              <h3 class="card-title">
                <i class="ti ti-alert-triangle icon text-warning me-2"></i>
                Shortages & Alerts
              </h3>
            </div>
            <div class="card-body">
              ${renderShortages(shortages)}
            </div>
          </div>
        </div>
      </div>
    ` : ''}
    
    <!-- Report Selection Cards -->
    <div class="row row-deck row-cards mb-3">
      <div class="col-md-6 col-lg-4">
        <div class="card card-link" onclick="openInventoryReport()">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="me-3">
                <i class="ti ti-list-details icon icon-lg text-primary"></i>
              </div>
              <div>
                <div class="h3 mb-0">Inventory Status</div>
                <div class="text-muted">Complete inventory listing with filters</div>
              </div>
            </div>
          </div>
          <div class="card-footer">
            <div class="row align-items-center">
              <div class="col">
                <small class="text-muted">View all items, filter by status/category</small>
              </div>
              <div class="col-auto">
                <i class="ti ti-chevron-right icon"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-md-6 col-lg-4">
        <div class="card card-link" onclick="openLocationReport()">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="me-3">
                <i class="ti ti-map-pin icon icon-lg text-info"></i>
              </div>
              <div>
                <div class="h3 mb-0">Items by Location</div>
                <div class="text-muted">Equipment at each theatre</div>
              </div>
            </div>
          </div>
          <div class="card-footer">
            <div class="row align-items-center">
              <div class="col">
                <small class="text-muted">View items currently in use by location</small>
              </div>
              <div class="col-auto">
                <i class="ti ti-chevron-right icon"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-md-6 col-lg-4">
        <div class="card card-link" onclick="openLowStockReport()">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="me-3">
                <i class="ti ti-alert-circle icon icon-lg text-warning"></i>
              </div>
              <div>
                <div class="h3 mb-0">Low Stock</div>
                <div class="text-muted">Items below threshold</div>
              </div>
            </div>
          </div>
          <div class="card-footer">
            <div class="row align-items-center">
              <div class="col">
                <small class="text-muted">Monitor inventory levels</small>
              </div>
              <div class="col-auto">
                <i class="ti ti-chevron-right icon"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-md-6 col-lg-4">
        <div class="card card-link" onclick="openItemsOutReport()">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="me-3">
                <i class="ti ti-arrow-right-circle icon icon-lg text-cyan"></i>
              </div>
              <div>
                <div class="h3 mb-0">Items Checked Out</div>
                <div class="text-muted">Currently in use</div>
              </div>
            </div>
          </div>
          <div class="card-footer">
            <div class="row align-items-center">
              <div class="col">
                <small class="text-muted">View all checked out equipment</small>
              </div>
              <div class="col-auto">
                <i class="ti ti-chevron-right icon"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-md-6 col-lg-4">
        <div class="card card-link" onclick="openShowEquipmentReport()">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="me-3">
                <i class="ti ti-theater icon icon-lg text-purple"></i>
              </div>
              <div>
                <div class="h3 mb-0">Show Equipment</div>
                <div class="text-muted">Equipment by show</div>
              </div>
            </div>
          </div>
          <div class="card-footer">
            <div class="row align-items-center">
              <div class="col">
                <small class="text-muted">View equipment for specific shows</small>
              </div>
              <div class="col-auto">
                <i class="ti ti-chevron-right icon"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-md-6 col-lg-4">
        <div class="card card-link" onclick="openActivityLogReport()">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="me-3">
                <i class="ti ti-timeline icon icon-lg text-teal"></i>
              </div>
              <div>
                <div class="h3 mb-0">Activity Log</div>
                <div class="text-muted">Recent actions & changes</div>
              </div>
            </div>
          </div>
          <div class="card-footer">
            <div class="row align-items-center">
              <div class="col">
                <small class="text-muted">Complete audit trail of actions</small>
              </div>
              <div class="col-auto">
                <i class="ti ti-chevron-right icon"></i>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Recent Activity Preview -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">Recent Activity</h3>
        <div class="card-actions">
          <button class="btn btn-sm btn-primary" onclick="openActivityLogReport()">
            <i class="ti ti-list icon"></i> View Full Log
          </button>
        </div>
      </div>
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
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
    
    <!-- Report Modals -->
    ${renderReportModals()}
  `;
}

// ===== RENDER HELPERS =====

function renderShortages(shortages) {
  if (shortages.length === 0) {
    return `
      <div class="text-center text-success py-3">
        <i class="ti ti-check icon icon-lg"></i>
        <p class="mb-0">No shortages detected</p>
      </div>
    `;
  }
  
  return `
    <div class="row">
      ${shortages.map(item => `
        <div class="col-md-6 mb-2">
          <div class="alert alert-warning mb-0">
            <div class="d-flex">
              <div>
                <i class="ti ti-alert-triangle icon alert-icon"></i>
              </div>
              <div>
                <h4 class="alert-title">${item.name}</h4>
                <div class="text-muted">
                  Available: ${item.quantity_in_stock || 0} | 
                  Out: ${item.quantity_out || 0} | 
                  ${item.category ? `Category: ${item.category}` : ''}
                </div>
              </div>
            </div>
          </div>
        </div>
      `).join('')}
    </div>
  `;
}

function renderActivityLog(logs) {
  if (!logs || logs.length === 0) {
    return `
      <tr>
        <td colspan="3" class="text-center text-muted">No recent activity</td>
      </tr>
    `;
  }
  
  return logs.map(log => {
    const time = new Date(log.created_at).toLocaleString();
    const actionBadgeClass = getActionBadgeClass(log.action_type);
    return `
      <tr>
        <td class="text-muted" style="white-space: nowrap;">
          <small>${time}</small>
        </td>
        <td>
          <span class="badge ${actionBadgeClass}">${log.action_type}</span>
        </td>
        <td>${escapeHtml(log.description || 'N/A')}</td>
      </tr>
    `;
  }).join('');
}

function getActionBadgeClass(actionType) {
  const type = actionType.toUpperCase();
  if (type.includes('CREATE') || type.includes('ADD')) return 'bg-success';
  if (type.includes('DELETE') || type.includes('REMOVE')) return 'bg-danger';
  if (type.includes('UPDATE') || type.includes('EDIT')) return 'bg-info';
  if (type.includes('PULL') || type.includes('CHECKOUT')) return 'bg-warning';
  if (type.includes('RETURN')) return 'bg-teal';
  return 'bg-secondary';
}

function renderReportModals() {
  return `
    <!-- Inventory Status Report Modal -->
    <div class="modal modal-blur fade" id="inventoryReportModal" tabindex="-1">
      <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="ti ti-list-details icon me-2"></i>
              Inventory Status Report
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="row mb-3">
              <div class="col-md-4">
                <label class="form-label">Filter by Status</label>
                <select class="form-select" id="inventoryStatusFilter">
                  <option value="">All Statuses</option>
                  <option value="available">Available</option>
                  <option value="checked_out">Checked Out</option>
                  <option value="maintenance">Maintenance</option>
                  <option value="damaged">Damaged</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Filter by Category</label>
                <select class="form-select" id="inventoryCategoryFilter">
                  <option value="">All Categories</option>
                  ${currentCategories.map(cat => `<option value="${cat}">${cat}</option>`).join('')}
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Search</label>
                <input type="text" class="form-select" id="inventorySearchFilter" placeholder="Search by name...">
              </div>
            </div>
            <div class="mb-3">
              <button class="btn btn-primary btn-sm" onclick="loadInventoryReport()">
                <i class="ti ti-filter icon"></i> Apply Filters
              </button>
              <button class="btn btn-secondary btn-sm ms-2" onclick="clearInventoryFilters()">
                <i class="ti ti-x icon"></i> Clear
              </button>
            </div>
            <div id="inventoryReportContent">
              <div class="text-center py-5">
                <div class="spinner-border" role="status"></div>
                <p class="text-muted mt-2">Loading report...</p>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn me-auto" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary" onclick="exportInventoryPDF()">
              <i class="ti ti-download icon"></i> Export to PDF
            </button>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Location Report Modal -->
    <div class="modal modal-blur fade" id="locationReportModal" tabindex="-1">
      <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="ti ti-map-pin icon me-2"></i>
              Items by Location Report
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Filter by Theatre</label>
              <select class="form-select" id="locationTheatreFilter" onchange="loadLocationReport()">
                <option value="">All Theatres</option>
                ${currentTheatres.map(t => `<option value="${t.id}">${t.name}</option>`).join('')}
              </select>
            </div>
            <div id="locationReportContent">
              <div class="text-center py-5">
                <div class="spinner-border" role="status"></div>
                <p class="text-muted mt-2">Loading report...</p>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn me-auto" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary" onclick="exportLocationPDF()">
              <i class="ti ti-download icon"></i> Export to PDF
            </button>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Low Stock Report Modal -->
    <div class="modal modal-blur fade" id="lowStockReportModal" tabindex="-1">
      <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="ti ti-alert-circle icon me-2"></i>
              Low Stock Report
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Threshold (show items with quantity below)</label>
              <input type="number" class="form-control" id="lowStockThreshold" value="5" min="1" max="100">
              <small class="form-hint">Items with available quantity below this number will be shown</small>
            </div>
            <div class="mb-3">
              <button class="btn btn-primary btn-sm" onclick="loadLowStockReport()">
                <i class="ti ti-refresh icon"></i> Refresh
              </button>
            </div>
            <div id="lowStockReportContent">
              <div class="text-center py-5">
                <div class="spinner-border" role="status"></div>
                <p class="text-muted mt-2">Loading report...</p>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn me-auto" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary" onclick="exportLowStockPDF()">
              <i class="ti ti-download icon"></i> Export to PDF
            </button>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Items Out Report Modal -->
    <div class="modal modal-blur fade" id="itemsOutReportModal" tabindex="-1">
      <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="ti ti-arrow-right-circle icon me-2"></i>
              Items Checked Out Report
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div id="itemsOutReportContent">
              <div class="text-center py-5">
                <div class="spinner-border" role="status"></div>
                <p class="text-muted mt-2">Loading report...</p>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn me-auto" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary" onclick="exportItemsOutPDF()">
              <i class="ti ti-download icon"></i> Export to PDF
            </button>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Show Equipment Report Modal -->
    <div class="modal modal-blur fade" id="showEquipmentReportModal" tabindex="-1">
      <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="ti ti-theater icon me-2"></i>
              Show Equipment Report
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Select Show</label>
              <select class="form-select" id="showEquipmentFilter" onchange="loadShowEquipmentReport()">
                <option value="">Select a show...</option>
              </select>
            </div>
            <div id="showEquipmentReportContent">
              <div class="empty">
                <div class="empty-icon">
                  <i class="ti ti-theater icon"></i>
                </div>
                <p class="empty-title">Select a show</p>
                <p class="empty-subtitle text-muted">Choose a show from the dropdown to view its equipment</p>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn me-auto" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary" onclick="exportShowEquipmentPDF()">
              <i class="ti ti-download icon"></i> Export to PDF
            </button>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Activity Log Report Modal -->
    <div class="modal modal-blur fade" id="activityLogReportModal" tabindex="-1">
      <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="ti ti-timeline icon me-2"></i>
              Activity Log Report
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="row mb-3">
              <div class="col-md-4">
                <label class="form-label">Filter by Action</label>
                <select class="form-select" id="activityActionFilter">
                  <option value="">All Actions</option>
                  <option value="CREATE">Create</option>
                  <option value="UPDATE">Update</option>
                  <option value="DELETE">Delete</option>
                  <option value="PULL">Pull</option>
                  <option value="RETURN">Return</option>
                  <option value="CHECKOUT">Checkout</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Date From</label>
                <input type="date" class="form-control" id="activityDateFrom">
              </div>
              <div class="col-md-4">
                <label class="form-label">Date To</label>
                <input type="date" class="form-control" id="activityDateTo">
              </div>
            </div>
            <div class="mb-3">
              <button class="btn btn-primary btn-sm" onclick="loadActivityLogReport(1)">
                <i class="ti ti-filter icon"></i> Apply Filters
              </button>
              <button class="btn btn-secondary btn-sm ms-2" onclick="clearActivityFilters()">
                <i class="ti ti-x icon"></i> Clear
              </button>
            </div>
            <div id="activityLogReportContent">
              <div class="text-center py-5">
                <div class="spinner-border" role="status"></div>
                <p class="text-muted mt-2">Loading report...</p>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn me-auto" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary" onclick="exportActivityLogPDF()">
              <i class="ti ti-download icon"></i> Export to PDF
            </button>
          </div>
        </div>
      </div>
    </div>
  `;
}

// ===== REPORT LOADERS =====

async function openInventoryReport() {
  currentReportType = 'inventory';
  const modal = new bootstrap.Modal(document.getElementById('inventoryReportModal'));
  modal.show();
  await loadInventoryReport();
}

async function loadInventoryReport() {
  const contentDiv = document.getElementById('inventoryReportContent');
  if (!contentDiv) return;
  
  contentDiv.innerHTML = '<div class="text-center py-5"><div class="spinner-border" role="status"></div></div>';
  
  try {
    let items = await window.api.inventory.getAll();
    
    // Apply filters
    const statusFilter = document.getElementById('inventoryStatusFilter')?.value;
    const categoryFilter = document.getElementById('inventoryCategoryFilter')?.value;
    const searchFilter = document.getElementById('inventorySearchFilter')?.value.toLowerCase();
    
    if (statusFilter) {
      items = items.filter(item => item.status === statusFilter);
    }
    
    if (categoryFilter) {
      items = items.filter(item => item.category === categoryFilter);
    }
    
    if (searchFilter) {
      items = items.filter(item => 
        item.name?.toLowerCase().includes(searchFilter) ||
        item.model?.toLowerCase().includes(searchFilter) ||
        item.barcode?.toLowerCase().includes(searchFilter)
      );
    }
    
    currentReport = items;
    contentDiv.innerHTML = renderInventoryTable(items);
  } catch (error) {
    console.error('Error loading inventory report:', error);
    contentDiv.innerHTML = `<div class="alert alert-danger">Failed to load report: ${error.message}</div>`;
  }
}

function renderInventoryTable(items) {
  if (!items || items.length === 0) {
    return `
      <div class="empty">
        <div class="empty-icon"><i class="ti ti-inbox icon"></i></div>
        <p class="empty-title">No items found</p>
        <p class="empty-subtitle text-muted">Try adjusting your filters</p>
      </div>
    `;
  }
  
  const totalQty = items.reduce((sum, item) => sum + (item.quantity_total || 0), 0);
  const availableQty = items.reduce((sum, item) => sum + (item.quantity_available || 0), 0);
  const outQty = totalQty - availableQty;
  
  return `
    <div class="alert alert-info mb-3">
      <strong>Summary:</strong> ${items.length} item(s) | 
      Total Qty: ${totalQty} | 
      Available: ${availableQty} | 
      Out: ${outQty}
    </div>
    
    <div class="table-responsive">
      <table class="table table-sm table-hover">
        <thead>
          <tr>
            <th>Name</th>
            <th>Category</th>
            <th>Model</th>
            <th>Barcode</th>
            <th>Total</th>
            <th>Available</th>
            <th>Out</th>
            <th>Status</th>
            <th>Location</th>
          </tr>
        </thead>
        <tbody>
          ${items.map(item => `
            <tr>
              <td><strong>${escapeHtml(item.name)}</strong></td>
              <td>${escapeHtml(item.category || '-')}</td>
              <td>${escapeHtml(item.model || '-')}</td>
              <td><code>${escapeHtml(item.barcode || '-')}</code></td>
              <td><span class="badge bg-secondary">${item.quantity_total || 0}</span></td>
              <td><span class="badge bg-success">${item.quantity_available || 0}</span></td>
              <td><span class="badge bg-info">${(item.quantity_total || 0) - (item.quantity_available || 0)}</span></td>
              <td>${renderStatusBadge(item.status)}</td>
              <td>${escapeHtml(item.location || '-')}</td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    </div>
  `;
}

async function clearInventoryFilters() {
  document.getElementById('inventoryStatusFilter').value = '';
  document.getElementById('inventoryCategoryFilter').value = '';
  document.getElementById('inventorySearchFilter').value = '';
  await loadInventoryReport();
}

async function openLocationReport() {
  currentReportType = 'location';
  const modal = new bootstrap.Modal(document.getElementById('locationReportModal'));
  modal.show();
  await loadLocationReport();
}

async function loadLocationReport() {
  const contentDiv = document.getElementById('locationReportContent');
  if (!contentDiv) return;
  
  contentDiv.innerHTML = '<div class="text-center py-5"><div class="spinner-border" role="status"></div></div>';
  
  try {
    const theatreFilter = document.getElementById('locationTheatreFilter')?.value;
    const items = await window.api.reports.getItemsByLocation(theatreFilter || null);
    
    currentReport = items;
    contentDiv.innerHTML = renderLocationTable(items);
  } catch (error) {
    console.error('Error loading location report:', error);
    contentDiv.innerHTML = `<div class="alert alert-danger">Failed to load report: ${error.message}</div>`;
  }
}

function renderLocationTable(items) {
  if (!items || items.length === 0) {
    return `
      <div class="empty">
        <div class="empty-icon"><i class="ti ti-map-pin icon"></i></div>
        <p class="empty-title">No items found</p>
        <p class="empty-subtitle text-muted">No items are currently in use at the selected location(s)</p>
      </div>
    `;
  }
  
  // Group by theatre
  const grouped = {};
  items.forEach(item => {
    if (!item.theatre_id) return;
    if (!grouped[item.theatre_id]) {
      grouped[item.theatre_id] = {
        theatre_name: item.theatre_name,
        items: []
      };
    }
    if (item.item_id) {
      grouped[item.theatre_id].items.push(item);
    }
  });
  
  let html = '';
  let totalItems = 0;
  let totalQty = 0;
  
  for (const theatreId in grouped) {
    const theatre = grouped[theatreId];
    const theatreQty = theatre.items.reduce((sum, item) => sum + (item.quantity || 0), 0);
    totalItems += theatre.items.length;
    totalQty += theatreQty;
    
    html += `
      <div class="card mb-3">
        <div class="card-header">
          <h4 class="card-title">
            <i class="ti ti-map-pin icon me-2"></i>
            ${escapeHtml(theatre.theatre_name)}
          </h4>
          <div class="card-subtitle">
            ${theatre.items.length} item(s) | Total Qty: ${theatreQty}
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
                  <td><strong>${escapeHtml(item.item_name)}</strong></td>
                  <td>${escapeHtml(item.show_name || 'N/A')}</td>
                  <td><span class="badge bg-primary">${item.quantity || 0}</span></td>
                  <td><code>${escapeHtml(item.serial_number || '-')}</code></td>
                  <td>${renderStatusBadge(item.status)}</td>
                </tr>
              `).join('')}
            </tbody>
          </table>
        </div>
      </div>
    `;
  }
  
  return `
    <div class="alert alert-info mb-3">
      <strong>Summary:</strong> ${totalItems} item(s) in ${Object.keys(grouped).length} theatre(s) | 
      Total Quantity: ${totalQty}
    </div>
    ${html}
  `;
}

async function openLowStockReport() {
  currentReportType = 'lowStock';
  const modal = new bootstrap.Modal(document.getElementById('lowStockReportModal'));
  modal.show();
  await loadLowStockReport();
}

async function loadLowStockReport() {
  const contentDiv = document.getElementById('lowStockReportContent');
  if (!contentDiv) return;
  
  contentDiv.innerHTML = '<div class="text-center py-5"><div class="spinner-border" role="status"></div></div>';
  
  try {
    const threshold = parseInt(document.getElementById('lowStockThreshold')?.value || 5);
    const items = await window.api.reports.getLowStock(threshold);
    
    currentReport = items;
    contentDiv.innerHTML = renderLowStockTable(items, threshold);
  } catch (error) {
    console.error('Error loading low stock report:', error);
    contentDiv.innerHTML = `<div class="alert alert-danger">Failed to load report: ${error.message}</div>`;
  }
}

function renderLowStockTable(items, threshold) {
  if (!items || items.length === 0) {
    return `
      <div class="alert alert-success">
        <div class="d-flex">
          <div><i class="ti ti-check icon alert-icon"></i></div>
          <div>
            <h4 class="alert-title">All items well stocked!</h4>
            <div class="text-muted">No items found below the threshold of ${threshold}</div>
          </div>
        </div>
      </div>
    `;
  }
  
  return `
    <div class="alert alert-warning mb-3">
      <strong>Warning:</strong> ${items.length} item(s) below threshold of ${threshold}
    </div>
    
    <div class="table-responsive">
      <table class="table table-sm table-hover">
        <thead>
          <tr>
            <th>Name</th>
            <th>Category</th>
            <th>Available</th>
            <th>Total</th>
            <th>Out</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          ${items.map(item => `
            <tr>
              <td><strong>${escapeHtml(item.name)}</strong></td>
              <td>${escapeHtml(item.category || '-')}</td>
              <td><span class="badge bg-warning">${item.quantity_available || 0}</span></td>
              <td><span class="badge bg-secondary">${item.quantity_total || 0}</span></td>
              <td><span class="badge bg-info">${(item.quantity_total || 0) - (item.quantity_available || 0)}</span></td>
              <td>${renderStatusBadge(item.status)}</td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    </div>
  `;
}

async function openItemsOutReport() {
  currentReportType = 'itemsOut';
  const modal = new bootstrap.Modal(document.getElementById('itemsOutReportModal'));
  modal.show();
  await loadItemsOutReport();
}

async function loadItemsOutReport() {
  const contentDiv = document.getElementById('itemsOutReportContent');
  if (!contentDiv) return;
  
  contentDiv.innerHTML = '<div class="text-center py-5"><div class="spinner-border" role="status"></div></div>';
  
  try {
    const items = await window.api.reports.getItemsOut();
    
    currentReport = items;
    contentDiv.innerHTML = renderItemsOutTable(items);
  } catch (error) {
    console.error('Error loading items out report:', error);
    contentDiv.innerHTML = `<div class="alert alert-danger">Failed to load report: ${error.message}</div>`;
  }
}

function renderItemsOutTable(items) {
  if (!items || items.length === 0) {
    return `
      <div class="empty">
        <div class="empty-icon"><i class="ti ti-check icon text-success"></i></div>
        <p class="empty-title">No items checked out</p>
        <p class="empty-subtitle text-muted">All equipment is currently in stock</p>
      </div>
    `;
  }
  
  const totalQty = items.reduce((sum, item) => sum + (item.quantity_out || 0), 0);
  
  return `
    <div class="alert alert-info mb-3">
      <strong>Summary:</strong> ${items.length} item(s) checked out | 
      Total Quantity Out: ${totalQty}
    </div>
    
    <div class="table-responsive">
      <table class="table table-sm table-hover">
        <thead>
          <tr>
            <th>Item Name</th>
            <th>Category</th>
            <th>Quantity Out</th>
            <th>Total Available</th>
            <th>Location</th>
            <th>Show</th>
          </tr>
        </thead>
        <tbody>
          ${items.map(item => `
            <tr>
              <td><strong>${escapeHtml(item.name)}</strong></td>
              <td>${escapeHtml(item.category || '-')}</td>
              <td><span class="badge bg-info">${item.quantity_out || 0}</span></td>
              <td><span class="badge bg-success">${item.quantity_available || 0}</span></td>
              <td>${escapeHtml(item.location || '-')}</td>
              <td>${escapeHtml(item.show_name || 'N/A')}</td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    </div>
  `;
}

async function openShowEquipmentReport() {
  currentReportType = 'showEquipment';
  const modal = new bootstrap.Modal(document.getElementById('showEquipmentReportModal'));
  
  // Load shows into dropdown
  const shows = await window.api.shows.getAll();
  const select = document.getElementById('showEquipmentFilter');
  select.innerHTML = '<option value="">Select a show...</option>' + 
    shows.map(s => `<option value="${s.id}">${escapeHtml(s.name)}</option>`).join('');
  
  modal.show();
}

async function loadShowEquipmentReport() {
  const contentDiv = document.getElementById('showEquipmentReportContent');
  if (!contentDiv) return;
  
  const showId = document.getElementById('showEquipmentFilter')?.value;
  
  if (!showId) {
    contentDiv.innerHTML = `
      <div class="empty">
        <div class="empty-icon"><i class="ti ti-theater icon"></i></div>
        <p class="empty-title">Select a show</p>
        <p class="empty-subtitle text-muted">Choose a show from the dropdown to view its equipment</p>
      </div>
    `;
    return;
  }
  
  contentDiv.innerHTML = '<div class="text-center py-5"><div class="spinner-border" role="status"></div></div>';
  
  try {
    const data = await window.api.reports.getShowEquipment(showId);
    
    currentReport = data;
    contentDiv.innerHTML = renderShowEquipmentTable(data);
  } catch (error) {
    console.error('Error loading show equipment report:', error);
    contentDiv.innerHTML = `<div class="alert alert-danger">Failed to load report: ${error.message}</div>`;
  }
}

function renderShowEquipmentTable(data) {
  if (!data || !data.items || data.items.length === 0) {
    return `
      <div class="empty">
        <div class="empty-icon"><i class="ti ti-inbox icon"></i></div>
        <p class="empty-title">No equipment found</p>
        <p class="empty-subtitle text-muted">This show has no equipment assigned</p>
      </div>
    `;
  }
  
  const totalQty = data.items.reduce((sum, item) => sum + (item.quantity || 0), 0);
  
  return `
    <div class="card mb-3">
      <div class="card-header">
        <h4 class="card-title">${escapeHtml(data.show_name)}</h4>
        <div class="card-subtitle">
          ${data.theatre_name ? `at ${escapeHtml(data.theatre_name)}` : ''}
          ${data.status ? `| Status: ${renderStatusBadge(data.status)}` : ''}
        </div>
      </div>
    </div>
    
    <div class="alert alert-info mb-3">
      <strong>Summary:</strong> ${data.items.length} item(s) | 
      Total Quantity: ${totalQty}
    </div>
    
    <div class="table-responsive">
      <table class="table table-sm table-hover">
        <thead>
          <tr>
            <th>Item Name</th>
            <th>Category</th>
            <th>Quantity</th>
            <th>Status</th>
            <th>Notes</th>
          </tr>
        </thead>
        <tbody>
          ${data.items.map(item => `
            <tr>
              <td><strong>${escapeHtml(item.name)}</strong></td>
              <td>${escapeHtml(item.category || '-')}</td>
              <td><span class="badge bg-primary">${item.quantity || 0}</span></td>
              <td>${renderStatusBadge(item.status)}</td>
              <td>${escapeHtml(item.notes || '-')}</td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    </div>
  `;
}

async function openActivityLogReport() {
  currentReportType = 'activityLog';
  activityLogPage = 1;
  const modal = new bootstrap.Modal(document.getElementById('activityLogReportModal'));
  modal.show();
  await loadActivityLogReport(1);
}

async function loadActivityLogReport(page = 1) {
  const contentDiv = document.getElementById('activityLogReportContent');
  if (!contentDiv) return;
  
  contentDiv.innerHTML = '<div class="text-center py-5"><div class="spinner-border" role="status"></div></div>';
  
  try {
    activityLogPage = page;
    
    const filters = {
      limit: activityLogPerPage,
      offset: (page - 1) * activityLogPerPage
    };
    
    const actionFilter = document.getElementById('activityActionFilter')?.value;
    if (actionFilter) filters.action_type = actionFilter;
    
    const dateFrom = document.getElementById('activityDateFrom')?.value;
    if (dateFrom) filters.date_from = dateFrom;
    
    const dateTo = document.getElementById('activityDateTo')?.value;
    if (dateTo) filters.date_to = dateTo;
    
    const logs = await window.api.reports.getActivityLog(filters);
    
    currentReport = logs;
    contentDiv.innerHTML = renderActivityLogTable(logs, page);
  } catch (error) {
    console.error('Error loading activity log report:', error);
    contentDiv.innerHTML = `<div class="alert alert-danger">Failed to load report: ${error.message}</div>`;
  }
}

function renderActivityLogTable(logs, page) {
  if (!logs || logs.length === 0) {
    return `
      <div class="empty">
        <div class="empty-icon"><i class="ti ti-timeline icon"></i></div>
        <p class="empty-title">No activity found</p>
        <p class="empty-subtitle text-muted">No activity matches your filter criteria</p>
      </div>
    `;
  }
  
  const hasMore = logs.length === activityLogPerPage;
  const hasPrev = page > 1;
  
  return `
    <div class="alert alert-info mb-3">
      <strong>Page ${page}</strong> | Showing ${logs.length} record(s)
    </div>
    
    <div class="table-responsive">
      <table class="table table-sm table-hover">
        <thead>
          <tr>
            <th>Date & Time</th>
            <th>Action</th>
            <th>Description</th>
            <th>User</th>
          </tr>
        </thead>
        <tbody>
          ${logs.map(log => `
            <tr>
              <td style="white-space: nowrap;">
                <small>${new Date(log.created_at).toLocaleString()}</small>
              </td>
              <td><span class="badge ${getActionBadgeClass(log.action_type)}">${escapeHtml(log.action_type)}</span></td>
              <td>${escapeHtml(log.description || 'N/A')}</td>
              <td>${escapeHtml(log.user || '-')}</td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    </div>
    
    <div class="d-flex justify-content-between mt-3">
      <button class="btn btn-secondary" onclick="loadActivityLogReport(${page - 1})" ${!hasPrev ? 'disabled' : ''}>
        <i class="ti ti-chevron-left icon"></i> Previous
      </button>
      <span class="text-muted">Page ${page}</span>
      <button class="btn btn-secondary" onclick="loadActivityLogReport(${page + 1})" ${!hasMore ? 'disabled' : ''}>
        Next <i class="ti ti-chevron-right icon"></i>
      </button>
    </div>
  `;
}

async function clearActivityFilters() {
  document.getElementById('activityActionFilter').value = '';
  document.getElementById('activityDateFrom').value = '';
  document.getElementById('activityDateTo').value = '';
  await loadActivityLogReport(1);
}

// ===== PDF EXPORT FUNCTIONS =====

async function exportInventoryPDF() {
  try {
    const filters = {
      status: document.getElementById('inventoryStatusFilter')?.value || null,
      category: document.getElementById('inventoryCategoryFilter')?.value || null,
      search: document.getElementById('inventorySearchFilter')?.value || null
    };
    
    const result = await window.api.pdf.generateInventoryReport(filters);
    
    if (result.success) {
      showSuccessNotification('Inventory report exported successfully!');
    } else {
      showErrorNotification('Failed to export report: ' + result.error);
    }
  } catch (error) {
    console.error('Error exporting inventory PDF:', error);
    showErrorNotification('Failed to export report: ' + error.message);
  }
}

async function exportLocationPDF() {
  try {
    const theatreId = document.getElementById('locationTheatreFilter')?.value || null;
    const result = await window.api.pdf.generateLocationReport(theatreId);
    
    if (result.success) {
      showSuccessNotification('Location report exported successfully!');
    } else {
      showErrorNotification('Failed to export report: ' + result.error);
    }
  } catch (error) {
    console.error('Error exporting location PDF:', error);
    showErrorNotification('Failed to export report: ' + error.message);
  }
}

async function exportLowStockPDF() {
  try {
    const threshold = parseInt(document.getElementById('lowStockThreshold')?.value || 5);
    const result = await window.api.pdf.generateInventoryReport({ lowStock: threshold });
    
    if (result.success) {
      showSuccessNotification('Low stock report exported successfully!');
    } else {
      showErrorNotification('Failed to export report: ' + result.error);
    }
  } catch (error) {
    console.error('Error exporting low stock PDF:', error);
    showErrorNotification('Failed to export report: ' + error.message);
  }
}

async function exportItemsOutPDF() {
  try {
    const result = await window.api.pdf.generateInventoryReport({ itemsOut: true });
    
    if (result.success) {
      showSuccessNotification('Items out report exported successfully!');
    } else {
      showErrorNotification('Failed to export report: ' + result.error);
    }
  } catch (error) {
    console.error('Error exporting items out PDF:', error);
    showErrorNotification('Failed to export report: ' + error.message);
  }
}

async function exportShowEquipmentPDF() {
  try {
    const showId = document.getElementById('showEquipmentFilter')?.value;
    if (!showId) {
      showErrorNotification('Please select a show first');
      return;
    }
    
    const result = await window.api.pdf.generateInventoryReport({ showId: parseInt(showId) });
    
    if (result.success) {
      showSuccessNotification('Show equipment report exported successfully!');
    } else {
      showErrorNotification('Failed to export report: ' + result.error);
    }
  } catch (error) {
    console.error('Error exporting show equipment PDF:', error);
    showErrorNotification('Failed to export report: ' + error.message);
  }
}

async function exportActivityLogPDF() {
  try {
    const filters = {
      action_type: document.getElementById('activityActionFilter')?.value || null,
      date_from: document.getElementById('activityDateFrom')?.value || null,
      date_to: document.getElementById('activityDateTo')?.value || null
    };
    
    const result = await window.api.pdf.generateInventoryReport({ activityLog: true, ...filters });
    
    if (result.success) {
      showSuccessNotification('Activity log report exported successfully!');
    } else {
      showErrorNotification('Failed to export report: ' + result.error);
    }
  } catch (error) {
    console.error('Error exporting activity log PDF:', error);
    showErrorNotification('Failed to export report: ' + error.message);
  }
}

// ===== UTILITY FUNCTIONS =====

function renderStatusBadge(status) {
  if (!status) return '<span class="badge bg-secondary">N/A</span>';
  
  const statusMap = {
    'available': 'bg-success',
    'checked_out': 'bg-info',
    'maintenance': 'bg-warning',
    'damaged': 'bg-danger',
    'active': 'bg-primary',
    'running': 'bg-primary',
    'planning': 'bg-secondary',
    'closed': 'bg-dark'
  };
  
  const badgeClass = statusMap[status.toLowerCase()] || 'bg-secondary';
  return `<span class="badge ${badgeClass}">${escapeHtml(status)}</span>`;
}

function escapeHtml(text) {
  if (!text) return '';
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

function showSuccessNotification(message) {
  // Simple notification - can be enhanced with toast library
  if (window.alert) {
    alert(message);
  } else {
    console.log('Success:', message);
  }
}

function showErrorNotification(message) {
  // Simple notification - can be enhanced with toast library
  if (window.alert) {
    alert(message);
  } else {
    console.error('Error:', message);
  }
}

// ===== INITIALIZATION =====

async function initReportsPage() {
  // Page is initialized when rendered
  console.log('Reports page initialized');
}

// Export functions to window for onclick handlers
window.openInventoryReport = openInventoryReport;
window.openLocationReport = openLocationReport;
window.openLowStockReport = openLowStockReport;
window.openItemsOutReport = openItemsOutReport;
window.openShowEquipmentReport = openShowEquipmentReport;
window.openActivityLogReport = openActivityLogReport;
window.exportReportToPDF = exportReportToPDF;

// Register page with navigation system
if (window.navigation) {
  window.navigation.registerPage('reports', {
    render: renderReportsPage,
    init: initReportsPage
  });
}

})();
