/**
 * Backup Utility
 * Handles database backup, restore, and export operations
 */

const fs = require('fs');
const path = require('path');
const { app, dialog } = require('electron');

/**
 * Create a backup of the database
 */
function createBackup() {
  const userDataPath = app.getPath('userData');
  const dbPath = path.join(userDataPath, 'inventory.db');
  const backupsDir = path.join(userDataPath, 'backups');
  
  if (!fs.existsSync(backupsDir)) {
    fs.mkdirSync(backupsDir, { recursive: true });
  }
  
  const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
  const backupFilename = `inventory_backup_${timestamp}.db`;
  const backupPath = path.join(backupsDir, backupFilename);
  
  try {
    fs.copyFileSync(dbPath, backupPath);
    return { success: true, path: backupPath };
  } catch (error) {
    return { success: false, error: error.message };
  }
}

/**
 * List all available backups
 */
function listBackups() {
  const userDataPath = app.getPath('userData');
  const backupsDir = path.join(userDataPath, 'backups');
  
  if (!fs.existsSync(backupsDir)) {
    return [];
  }
  
  const files = fs.readdirSync(backupsDir);
  return files
    .filter(f => f.endsWith('.db'))
    .map(f => ({
      name: f,
      path: path.join(backupsDir, f),
      size: fs.statSync(path.join(backupsDir, f)).size,
      created: fs.statSync(path.join(backupsDir, f)).mtime
    }))
    .sort((a, b) => b.created - a.created);
}

/**
 * Restore database from backup
 */
function restoreBackup(backupPath) {
  const userDataPath = app.getPath('userData');
  const dbPath = path.join(userDataPath, 'inventory.db');
  
  try {
    // Create a backup of current database before restoring
    const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
    const preRestoreBackup = path.join(userDataPath, 'backups', `pre_restore_${timestamp}.db`);
    
    if (fs.existsSync(dbPath)) {
      fs.copyFileSync(dbPath, preRestoreBackup);
    }
    
    // Restore the backup
    fs.copyFileSync(backupPath, dbPath);
    
    return { success: true, preRestoreBackup };
  } catch (error) {
    return { success: false, error: error.message };
  }
}

/**
 * Export database to a user-selected location
 */
async function exportDatabase(mainWindow) {
  const userDataPath = app.getPath('userData');
  const dbPath = path.join(userDataPath, 'inventory.db');
  
  const result = await dialog.showSaveDialog(mainWindow, {
    title: 'Export Database',
    defaultPath: `inventory_export_${new Date().toISOString().split('T')[0]}.db`,
    filters: [
      { name: 'Database Files', extensions: ['db'] },
      { name: 'All Files', extensions: ['*'] }
    ]
  });
  
  if (!result.canceled && result.filePath) {
    try {
      fs.copyFileSync(dbPath, result.filePath);
      return { success: true, path: result.filePath };
    } catch (error) {
      return { success: false, error: error.message };
    }
  }
  
  return { success: false, canceled: true };
}

/**
 * Import database from a user-selected file
 */
async function importDatabase(mainWindow) {
  const result = await dialog.showOpenDialog(mainWindow, {
    title: 'Import Database',
    filters: [
      { name: 'Database Files', extensions: ['db'] },
      { name: 'All Files', extensions: ['*'] }
    ],
    properties: ['openFile']
  });
  
  if (!result.canceled && result.filePaths.length > 0) {
    return restoreBackup(result.filePaths[0]);
  }
  
  return { success: false, canceled: true };
}

/**
 * Delete old backups, keeping only the most recent N backups
 */
function cleanupOldBackups(keepCount = 10) {
  const backups = listBackups();
  
  if (backups.length <= keepCount) {
    return { deleted: 0 };
  }
  
  const toDelete = backups.slice(keepCount);
  let deleted = 0;
  
  toDelete.forEach(backup => {
    try {
      fs.unlinkSync(backup.path);
      deleted++;
    } catch (error) {
      console.error(`Failed to delete backup: ${backup.name}`, error);
    }
  });
  
  return { deleted };
}

module.exports = {
  createBackup,
  listBackups,
  restoreBackup,
  exportDatabase,
  importDatabase,
  cleanupOldBackups
};
