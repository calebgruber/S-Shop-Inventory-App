/**
 * Database Module
 * Manages SQLite database operations for the inventory system
 */

const Database = require('better-sqlite3');
const path = require('path');
const fs = require('fs');
const { app } = require('electron');

class InventoryDatabase {
  constructor() {
    // Store database in user data directory for persistence
    const userDataPath = app.getPath('userData');
    const dbPath = path.join(userDataPath, 'inventory.db');
    
    // Ensure directory exists
    if (!fs.existsSync(userDataPath)) {
      fs.mkdirSync(userDataPath, { recursive: true });
    }

    this.db = new Database(dbPath);
    this.db.pragma('journal_mode = WAL'); // Enable Write-Ahead Logging for better performance
    this.db.pragma('foreign_keys = ON'); // Enable foreign key constraints
    
    console.log(`Database initialized at: ${dbPath}`);
  }

  /**
   * Initialize database schema
   */
  init() {
    // Items table - stores all inventory items
    this.db.exec(`
      CREATE TABLE IF NOT EXISTS items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        description TEXT,
        category TEXT,
        manufacturer TEXT,
        model TEXT,
        serial_number TEXT UNIQUE,
        barcode TEXT UNIQUE,
        quantity_total INTEGER DEFAULT 0,
        quantity_available INTEGER DEFAULT 0,
        location TEXT,
        status TEXT DEFAULT 'available',
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
      )
    `);

    // Shows table - stores show/production information
    this.db.exec(`
      CREATE TABLE IF NOT EXISTS shows (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        description TEXT,
        venue TEXT,
        start_date DATE,
        end_date DATE,
        status TEXT DEFAULT 'planning',
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
      )
    `);

    // Pull sheets table - equipment checkout lists for shows
    this.db.exec(`
      CREATE TABLE IF NOT EXISTS pull_sheets (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        show_id INTEGER NOT NULL,
        name TEXT,
        status TEXT DEFAULT 'draft',
        pulled_date DATETIME,
        pulled_by TEXT,
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (show_id) REFERENCES shows(id) ON DELETE CASCADE
      )
    `);

    // Pull sheet items - junction table for pull sheets and items
    this.db.exec(`
      CREATE TABLE IF NOT EXISTS pull_sheet_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        pull_sheet_id INTEGER NOT NULL,
        item_id INTEGER NOT NULL,
        quantity_requested INTEGER NOT NULL,
        quantity_pulled INTEGER DEFAULT 0,
        status TEXT DEFAULT 'pending',
        notes TEXT,
        FOREIGN KEY (pull_sheet_id) REFERENCES pull_sheets(id) ON DELETE CASCADE,
        FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
      )
    `);

    // Change orders table - tracks modifications to pull sheets
    this.db.exec(`
      CREATE TABLE IF NOT EXISTS change_orders (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        show_id INTEGER NOT NULL,
        pull_sheet_id INTEGER,
        type TEXT NOT NULL,
        description TEXT,
        requested_by TEXT,
        status TEXT DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        completed_at DATETIME,
        FOREIGN KEY (show_id) REFERENCES shows(id) ON DELETE CASCADE,
        FOREIGN KEY (pull_sheet_id) REFERENCES pull_sheets(id) ON DELETE SET NULL
      )
    `);

    // Change order items - items affected by change orders
    this.db.exec(`
      CREATE TABLE IF NOT EXISTS change_order_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        change_order_id INTEGER NOT NULL,
        item_id INTEGER NOT NULL,
        quantity_change INTEGER NOT NULL,
        action TEXT NOT NULL,
        FOREIGN KEY (change_order_id) REFERENCES change_orders(id) ON DELETE CASCADE,
        FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
      )
    `);

    // Returns table - tracks equipment returns
    this.db.exec(`
      CREATE TABLE IF NOT EXISTS returns (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        pull_sheet_id INTEGER NOT NULL,
        return_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        returned_by TEXT,
        status TEXT DEFAULT 'pending',
        notes TEXT,
        completed_at DATETIME,
        FOREIGN KEY (pull_sheet_id) REFERENCES pull_sheets(id) ON DELETE CASCADE
      )
    `);

    // Return items - items being returned
    this.db.exec(`
      CREATE TABLE IF NOT EXISTS return_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        return_id INTEGER NOT NULL,
        item_id INTEGER NOT NULL,
        quantity INTEGER NOT NULL,
        condition TEXT,
        notes TEXT,
        FOREIGN KEY (return_id) REFERENCES returns(id) ON DELETE CASCADE,
        FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE
      )
    `);

    // Activity log - audit trail of all actions
    this.db.exec(`
      CREATE TABLE IF NOT EXISTS activity_log (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        action_type TEXT NOT NULL,
        description TEXT,
        user TEXT,
        data TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
      )
    `);

    // Create indexes for performance
    this.db.exec(`
      CREATE INDEX IF NOT EXISTS idx_items_barcode ON items(barcode);
      CREATE INDEX IF NOT EXISTS idx_items_serial ON items(serial_number);
      CREATE INDEX IF NOT EXISTS idx_items_status ON items(status);
      CREATE INDEX IF NOT EXISTS idx_shows_status ON shows(status);
      CREATE INDEX IF NOT EXISTS idx_pull_sheets_show ON pull_sheets(show_id);
      CREATE INDEX IF NOT EXISTS idx_pull_sheets_status ON pull_sheets(status);
      CREATE INDEX IF NOT EXISTS idx_change_orders_show ON change_orders(show_id);
      CREATE INDEX IF NOT EXISTS idx_activity_log_type ON activity_log(action_type);
      CREATE INDEX IF NOT EXISTS idx_activity_log_date ON activity_log(created_at);
    `);

    console.log('Database schema initialized');
  }

  // ===== INVENTORY METHODS =====

  getAllItems(filters = {}) {
    let query = 'SELECT * FROM items WHERE 1=1';
    const params = [];

    if (filters.category) {
      query += ' AND category = ?';
      params.push(filters.category);
    }

    if (filters.status) {
      query += ' AND status = ?';
      params.push(filters.status);
    }

    if (filters.search) {
      query += ' AND (name LIKE ? OR description LIKE ? OR model LIKE ? OR barcode LIKE ?)';
      const searchTerm = `%${filters.search}%`;
      params.push(searchTerm, searchTerm, searchTerm, searchTerm);
    }

    query += ' ORDER BY name ASC';

    return this.db.prepare(query).all(...params);
  }

  getItemById(id) {
    return this.db.prepare('SELECT * FROM items WHERE id = ?').get(id);
  }

  getItemByBarcode(barcode) {
    return this.db.prepare('SELECT * FROM items WHERE barcode = ?').get(barcode);
  }

  searchItems(query) {
    const searchTerm = `%${query}%`;
    return this.db.prepare(`
      SELECT * FROM items 
      WHERE name LIKE ? 
         OR description LIKE ? 
         OR model LIKE ? 
         OR barcode LIKE ?
         OR serial_number LIKE ?
      ORDER BY name ASC
    `).all(searchTerm, searchTerm, searchTerm, searchTerm, searchTerm);
  }

  createItem(item) {
    const stmt = this.db.prepare(`
      INSERT INTO items (
        name, description, category, manufacturer, model, 
        serial_number, barcode, quantity_total, quantity_available, 
        location, status, notes
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    `);

    const result = stmt.run(
      item.name,
      item.description || null,
      item.category || null,
      item.manufacturer || null,
      item.model || null,
      item.serial_number || null,
      item.barcode || null,
      item.quantity_total || 0,
      item.quantity_available || item.quantity_total || 0,
      item.location || null,
      item.status || 'available',
      item.notes || null
    );

    return { id: result.lastInsertRowid, ...item };
  }

  updateItem(id, item) {
    const stmt = this.db.prepare(`
      UPDATE items SET 
        name = ?, description = ?, category = ?, manufacturer = ?, 
        model = ?, serial_number = ?, barcode = ?, quantity_total = ?, 
        quantity_available = ?, location = ?, status = ?, notes = ?,
        updated_at = CURRENT_TIMESTAMP
      WHERE id = ?
    `);

    stmt.run(
      item.name,
      item.description,
      item.category,
      item.manufacturer,
      item.model,
      item.serial_number,
      item.barcode,
      item.quantity_total,
      item.quantity_available,
      item.location,
      item.status,
      item.notes,
      id
    );

    return this.getItemById(id);
  }

  deleteItem(id) {
    this.db.prepare('DELETE FROM items WHERE id = ?').run(id);
    return { success: true };
  }

  checkItemAvailability(itemId, quantity) {
    const item = this.getItemById(itemId);
    if (!item) return { available: false, message: 'Item not found' };
    
    const available = item.quantity_available >= quantity;
    return {
      available,
      requested: quantity,
      available_quantity: item.quantity_available,
      shortage: available ? 0 : quantity - item.quantity_available
    };
  }

  // ===== SHOW METHODS =====

  getAllShows() {
    return this.db.prepare('SELECT * FROM shows ORDER BY start_date DESC').all();
  }

  getShowById(id) {
    return this.db.prepare('SELECT * FROM shows WHERE id = ?').get(id);
  }

  createShow(show) {
    const stmt = this.db.prepare(`
      INSERT INTO shows (name, description, venue, start_date, end_date, status, notes)
      VALUES (?, ?, ?, ?, ?, ?, ?)
    `);

    const result = stmt.run(
      show.name,
      show.description || null,
      show.venue || null,
      show.start_date || null,
      show.end_date || null,
      show.status || 'planning',
      show.notes || null
    );

    return { id: result.lastInsertRowid, ...show };
  }

  updateShow(id, show) {
    const stmt = this.db.prepare(`
      UPDATE shows SET 
        name = ?, description = ?, venue = ?, start_date = ?, 
        end_date = ?, status = ?, notes = ?, updated_at = CURRENT_TIMESTAMP
      WHERE id = ?
    `);

    stmt.run(
      show.name,
      show.description,
      show.venue,
      show.start_date,
      show.end_date,
      show.status,
      show.notes,
      id
    );

    return this.getShowById(id);
  }

  deleteShow(id) {
    this.db.prepare('DELETE FROM shows WHERE id = ?').run(id);
    return { success: true };
  }

  // ===== PULL SHEET METHODS =====

  getAllPullSheets() {
    return this.db.prepare(`
      SELECT ps.*, s.name as show_name 
      FROM pull_sheets ps
      JOIN shows s ON ps.show_id = s.id
      ORDER BY ps.created_at DESC
    `).all();
  }

  getPullSheetById(id) {
    const pullSheet = this.db.prepare(`
      SELECT ps.*, s.name as show_name, s.venue
      FROM pull_sheets ps
      JOIN shows s ON ps.show_id = s.id
      WHERE ps.id = ?
    `).get(id);

    if (pullSheet) {
      pullSheet.items = this.db.prepare(`
        SELECT psi.*, i.name, i.description, i.barcode, i.location
        FROM pull_sheet_items psi
        JOIN items i ON psi.item_id = i.id
        WHERE psi.pull_sheet_id = ?
      `).all(id);
    }

    return pullSheet;
  }

  getPullSheetsByShow(showId) {
    return this.db.prepare('SELECT * FROM pull_sheets WHERE show_id = ? ORDER BY created_at DESC').all(showId);
  }

  createPullSheet(pullSheet) {
    const stmt = this.db.prepare(`
      INSERT INTO pull_sheets (show_id, name, status, pulled_by, notes)
      VALUES (?, ?, ?, ?, ?)
    `);

    const result = stmt.run(
      pullSheet.show_id,
      pullSheet.name || null,
      pullSheet.status || 'draft',
      pullSheet.pulled_by || null,
      pullSheet.notes || null
    );

    return { id: result.lastInsertRowid, ...pullSheet };
  }

  updatePullSheet(id, pullSheet) {
    const stmt = this.db.prepare(`
      UPDATE pull_sheets SET 
        name = ?, status = ?, pulled_date = ?, pulled_by = ?, notes = ?,
        updated_at = CURRENT_TIMESTAMP
      WHERE id = ?
    `);

    stmt.run(
      pullSheet.name,
      pullSheet.status,
      pullSheet.pulled_date,
      pullSheet.pulled_by,
      pullSheet.notes,
      id
    );

    return this.getPullSheetById(id);
  }

  deletePullSheet(id) {
    this.db.prepare('DELETE FROM pull_sheets WHERE id = ?').run(id);
    return { success: true };
  }

  addItemToPullSheet(pullSheetId, itemId, quantity) {
    const stmt = this.db.prepare(`
      INSERT INTO pull_sheet_items (pull_sheet_id, item_id, quantity_requested)
      VALUES (?, ?, ?)
    `);

    const result = stmt.run(pullSheetId, itemId, quantity);
    return { id: result.lastInsertRowid };
  }

  removeItemFromPullSheet(pullSheetId, itemId) {
    this.db.prepare('DELETE FROM pull_sheet_items WHERE pull_sheet_id = ? AND item_id = ?')
      .run(pullSheetId, itemId);
    return { success: true };
  }

  // ===== CHANGE ORDER METHODS =====

  getAllChangeOrders() {
    return this.db.prepare(`
      SELECT co.*, s.name as show_name
      FROM change_orders co
      JOIN shows s ON co.show_id = s.id
      ORDER BY co.created_at DESC
    `).all();
  }

  getChangeOrdersByShow(showId) {
    return this.db.prepare('SELECT * FROM change_orders WHERE show_id = ? ORDER BY created_at DESC')
      .all(showId);
  }

  createChangeOrder(changeOrder) {
    const stmt = this.db.prepare(`
      INSERT INTO change_orders (show_id, pull_sheet_id, type, description, requested_by, status)
      VALUES (?, ?, ?, ?, ?, ?)
    `);

    const result = stmt.run(
      changeOrder.show_id,
      changeOrder.pull_sheet_id || null,
      changeOrder.type,
      changeOrder.description || null,
      changeOrder.requested_by || null,
      changeOrder.status || 'pending'
    );

    return { id: result.lastInsertRowid, ...changeOrder };
  }

  updateChangeOrderStatus(id, status) {
    const completedAt = status === 'completed' ? new Date().toISOString() : null;
    this.db.prepare(`
      UPDATE change_orders 
      SET status = ?, completed_at = ?
      WHERE id = ?
    `).run(status, completedAt, id);

    return { success: true };
  }

  // ===== RETURN METHODS =====

  getAllReturns() {
    return this.db.prepare(`
      SELECT r.*, ps.name as pull_sheet_name, s.name as show_name
      FROM returns r
      JOIN pull_sheets ps ON r.pull_sheet_id = ps.id
      JOIN shows s ON ps.show_id = s.id
      ORDER BY r.return_date DESC
    `).all();
  }

  createReturn(returnData) {
    const stmt = this.db.prepare(`
      INSERT INTO returns (pull_sheet_id, returned_by, status, notes)
      VALUES (?, ?, ?, ?)
    `);

    const result = stmt.run(
      returnData.pull_sheet_id,
      returnData.returned_by || null,
      returnData.status || 'pending',
      returnData.notes || null
    );

    return { id: result.lastInsertRowid, ...returnData };
  }

  completeReturn(id) {
    this.db.prepare(`
      UPDATE returns 
      SET status = 'completed', completed_at = CURRENT_TIMESTAMP
      WHERE id = ?
    `).run(id);

    return { success: true };
  }

  // ===== REPORTING METHODS =====

  getShortages() {
    return this.db.prepare(`
      SELECT i.*, 
        (i.quantity_total - i.quantity_available) as quantity_out,
        i.quantity_available as quantity_in_stock
      FROM items i
      WHERE i.quantity_available < 0 
         OR (i.quantity_total - i.quantity_available) > i.quantity_total
      ORDER BY i.name ASC
    `).all();
  }

  getItemStatus(itemId) {
    const item = this.getItemById(itemId);
    
    const allocations = this.db.prepare(`
      SELECT ps.id, ps.name, s.name as show_name, psi.quantity_requested
      FROM pull_sheet_items psi
      JOIN pull_sheets ps ON psi.pull_sheet_id = ps.id
      JOIN shows s ON ps.show_id = s.id
      WHERE psi.item_id = ? AND ps.status != 'returned'
    `).all(itemId);

    return {
      item,
      allocations,
      total_allocated: allocations.reduce((sum, a) => sum + a.quantity_requested, 0)
    };
  }

  getActivityLog(filters = {}) {
    let query = 'SELECT * FROM activity_log WHERE 1=1';
    const params = [];

    if (filters.action_type) {
      query += ' AND action_type = ?';
      params.push(filters.action_type);
    }

    if (filters.start_date) {
      query += ' AND created_at >= ?';
      params.push(filters.start_date);
    }

    if (filters.end_date) {
      query += ' AND created_at <= ?';
      params.push(filters.end_date);
    }

    query += ' ORDER BY created_at DESC LIMIT 1000';

    return this.db.prepare(query).all(...params);
  }

  logActivity(actionType, description, data = null) {
    this.db.prepare(`
      INSERT INTO activity_log (action_type, description, data)
      VALUES (?, ?, ?)
    `).run(actionType, description, JSON.stringify(data));
  }

  close() {
    if (this.db) {
      this.db.close();
      console.log('Database connection closed');
    }
  }
}

module.exports = InventoryDatabase;
