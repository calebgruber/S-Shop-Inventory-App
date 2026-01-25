/**
 * Inventory Management Module
 * Handles inventory listing, creation, editing, and management
 */

let currentInventoryItems = [];
let currentFilter = {};

// Render inventory page
async function renderInventoryPage() {
  currentInventoryItems = await window.api.inventory.getAll();
  
  return `
    <div class="row mb-3">
      <div class="col-md-8">
        <div class="input-group">
          <input type="text" class="form-control" id="inventorySearch" placeholder="Search items by name, model, barcode...">
          <button class="btn btn-primary" id="searchInventoryBtn">
            <i class="ti ti-search icon"></i> Search
          </button>
        </div>
      </div>
      <div class="col-md-4 text-end">
        <div class="btn-group">
          <button class="btn btn-success" id="addInventoryItemBtn">
            <i class="ti ti-plus icon"></i> Add Item
          </button>
          <button type="button" class="btn btn-success dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
            <span class="visually-hidden">Toggle Dropdown</span>
          </button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="#" id="printLabelsBtn">
              <i class="ti ti-printer icon"></i> Print Barcode Labels
            </a></li>
            <li><a class="dropdown-item" href="#" id="generateBarcodesBtn">
              <i class="ti ti-barcode icon"></i> Generate Missing Barcodes
            </a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="#" id="exportInventoryBtn">
              <i class="ti ti-download icon"></i> Export PDF Report
            </a></li>
          </ul>
        </div>
      </div>
    </div>
    
    <div class="row mb-3">
      <div class="col-md-12">
        <div class="btn-group" role="group">
          <input type="checkbox" class="btn-check" id="selectAllItems">
          <label class="btn btn-outline-secondary btn-sm" for="selectAllItems">
            <i class="ti ti-checkbox icon"></i> Select All
          </label>
        </div>
        <span class="ms-2 text-muted" id="selectedCount">0 items selected</span>
        <button class="btn btn-sm btn-primary ms-2" id="printSelectedLabelsBtn" style="display: none;">
          <i class="ti ti-printer icon"></i> Print Labels for Selected
        </button>
      </div>
    </div>
    
    <div class="row mb-3">
      <div class="col-md-12">
        <div class="btn-group" role="group">
          <input type="radio" class="btn-check" name="statusFilter" id="filterAll" value="all" checked>
          <label class="btn btn-outline-primary" for="filterAll">All Items</label>
          
          <input type="radio" class="btn-check" name="statusFilter" id="filterAvailable" value="available">
          <label class="btn btn-outline-success" for="filterAvailable">Available</label>
          
          <input type="radio" class="btn-check" name="statusFilter" id="filterLowStock" value="low">
          <label class="btn btn-outline-warning" for="filterLowStock">Low Stock</label>
          
          <input type="radio" class="btn-check" name="statusFilter" id="filterCheckedOut" value="out">
          <label class="btn btn-outline-info" for="filterCheckedOut">Checked Out</label>
        </div>
      </div>
    </div>
    
    <div class="card">
      <div class="table-responsive">
        <table class="table table-hover inventory-table">
          <thead>
            <tr>
              <th width="30"><input type="checkbox" id="selectAllCheckbox"></th>
              <th>Name</th>
              <th>Category</th>
              <th>Model</th>
              <th>Barcode</th>
              <th>Total</th>
              <th>Available</th>
              <th>Status</th>
              <th>Location</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="inventoryTableBody">
            ${renderInventoryRows(currentInventoryItems)}
          </tbody>
        </table>
      </div>
    </div>
    
    <!-- Add/Edit Item Modal -->
    <div class="modal modal-blur fade" id="itemModal" tabindex="-1">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="itemModalTitle">Add Item</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <form id="itemForm">
              <input type="hidden" id="itemId">
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label required">Item Name</label>
                  <input type="text" class="form-control" id="itemName" required>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label">Category</label>
                  <select class="form-select" id="itemCategory">
                    <option value="">Select category...</option>
                    <option value="Microphones">Microphones</option>
                    <option value="Wireless">Wireless Systems</option>
                    <option value="Cables">Cables</option>
                    <option value="Adapters">Adapters</option>
                    <option value="Speakers">Speakers</option>
                    <option value="Mixers">Mixers</option>
                    <option value="Processing">Signal Processing</option>
                    <option value="Accessories">Accessories</option>
                    <option value="Other">Other</option>
                  </select>
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea class="form-control" id="itemDescription" rows="2"></textarea>
              </div>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label">Manufacturer</label>
                  <input type="text" class="form-control" id="itemManufacturer">
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label">Model</label>
                  <input type="text" class="form-control" id="itemModel">
                </div>
              </div>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label">Serial Number</label>
                  <input type="text" class="form-control text-mono" id="itemSerial" placeholder="For serialized items">
                  <small class="form-hint">Leave blank for quantity-based items</small>
                </div>
                <div class="col-md-6 mb-3">
                  <label class="form-label">Barcode</label>
                  <input type="text" class="form-control text-mono" id="itemBarcode" placeholder="Auto-generated if left blank">
                  <small class="form-hint">Leave blank to auto-generate, or scan/enter manually</small>
                </div>
              </div>
              <div class="row">
                <div class="col-md-4 mb-3">
                  <label class="form-label">Quantity Total</label>
                  <input type="number" class="form-control" id="itemQuantityTotal" min="0" value="1">
                </div>
                <div class="col-md-4 mb-3">
                  <label class="form-label">Quantity Available</label>
                  <input type="number" class="form-control" id="itemQuantityAvailable" min="0" value="1">
                </div>
                <div class="col-md-4 mb-3">
                  <label class="form-label">Status</label>
                  <select class="form-select" id="itemStatus">
                    <option value="available">Available</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="damaged">Damaged</option>
                    <option value="lost">Lost</option>
                  </select>
                </div>
              </div>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label">Location</label>
                  <input type="text" class="form-control" id="itemLocation" placeholder="Shelf, drawer, etc.">
                </div>
              </div>
              <div class="mb-3">
                <label class="form-label">Notes</label>
                <textarea class="form-control" id="itemNotes" rows="2"></textarea>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn me-auto" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="saveItemBtn">Save Item</button>
          </div>
        </div>
      </div>
    </div>
  `;
}

// Render inventory table rows
function renderInventoryRows(items) {
  if (items.length === 0) {
    return `
      <tr>
        <td colspan="10" class="text-center text-muted py-5">
          <div class="empty-state">
            <i class="ti ti-box-off empty-state-icon"></i>
            <p>No items found. Add your first inventory item to get started.</p>
          </div>
        </td>
      </tr>
    `;
  }
  
  return items.map(item => {
    const statusBadge = getStatusBadge(item);
    const availabilityClass = item.quantity_available > 0 ? 'text-success' : 'text-danger';
    
    return `
      <tr data-item-id="${item.id}">
        <td onclick="event.stopPropagation()">
          <input type="checkbox" class="item-checkbox" data-item-id="${item.id}" onchange="updateSelectedCount()">
        </td>
        <td class="cursor-pointer" onclick="editInventoryItem(${item.id})"><strong>${item.name}</strong></td>
        <td>${item.category || '-'}</td>
        <td><span class="text-mono">${item.model || '-'}</span></td>
        <td><span class="text-mono">${item.barcode || '<em>No barcode</em>'}</span></td>
        <td class="text-center">${item.quantity_total || 0}</td>
        <td class="text-center ${availabilityClass}"><strong>${item.quantity_available || 0}</strong></td>
        <td>${statusBadge}</td>
        <td>${item.location || '-'}</td>
        <td>
          <button class="btn btn-sm btn-ghost-primary" onclick="event.stopPropagation(); editInventoryItem(${item.id})">
            <i class="ti ti-edit icon"></i>
          </button>
          <button class="btn btn-sm btn-ghost-danger" onclick="event.stopPropagation(); deleteInventoryItem(${item.id})">
            <i class="ti ti-trash icon"></i>
          </button>
        </td>
      </tr>
    `;
  }).join('');
}

// Get status badge HTML
function getStatusBadge(item) {
  const status = item.status || 'available';
  const badges = {
    available: '<span class="badge badge-available">Available</span>',
    maintenance: '<span class="badge badge-maintenance">Maintenance</span>',
    damaged: '<span class="badge bg-danger">Damaged</span>',
    lost: '<span class="badge bg-dark">Lost</span>'
  };
  return badges[status] || badges.available;
}

// Initialize inventory page
function initInventoryPage() {
  // Search functionality
  document.getElementById('searchInventoryBtn')?.addEventListener('click', async () => {
    const query = document.getElementById('inventorySearch').value;
    if (query) {
      currentInventoryItems = await window.api.inventory.search(query);
    } else {
      currentInventoryItems = await window.api.inventory.getAll();
    }
    refreshInventoryTable();
  });
  
  // Search on Enter key
  document.getElementById('inventorySearch')?.addEventListener('keypress', (e) => {
    if (e.key === 'Enter') {
      document.getElementById('searchInventoryBtn').click();
    }
  });
  
  // Filter buttons
  document.querySelectorAll('[name="statusFilter"]').forEach(radio => {
    radio.addEventListener('change', async (e) => {
      const filter = e.target.value;
      currentFilter = {};
      
      if (filter !== 'all') {
        currentFilter.status = filter;
      }
      
      currentInventoryItems = await window.api.inventory.getAll(currentFilter);
      refreshInventoryTable();
    });
  });
  
  // Add item button
  document.getElementById('addInventoryItemBtn')?.addEventListener('click', () => {
    openItemModal();
  });
  
  // Export button
  document.getElementById('exportInventoryBtn')?.addEventListener('click', async () => {
    const result = await window.api.pdf.generateInventoryReport({});
    if (result.success) {
      alert(`Inventory report exported to: ${result.filePath}`);
    } else {
      alert('Failed to export inventory report: ' + result.error);
    }
  });
  
  // Print labels button
  document.getElementById('printLabelsBtn')?.addEventListener('click', async () => {
    const result = await window.api.pdf.generateBarcodeLabels(
      currentInventoryItems.filter(item => item.barcode).map(item => item.id),
      {}
    );
    if (result.success) {
      alert(`Barcode labels PDF generated for ${result.count} items: ${result.filePath}\n\nReady to print on standard label sheets (Avery 5160 or equivalent).`);
    } else {
      alert('Failed to generate barcode labels: ' + result.error);
    }
  });
  
  // Generate missing barcodes button
  document.getElementById('generateBarcodesBtn')?.addEventListener('click', async () => {
    const confirmed = await window.api.dialog.showMessage({
      type: 'question',
      title: 'Generate Barcodes',
      message: 'Generate barcodes for items that don\'t have one?',
      buttons: ['Cancel', 'Generate'],
      defaultId: 1
    });
    
    if (confirmed.response === 1) {
      const result = await window.api.barcode.generateMissing();
      if (result.success) {
        alert(`Generated ${result.updated} barcodes for items without barcodes.`);
        // Refresh the list
        currentInventoryItems = await window.api.inventory.getAll(currentFilter);
        refreshInventoryTable();
      } else {
        alert('Failed to generate barcodes: ' + result.error);
      }
    }
  });
  
  // Print selected labels button
  document.getElementById('printSelectedLabelsBtn')?.addEventListener('click', async () => {
    const selectedIds = getSelectedItemIds();
    if (selectedIds.length === 0) {
      alert('No items selected');
      return;
    }
    
    const result = await window.api.pdf.generateBarcodeLabels(selectedIds, {});
    if (result.success) {
      alert(`Barcode labels PDF generated for ${result.count} selected items: ${result.filePath}\n\nReady to print on standard label sheets (Avery 5160 or equivalent).`);
    } else {
      alert('Failed to generate barcode labels: ' + result.error);
    }
  });
  
  // Select all checkbox
  document.getElementById('selectAllCheckbox')?.addEventListener('change', (e) => {
    const checkboxes = document.querySelectorAll('.item-checkbox');
    checkboxes.forEach(cb => {
      cb.checked = e.target.checked;
    });
    updateSelectedCount();
  });
  
  // Save item button
  document.getElementById('saveItemBtn')?.addEventListener('click', saveInventoryItem);
}

// Refresh inventory table
function refreshInventoryTable() {
  const tbody = document.getElementById('inventoryTableBody');
  if (tbody) {
    tbody.innerHTML = renderInventoryRows(currentInventoryItems);
  }
  updateSelectedCount();
}

// Get selected item IDs
function getSelectedItemIds() {
  const checkboxes = document.querySelectorAll('.item-checkbox:checked');
  return Array.from(checkboxes).map(cb => parseInt(cb.getAttribute('data-item-id')));
}

// Update selected count display
function updateSelectedCount() {
  const selectedIds = getSelectedItemIds();
  const countElement = document.getElementById('selectedCount');
  const printBtn = document.getElementById('printSelectedLabelsBtn');
  
  if (countElement) {
    countElement.textContent = `${selectedIds.length} items selected`;
  }
  
  if (printBtn) {
    printBtn.style.display = selectedIds.length > 0 ? 'inline-block' : 'none';
  }
}

// Make function globally available
window.updateSelectedCount = updateSelectedCount;

// Open item modal
function openItemModal(item = null) {
  const modal = new bootstrap.Modal(document.getElementById('itemModal'));
  const title = document.getElementById('itemModalTitle');
  
  if (item) {
    title.textContent = 'Edit Item';
    document.getElementById('itemId').value = item.id;
    document.getElementById('itemName').value = item.name || '';
    document.getElementById('itemCategory').value = item.category || '';
    document.getElementById('itemDescription').value = item.description || '';
    document.getElementById('itemManufacturer').value = item.manufacturer || '';
    document.getElementById('itemModel').value = item.model || '';
    document.getElementById('itemSerial').value = item.serial_number || '';
    document.getElementById('itemBarcode').value = item.barcode || '';
    document.getElementById('itemQuantityTotal').value = item.quantity_total || 0;
    document.getElementById('itemQuantityAvailable').value = item.quantity_available || 0;
    document.getElementById('itemStatus').value = item.status || 'available';
    document.getElementById('itemLocation').value = item.location || '';
    document.getElementById('itemNotes').value = item.notes || '';
  } else {
    title.textContent = 'Add Item';
    document.getElementById('itemForm').reset();
    document.getElementById('itemId').value = '';
  }
  
  modal.show();
}

// Edit inventory item
async function editInventoryItem(itemId) {
  const item = await window.api.inventory.getById(itemId);
  if (item) {
    openItemModal(item);
  }
}

// Save inventory item
async function saveInventoryItem() {
  const itemData = {
    name: document.getElementById('itemName').value,
    category: document.getElementById('itemCategory').value,
    description: document.getElementById('itemDescription').value,
    manufacturer: document.getElementById('itemManufacturer').value,
    model: document.getElementById('itemModel').value,
    serial_number: document.getElementById('itemSerial').value,
    barcode: document.getElementById('itemBarcode').value,
    quantity_total: parseInt(document.getElementById('itemQuantityTotal').value) || 0,
    quantity_available: parseInt(document.getElementById('itemQuantityAvailable').value) || 0,
    status: document.getElementById('itemStatus').value,
    location: document.getElementById('itemLocation').value,
    notes: document.getElementById('itemNotes').value
  };
  
  const itemId = document.getElementById('itemId').value;
  
  try {
    if (itemId) {
      await window.api.inventory.update(itemId, itemData);
    } else {
      await window.api.inventory.create(itemData);
    }
    
    // Close modal
    bootstrap.Modal.getInstance(document.getElementById('itemModal')).hide();
    
    // Refresh inventory list
    currentInventoryItems = await window.api.inventory.getAll(currentFilter);
    refreshInventoryTable();
  } catch (error) {
    alert('Error saving item: ' + error.message);
  }
}

// Delete inventory item
async function deleteInventoryItem(itemId) {
  const result = await window.api.dialog.showMessage({
    type: 'warning',
    title: 'Delete Item',
    message: 'Are you sure you want to delete this item? This action cannot be undone.',
    buttons: ['Cancel', 'Delete'],
    defaultId: 0
  });
  
  if (result.response === 1) {
    await window.api.inventory.delete(itemId);
    currentInventoryItems = await window.api.inventory.getAll(currentFilter);
    refreshInventoryTable();
  }
}

// Make functions globally available
window.editInventoryItem = editInventoryItem;
window.deleteInventoryItem = deleteInventoryItem;

// Register page with navigation
window.navigation.registerPage('inventory', {
  render: renderInventoryPage,
  init: initInventoryPage
});
