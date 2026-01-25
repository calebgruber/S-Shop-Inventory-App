/**
 * Auto-Updater Module
 * Handles automatic updates from GitHub releases
 */

const { autoUpdater } = require('electron-updater');
const { dialog } = require('electron');
const log = require('electron-log');

// Configure logging
autoUpdater.logger = log;
autoUpdater.logger.transports.file.level = 'info';

// Configure update behavior
autoUpdater.autoDownload = false; // Don't auto-download, ask user first
autoUpdater.autoInstallOnAppQuit = true; // Install when app closes

let mainWindow = null;
let updateCheckInProgress = false;

/**
 * Initialize auto-updater
 */
function initAutoUpdater(window) {
  mainWindow = window;
  
  // Set up event listeners
  setupEventListeners();
  
  // Check for updates on startup (after a delay)
  setTimeout(() => {
    checkForUpdates(false); // Silent check on startup
  }, 10000); // Wait 10 seconds after startup
  
  log.info('Auto-updater initialized');
}

/**
 * Set up auto-updater event listeners
 */
function setupEventListeners() {
  // Checking for updates
  autoUpdater.on('checking-for-update', () => {
    log.info('Checking for updates...');
    sendStatusToWindow('Checking for updates...');
  });

  // Update available
  autoUpdater.on('update-available', (info) => {
    log.info('Update available:', info.version);
    
    // Ask user if they want to download
    dialog.showMessageBox(mainWindow, {
      type: 'info',
      title: 'Update Available',
      message: `A new version (${info.version}) is available!`,
      detail: 'Would you like to download it now? The update will be installed when you close the app.',
      buttons: ['Download', 'Later'],
      defaultId: 0,
      cancelId: 1
    }).then(result => {
      if (result.response === 0) {
        // User wants to download
        autoUpdater.downloadUpdate();
        sendStatusToWindow('Downloading update...');
      }
    });
  });

  // No update available
  autoUpdater.on('update-not-available', (info) => {
    log.info('Update not available:', info.version);
    sendStatusToWindow('App is up to date');
  });

  // Error occurred
  autoUpdater.on('error', (err) => {
    log.error('Error in auto-updater:', err);
    sendStatusToWindow('Error checking for updates');
    updateCheckInProgress = false;
  });

  // Download progress
  autoUpdater.on('download-progress', (progressObj) => {
    const message = `Download speed: ${progressObj.bytesPerSecond} - Downloaded ${progressObj.percent}%`;
    log.info(message);
    sendStatusToWindow(message);
  });

  // Update downloaded
  autoUpdater.on('update-downloaded', (info) => {
    log.info('Update downloaded:', info.version);
    
    // Notify user
    dialog.showMessageBox(mainWindow, {
      type: 'info',
      title: 'Update Ready',
      message: `Version ${info.version} has been downloaded`,
      detail: 'The update will be installed when you close the application. You can continue working - the update will be applied on next restart.',
      buttons: ['Restart Now', 'Later'],
      defaultId: 1,
      cancelId: 1
    }).then(result => {
      if (result.response === 0) {
        // User wants to restart now
        autoUpdater.quitAndInstall();
      }
    });
    
    updateCheckInProgress = false;
  });
}

/**
 * Check for updates manually
 */
function checkForUpdates(showNoUpdateDialog = true) {
  if (updateCheckInProgress) {
    log.info('Update check already in progress');
    return;
  }
  
  updateCheckInProgress = true;
  
  autoUpdater.checkForUpdates()
    .then(result => {
      if (showNoUpdateDialog && !result.updateInfo.version) {
        dialog.showMessageBox(mainWindow, {
          type: 'info',
          title: 'No Updates',
          message: 'You are running the latest version',
          buttons: ['OK']
        });
      }
    })
    .catch(err => {
      log.error('Error checking for updates:', err);
      if (showNoUpdateDialog) {
        dialog.showMessageBox(mainWindow, {
          type: 'error',
          title: 'Update Check Failed',
          message: 'Could not check for updates',
          detail: err.message,
          buttons: ['OK']
        });
      }
      updateCheckInProgress = false;
    });
}

/**
 * Send status message to renderer window
 */
function sendStatusToWindow(text) {
  if (mainWindow && mainWindow.webContents) {
    mainWindow.webContents.send('update-status', text);
  }
}

/**
 * Get current version
 */
function getCurrentVersion() {
  return autoUpdater.currentVersion;
}

module.exports = {
  initAutoUpdater,
  checkForUpdates,
  getCurrentVersion
};
