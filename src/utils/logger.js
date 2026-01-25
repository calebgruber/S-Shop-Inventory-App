/**
 * Logger Utility
 * Handles activity logging for audit trails
 */

const fs = require('fs');
const path = require('path');
const { app } = require('electron');

/**
 * Log an action to the database and optionally to file
 */
function logAction(actionType, description, data = null) {
  const logEntry = {
    timestamp: new Date().toISOString(),
    type: actionType,
    description: description,
    data: data
  };

  // Console log for development
  console.log(`[${actionType}] ${description}`, data || '');

  // Additional file logging could be added here if needed
  // For now, database logging is handled in the main process
  
  return logEntry;
}

/**
 * Export logs to file
 */
function exportLogs(logs, filename) {
  const userDataPath = app.getPath('userData');
  const logsDir = path.join(userDataPath, 'exports');
  
  if (!fs.existsSync(logsDir)) {
    fs.mkdirSync(logsDir, { recursive: true });
  }

  const filePath = path.join(logsDir, filename);
  fs.writeFileSync(filePath, JSON.stringify(logs, null, 2));
  
  return filePath;
}

module.exports = {
  logAction,
  exportLogs
};
