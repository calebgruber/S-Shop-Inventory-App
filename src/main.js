/**
 * Main Electron Process
 * Manages application lifecycle, window creation, and IPC communication
 */

// Handle Squirrel events for Windows installer
if (require('electron-squirrel-startup')) {
  process.exit(0);
}

const { app, BrowserWindow, ipcMain, dialog } = require('electron');
const path = require('path');
const Database = require('./database/db');
const { generatePDF } = require('./utils/pdfGenerator');
const { logAction } = require('./utils/logger');
const { createBackup, listBackups, exportDatabase } = require('./utils/backup');
const { initAutoUpdater, checkForUpdates, getCurrentVersion } = require('./utils/autoUpdater');

let mainWindow;
let database;

/**
 * Create the main application window
 */
function createWindow() {
  mainWindow = new BrowserWindow({
    width: 1400,
    height: 900,
    minWidth: 1024,
    minHeight: 768,
    webPreferences: {
      nodeIntegration: true,
      contextIsolation: false,
      enableRemoteModule: true
    },
    icon: path.join(__dirname, '../assets/icon.png'),
    show: false
  });

  mainWindow.loadFile(path.join(__dirname, 'renderer/index.html'));

  // Show window when ready to avoid visual flash
  mainWindow.once('ready-to-show', () => {
    mainWindow.show();
    
    // Initialize auto-updater after window is shown
    initAutoUpdater(mainWindow);
  });

  // Open DevTools in development mode
  if (process.argv.includes('--dev')) {
    mainWindow.webContents.openDevTools();
  }

  mainWindow.on('closed', () => {
    mainWindow = null;
  });
}

/**
 * Initialize the database
 */
function initDatabase() {
  try {
    database = new Database();
    database.init();
    logAction('SYSTEM', 'Application started');
    console.log('Database initialized successfully');
  } catch (error) {
    console.error('Failed to initialize database:', error);
    dialog.showErrorBox('Database Error', 'Failed to initialize database: ' + error.message);
    app.quit();
  }
}

// App lifecycle events
app.whenReady().then(() => {
  initDatabase();
  createWindow();

  app.on('activate', () => {
    if (BrowserWindow.getAllWindows().length === 0) {
      createWindow();
    }
  });
});

app.on('window-all-closed', () => {
  if (process.platform !== 'darwin') {
    if (database) {
      database.close();
    }
    app.quit();
  }
});

app.on('before-quit', () => {
  if (database) {
    database.close();
  }
  logAction('SYSTEM', 'Application closed');
});

// IPC Handlers for Database Operations

// Inventory Management
ipcMain.handle('inventory:getAll', async () => {
  return database.getAllItems();
});

ipcMain.handle('inventory:getById', async (event, id) => {
  return database.getItemById(id);
});

ipcMain.handle('inventory:search', async (event, query) => {
  return database.searchItems(query);
});

ipcMain.handle('inventory:create', async (event, item) => {
  const result = database.createItem(item);
  logAction('INVENTORY', `Created item: ${item.name}`, item);
  return result;
});

ipcMain.handle('inventory:update', async (event, id, item) => {
  const result = database.updateItem(id, item);
  logAction('INVENTORY', `Updated item ID: ${id}`, item);
  return result;
});

ipcMain.handle('inventory:delete', async (event, id) => {
  const result = database.deleteItem(id);
  logAction('INVENTORY', `Deleted item ID: ${id}`);
  return result;
});

ipcMain.handle('inventory:getByBarcode', async (event, barcode) => {
  return database.getItemByBarcode(barcode);
});

ipcMain.handle('inventory:checkAvailability', async (event, itemId, quantity) => {
  return database.checkItemAvailability(itemId, quantity);
});

// Show Management
ipcMain.handle('shows:getAll', async () => {
  return database.getAllShows();
});

ipcMain.handle('shows:getById', async (event, id) => {
  return database.getShowById(id);
});

ipcMain.handle('shows:create', async (event, show) => {
  const result = database.createShow(show);
  logAction('SHOW', `Created show: ${show.name}`, show);
  return result;
});

ipcMain.handle('shows:update', async (event, id, show) => {
  const result = database.updateShow(id, show);
  logAction('SHOW', `Updated show ID: ${id}`, show);
  return result;
});

ipcMain.handle('shows:delete', async (event, id) => {
  const result = database.deleteShow(id);
  logAction('SHOW', `Deleted show ID: ${id}`);
  return result;
});

// Theatre Management
ipcMain.handle('theatres:getAll', async () => {
  return database.getAllTheatres();
});

ipcMain.handle('theatres:getById', async (event, id) => {
  return database.getTheatreById(id);
});

ipcMain.handle('theatres:create', async (event, theatre) => {
  const result = database.createTheatre(theatre);
  logAction('THEATRE', `Created theatre: ${theatre.name}`, theatre);
  return result;
});

ipcMain.handle('theatres:update', async (event, id, theatre) => {
  const result = database.updateTheatre(id, theatre);
  logAction('THEATRE', `Updated theatre ID: ${id}`, theatre);
  return result;
});

ipcMain.handle('theatres:delete', async (event, id) => {
  const result = database.deleteTheatre(id);
  if (result.success) {
    logAction('THEATRE', `Deleted theatre ID: ${id}`);
  }
  return result;
});

// Pull Sheet Management
ipcMain.handle('pullsheets:getAll', async () => {
  return database.getAllPullSheets();
});

ipcMain.handle('pullsheets:getById', async (event, id) => {
  return database.getPullSheetById(id);
});

ipcMain.handle('pullsheets:getByShow', async (event, showId) => {
  return database.getPullSheetsByShow(showId);
});

ipcMain.handle('pullsheets:create', async (event, pullSheet) => {
  const result = database.createPullSheet(pullSheet);
  logAction('PULLSHEET', `Created pull sheet for show ID: ${pullSheet.show_id}`, pullSheet);
  return result;
});

ipcMain.handle('pullsheets:update', async (event, id, pullSheet) => {
  const result = database.updatePullSheet(id, pullSheet);
  logAction('PULLSHEET', `Updated pull sheet ID: ${id}`, pullSheet);
  return result;
});

ipcMain.handle('pullsheets:delete', async (event, id) => {
  const result = database.deletePullSheet(id);
  logAction('PULLSHEET', `Deleted pull sheet ID: ${id}`);
  return result;
});

ipcMain.handle('pullsheets:addItem', async (event, pullSheetId, itemId, quantity) => {
  const result = database.addItemToPullSheet(pullSheetId, itemId, quantity);
  logAction('PULLSHEET', `Added item ${itemId} to pull sheet ${pullSheetId}`);
  return result;
});

ipcMain.handle('pullsheets:removeItem', async (event, pullSheetId, itemId) => {
  const result = database.removeItemFromPullSheet(pullSheetId, itemId);
  logAction('PULLSHEET', `Removed item ${itemId} from pull sheet ${pullSheetId}`);
  return result;
});

// Change Order Management
ipcMain.handle('changeorders:getAll', async () => {
  return database.getAllChangeOrders();
});

ipcMain.handle('changeorders:getByShow', async (event, showId) => {
  return database.getChangeOrdersByShow(showId);
});

ipcMain.handle('changeorders:create', async (event, changeOrder) => {
  const result = database.createChangeOrder(changeOrder);
  logAction('CHANGEORDER', `Created change order for show ID: ${changeOrder.show_id}`, changeOrder);
  return result;
});

ipcMain.handle('changeorders:update', async (event, id, status) => {
  const result = database.updateChangeOrderStatus(id, status);
  logAction('CHANGEORDER', `Updated change order ID: ${id} to status: ${status}`);
  return result;
});

// Return Management
ipcMain.handle('returns:getAll', async () => {
  return database.getAllReturns();
});

ipcMain.handle('returns:create', async (event, returnData) => {
  const result = database.createReturn(returnData);
  logAction('RETURN', `Created return for pull sheet ID: ${returnData.pull_sheet_id}`, returnData);
  return result;
});

ipcMain.handle('returns:complete', async (event, id) => {
  const result = database.completeReturn(id);
  logAction('RETURN', `Completed return ID: ${id}`);
  return result;
});

// Reports and Analytics
ipcMain.handle('reports:getShortages', async () => {
  return database.getShortages();
});

ipcMain.handle('reports:getItemStatus', async (event, itemId) => {
  return database.getItemStatus(itemId);
});

ipcMain.handle('reports:getActivityLog', async (event, filters) => {
  return database.getActivityLog(filters);
});

ipcMain.handle('reports:getItemsByLocation', async (event, theatreId) => {
  return database.getItemsByLocation(theatreId);
});

// PDF Generation
ipcMain.handle('pdf:generatePullSheet', async (event, pullSheetId) => {
  try {
    const pullSheet = database.getPullSheetById(pullSheetId);
    const filePath = await generatePDF('pullsheet', pullSheet);
    logAction('PDF', `Generated pull sheet PDF for ID: ${pullSheetId}`);
    return { success: true, filePath };
  } catch (error) {
    console.error('PDF generation error:', error);
    return { success: false, error: error.message };
  }
});

ipcMain.handle('pdf:generateInventoryReport', async (event, filters) => {
  try {
    const items = database.getAllItems(filters);
    const filePath = await generatePDF('inventory', { items });
    logAction('PDF', 'Generated inventory report PDF');
    return { success: true, filePath };
  } catch (error) {
    console.error('PDF generation error:', error);
    return { success: false, error: error.message };
  }
});

ipcMain.handle('pdf:generateBarcodeLabels', async (event, itemIds, options) => {
  try {
    const items = database.getItemsForLabels({ ids: itemIds });
    const filePath = await generatePDF('labels', { items, options });
    logAction('PDF', `Generated barcode labels PDF for ${items.length} items`);
    return { success: true, filePath, count: items.length };
  } catch (error) {
    console.error('PDF generation error:', error);
    return { success: false, error: error.message };
  }
});

ipcMain.handle('pdf:generateLocationReport', async (event, theatreId) => {
  try {
    const theatres = theatreId ? [database.getTheatreById(theatreId)] : database.getAllTheatres();
    const items = database.getItemsByLocation(theatreId);
    const filePath = await generatePDF('location', { theatres, items });
    logAction('PDF', `Generated location report PDF`);
    return { success: true, filePath };
  } catch (error) {
    console.error('PDF generation error:', error);
    return { success: false, error: error.message };
  }
});

// Barcode Generation
ipcMain.handle('barcode:generateMissing', async () => {
  try {
    const result = database.generateMissingBarcodes();
    logAction('BARCODE', `Generated ${result.updated} missing barcodes`);
    return { success: true, ...result };
  } catch (error) {
    console.error('Barcode generation error:', error);
    return { success: false, error: error.message };
  }
});

// Dialog handlers
ipcMain.handle('dialog:showError', async (event, title, message) => {
  dialog.showErrorBox(title, message);
});

ipcMain.handle('dialog:showMessage', async (event, options) => {
  return dialog.showMessageBox(mainWindow, options);
});

// Backup handlers
ipcMain.handle('backup:create', async () => {
  return createBackup();
});

ipcMain.handle('backup:list', async () => {
  return listBackups();
});

ipcMain.handle('backup:export', async () => {
  return exportDatabase(mainWindow);
});

// Update handlers
ipcMain.handle('update:check', async () => {
  checkForUpdates(true);
});

ipcMain.handle('update:getVersion', async () => {
  return {
    current: app.getVersion(),
    name: app.getName()
  };
});

console.log('Main process initialized');
