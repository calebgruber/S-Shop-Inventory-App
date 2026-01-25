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
  
  // Check if it's a show/pull sheet barcode
  if (barcode.startsWith('SHOW-') || barcode.startsWith('PULL-')) {
    handlePullSheetBarcode(barcode);
    return;
  }
  
  // Lookup item by barcode
  try {
    const item = await window.api.inventory.getByBarcode(barcode);
    
    if (item) {
      showBarcodeResult(item);
    } else {
      showBarcodeError('Item not found', `No item found with barcode: ${barcode}`);
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
  
  // Focus and select input when modal is shown
  document.getElementById('barcodeScanModal').addEventListener('shown.bs.modal', () => {
    const input = document.getElementById('barcodeInput');
    input.focus();
    input.select();
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
  
  const quantityOut = item.quantity_total - item.quantity_available;
  const availableClass = item.quantity_available > 0 ? 'text-success' : 'text-danger';
  
  resultDiv.innerHTML = `
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">${item.name}</h3>
      </div>
      <div class="card-body">
        <div class="row mb-3">
          <div class="col-6">
            <strong>Category:</strong><br>
            ${item.category || 'N/A'}
          </div>
          <div class="col-6">
            <strong>Location:</strong><br>
            ${item.location || 'N/A'}
          </div>
        </div>
        <div class="row mb-3">
          <div class="col-4 text-center">
            <div class="text-muted small">Total</div>
            <div class="h3">${item.quantity_total || 0}</div>
          </div>
          <div class="col-4 text-center">
            <div class="text-muted small">Available</div>
            <div class="h3 ${availableClass}">${item.quantity_available || 0}</div>
          </div>
          <div class="col-4 text-center">
            <div class="text-muted small">Checked Out</div>
            <div class="h3 text-info">${quantityOut}</div>
          </div>
        </div>
        ${item.description ? `<p class="text-muted">${item.description}</p>` : ''}
        ${item.barcode ? `<div class="text-mono small">Barcode: ${item.barcode}</div>` : ''}
      </div>
      <div class="card-footer">
        <button class="btn btn-primary" onclick="closeBarcodeScanAndViewItem(${item.id})">
          <i class="ti ti-eye icon"></i> View Full Details
        </button>
      </div>
    </div>
  `;
}

// Close barcode modal and view item details
async function closeBarcodeScanAndViewItem(itemId) {
  // Close the barcode modal
  const modal = bootstrap.Modal.getInstance(document.getElementById('barcodeScanModal'));
  if (modal) {
    modal.hide();
  }
  
  // Navigate to inventory page with callback to open item details
  await window.navigation.loadPage('inventory', () => {
    callIfExists('editInventoryItem', itemId);
  });
}

window.closeBarcodeScanAndViewItem = closeBarcodeScanAndViewItem;

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

// Handle pull sheet barcode scan  
async function handlePullSheetBarcode(barcode) {
  try {
    // Try to get pull sheet by barcode using the API
    const pullSheet = await window.api.pullsheets.getByBarcode(barcode);
    
    if (pullSheet) {
      // Close any open modals
      const modal = bootstrap.Modal.getInstance(document.getElementById('barcodeScanModal'));
      if (modal) {
        modal.hide();
      }
      
      // Navigate to pull sheets page and show details
      await window.navigation.loadPage('pullsheets', () => {
        if (window.viewPullSheet) {
          window.viewPullSheet(pullSheet.id);
        }
      });
    } else {
      showBarcodeError('Pull Sheet Not Found', `No pull sheet found with barcode: ${barcode}`);
    }
  } catch (error) {
    console.error('Error looking up pull sheet:', error);
    showBarcodeError('Lookup Error', error.message);
  }
}

// Navigate to pull sheet details (shared utility)
async function navigateToPullSheet(pullSheetId) {
  await window.navigation.loadPage('pullsheets', () => {
    callIfExists('viewPullSheet', pullSheetId);
  });
}

// Utility to call a function only if it exists
function callIfExists(functionName, ...args) {
  if (typeof window[functionName] === 'function') {
    window[functionName](...args);
  } else {
    console.error(`${functionName} function not available`);
  }
}

// Add item to current pull sheet (placeholder)
function addItemToCurrentPullSheet(itemId) {
  // In full implementation, would add to active pull sheet
  console.log('Adding item to pull sheet:', itemId);
  alert('This functionality will be implemented when editing pull sheets.');
}

// Make functions globally available
window.addItemToCurrentPullSheet = addItemToCurrentPullSheet;

// Export functions
window.barcode = {
  init: initBarcodeScanning,
  process: processBarcode,
  openModal: openBarcodeScanModal,
  navigateToPullSheet: navigateToPullSheet
};
