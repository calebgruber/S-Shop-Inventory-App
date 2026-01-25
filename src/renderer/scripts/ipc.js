/**
 * IPC Communication Module
 * Handles all Inter-Process Communication with the main Electron process
 */

const { ipcRenderer } = require('electron');

// Inventory operations
const inventoryAPI = {
  getAll: () => ipcRenderer.invoke('inventory:getAll'),
  getById: (id) => ipcRenderer.invoke('inventory:getById', id),
  search: (query) => ipcRenderer.invoke('inventory:search', query),
  create: (item) => ipcRenderer.invoke('inventory:create', item),
  update: (id, item) => ipcRenderer.invoke('inventory:update', id, item),
  delete: (id) => ipcRenderer.invoke('inventory:delete', id),
  getByBarcode: (barcode) => ipcRenderer.invoke('inventory:getByBarcode', barcode),
  checkAvailability: (itemId, quantity) => ipcRenderer.invoke('inventory:checkAvailability', itemId, quantity)
};

// Show operations
const showsAPI = {
  getAll: () => ipcRenderer.invoke('shows:getAll'),
  getById: (id) => ipcRenderer.invoke('shows:getById', id),
  create: (show) => ipcRenderer.invoke('shows:create', show),
  update: (id, show) => ipcRenderer.invoke('shows:update', id, show),
  delete: (id) => ipcRenderer.invoke('shows:delete', id)
};

// Pull sheet operations
const pullsheetsAPI = {
  getAll: () => ipcRenderer.invoke('pullsheets:getAll'),
  getById: (id) => ipcRenderer.invoke('pullsheets:getById', id),
  getByShow: (showId) => ipcRenderer.invoke('pullsheets:getByShow', showId),
  create: (pullSheet) => ipcRenderer.invoke('pullsheets:create', pullSheet),
  update: (id, pullSheet) => ipcRenderer.invoke('pullsheets:update', id, pullSheet),
  delete: (id) => ipcRenderer.invoke('pullsheets:delete', id),
  addItem: (pullSheetId, itemId, quantity) => ipcRenderer.invoke('pullsheets:addItem', pullSheetId, itemId, quantity),
  removeItem: (pullSheetId, itemId) => ipcRenderer.invoke('pullsheets:removeItem', pullSheetId, itemId)
};

// Change order operations
const changeOrdersAPI = {
  getAll: () => ipcRenderer.invoke('changeorders:getAll'),
  getByShow: (showId) => ipcRenderer.invoke('changeorders:getByShow', showId),
  create: (changeOrder) => ipcRenderer.invoke('changeorders:create', changeOrder),
  updateStatus: (id, status) => ipcRenderer.invoke('changeorders:update', id, status)
};

// Return operations
const returnsAPI = {
  getAll: () => ipcRenderer.invoke('returns:getAll'),
  create: (returnData) => ipcRenderer.invoke('returns:create', returnData),
  complete: (id) => ipcRenderer.invoke('returns:complete', id)
};

// Reports and analytics
const reportsAPI = {
  getShortages: () => ipcRenderer.invoke('reports:getShortages'),
  getItemStatus: (itemId) => ipcRenderer.invoke('reports:getItemStatus', itemId),
  getActivityLog: (filters) => ipcRenderer.invoke('reports:getActivityLog', filters)
};

// PDF generation
const pdfAPI = {
  generatePullSheet: (pullSheetId) => ipcRenderer.invoke('pdf:generatePullSheet', pullSheetId),
  generateInventoryReport: (filters) => ipcRenderer.invoke('pdf:generateInventoryReport', filters),
  generateBarcodeLabels: (itemIds, options) => ipcRenderer.invoke('pdf:generateBarcodeLabels', itemIds, options)
};

// Barcode operations
const barcodeAPI = {
  generateMissing: () => ipcRenderer.invoke('barcode:generateMissing')
};

// Dialog helpers
const dialogAPI = {
  showError: (title, message) => ipcRenderer.invoke('dialog:showError', title, message),
  showMessage: (options) => ipcRenderer.invoke('dialog:showMessage', options)
};

// Update helpers
const updateAPI = {
  check: () => ipcRenderer.invoke('update:check'),
  getVersion: () => ipcRenderer.invoke('update:getVersion')
};

// Backup helpers
const backupAPI = {
  create: () => ipcRenderer.invoke('backup:create'),
  list: () => ipcRenderer.invoke('backup:list'),
  export: () => ipcRenderer.invoke('backup:export')
};

// Export all APIs
window.api = {
  inventory: inventoryAPI,
  shows: showsAPI,
  pullsheets: pullsheetsAPI,
  changeOrders: changeOrdersAPI,
  returns: returnsAPI,
  reports: reportsAPI,
  pdf: pdfAPI,
  barcode: barcodeAPI,
  dialog: dialogAPI,
  update: updateAPI,
  backup: backupAPI
};

// Listen for update status messages
ipcRenderer.on('update-status', (event, message) => {
  console.log('Update status:', message);
  // Show update status in UI if needed
  if (window.app && window.app.showUpdateStatus) {
    window.app.showUpdateStatus(message);
  }
});
