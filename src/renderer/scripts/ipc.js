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

// Theatre operations
const theatresAPI = {
  getAll: () => ipcRenderer.invoke('theatres:getAll'),
  getById: (id) => ipcRenderer.invoke('theatres:getById', id),
  create: (theatre) => ipcRenderer.invoke('theatres:create', theatre),
  update: (id, theatre) => ipcRenderer.invoke('theatres:update', id, theatre),
  delete: (id) => ipcRenderer.invoke('theatres:delete', id)
};

// Pull sheet operations
const pullsheetsAPI = {
  getAll: () => ipcRenderer.invoke('pullsheets:getAll'),
  getById: (id) => ipcRenderer.invoke('pullsheets:getById', id),
  getByShow: (showId) => ipcRenderer.invoke('pullsheets:getByShow', showId),
  getByBarcode: (barcode) => ipcRenderer.invoke('pullsheets:getByBarcode', barcode),
  create: (pullSheet) => ipcRenderer.invoke('pullsheets:create', pullSheet),
  update: (id, pullSheet) => ipcRenderer.invoke('pullsheets:update', id, pullSheet),
  delete: (id) => ipcRenderer.invoke('pullsheets:delete', id),
  addItem: (pullSheetId, itemId, quantity) => ipcRenderer.invoke('pullsheets:addItem', pullSheetId, itemId, quantity),
  removeItem: (pullSheetId, itemId) => ipcRenderer.invoke('pullsheets:removeItem', pullSheetId, itemId),
  updateItem: (pullSheetId, itemId, quantity, status) => ipcRenderer.invoke('pullsheets:updateItem', pullSheetId, itemId, quantity, status),
  finalize: (pullSheetId, pulledBy) => ipcRenderer.invoke('pullsheets:finalize', pullSheetId, pulledBy)
};

// Change order operations
const changeOrdersAPI = {
  getAll: () => ipcRenderer.invoke('changeorders:getAll'),
  getById: (id) => ipcRenderer.invoke('changeorders:getById', id),
  getByShow: (showId) => ipcRenderer.invoke('changeorders:getByShow', showId),
  getByPullSheet: (pullSheetId) => ipcRenderer.invoke('changeorders:getByPullSheet', pullSheetId),
  create: (changeOrder) => ipcRenderer.invoke('changeorders:create', changeOrder),
  addItem: (changeOrderId, itemId, quantityChange, action) => ipcRenderer.invoke('changeorders:addItem', changeOrderId, itemId, quantityChange, action),
  process: (changeOrderId) => ipcRenderer.invoke('changeorders:process', changeOrderId),
  updateStatus: (id, status) => ipcRenderer.invoke('changeorders:update', id, status)
};

// Return operations
const returnsAPI = {
  getAll: () => ipcRenderer.invoke('returns:getAll'),
  getById: (id) => ipcRenderer.invoke('returns:getById', id),
  getByPullSheet: (pullSheetId) => ipcRenderer.invoke('returns:getByPullSheet', pullSheetId),
  create: (returnData) => ipcRenderer.invoke('returns:create', returnData),
  addItem: (returnId, itemId, quantity, condition, notes) => ipcRenderer.invoke('returns:addItem', returnId, itemId, quantity, condition, notes),
  complete: (id) => ipcRenderer.invoke('returns:complete', id)
};

// Reports and analytics
const reportsAPI = {
  getShortages: () => ipcRenderer.invoke('reports:getShortages'),
  getLowStock: (threshold) => ipcRenderer.invoke('reports:getLowStock', threshold),
  getItemsOut: () => ipcRenderer.invoke('reports:getItemsOut'),
  getShowEquipment: (showId) => ipcRenderer.invoke('reports:getShowEquipment', showId),
  getDashboardStats: () => ipcRenderer.invoke('reports:getDashboardStats'),
  getItemStatus: (itemId) => ipcRenderer.invoke('reports:getItemStatus', itemId),
  getActivityLog: (filters) => ipcRenderer.invoke('reports:getActivityLog', filters),
  getItemsByLocation: (theatreId) => ipcRenderer.invoke('reports:getItemsByLocation', theatreId)
};

// PDF generation
const pdfAPI = {
  generatePullSheet: (pullSheetId) => ipcRenderer.invoke('pdf:generatePullSheet', pullSheetId),
  generateInventoryReport: (filters) => ipcRenderer.invoke('pdf:generateInventoryReport', filters),
  generateBarcodeLabels: (itemIds, options) => ipcRenderer.invoke('pdf:generateBarcodeLabels', itemIds, options),
  generateLocationReport: (theatreId) => ipcRenderer.invoke('pdf:generateLocationReport', theatreId)
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

// Category operations
const categoriesAPI = {
  getAll: () => ipcRenderer.invoke('categories:getAll'),
  getById: (id) => ipcRenderer.invoke('categories:getById', id),
  create: (category) => ipcRenderer.invoke('categories:create', category),
  update: (id, category) => ipcRenderer.invoke('categories:update', id, category),
  delete: (id) => ipcRenderer.invoke('categories:delete', id)
};

// Settings operations
const settingsAPI = {
  get: (key) => ipcRenderer.invoke('settings:get', key),
  set: (key, value) => ipcRenderer.invoke('settings:set', key, value),
  getAll: () => ipcRenderer.invoke('settings:getAll')
};

// Export all APIs
window.api = {
  inventory: inventoryAPI,
  shows: showsAPI,
  theatres: theatresAPI,
  pullsheets: pullsheetsAPI,
  changeOrders: changeOrdersAPI,
  returns: returnsAPI,
  reports: reportsAPI,
  pdf: pdfAPI,
  barcode: barcodeAPI,
  dialog: dialogAPI,
  update: updateAPI,
  backup: backupAPI,
  categories: categoriesAPI,
  settings: settingsAPI
};

// Listen for update status messages
ipcRenderer.on('update-status', (event, message) => {
  console.log('Update status:', message);
  // Show update status in UI if needed
  if (window.app && window.app.showUpdateStatus) {
    window.app.showUpdateStatus(message);
  }
});
