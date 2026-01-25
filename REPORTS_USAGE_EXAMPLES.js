/**
 * USAGE EXAMPLES for reports-complete.js
 * 
 * This file demonstrates how to use the complete reports module
 */

// ============================================================================
// EXAMPLE 1: Opening Reports Programmatically
// ============================================================================

// Open inventory report with default filters
openInventoryReport();

// Open location report for a specific theatre
async function viewTheatreEquipment(theatreId) {
  await openLocationReport();
  // After modal opens, set the filter
  setTimeout(() => {
    document.getElementById('locationTheatreFilter').value = theatreId;
    loadLocationReport();
  }, 500);
}

// Open low stock report with custom threshold
async function checkLowStock(threshold = 3) {
  await openLowStockReport();
  setTimeout(() => {
    document.getElementById('lowStockThreshold').value = threshold;
    loadLowStockReport();
  }, 500);
}

// ============================================================================
// EXAMPLE 2: Customizing Report Display
// ============================================================================

// Add custom styling to reports
function customizeReportStyles() {
  const style = document.createElement('style');
  style.textContent = `
    .report-table-custom {
      font-size: 0.9rem;
    }
    .report-badge-custom {
      font-weight: bold;
    }
  `;
  document.head.appendChild(style);
}

// ============================================================================
// EXAMPLE 3: Integrating with Other Modules
// ============================================================================

// From inventory module: View items report filtered by category
async function viewCategoryReport(category) {
  await openInventoryReport();
  setTimeout(() => {
    document.getElementById('inventoryCategoryFilter').value = category;
    loadInventoryReport();
  }, 500);
}

// From shows module: View equipment for a specific show
async function viewShowReport(showId) {
  await openShowEquipmentReport();
  setTimeout(() => {
    document.getElementById('showEquipmentFilter').value = showId;
    loadShowEquipmentReport();
  }, 500);
}

// From pullsheets module: View all checked out items
function viewAllCheckouts() {
  openItemsOutReport();
}

// ============================================================================
// EXAMPLE 4: Exporting Reports
// ============================================================================

// Export current inventory with filters applied
async function exportFilteredInventory(status, category) {
  await openInventoryReport();
  setTimeout(async () => {
    document.getElementById('inventoryStatusFilter').value = status;
    document.getElementById('inventoryCategoryFilter').value = category;
    await loadInventoryReport();
    
    // Wait for load then export
    setTimeout(() => {
      exportInventoryPDF();
    }, 1000);
  }, 500);
}

// Export all reports at once (for end-of-day reporting)
async function exportAllReports() {
  const reports = [
    { name: 'inventory', fn: exportInventoryPDF },
    { name: 'location', fn: exportLocationPDF },
    { name: 'lowStock', fn: exportLowStockPDF },
    { name: 'itemsOut', fn: exportItemsOutPDF },
    { name: 'activityLog', fn: exportActivityLogPDF }
  ];
  
  for (const report of reports) {
    console.log(`Exporting ${report.name}...`);
    await report.fn();
    // Wait between exports
    await new Promise(resolve => setTimeout(resolve, 2000));
  }
  
  console.log('All reports exported!');
}

// ============================================================================
// EXAMPLE 5: Custom Report Filters
// ============================================================================

// View items checked out in the last 7 days
async function viewRecentCheckouts() {
  await openActivityLogReport();
  setTimeout(() => {
    const today = new Date();
    const weekAgo = new Date(today - 7 * 24 * 60 * 60 * 1000);
    
    document.getElementById('activityActionFilter').value = 'CHECKOUT';
    document.getElementById('activityDateFrom').value = weekAgo.toISOString().split('T')[0];
    document.getElementById('activityDateTo').value = today.toISOString().split('T')[0];
    
    loadActivityLogReport(1);
  }, 500);
}

// ============================================================================
// EXAMPLE 6: Dashboard Integration
// ============================================================================

// Add quick links to dashboard
function addReportQuickLinks() {
  const dashboard = document.getElementById('page-content');
  if (!dashboard) return;
  
  const quickLinks = `
    <div class="card mb-3">
      <div class="card-header">
        <h3 class="card-title">Quick Reports</h3>
      </div>
      <div class="card-body">
        <div class="btn-group" role="group">
          <button class="btn btn-outline-primary" onclick="openInventoryReport()">
            <i class="ti ti-list icon"></i> Inventory
          </button>
          <button class="btn btn-outline-info" onclick="openLocationReport()">
            <i class="ti ti-map-pin icon"></i> Locations
          </button>
          <button class="btn btn-outline-warning" onclick="openLowStockReport()">
            <i class="ti ti-alert-circle icon"></i> Low Stock
          </button>
          <button class="btn btn-outline-success" onclick="openItemsOutReport()">
            <i class="ti ti-arrow-right icon"></i> Checked Out
          </button>
        </div>
      </div>
    </div>
  `;
  
  dashboard.insertAdjacentHTML('afterbegin', quickLinks);
}

// ============================================================================
// EXAMPLE 7: Scheduled Reports
// ============================================================================

// Automatically check for low stock every hour
function setupLowStockMonitoring(threshold = 5) {
  setInterval(async () => {
    const items = await window.api.reports.getLowStock(threshold);
    if (items.length > 0) {
      console.warn(`Low stock alert: ${items.length} items below threshold`);
      // Could show a notification or alert
      showLowStockNotification(items.length);
    }
  }, 60 * 60 * 1000); // Every hour
}

function showLowStockNotification(count) {
  // Create a notification badge
  const badge = document.createElement('div');
  badge.className = 'alert alert-warning alert-dismissible';
  badge.innerHTML = `
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    <strong>Low Stock Alert!</strong> ${count} item(s) need attention.
    <button class="btn btn-sm btn-warning ms-2" onclick="openLowStockReport()">
      View Report
    </button>
  `;
  document.querySelector('.page-body .container-xl').prepend(badge);
}

// ============================================================================
// EXAMPLE 8: Custom Data Processing
// ============================================================================

// Get report data programmatically (without opening modal)
async function getReportData(type, filters = {}) {
  switch (type) {
    case 'inventory':
      return await window.api.inventory.getAll();
    case 'location':
      return await window.api.reports.getItemsByLocation(filters.theatreId);
    case 'lowStock':
      return await window.api.reports.getLowStock(filters.threshold || 5);
    case 'itemsOut':
      return await window.api.reports.getItemsOut();
    case 'showEquipment':
      return await window.api.reports.getShowEquipment(filters.showId);
    case 'activityLog':
      return await window.api.reports.getActivityLog(filters);
    default:
      throw new Error(`Unknown report type: ${type}`);
  }
}

// Use the data for custom analysis
async function analyzeInventory() {
  const items = await getReportData('inventory');
  
  const analysis = {
    totalItems: items.length,
    totalValue: items.reduce((sum, item) => sum + (item.value || 0), 0),
    byCategory: {},
    byStatus: {}
  };
  
  items.forEach(item => {
    // Group by category
    if (!analysis.byCategory[item.category]) {
      analysis.byCategory[item.category] = 0;
    }
    analysis.byCategory[item.category]++;
    
    // Group by status
    if (!analysis.byStatus[item.status]) {
      analysis.byStatus[item.status] = 0;
    }
    analysis.byStatus[item.status]++;
  });
  
  console.log('Inventory Analysis:', analysis);
  return analysis;
}

// ============================================================================
// EXAMPLE 9: Keyboard Shortcuts
// ============================================================================

// Add keyboard shortcuts for reports
function setupReportShortcuts() {
  document.addEventListener('keydown', (e) => {
    // Ctrl+Shift+R: Open Reports page
    if (e.ctrlKey && e.shiftKey && e.key === 'R') {
      window.navigation.navigateTo('reports');
    }
    
    // Ctrl+Shift+I: Open Inventory Report
    if (e.ctrlKey && e.shiftKey && e.key === 'I') {
      openInventoryReport();
    }
    
    // Ctrl+Shift+L: Open Location Report
    if (e.ctrlKey && e.shiftKey && e.key === 'L') {
      openLocationReport();
    }
    
    // Ctrl+Shift+A: Open Activity Log
    if (e.ctrlKey && e.shiftKey && e.key === 'A') {
      openActivityLogReport();
    }
  });
}

// ============================================================================
// EXAMPLE 10: Report Automation
// ============================================================================

// Generate daily report summary
async function generateDailySummary() {
  const stats = await window.api.reports.getDashboardStats();
  const shortages = await window.api.reports.getShortages();
  const lowStock = await window.api.reports.getLowStock(5);
  
  const today = new Date().toISOString().split('T')[0];
  const activityLog = await window.api.reports.getActivityLog({
    date_from: today,
    limit: 100
  });
  
  const summary = {
    date: today,
    stats: stats,
    alerts: {
      shortages: shortages.length,
      lowStock: lowStock.length
    },
    activity: {
      totalActions: activityLog.length,
      byType: {}
    }
  };
  
  activityLog.forEach(log => {
    if (!summary.activity.byType[log.action_type]) {
      summary.activity.byType[log.action_type] = 0;
    }
    summary.activity.byType[log.action_type]++;
  });
  
  console.log('Daily Summary:', summary);
  return summary;
}

// ============================================================================
// INITIALIZATION
// ============================================================================

// Initialize all custom report features
function initCustomReports() {
  console.log('Initializing custom report features...');
  
  // Setup keyboard shortcuts
  setupReportShortcuts();
  
  // Setup low stock monitoring
  setupLowStockMonitoring(5);
  
  console.log('Custom report features initialized!');
}

// Run on page load
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initCustomReports);
} else {
  initCustomReports();
}
