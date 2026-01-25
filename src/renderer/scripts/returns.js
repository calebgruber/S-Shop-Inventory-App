/**
 * Returns Module - COMPLETE IMPLEMENTATION
 * Handles equipment return workflows with barcode scanning and condition tracking
 * Following patterns from pullsheets.js with Tabler UI components
 */

let currentReturns = [];
let currentPullSheets = [];
let activeReturn = null;
let returnItems = [];
let scannedItems = new Map(); // Track scanned items: barcode -> {item, quantity, condition}
let expectedItems = new Map(); // Expected items from pull sheet: itemId -> quantity

// Render returns page
async function renderReturnsPage() {
  currentReturns = await window.api.returns.getAll();
  
  return `
    <div class="row mb-3">
      <div class="col-md-8">
        <p class="text-muted">Process equipment returns after show closes</p>
      </div>
      <div class="col-md-4 text-end">
        <button class="btn btn-success" id="startReturnBtn">
          <i class="ti ti-arrow-back icon"></i> Start Return
        </button>
      </div>
    </div>
    
    <div class="row mb-3">
      <div class="col-md-12">
        <div class="btn-group" role="group">
          <input type="radio" class="btn-check" name="returnStatusFilter" id="returnFilterAll" value="all" checked>
          <label class="btn btn-outline-primary" for="returnFilterAll">All</label>
          
          <input type="radio" class="btn-check" name="returnStatusFilter" id="returnFilterPending" value="pending">
          <label class="btn btn-outline-warning" for="returnFilterPending">Pending</label>
          
          <input type="radio" class="btn-check" name="returnStatusFilter" id="returnFilterCompleted" value="completed">
          <label class="btn btn-outline-success" for="returnFilterCompleted">Completed</label>
        </div>
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
              <th>Status</th>
              <th>Items Returned</th>
              <th>Return Date</th>
              <th>Returned By</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="returnsTableBody">
            ${renderReturnRows(currentReturns)}
          </tbody>
        </table>
      </div>
    </div>
    
    <!-- Select Pull Sheet Modal -->
    <div class="modal modal-blur fade" id="selectPullSheetModal" tabindex="-1">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Select Pull Sheet to Return</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Pull Sheet</label>
              <select class="form-select" id="selectPullSheetDropdown" size="10">
                <option value="">Select a pull sheet to return...</option>
              </select>
              <small class="text-muted">Only finalized pull sheets can be returned</small>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn me-auto" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="confirmPullSheetBtn">Start Return</button>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Return Process Modal -->
    <div class="modal modal-blur fade" id="returnProcessModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
      <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="returnProcessTitle">Return Equipment</h5>
            <button type="button" class="btn-close" onclick="cancelReturnProcess()"></button>
          </div>
          <div class="modal-body">
            <div class="row">
              <!-- Scanning Section -->
              <div class="col-md-6 border-end">
                <h4 class="mb-3">Scan Items to Return</h4>
                
                <!-- Progress Card -->
                <div class="card mb-3 bg-primary-lt">
                  <div class="card-body">
                    <div class="row align-items-center">
                      <div class="col">
                        <div class="text-primary mb-1">Return Progress</div>
                        <div class="h1 mb-0" id="returnProgress">0 of 0</div>
                        <div class="text-muted">Items accounted for</div>
                      </div>
                      <div class="col-auto">
                        <div class="chart-circle chart-circle-lg" id="returnProgressCircle">
                          <div class="chart-circle-value">0%</div>
                        </div>
                      </div>
                    </div>
                    <div class="progress progress-sm mt-3">
                      <div class="progress-bar bg-primary" id="returnProgressBar" style="width: 0%"></div>
                    </div>
                  </div>
                </div>
                
                <!-- Scan Input -->
                <div class="card mb-3">
                  <div class="card-body">
                    <label class="form-label required">Scan or Enter Barcode</label>
                    <div class="input-group input-group-lg">
                      <input type="text" class="form-control" id="returnScanInput" placeholder="Scan item barcode..." autofocus>
                      <button class="btn btn-primary" id="processScanBtn">
                        <i class="ti ti-scan icon"></i> Process
                      </button>
                    </div>
                    <div id="scanFeedback" class="mt-2"></div>
                  </div>
                </div>
                
                <!-- Condition Selector -->
                <div class="card" id="conditionSelector" style="display: none;">
                  <div class="card-body">
                    <h5 class="mb-3">Item Condition</h5>
                    <p id="currentItemName" class="text-muted mb-3"></p>
                    
                    <div class="d-grid gap-2">
                      <button class="btn btn-lg btn-success" onclick="markItemCondition('returned')">
                        <i class="ti ti-check icon"></i> Returned - Good Condition
                      </button>
                      <button class="btn btn-lg btn-warning" onclick="markItemCondition('damaged')">
                        <i class="ti ti-alert-triangle icon"></i> Damaged
                      </button>
                      <button class="btn btn-lg btn-danger" onclick="markItemCondition('lost')">
                        <i class="ti ti-x icon"></i> Lost/Missing
                      </button>
                    </div>
                  </div>
                </div>
                
                <!-- Legend -->
                <div class="card">
                  <div class="card-body">
                    <h6 class="mb-2">Legend</h6>
                    <div class="row">
                      <div class="col-6">
                        <span class="badge bg-success me-1">✓</span> Returned
                      </div>
                      <div class="col-6">
                        <span class="badge bg-warning me-1">△</span> Damaged
                      </div>
                      <div class="col-6">
                        <span class="badge bg-danger me-1">✗</span> Lost
                      </div>
                      <div class="col-6">
                        <span class="badge bg-secondary me-1">○</span> Pending
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Expected Items Section -->
              <div class="col-md-6">
                <h4 class="mb-3">Expected Items</h4>
                
                <div class="mb-3">
                  <input type="text" class="form-control" id="filterItemsInput" placeholder="Filter items...">
                </div>
                
                <div id="expectedItemsList" class="list-group" style="max-height: calc(100vh - 300px); overflow-y: auto;">
                  <!-- Expected items will be populated here -->
                </div>
                
                <div class="alert alert-info mt-3">
                  <i class="ti ti-info-circle icon"></i>
                  <strong>Tip:</strong> Scan barcodes to quickly mark items as returned. Items not scanned can be marked as lost.
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary me-auto" onclick="cancelReturnProcess()">Cancel</button>
            <button type="button" class="btn btn-outline-danger" id="markRemainingLostBtn" onclick="markRemainingAsLost()" disabled>
              <i class="ti ti-alert-triangle icon"></i> Mark Remaining as Lost
            </button>
            <button type="button" class="btn btn-success" id="completeReturnBtn" onclick="completeActiveReturn()" disabled>
              <i class="ti ti-check icon"></i> Complete Return
            </button>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Return Detail Modal -->
    <div class="modal modal-blur fade" id="returnDetailModal" tabindex="-1">
      <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="returnDetailTitle">Return Details</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div id="returnDetailContent">Loading...</div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-success" id="generateReturnSummaryBtn">
              <i class="ti ti-file-download icon"></i> Generate Summary
            </button>
          </div>
        </div>
      </div>
    </div>
  `;
}

// Render return rows
function renderReturnRows(returns) {
  if (returns.length === 0) {
    return `
      <tr>
        <td colspan="8" class="text-center text-muted py-5">
          <div class="empty-state">
            <i class="ti ti-arrow-back-up empty-state-icon"></i>
            <p>No returns yet. Start a return when equipment needs to come back from a show.</p>
          </div>
        </td>
      </tr>
    `;
  }
  
  return returns.map(ret => {
    const returnDate = new Date(ret.return_date).toLocaleString();
    const statusBadge = getReturnStatusBadge(ret.status);
    const itemsCount = ret.items ? ret.items.length : 0;
    const totalExpected = ret.items ? ret.items.reduce((sum, item) => sum + item.quantity, 0) : 0;
    
    return `
      <tr>
        <td><strong>#${ret.id}</strong></td>
        <td>${ret.pull_sheet_name || `Pull Sheet #${ret.pull_sheet_id}`}</td>
        <td>${ret.show_name || 'N/A'}</td>
        <td>${statusBadge}</td>
        <td class="text-center">${itemsCount} items (${totalExpected} units)</td>
        <td>${returnDate}</td>
        <td>${ret.returned_by || '-'}</td>
        <td>
          <button class="btn btn-sm btn-ghost-primary" onclick="viewReturn(${ret.id})">
            <i class="ti ti-eye icon"></i> View
          </button>
          ${ret.status === 'completed' ? `
            <button class="btn btn-sm btn-ghost-success" onclick="generateReturnSummary(${ret.id})">
              <i class="ti ti-file-download icon"></i> Summary
            </button>
          ` : `
            <button class="btn btn-sm btn-ghost-success" onclick="resumeReturn(${ret.id})">
              <i class="ti ti-arrow-back icon"></i> Resume
            </button>
          `}
        </td>
      </tr>
    `;
  }).join('');
}

// Get return status badge
function getReturnStatusBadge(status) {
  const badges = {
    pending: '<span class="badge bg-warning">Pending</span>',
    completed: '<span class="badge bg-success">Completed</span>'
  };
  return badges[status] || badges.pending;
}

// Initialize returns page
function initReturnsPage() {
  // Start return button
  document.getElementById('startReturnBtn')?.addEventListener('click', openSelectPullSheetModal);
  
  // Filter buttons
  document.querySelectorAll('[name="returnStatusFilter"]').forEach(radio => {
    radio.addEventListener('change', async (e) => {
      const filter = e.target.value;
      let filtered = currentReturns;
      
      if (filter !== 'all') {
        filtered = currentReturns.filter(r => r.status === filter);
      }
      
      document.getElementById('returnsTableBody').innerHTML = renderReturnRows(filtered);
    });
  });
}

// Open select pull sheet modal
async function openSelectPullSheetModal() {
  // Get all finalized pull sheets that haven't been returned
  currentPullSheets = await window.api.pullsheets.getAll();
  const eligiblePullSheets = currentPullSheets.filter(ps => 
    ps.status === 'finalized' || ps.status === 'pulled'
  );
  
  if (eligiblePullSheets.length === 0) {
    alert('No pull sheets available for return. Pull sheets must be finalized first.');
    return;
  }
  
  const dropdown = document.getElementById('selectPullSheetDropdown');
  dropdown.innerHTML = eligiblePullSheets.map(ps => `
    <option value="${ps.id}">
      ${ps.name || `Pull Sheet #${ps.id}`} - ${ps.show_name} (${ps.items?.length || 0} items)
    </option>
  `).join('');
  
  const modal = new bootstrap.Modal(document.getElementById('selectPullSheetModal'));
  modal.show();
  
  // Setup confirm button
  document.getElementById('confirmPullSheetBtn').onclick = async () => {
    const pullSheetId = parseInt(dropdown.value);
    if (pullSheetId) {
      modal.hide();
      await window.startReturnProcess(pullSheetId);
    } else {
      alert('Please select a pull sheet');
    }
  };
}

// Start return process - entry point
window.startReturnProcess = async function(pullSheetId) {
  // Get pull sheet details
  const pullSheet = await window.api.pullsheets.getById(pullSheetId);
  
  if (!pullSheet) {
    alert('Pull sheet not found');
    return;
  }
  
  if (!pullSheet.items || pullSheet.items.length === 0) {
    alert('This pull sheet has no items to return');
    return;
  }
  
  // Prompt for returned by
  const returnedBy = prompt('Enter your name:');
  if (!returnedBy) return;
  
  // Create return record
  try {
    const returnData = {
      pull_sheet_id: pullSheetId,
      returned_by: returnedBy,
      status: 'pending'
    };
    
    const result = await window.api.returns.create(returnData);
    activeReturn = result;
    
    // Setup expected items map
    expectedItems.clear();
    scannedItems.clear();
    returnItems = pullSheet.items.map(item => ({
      ...item,
      quantity_returned: 0,
      condition: 'pending',
      notes: null
    }));
    
    pullSheet.items.forEach(item => {
      expectedItems.set(item.item_id, {
        ...item,
        quantity_expected: item.quantity_requested,
        quantity_returned: 0,
        condition: 'pending'
      });
    });
    
    // Open return process modal
    openReturnProcessModal(pullSheet);
  } catch (error) {
    alert('Error starting return: ' + error.message);
  }
};

// Open return process modal
function openReturnProcessModal(pullSheet) {
  const modal = new bootstrap.Modal(document.getElementById('returnProcessModal'));
  document.getElementById('returnProcessTitle').textContent = 
    `Return: ${pullSheet.name || `Pull Sheet #${pullSheet.id}`}`;
  
  // Render expected items
  renderExpectedItems();
  
  // Update progress
  updateReturnProgress();
  
  // Setup scan input
  const scanInput = document.getElementById('returnScanInput');
  const processBtn = document.getElementById('processScanBtn');
  
  scanInput.value = '';
  scanInput.focus();
  
  processBtn.onclick = processScan;
  scanInput.onkeypress = (e) => {
    if (e.key === 'Enter') {
      processScan();
    }
  };
  
  // Setup filter
  const filterInput = document.getElementById('filterItemsInput');
  filterInput.value = '';
  filterInput.oninput = () => {
    renderExpectedItems(filterInput.value);
  };
  
  modal.show();
}

// Render expected items list
function renderExpectedItems(filter = '') {
  const container = document.getElementById('expectedItemsList');
  
  let items = Array.from(expectedItems.values());
  
  // Apply filter
  if (filter) {
    const lowerFilter = filter.toLowerCase();
    items = items.filter(item => 
      item.name.toLowerCase().includes(lowerFilter) ||
      (item.barcode && item.barcode.toLowerCase().includes(lowerFilter))
    );
  }
  
  if (items.length === 0) {
    container.innerHTML = `
      <div class="text-center text-muted py-5">
        <p>No items ${filter ? 'match filter' : 'to return'}</p>
      </div>
    `;
    return;
  }
  
  container.innerHTML = items.map(item => {
    const returned = item.quantity_returned;
    const expected = item.quantity_expected;
    const percentage = expected > 0 ? Math.round((returned / expected) * 100) : 0;
    const isComplete = returned >= expected;
    
    let statusBadge = '';
    let statusColor = 'secondary';
    
    if (item.condition === 'returned') {
      statusBadge = '<span class="badge bg-success">✓ Returned</span>';
      statusColor = 'success';
    } else if (item.condition === 'damaged') {
      statusBadge = '<span class="badge bg-warning">△ Damaged</span>';
      statusColor = 'warning';
    } else if (item.condition === 'lost') {
      statusBadge = '<span class="badge bg-danger">✗ Lost</span>';
      statusColor = 'danger';
    } else {
      statusBadge = '<span class="badge bg-secondary">○ Pending</span>';
    }
    
    return `
      <div class="list-group-item" id="item-${item.item_id}">
        <div class="row align-items-center">
          <div class="col">
            <div class="fw-bold">${item.name}</div>
            <div class="text-muted small">${item.barcode || 'No barcode'}</div>
            <div class="mt-1">
              <span class="text-${statusColor} fw-bold">${returned}</span> of ${expected} returned
            </div>
            <div class="progress progress-sm mt-1">
              <div class="progress-bar bg-${statusColor}" style="width: ${percentage}%"></div>
            </div>
          </div>
          <div class="col-auto">
            ${statusBadge}
            ${!isComplete && item.condition === 'pending' ? `
              <button class="btn btn-sm btn-ghost-primary ms-2" onclick="manualMarkItem(${item.item_id})">
                <i class="ti ti-hand-click icon"></i> Mark
              </button>
            ` : ''}
          </div>
        </div>
      </div>
    `;
  }).join('');
}

// Process barcode scan
async function processScan() {
  const scanInput = document.getElementById('returnScanInput');
  const barcode = scanInput.value.trim();
  const feedback = document.getElementById('scanFeedback');
  
  if (!barcode) {
    feedback.innerHTML = '<div class="alert alert-warning">Please enter a barcode</div>';
    return;
  }
  
  feedback.innerHTML = '';
  
  // Find item by barcode
  const item = await window.api.inventory.getByBarcode(barcode);
  
  if (!item) {
    feedback.innerHTML = '<div class="alert alert-danger">Item not found in inventory</div>';
    playSound('error');
    scanInput.value = '';
    return;
  }
  
  // Check if item is expected
  const expectedItem = expectedItems.get(item.id);
  
  if (!expectedItem) {
    feedback.innerHTML = `<div class="alert alert-danger">
      <strong>${item.name}</strong> is not on this pull sheet
    </div>`;
    playSound('error');
    scanInput.value = '';
    return;
  }
  
  // Check if already fully returned
  if (expectedItem.quantity_returned >= expectedItem.quantity_expected) {
    feedback.innerHTML = `<div class="alert alert-warning">
      <strong>${item.name}</strong> has already been fully returned
    </div>`;
    playSound('warning');
    scanInput.value = '';
    return;
  }
  
  // Show condition selector
  showConditionSelector(item, expectedItem);
  scanInput.value = '';
  playSound('success');
}

// Show condition selector for scanned item
let currentScannedItem = null;
let currentExpectedItem = null;

function showConditionSelector(item, expectedItem) {
  currentScannedItem = item;
  currentExpectedItem = expectedItem;
  
  const selector = document.getElementById('conditionSelector');
  const itemName = document.getElementById('currentItemName');
  
  itemName.textContent = `${item.name} (${expectedItem.quantity_returned + 1} of ${expectedItem.quantity_expected})`;
  selector.style.display = 'block';
  
  // Disable scan input while selecting condition
  document.getElementById('returnScanInput').disabled = true;
  document.getElementById('processScanBtn').disabled = true;
}

// Mark item condition
async function markItemCondition(condition) {
  if (!currentScannedItem || !currentExpectedItem) return;
  
  const feedback = document.getElementById('scanFeedback');
  
  try {
    // Add to return
    await window.api.returns.addItem(
      activeReturn.id,
      currentScannedItem.id,
      1,
      condition,
      null
    );
    
    // Update expected items
    currentExpectedItem.quantity_returned += 1;
    currentExpectedItem.condition = condition;
    
    // Show feedback
    const conditionLabel = {
      'returned': 'Good Condition',
      'damaged': 'Damaged',
      'lost': 'Lost'
    }[condition];
    
    feedback.innerHTML = `<div class="alert alert-success">
      <strong>${currentScannedItem.name}</strong> marked as ${conditionLabel}
    </div>`;
    
    // Hide condition selector
    document.getElementById('conditionSelector').style.display = 'none';
    
    // Re-enable scan input
    document.getElementById('returnScanInput').disabled = false;
    document.getElementById('processScanBtn').disabled = false;
    document.getElementById('returnScanInput').focus();
    
    // Update UI
    renderExpectedItems(document.getElementById('filterItemsInput').value);
    updateReturnProgress();
    
    // Clear feedback after delay
    setTimeout(() => {
      feedback.innerHTML = '';
    }, 3000);
    
    currentScannedItem = null;
    currentExpectedItem = null;
  } catch (error) {
    feedback.innerHTML = `<div class="alert alert-danger">Error: ${error.message}</div>`;
  }
}

// Manual mark item (without scanning)
async function manualMarkItem(itemId) {
  const expectedItem = expectedItems.get(itemId);
  if (!expectedItem) return;
  
  const item = await window.api.inventory.getById(itemId);
  if (!item) return;
  
  showConditionSelector(item, expectedItem);
}

// Update return progress
function updateReturnProgress() {
  const totalExpected = Array.from(expectedItems.values())
    .reduce((sum, item) => sum + item.quantity_expected, 0);
  
  const totalReturned = Array.from(expectedItems.values())
    .reduce((sum, item) => sum + item.quantity_returned, 0);
  
  const percentage = totalExpected > 0 ? Math.round((totalReturned / totalExpected) * 100) : 0;
  
  document.getElementById('returnProgress').textContent = `${totalReturned} of ${totalExpected}`;
  document.getElementById('returnProgressBar').style.width = `${percentage}%`;
  document.getElementById('returnProgressCircle').innerHTML = `<div class="chart-circle-value">${percentage}%</div>`;
  
  // Enable/disable complete button
  const completeBtn = document.getElementById('completeReturnBtn');
  const markLostBtn = document.getElementById('markRemainingLostBtn');
  
  if (totalReturned >= totalExpected) {
    completeBtn.disabled = false;
    markLostBtn.disabled = true;
  } else {
    completeBtn.disabled = true;
    markLostBtn.disabled = false;
  }
}

// Mark remaining items as lost
async function markRemainingAsLost() {
  const confirmed = await window.api.dialog.showMessage({
    type: 'warning',
    title: 'Mark Remaining as Lost',
    message: 'Mark all remaining items as lost? This action will help complete the return but should only be used if items are truly missing.',
    buttons: ['Cancel', 'Mark as Lost'],
    defaultId: 0
  });
  
  if (confirmed.response === 1) {
    const remaining = Array.from(expectedItems.values())
      .filter(item => item.quantity_returned < item.quantity_expected);
    
    for (const expectedItem of remaining) {
      const quantityToMark = expectedItem.quantity_expected - expectedItem.quantity_returned;
      
      await window.api.returns.addItem(
        activeReturn.id,
        expectedItem.item_id,
        quantityToMark,
        'lost',
        'Marked as lost to complete return'
      );
      
      expectedItem.quantity_returned = expectedItem.quantity_expected;
      expectedItem.condition = 'lost';
    }
    
    renderExpectedItems(document.getElementById('filterItemsInput').value);
    updateReturnProgress();
  }
}

// Complete active return
async function completeActiveReturn() {
  const confirmed = await window.api.dialog.showMessage({
    type: 'question',
    title: 'Complete Return',
    message: 'Complete this return? This will update inventory availability and close the pull sheet.',
    buttons: ['Cancel', 'Complete'],
    defaultId: 1
  });
  
  if (confirmed.response === 1) {
    try {
      await window.api.returns.complete(activeReturn.id);
      
      // Close modal
      bootstrap.Modal.getInstance(document.getElementById('returnProcessModal')).hide();
      
      // Show success message
      alert('Return completed successfully! Inventory has been updated.');
      
      // Reset state
      activeReturn = null;
      expectedItems.clear();
      scannedItems.clear();
      returnItems = [];
      
      // Refresh list
      await refreshReturns();
    } catch (error) {
      alert('Error completing return: ' + error.message);
    }
  }
}

// Cancel return process
async function cancelReturnProcess() {
  const confirmed = await window.api.dialog.showMessage({
    type: 'warning',
    title: 'Cancel Return',
    message: 'Cancel this return? Progress will be saved and you can resume later.',
    buttons: ['Continue Return', 'Cancel Return'],
    defaultId: 0
  });
  
  if (confirmed.response === 1) {
    // Hide condition selector if visible
    document.getElementById('conditionSelector').style.display = 'none';
    
    // Close modal
    bootstrap.Modal.getInstance(document.getElementById('returnProcessModal')).hide();
    
    // Reset state
    activeReturn = null;
    expectedItems.clear();
    scannedItems.clear();
    returnItems = [];
    
    // Refresh list
    await refreshReturns();
  }
}

// Resume return
async function resumeReturn(returnId) {
  const returnRecord = await window.api.returns.getById(returnId);
  
  if (!returnRecord) {
    alert('Return not found');
    return;
  }
  
  const pullSheet = await window.api.pullsheets.getById(returnRecord.pull_sheet_id);
  
  if (!pullSheet) {
    alert('Pull sheet not found');
    return;
  }
  
  // Set active return
  activeReturn = returnRecord;
  
  // Setup expected items map with current progress
  expectedItems.clear();
  scannedItems.clear();
  
  const returnedItemsMap = new Map();
  returnRecord.items.forEach(item => {
    if (!returnedItemsMap.has(item.item_id)) {
      returnedItemsMap.set(item.item_id, {
        quantity: 0,
        condition: item.condition
      });
    }
    returnedItemsMap.get(item.item_id).quantity += item.quantity;
  });
  
  pullSheet.items.forEach(item => {
    const returned = returnedItemsMap.get(item.item_id) || { quantity: 0, condition: 'pending' };
    expectedItems.set(item.item_id, {
      ...item,
      quantity_expected: item.quantity_requested,
      quantity_returned: returned.quantity,
      condition: returned.condition
    });
  });
  
  // Open return process modal
  openReturnProcessModal(pullSheet);
}

// View return details
async function viewReturn(returnId) {
  const returnRecord = await window.api.returns.getById(returnId);
  
  if (!returnRecord) {
    alert('Return not found');
    return;
  }
  
  const modal = new bootstrap.Modal(document.getElementById('returnDetailModal'));
  document.getElementById('returnDetailTitle').textContent = `Return #${returnRecord.id}`;
  
  renderReturnDetail(returnRecord);
  modal.show();
  
  // Setup summary button
  document.getElementById('generateReturnSummaryBtn').onclick = () => {
    generateReturnSummary(returnId);
  };
}

// Render return detail
function renderReturnDetail(returnRecord) {
  const items = returnRecord.items || [];
  const returnDate = new Date(returnRecord.return_date).toLocaleString();
  const completedDate = returnRecord.completed_at ? new Date(returnRecord.completed_at).toLocaleString() : null;
  
  // Group items by condition
  const returnedItems = items.filter(i => i.condition === 'returned');
  const damagedItems = items.filter(i => i.condition === 'damaged');
  const lostItems = items.filter(i => i.condition === 'lost');
  
  const totalReturned = returnedItems.reduce((sum, i) => sum + i.quantity, 0);
  const totalDamaged = damagedItems.reduce((sum, i) => sum + i.quantity, 0);
  const totalLost = lostItems.reduce((sum, i) => sum + i.quantity, 0);
  
  let content = `
    <div class="row mb-4">
      <div class="col-md-6">
        <h5>Return Information</h5>
        <p><strong>Pull Sheet:</strong> ${returnRecord.pull_sheet_name || `Pull Sheet #${returnRecord.pull_sheet_id}`}</p>
        <p><strong>Show:</strong> ${returnRecord.show_name || 'N/A'}</p>
        <p><strong>Status:</strong> ${getReturnStatusBadge(returnRecord.status)}</p>
        <p><strong>Returned By:</strong> ${returnRecord.returned_by || '-'}</p>
      </div>
      <div class="col-md-6">
        <h5>Dates</h5>
        <p><strong>Return Started:</strong> ${returnDate}</p>
        ${completedDate ? `<p><strong>Completed:</strong> ${completedDate}</p>` : ''}
        ${returnRecord.notes ? `
          <h5>Notes</h5>
          <p>${returnRecord.notes}</p>
        ` : ''}
      </div>
    </div>
    
    <div class="row mb-4">
      <div class="col-md-4">
        <div class="card bg-success-lt">
          <div class="card-body text-center">
            <div class="h1 mb-0">${totalReturned}</div>
            <div class="text-muted">Returned (Good)</div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card bg-warning-lt">
          <div class="card-body text-center">
            <div class="h1 mb-0">${totalDamaged}</div>
            <div class="text-muted">Damaged</div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card bg-danger-lt">
          <div class="card-body text-center">
            <div class="h1 mb-0">${totalLost}</div>
            <div class="text-muted">Lost</div>
          </div>
        </div>
      </div>
    </div>
    
    <h5 class="mb-3">Return Items</h5>
    <div class="card">
      <div class="table-responsive">
        <table class="table table-sm">
          <thead>
            <tr>
              <th>Item</th>
              <th>Barcode</th>
              <th>Quantity</th>
              <th>Condition</th>
              <th>Notes</th>
            </tr>
          </thead>
          <tbody>
            ${items.length === 0 ? `
              <tr>
                <td colspan="5" class="text-center text-muted">No items returned yet</td>
              </tr>
            ` : items.map(item => {
              let conditionBadge = '';
              if (item.condition === 'returned') {
                conditionBadge = '<span class="badge bg-success">Returned</span>';
              } else if (item.condition === 'damaged') {
                conditionBadge = '<span class="badge bg-warning">Damaged</span>';
              } else if (item.condition === 'lost') {
                conditionBadge = '<span class="badge bg-danger">Lost</span>';
              }
              
              return `
                <tr>
                  <td><strong>${item.name}</strong></td>
                  <td><span class="text-mono">${item.barcode || '-'}</span></td>
                  <td class="text-center">${item.quantity}</td>
                  <td>${conditionBadge}</td>
                  <td>${item.notes || '-'}</td>
                </tr>
              `;
            }).join('')}
          </tbody>
        </table>
      </div>
    </div>
  `;
  
  document.getElementById('returnDetailContent').innerHTML = content;
}

// Generate return summary
async function generateReturnSummary(returnId) {
  try {
    const result = await window.api.pdf.generateReturnSummary(returnId);
    if (result.success) {
      alert(`Return summary generated: ${result.filePath}`);
    } else {
      alert('Failed to generate summary: ' + result.error);
    }
  } catch (error) {
    alert('Return summary generation not yet implemented in backend. Summary would show:\n- Return details\n- Items returned, damaged, and lost\n- Inventory changes');
  }
}

// Refresh returns list
async function refreshReturns() {
  currentReturns = await window.api.returns.getAll();
  document.getElementById('returnsTableBody').innerHTML = renderReturnRows(currentReturns);
}

// Play sound feedback (if available)
function playSound(type) {
  // Could implement sound feedback here
  // For now, just visual feedback
}

// Make functions globally available
window.viewReturn = viewReturn;
window.resumeReturn = resumeReturn;
window.generateReturnSummary = generateReturnSummary;
window.manualMarkItem = manualMarkItem;
window.markItemCondition = markItemCondition;
window.markRemainingAsLost = markRemainingAsLost;
window.completeActiveReturn = completeActiveReturn;
window.cancelReturnProcess = cancelReturnProcess;

// Register page with navigation
window.navigation.registerPage('returns', {
  render: renderReturnsPage,
  init: initReturnsPage
});
