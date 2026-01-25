/**
 * Barcode Module
 * Handles barcode scanning and lookup operations
 */

let barcodeBuffer = '';
let barcodeTimeout = null;
const BARCODE_TIMEOUT = 100; // milliseconds between keystrokes

// Initialize barcode scanning
function initBarcodeScanning() {
  // Keyboard wedge mode - capture rapid keystrokes as barcode input
  document.addEventListener('keypress', handleBarcodeInput);
  
  // F1 key opens barcode modal
  document.addEventListener('keydown', (e) => {
    if (e.key === 'F1') {
      e.preventDefault();
      openBarcodeScanModal();
    }
  });
  
  // Setup barcode scan button
  const scanBtn = document.getElementById('barcode-scan-btn');
  if (scanBtn) {
    scanBtn.addEventListener('click', (e) => {
      e.preventDefault();
      openBarcodeScanModal();
    });
  }
  
  // Setup barcode lookup button
  const lookupBtn = document.getElementById('barcodeLookupBtn');
  if (lookupBtn) {
    lookupBtn.addEventListener('click', handleBarcodeLookup);
  }
  
  // Auto-lookup on Enter in barcode input
  const barcodeInput = document.getElementById('barcodeInput');
  if (barcodeInput) {
    barcodeInput.addEventListener('keypress', (e) => {
      if (e.key === 'Enter') {
        e.preventDefault();
        handleBarcodeLookup();
      }
    });
  }
}

// Handle keyboard wedge barcode input
function handleBarcodeInput(e) {
  // Ignore if typing in an input field (except the barcode input)
  if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
    if (e.target.id !== 'barcodeInput') {
      return;
    }
  }
  
  // Clear previous timeout
  if (barcodeTimeout) {
    clearTimeout(barcodeTimeout);
  }
  
  // Add character to buffer
  barcodeBuffer += e.key;
  
  // Set timeout to process barcode
  barcodeTimeout = setTimeout(() => {
    if (barcodeBuffer.length > 3) { // Minimum barcode length
      processBarcode(barcodeBuffer);
    }
    barcodeBuffer = '';
  }, BARCODE_TIMEOUT);
}

// Process scanned barcode
async function processBarcode(barcode) {
  console.log('Barcode scanned:', barcode);
  
  // Visual feedback
  document.body.classList.add('scanning-active');
  setTimeout(() => {
    document.body.classList.remove('scanning-active');
  }, 500);
  
  // Lookup item by barcode
  try {
    const item = await window.api.inventory.getByBarcode(barcode);
    
    if (item) {
      showBarcodeResult(item);
    } else {
      // Check if it's a show barcode
      if (barcode.startsWith('SHOW-')) {
        handleShowBarcode(barcode);
      } else {
        showBarcodeError('Item not found', `No item found with barcode: ${barcode}`);
      }
    }
  } catch (error) {
    console.error('Barcode lookup error:', error);
    showBarcodeError('Lookup Error', error.message);
  }
}

// Open barcode scan modal
function openBarcodeScanModal() {
  const modal = new bootstrap.Modal(document.getElementById('barcodeScanModal'));
  modal.show();
  
  // Focus input when modal is shown
  document.getElementById('barcodeScanModal').addEventListener('shown.bs.modal', () => {
    document.getElementById('barcodeInput').focus();
  });
  
  // Clear input when modal is hidden
  document.getElementById('barcodeScanModal').addEventListener('hidden.bs.modal', () => {
    document.getElementById('barcodeInput').value = '';
    document.getElementById('barcodeScanResult').innerHTML = '';
  });
}

// Handle manual barcode lookup
async function handleBarcodeLookup() {
  const barcode = document.getElementById('barcodeInput').value.trim();
  
  if (!barcode) {
    return;
  }
  
  await processBarcode(barcode);
}

// Show barcode scan result
function showBarcodeResult(item) {
  const resultDiv = document.getElementById('barcodeScanResult');
  
  const availabilityBadge = item.quantity_available > 0 
    ? `<span class="badge badge-available">Available: ${item.quantity_available}</span>`
    : `<span class="badge badge-unavailable">Out of Stock</span>`;
  
  resultDiv.innerHTML = `
    <div class="alert alert-success">
      <h4 class="alert-title">Item Found</h4>
      <div class="mb-2">
        <strong>${item.name}</strong>
        ${item.description ? `<br><small>${item.description}</small>` : ''}
      </div>
      <div class="mb-2">
        ${availabilityBadge}
        ${item.serial_number ? `<span class="badge bg-info ms-2">Serial: ${item.serial_number}</span>` : ''}
      </div>
      <div class="btn-group mt-2" role="group">
        <button class="btn btn-sm btn-primary" onclick="viewItemDetails(${item.id})">
          <i class="ti ti-eye icon"></i> View Details
        </button>
        <button class="btn btn-sm btn-success" onclick="addItemToCurrentPullSheet(${item.id})">
          <i class="ti ti-plus icon"></i> Add to Pull Sheet
        </button>
      </div>
    </div>
  `;
}

// Show barcode error
function showBarcodeError(title, message) {
  const resultDiv = document.getElementById('barcodeScanResult');
  
  resultDiv.innerHTML = `
    <div class="alert alert-danger">
      <h4 class="alert-title">${title}</h4>
      <p>${message}</p>
    </div>
  `;
}

// Handle show barcode scan
function handleShowBarcode(barcode) {
  // Extract show ID from barcode (format: SHOW-{id}-{timestamp})
  const parts = barcode.split('-');
  if (parts.length >= 2) {
    const showId = parts[1];
    // Navigate to show details
    window.navigation.loadPage('shows');
    // In a full implementation, this would open the specific show
    console.log('Opening show:', showId);
  }
}

// View item details (placeholder)
function viewItemDetails(itemId) {
  window.navigation.loadPage('inventory');
  // In full implementation, would open item detail modal
  console.log('Viewing item:', itemId);
}

// Add item to current pull sheet (placeholder)
function addItemToCurrentPullSheet(itemId) {
  // In full implementation, would add to active pull sheet
  console.log('Adding item to pull sheet:', itemId);
  alert('This functionality will be implemented when editing pull sheets.');
}

// Make functions globally available
window.viewItemDetails = viewItemDetails;
window.addItemToCurrentPullSheet = addItemToCurrentPullSheet;

// Export functions
window.barcode = {
  init: initBarcodeScanning,
  process: processBarcode,
  openModal: openBarcodeScanModal
};
