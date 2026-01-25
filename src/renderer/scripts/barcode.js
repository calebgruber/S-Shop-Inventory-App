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
async function showBarcodeResult(item) {
  // Close the barcode modal
  const modal = bootstrap.Modal.getInstance(document.getElementById('barcodeScanModal'));
  if (modal) {
    modal.hide();
  }
  
  // Navigate to inventory page with callback to open item details
  await window.navigation.loadPage('inventory', () => {
    window.editInventoryItem(item.id);
  });
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

// Handle pull sheet barcode scan  
async function handlePullSheetBarcode(barcode) {
  // Extract pull sheet ID from barcode (format: SHOW-{id} or PULL-{id})
  const parts = barcode.split('-');
  if (parts.length >= 2) {
    const pullSheetId = parts[1];
    // Navigate to pull sheets page with callback to open details
    await window.navigation.loadPage('pullsheets', () => {
      if (typeof window.viewPullSheet === 'function') {
        window.viewPullSheet(pullSheetId);
      } else {
        console.error('viewPullSheet function not available');
      }
    });
    console.log('Opening pull sheet:', pullSheetId);
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
  openModal: openBarcodeScanModal
};
