/**
 * Database Module
 * Manages SQLite database operations for the inventory system
 */

const Database = require('better-sqlite3');
const path = require('path');
const fs = require('fs');
const { app } = require('electron');
const { generateItemBarcode, generateSerialBarcode } = require('../utils/barcodeGenerator');

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
        theatre_id INTEGER,
        start_date DATE,
        end_date DATE,
        status TEXT DEFAULT 'planning',
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (theatre_id) REFERENCES theatres(id) ON DELETE SET NULL
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

    // Theatres table - stores theatre/space information
    this.db.exec(`
      CREATE TABLE IF NOT EXISTS theatres (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        description TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
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

    // Categories table - stores user-defined item categories
    this.db.exec(`
      CREATE TABLE IF NOT EXISTS categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL UNIQUE,
        description TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
      )
    `);

    // Settings table - stores application settings including logo
    this.db.exec(`
      CREATE TABLE IF NOT EXISTS settings (
        key TEXT PRIMARY KEY,
        value TEXT,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
      )
    `);

    // Create indexes for performance
    this.db.exec(`
      CREATE INDEX IF NOT EXISTS idx_items_barcode ON items(barcode);
      CREATE INDEX IF NOT EXISTS idx_items_serial ON items(serial_number);
      CREATE INDEX IF NOT EXISTS idx_items_status ON items(status);
      CREATE INDEX IF NOT EXISTS idx_shows_status ON shows(status);
      CREATE INDEX IF NOT EXISTS idx_shows_theatre ON shows(theatre_id);
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
    // Auto-generate barcode if not provided
    let barcode = item.barcode;
    
    if (!barcode) {
      // If item has serial number, generate serial-based barcode
      if (item.serial_number) {
        barcode = generateSerialBarcode(item.serial_number);
      }
      // Otherwise, we'll generate it after getting the ID
    }
    
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
      barcode || null,
      item.quantity_total || 0,
      item.quantity_available || item.quantity_total || 0,
      item.location || null,
      item.status || 'available',
      item.notes || null
    );

    const itemId = result.lastInsertRowid;
    
    // If no barcode was provided and no serial number, generate one based on ID
    if (!barcode && !item.serial_number) {
      barcode = generateItemBarcode(itemId, item.category);
      this.db.prepare('UPDATE items SET barcode = ? WHERE id = ?').run(barcode, itemId);
    }

    return { id: itemId, ...item, barcode };
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
      INSERT INTO shows (name, description, venue, theatre_id, start_date, end_date, status, notes)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    `);

    const result = stmt.run(
      show.name,
      show.description || null,
      show.venue || null,
      show.theatre_id || null,
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
        name = ?, description = ?, venue = ?, theatre_id = ?, start_date = ?, 
        end_date = ?, status = ?, notes = ?, updated_at = CURRENT_TIMESTAMP
      WHERE id = ?
    `);

    stmt.run(
      show.name,
      show.description,
      show.venue,
      show.theatre_id,
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

  // ===== THEATRE METHODS =====

  getAllTheatres() {
    return this.db.prepare('SELECT * FROM theatres ORDER BY name ASC').all();
  }

  getTheatreById(id) {
    return this.db.prepare('SELECT * FROM theatres WHERE id = ?').get(id);
  }

  createTheatre(theatre) {
    const stmt = this.db.prepare(`
      INSERT INTO theatres (name, description)
      VALUES (?, ?)
    `);

    const result = stmt.run(
      theatre.name,
      theatre.description || null
    );

    return { id: result.lastInsertRowid, ...theatre };
  }

  updateTheatre(id, theatre) {
    const stmt = this.db.prepare(`
      UPDATE theatres SET 
        name = ?, description = ?
      WHERE id = ?
    `);

    stmt.run(
      theatre.name,
      theatre.description,
      id
    );

    return this.getTheatreById(id);
  }

  deleteTheatre(id) {
    // Check if any shows reference this theatre
    const showCount = this.db.prepare('SELECT COUNT(*) as count FROM shows WHERE theatre_id = ?').get(id);
    if (showCount.count > 0) {
      return { success: false, error: 'Cannot delete theatre with active shows' };
    }
    
    this.db.prepare('DELETE FROM theatres WHERE id = ?').run(id);
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

  updatePullSheetItem(pullSheetId, itemId, quantity, status = null) {
    const updates = ['quantity_requested = ?'];
    const params = [quantity];
    
    if (status) {
      updates.push('status = ?');
      params.push(status);
    }
    
    params.push(pullSheetId, itemId);
    
    this.db.prepare(`
      UPDATE pull_sheet_items 
      SET ${updates.join(', ')}
      WHERE pull_sheet_id = ? AND item_id = ?
    `).run(...params);
    
    return { success: true };
  }

  finalizePullSheet(pullSheetId, pulledBy) {
    // Start a transaction
    const finalize = this.db.transaction(() => {
      // Get all items in the pull sheet
      const items = this.db.prepare(`
        SELECT item_id, quantity_requested 
        FROM pull_sheet_items 
        WHERE pull_sheet_id = ?
      `).all(pullSheetId);
      
      // Decrement availability for each item
      for (const item of items) {
        this.db.prepare(`
          UPDATE items 
          SET quantity_available = quantity_available - ?
          WHERE id = ?
        `).run(item.quantity_requested, item.item_id);
        
        // Update pull sheet item status
        this.db.prepare(`
          UPDATE pull_sheet_items 
          SET quantity_pulled = quantity_requested, status = 'pulled'
          WHERE pull_sheet_id = ? AND item_id = ?
        `).run(pullSheetId, item.item_id);
      }
      
      // Update pull sheet status
      this.db.prepare(`
        UPDATE pull_sheets 
        SET status = 'finalized', pulled_date = CURRENT_TIMESTAMP, pulled_by = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
      `).run(pulledBy, pullSheetId);
    });
    
    finalize();
    return { success: true };
  }

  getPullSheetByBarcode(barcode) {
    // Pull sheet barcodes follow pattern PULL-{id}-{timestamp}
    const match = barcode.match(/^PULL-(\d+)/);
    if (match) {
      return this.getPullSheetById(parseInt(match[1]));
    }
    return null;
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

  getChangeOrdersByPullSheet(pullSheetId) {
    return this.db.prepare(`
      SELECT co.*, s.name as show_name
      FROM change_orders co
      JOIN shows s ON co.show_id = s.id
      WHERE co.pull_sheet_id = ?
      ORDER BY co.created_at DESC
    `).all(pullSheetId);
  }

  getChangeOrderById(id) {
    const changeOrder = this.db.prepare(`
      SELECT co.*, s.name as show_name
      FROM change_orders co
      JOIN shows s ON co.show_id = s.id
      WHERE co.id = ?
    `).get(id);
    
    if (changeOrder) {
      changeOrder.items = this.db.prepare(`
        SELECT coi.*, i.name, i.barcode, i.quantity_available
        FROM change_order_items coi
        JOIN items i ON coi.item_id = i.id
        WHERE coi.change_order_id = ?
      `).all(id);
    }
    
    return changeOrder;
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

  addChangeOrderItem(changeOrderId, itemId, quantityChange, action) {
    const stmt = this.db.prepare(`
      INSERT INTO change_order_items (change_order_id, item_id, quantity_change, action)
      VALUES (?, ?, ?, ?)
    `);
    
    const result = stmt.run(changeOrderId, itemId, quantityChange, action);
    return { id: result.lastInsertRowid };
  }

  processChangeOrder(changeOrderId) {
    // Start a transaction
    const process = this.db.transaction(() => {
      const changeOrder = this.getChangeOrderById(changeOrderId);
      
      if (!changeOrder || !changeOrder.pull_sheet_id) {
        throw new Error('Invalid change order or no pull sheet associated');
      }
      
      // Process each item
      for (const item of changeOrder.items) {
        if (item.action === 'add') {
          // Add item to pull sheet
          const existing = this.db.prepare(`
            SELECT * FROM pull_sheet_items 
            WHERE pull_sheet_id = ? AND item_id = ?
          `).get(changeOrder.pull_sheet_id, item.item_id);
          
          if (existing) {
            // Update quantity
            this.db.prepare(`
              UPDATE pull_sheet_items 
              SET quantity_requested = quantity_requested + ?,
                  quantity_pulled = quantity_pulled + ?
              WHERE pull_sheet_id = ? AND item_id = ?
            `).run(item.quantity_change, item.quantity_change, changeOrder.pull_sheet_id, item.item_id);
          } else {
            // Add new item
            this.db.prepare(`
              INSERT INTO pull_sheet_items (pull_sheet_id, item_id, quantity_requested, quantity_pulled, status)
              VALUES (?, ?, ?, ?, 'pulled')
            `).run(changeOrder.pull_sheet_id, item.item_id, item.quantity_change, item.quantity_change);
          }
          
          // Decrement availability
          this.db.prepare(`
            UPDATE items 
            SET quantity_available = quantity_available - ?
            WHERE id = ?
          `).run(item.quantity_change, item.item_id);
          
        } else if (item.action === 'remove') {
          // Remove item quantity from pull sheet
          const existing = this.db.prepare(`
            SELECT * FROM pull_sheet_items 
            WHERE pull_sheet_id = ? AND item_id = ?
          `).get(changeOrder.pull_sheet_id, item.item_id);
          
          if (existing) {
            const newQuantity = existing.quantity_requested - item.quantity_change;
            if (newQuantity <= 0) {
              // Remove item completely
              this.db.prepare(`
                DELETE FROM pull_sheet_items 
                WHERE pull_sheet_id = ? AND item_id = ?
              `).run(changeOrder.pull_sheet_id, item.item_id);
            } else {
              // Update quantity
              this.db.prepare(`
                UPDATE pull_sheet_items 
                SET quantity_requested = ?,
                    quantity_pulled = ?
                WHERE pull_sheet_id = ? AND item_id = ?
              `).run(newQuantity, newQuantity, changeOrder.pull_sheet_id, item.item_id);
            }
          }
          
          // Increment availability
          this.db.prepare(`
            UPDATE items 
            SET quantity_available = quantity_available + ?
            WHERE id = ?
          `).run(item.quantity_change, item.item_id);
        }
      }
      
      // Mark change order as completed
      this.db.prepare(`
        UPDATE change_orders 
        SET status = 'completed', completed_at = CURRENT_TIMESTAMP
        WHERE id = ?
      `).run(changeOrderId);
    });
    
    process();
    return { success: true };
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

  getReturnById(id) {
    const returnData = this.db.prepare(`
      SELECT r.*, ps.name as pull_sheet_name, s.name as show_name
      FROM returns r
      JOIN pull_sheets ps ON r.pull_sheet_id = ps.id
      JOIN shows s ON ps.show_id = s.id
      WHERE r.id = ?
    `).get(id);
    
    if (returnData) {
      returnData.items = this.db.prepare(`
        SELECT ri.*, i.name, i.barcode
        FROM return_items ri
        JOIN items i ON ri.item_id = i.id
        WHERE ri.return_id = ?
      `).all(id);
    }
    
    return returnData;
  }

  getReturnByPullSheet(pullSheetId) {
    return this.db.prepare(`
      SELECT r.*, ps.name as pull_sheet_name, s.name as show_name
      FROM returns r
      JOIN pull_sheets ps ON r.pull_sheet_id = ps.id
      JOIN shows s ON ps.show_id = s.id
      WHERE r.pull_sheet_id = ? AND r.status = 'pending'
      ORDER BY r.return_date DESC
      LIMIT 1
    `).get(pullSheetId);
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

  addReturnItem(returnId, itemId, quantity, condition, notes = null) {
    const stmt = this.db.prepare(`
      INSERT INTO return_items (return_id, item_id, quantity, condition, notes)
      VALUES (?, ?, ?, ?, ?)
    `);
    
    const result = stmt.run(returnId, itemId, quantity, condition, notes);
    
    // Update inventory availability based on condition
    if (condition === 'good' || condition === 'returned') {
      this.db.prepare(`
        UPDATE items 
        SET quantity_available = quantity_available + ?
        WHERE id = ?
      `).run(quantity, itemId);
    } else if (condition === 'damaged') {
      // Increment available but maybe mark item as damaged
      this.db.prepare(`
        UPDATE items 
        SET quantity_available = quantity_available + ?
        WHERE id = ?
      `).run(quantity, itemId);
    }
    // For 'lost', we don't increment availability
    
    return { id: result.lastInsertRowid };
  }

  completeReturn(id) {
    // Start transaction
    const complete = this.db.transaction(() => {
      // Mark return as completed
      this.db.prepare(`
        UPDATE returns 
        SET status = 'completed', completed_at = CURRENT_TIMESTAMP
        WHERE id = ?
      `).run(id);
      
      // Update pull sheet status
      const returnData = this.db.prepare('SELECT pull_sheet_id FROM returns WHERE id = ?').get(id);
      if (returnData) {
        this.db.prepare(`
          UPDATE pull_sheets 
          SET status = 'returned'
          WHERE id = ?
        `).run(returnData.pull_sheet_id);
        
        // Update pull sheet items status
        this.db.prepare(`
          UPDATE pull_sheet_items 
          SET status = 'returned'
          WHERE pull_sheet_id = ?
        `).run(returnData.pull_sheet_id);
      }
    });
    
    complete();
    return { success: true };
  }

  // ===== REPORTING METHODS =====

  getShortages() {
    // Returns items that are oversold (more out than total) or have negative availability
    return this.db.prepare(`
      SELECT i.*, 
        (i.quantity_total - i.quantity_available) as quantity_out,
        i.quantity_available as quantity_in_stock
      FROM items i
      WHERE i.quantity_available < 0 
         OR i.quantity_available < (i.quantity_total * 0.2)
      ORDER BY i.quantity_available ASC, i.name ASC
    `).all();
  }

  getLowStockItems(threshold = 2) {
    return this.db.prepare(`
      SELECT * FROM items 
      WHERE quantity_available <= ? AND quantity_available >= 0
      ORDER BY quantity_available ASC, name ASC
    `).all(threshold);
  }

  getItemsOut() {
    // Get all items currently checked out
    return this.db.prepare(`
      SELECT 
        i.*,
        (i.quantity_total - i.quantity_available) as quantity_out,
        GROUP_CONCAT(DISTINCT s.name) as show_names
      FROM items i
      JOIN pull_sheet_items psi ON i.id = psi.item_id
      JOIN pull_sheets ps ON psi.pull_sheet_id = ps.id
      JOIN shows s ON ps.show_id = s.id
      WHERE ps.status IN ('finalized', 'pulled') 
        AND psi.status = 'pulled'
      GROUP BY i.id
      HAVING quantity_out > 0
      ORDER BY i.name ASC
    `).all();
  }

  getShowEquipmentReport(showId) {
    // Get all equipment for a specific show across all pull sheets
    return this.db.prepare(`
      SELECT 
        i.id,
        i.name,
        i.barcode,
        i.category,
        SUM(psi.quantity_pulled) as total_quantity,
        ps.name as pull_sheet_name,
        ps.status as pull_sheet_status
      FROM items i
      JOIN pull_sheet_items psi ON i.id = psi.item_id
      JOIN pull_sheets ps ON psi.pull_sheet_id = ps.id
      WHERE ps.show_id = ?
      GROUP BY i.id, ps.id
      ORDER BY i.name ASC
    `).all(showId);
  }

  getDashboardStats() {
    // Get comprehensive dashboard statistics
    const totalItems = this.db.prepare('SELECT COUNT(*) as count FROM items').get().count;
    const availableItems = this.db.prepare('SELECT COUNT(*) as count FROM items WHERE quantity_available > 0').get().count;
    const itemsOut = this.db.prepare(`
      SELECT COUNT(DISTINCT i.id) as count 
      FROM items i
      JOIN pull_sheet_items psi ON i.id = psi.item_id
      JOIN pull_sheets ps ON psi.pull_sheet_id = ps.id
      WHERE ps.status IN ('finalized', 'pulled') AND psi.status = 'pulled'
    `).get().count;
    const activeShows = this.db.prepare('SELECT COUNT(*) as count FROM shows WHERE status IN (\'active\', \'running\')').get().count;
    const pendingReturns = this.db.prepare('SELECT COUNT(*) as count FROM returns WHERE status = \'pending\'').get().count;
    const shortagesCount = this.db.prepare(`
      SELECT COUNT(*) as count FROM items 
      WHERE quantity_available < 0 OR quantity_available < (quantity_total * 0.2)
    `).get().count;
    
    return {
      totalItems,
      availableItems,
      itemsOut,
      activeShows,
      pendingReturns,
      shortagesCount
    };
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

    const limit = filters.limit || 1000;
    query += ' ORDER BY created_at DESC LIMIT ?';
    params.push(limit);

    return this.db.prepare(query).all(...params);
  }

  getItemsByLocation(theatreId = null) {
    // Get items currently in use by theatre from active shows
    let query = `
      SELECT 
        t.id as theatre_id,
        t.name as theatre_name,
        i.id as item_id,
        i.name as item_name,
        i.serial_number,
        i.barcode,
        i.status,
        SUM(psi.quantity_pulled) as quantity,
        s.name as show_name,
        s.status as show_status
      FROM theatres t
      LEFT JOIN shows s ON t.id = s.theatre_id
      LEFT JOIN pull_sheets ps ON s.id = ps.show_id
      LEFT JOIN pull_sheet_items psi ON ps.id = psi.pull_sheet_id
      LEFT JOIN items i ON psi.item_id = i.id
      WHERE (s.status = 'active' OR s.status = 'running')
        AND ps.status = 'pulled'
    `;
    
    const params = [];
    if (theatreId) {
      query += ' AND t.id = ?';
      params.push(theatreId);
    }
    
    query += `
      GROUP BY t.id, i.id
      ORDER BY t.name, i.name
    `;
    
    return this.db.prepare(query).all(...params);
  }

  logActivity(actionType, description, data = null) {
    this.db.prepare(`
      INSERT INTO activity_log (action_type, description, data)
      VALUES (?, ?, ?)
    `).run(actionType, description, JSON.stringify(data));
  }

  /**
   * Generate barcodes for items that don't have one
   */
  generateMissingBarcodes() {
    const itemsWithoutBarcodes = this.db.prepare('SELECT * FROM items WHERE barcode IS NULL').all();
    let updated = 0;
    
    for (const item of itemsWithoutBarcodes) {
      let barcode;
      
      if (item.serial_number) {
        barcode = generateSerialBarcode(item.serial_number);
      } else {
        barcode = generateItemBarcode(item.id, item.category);
      }
      
      this.db.prepare('UPDATE items SET barcode = ? WHERE id = ?').run(barcode, item.id);
      updated++;
    }
    
    return { updated, total: itemsWithoutBarcodes.length };
  }
  
  /**
   * Get items for barcode label printing
   */
  getItemsForLabels(filters = {}) {
    let query = 'SELECT id, name, barcode, category, location FROM items WHERE barcode IS NOT NULL';
    const params = [];
    
    if (filters.category) {
      query += ' AND category = ?';
      params.push(filters.category);
    }
    
    if (filters.ids && filters.ids.length > 0) {
      query += ` AND id IN (${filters.ids.map(() => '?').join(',')})`;
      params.push(...filters.ids);
    }
    
    query += ' ORDER BY category, name';
    
    return this.db.prepare(query).all(...params);
  }

  // ===== CATEGORY MANAGEMENT METHODS =====

  getAllCategories() {
    return this.db.prepare('SELECT * FROM categories ORDER BY name ASC').all();
  }

  getCategoryById(id) {
    return this.db.prepare('SELECT * FROM categories WHERE id = ?').get(id);
  }

  createCategory(category) {
    const stmt = this.db.prepare(`
      INSERT INTO categories (name, description) 
      VALUES (?, ?)
    `);
    const result = stmt.run(category.name, category.description || null);
    return { id: result.lastInsertRowid, ...category };
  }

  updateCategory(id, category) {
    const stmt = this.db.prepare(`
      UPDATE categories SET name = ?, description = ? WHERE id = ?
    `);
    stmt.run(category.name, category.description || null, id);
    return { id, ...category };
  }

  deleteCategory(id) {
    // Check if category is in use
    const itemCount = this.db.prepare('SELECT COUNT(*) as count FROM items WHERE category = (SELECT name FROM categories WHERE id = ?)').get(id);
    
    if (itemCount && itemCount.count > 0) {
      return { success: false, error: `Cannot delete category: ${itemCount.count} items are using it` };
    }
    
    this.db.prepare('DELETE FROM categories WHERE id = ?').run(id);
    return { success: true };
  }

  // ===== SETTINGS METHODS =====

  getSetting(key) {
    const result = this.db.prepare('SELECT value FROM settings WHERE key = ?').get(key);
    return result ? result.value : null;
  }

  setSetting(key, value) {
    const stmt = this.db.prepare(`
      INSERT INTO settings (key, value, updated_at) 
      VALUES (?, ?, CURRENT_TIMESTAMP)
      ON CONFLICT(key) DO UPDATE SET value = ?, updated_at = CURRENT_TIMESTAMP
    `);
    stmt.run(key, value, value);
    return { key, value };
  }

  getAllSettings() {
    return this.db.prepare('SELECT * FROM settings').all();
  }

  close() {
    if (this.db) {
      this.db.close();
      console.log('Database connection closed');
    }
  }
}

module.exports = InventoryDatabase;
