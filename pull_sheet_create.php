<?php
require_once 'config.php';
require_once 'db.php';
require_once 'functions.php';

$db = getDB();

$pullSheetId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$showId = isset($_GET['show_id']) ? (int)$_GET['show_id'] : 0;
$errors = [];
$pullSheet = null;
$items = [];

// Load existing pull sheet or create new
if ($pullSheetId > 0) {
    $stmt = $db->prepare("SELECT ps.*, s.name as show_name 
                          FROM pull_sheets ps 
                          INNER JOIN shows s ON ps.show_id = s.id 
                          WHERE ps.id = ?");
    $stmt->bind_param("i", $pullSheetId);
    $stmt->execute();
    $result = $stmt->get_result();
    $pullSheet = $result->fetch_assoc();
    
    if (!$pullSheet || $pullSheet['status'] !== 'draft') {
        redirectTo('pull_sheets.php');
    }
    
    $showId = $pullSheet['show_id'];
    
    // Load items
    $stmt = $db->prepare("SELECT psi.*, i.name, i.barcode, i.available_quantity 
                          FROM pull_sheet_items psi 
                          INNER JOIN items i ON psi.item_id = i.id 
                          WHERE psi.pull_sheet_id = ?");
    $stmt->bind_param("i", $pullSheetId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'search_item') {
        $search = trim($_POST['search'] ?? '');
        // First try exact barcode match, then fallback to name search
        $stmt = $db->prepare("SELECT * FROM items 
                              WHERE barcode = ? 
                              UNION 
                              SELECT * FROM items 
                              WHERE name LIKE ? 
                              LIMIT 10");
        $searchLike = "%$search%";
        $stmt->bind_param("ss", $search, $searchLike);
        $stmt->execute();
        $result = $stmt->get_result();
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        echo json_encode(['success' => true, 'items' => $items]);
        exit;
    }
    
    if ($_POST['action'] === 'save_draft') {
        $showId = (int)$_POST['show_id'];
        $createdBy = trim($_POST['created_by'] ?? '');
        $itemsJson = $_POST['items'] ?? '';
        $items = json_decode($itemsJson, true);
        
        if (!$showId || !is_array($items) || empty($items)) {
            echo json_encode(['success' => false, 'message' => 'Invalid data provided']);
            exit;
        }
        
        try {
            if ($pullSheetId > 0) {
                $id = $pullSheetId;
            } else {
                $barcode = generatePDF417Barcode('pullsheet');
                $stmt = $db->prepare("INSERT INTO pull_sheets (show_id, barcode, created_by, status) 
                                      VALUES (?, ?, ?, 'draft')");
                $stmt->bind_param("iss", $showId, $barcode, $createdBy);
                $stmt->execute();
                $id = $stmt->insert_id;
            }
            
            // Clear existing items
            $stmt = $db->prepare("DELETE FROM pull_sheet_items WHERE pull_sheet_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            
            // Add items
            foreach ($items as $item) {
                $stmt = $db->prepare("INSERT INTO pull_sheet_items (pull_sheet_id, item_id, quantity_needed) 
                                      VALUES (?, ?, ?)");
                $stmt->bind_param("iii", $id, $item['item_id'], $item['quantity']);
                $stmt->execute();
            }
            
            echo json_encode(['success' => true, 'id' => $id]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($_POST['action'] === 'finalize') {
        $id = (int)$_POST['pull_sheet_id'];
        $showId = (int)$_POST['show_id'];
        $createdBy = trim($_POST['created_by'] ?? '');
        $items = json_decode($_POST['items'], true);
        $isMain = isset($_POST['is_main']) && $_POST['is_main'] === 'true';
        
        if ($id === 0) {
            $barcode = generatePDF417Barcode('pullsheet');
            $stmt = $db->prepare("INSERT INTO pull_sheets (show_id, barcode, created_by, status, is_main, finalized_at) 
                                  VALUES (?, ?, ?, 'finalized', ?, NOW())");
            $stmt->bind_param("issi", $showId, $barcode, $createdBy, $isMain);
            $stmt->execute();
            $id = $stmt->insert_id;
        } else {
            $stmt = $db->prepare("UPDATE pull_sheets 
                                  SET status = 'finalized', is_main = ?, finalized_at = NOW() 
                                  WHERE id = ?");
            $stmt->bind_param("ii", $isMain, $id);
            $stmt->execute();
            
            // Clear existing items
            $stmt = $db->prepare("DELETE FROM pull_sheet_items WHERE pull_sheet_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
        }
        
        // Add items and reserve quantities
        foreach ($items as $item) {
            $stmt = $db->prepare("INSERT INTO pull_sheet_items (pull_sheet_id, item_id, quantity_needed) 
                                  VALUES (?, ?, ?)");
            $stmt->bind_param("iii", $id, $item['item_id'], $item['quantity']);
            $stmt->execute();
            
            // Reserve inventory
            updateItemQuantity($item['item_id'], $item['quantity'], 'reserve');
            logTransaction($item['item_id'], 'reserve', $item['quantity'], $createdBy, $id, null, $showId);
        }
        
        echo json_encode(['success' => true, 'id' => $id]);
        exit;
    }
}

// Get shows for dropdown
$shows = $db->query("SELECT id, name FROM shows WHERE status = 'active' ORDER BY name");

$pageTitle = ($pullSheetId > 0 ? "Edit" : "Create") . " Pull Sheet - " . APP_NAME;
$pageHeader = ($pullSheetId > 0 ? "Edit" : "Create") . " Pull Sheet";
$pageActions = '<a href="pull_sheets.php" class="btn btn-secondary"><i class="ti ti-arrow-left"></i> Back</a>';

// Prepare data for JavaScript
$itemsJSON = json_encode($items);
$pullSheetIdJS = $pullSheetId;

$additionalJS = <<<'JS'
<script>
let pullSheetItems = [];

document.addEventListener('DOMContentLoaded', function() {
    // Load existing items if editing
    const existingItems = ITEMS_DATA_PLACEHOLDER;
    if (existingItems && existingItems.length > 0) {
        existingItems.forEach(item => {
            pullSheetItems.push({
                item_id: item.item_id,
                name: item.name,
                barcode: item.barcode,
                quantity: item.quantity_needed,
                available: item.available_quantity
            });
        });
        renderItems();
    }
    
    // Search/scan handler with auto-search
    const searchInput = document.getElementById('item-search');
    if (searchInput) {
        let searchTimeout;
        
        // Auto-search as user types
        searchInput.addEventListener('input', function(e) {
            clearTimeout(searchTimeout);
            const search = this.value.trim();
            
            if (search.length >= 2) {
                searchTimeout = setTimeout(() => {
                    searchItem(search);
                }, 300); // Wait 300ms after typing stops
            }
        });
        
        // Also support Enter key
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                clearTimeout(searchTimeout);
                const search = this.value.trim();
                if (search) {
                    searchItem(search);
                }
            }
        });
    }
});

function searchItem(search) {
    if (!search) return;
    
    fetch('pull_sheet_create.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'ajax=1&action=search_item&search=' + encodeURIComponent(search)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success && data.items && data.items.length > 0) {
            if (data.items.length === 1) {
                showItemModal(data.items[0]);
            } else {
                showItemList(data.items);
            }
        } else {
            // Don't show alert for short auto-search queries
            if (search.length < 3) return;
            alert('No items found');
        }
        document.getElementById('item-search').value = '';
        document.getElementById('item-search').focus();
    })
    .catch(error => {
        console.error('Search error:', error);
        alert('Error searching for items. Please try again.');
        document.getElementById('item-search').focus();
    });
}

function showItemList(items) {
    const modal = new bootstrap.Modal(document.getElementById('item-select-modal'));
    const list = document.getElementById('item-list');
    list.innerHTML = '';
    
    items.forEach(item => {
        const div = document.createElement('div');
        div.className = 'list-group-item list-group-item-action';
        div.style.cursor = 'pointer';
        div.innerHTML = `
            <div><strong>${escapeHtml(item.name)}</strong></div>
            <div class="text-muted small">${escapeHtml(item.barcode)} | Available: ${item.available_quantity}</div>
        `;
        div.onclick = () => {
            modal.hide();
            showItemModal(item);
        };
        list.appendChild(div);
    });
    
    modal.show();
}

function showItemModal(item) {
    document.getElementById('modal-item-name').textContent = item.name;
    document.getElementById('modal-item-barcode').textContent = item.barcode;
    document.getElementById('modal-item-available').textContent = item.available_quantity;
    document.getElementById('modal-item-id').value = item.id;
    document.getElementById('modal-item-quantity').value = '1';
    document.getElementById('modal-item-quantity').max = item.available_quantity;
    
    // Check stock
    const stockStatus = document.getElementById('modal-stock-status');
    if (item.available_quantity > 10) {
        stockStatus.innerHTML = '<span class="badge bg-success">In Stock</span>';
    } else if (item.available_quantity > 0) {
        stockStatus.innerHTML = '<span class="badge bg-warning">Low Stock</span>';
    } else {
        stockStatus.innerHTML = '<span class="badge bg-danger">Out of Stock</span>';
    }
    
    const modal = new bootstrap.Modal(document.getElementById('item-modal'));
    modal.show();
    
    setTimeout(() => {
        document.getElementById('modal-item-quantity').focus();
        document.getElementById('modal-item-quantity').select();
    }, 500);
}

function addItemToList() {
    const itemId = parseInt(document.getElementById('modal-item-id').value);
    const quantity = parseInt(document.getElementById('modal-item-quantity').value);
    const name = document.getElementById('modal-item-name').textContent;
    const barcode = document.getElementById('modal-item-barcode').textContent;
    const available = parseInt(document.getElementById('modal-item-available').textContent);
    
    if (quantity <= 0 || quantity > available) {
        alert('Invalid quantity');
        return;
    }
    
    // Check if item already exists
    const existingIndex = pullSheetItems.findIndex(i => i.item_id === itemId);
    if (existingIndex >= 0) {
        pullSheetItems[existingIndex].quantity += quantity;
    } else {
        pullSheetItems.push({item_id: itemId, name, barcode, quantity, available});
    }
    
    renderItems();
    bootstrap.Modal.getInstance(document.getElementById('item-modal')).hide();
    document.getElementById('item-search').focus();
}

function removeItem(index) {
    if (confirm('Remove this item?')) {
        pullSheetItems.splice(index, 1);
        renderItems();
    }
}

function renderItems() {
    const tbody = document.getElementById('items-list');
    tbody.innerHTML = '';
    
    pullSheetItems.forEach((item, index) => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${escapeHtml(item.name)}</td>
            <td>${escapeHtml(item.barcode)}</td>
            <td>${item.quantity}</td>
            <td>${item.available}</td>
            <td>
                <button class="btn btn-sm btn-danger" onclick="removeItem(${index})">
                    <i class="ti ti-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });
    
    document.getElementById('items-count').textContent = pullSheetItems.length;
}

function saveDraft() {
    const showId = document.getElementById('show_id').value;
    const createdBy = document.getElementById('created_by').value;
    
    if (!showId) {
        alert('Please select a show');
        return;
    }
    
    if (pullSheetItems.length === 0) {
        alert('Please add at least one item');
        return;
    }
    
    fetch('pull_sheet_create.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `ajax=1&action=save_draft&show_id=${showId}&created_by=${encodeURIComponent(createdBy)}&items=${encodeURIComponent(JSON.stringify(pullSheetItems))}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Draft saved successfully');
            window.location.href = 'pull_sheet_view.php?id=' + data.id;
        } else {
            alert('Error saving draft: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Save error:', error);
        alert('Error saving draft. Please try again.');
    });
}

function finalizePullSheet() {
    const showId = document.getElementById('show_id').value;
    const createdBy = document.getElementById('created_by').value;
    const isMain = document.getElementById('is_main').checked;
    
    if (!showId) {
        alert('Please select a show');
        return;
    }
    
    if (pullSheetItems.length === 0) {
        alert('Please add at least one item');
        return;
    }
    
    if (!confirm('Finalize this pull sheet? This will reserve inventory and cannot be undone.')) {
        return;
    }
    
    const pullSheetId = PULLSHEET_ID_PLACEHOLDER;
    
    fetch('pull_sheet_create.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `ajax=1&action=finalize&pull_sheet_id=${pullSheetId}&show_id=${showId}&created_by=${encodeURIComponent(createdBy)}&is_main=${isMain}&items=${encodeURIComponent(JSON.stringify(pullSheetItems))}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Pull sheet finalized successfully');
            window.location.href = 'pull_sheet_view.php?id=' + data.id;
        }
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
JS;

// Replace placeholders with actual data
$additionalJS = str_replace('ITEMS_DATA_PLACEHOLDER', $itemsJSON, $additionalJS);
$additionalJS = str_replace('PULLSHEET_ID_PLACEHOLDER', $pullSheetIdJS, $additionalJS);

ob_start();
?>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label required">Show</label>
                        <select id="show_id" class="form-select" <?php echo $pullSheetId > 0 ? 'disabled' : ''; ?>>
                            <option value="">Select show</option>
                            <?php while ($show = $shows->fetch_assoc()): ?>
                            <option value="<?php echo $show['id']; ?>" 
                                <?php echo $showId == $show['id'] ? 'selected' : ''; ?>>
                                <?php echo sanitize($show['name']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Created By</label>
                        <input type="text" id="created_by" class="form-control" 
                               value="<?php echo sanitize($pullSheet['created_by'] ?? ''); ?>"
                               placeholder="Your name">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-check">
                        <input type="checkbox" id="is_main" class="form-check-input" 
                               <?php echo ($pullSheet && $pullSheet['is_main']) ? 'checked' : ''; ?>>
                        <span class="form-check-label">Main Pull Sheet</span>
                    </label>
                </div>
            </div>
        </div>
        
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">Add Items</h3>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Search or Scan Item</label>
                    <input type="text" id="item-search" class="form-control auto-focus" 
                           placeholder="Enter barcode or item name">
                    <small class="form-hint">Press Enter to search</small>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Items (<span id="items-count">0</span>)</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Barcode</th>
                            <th>Quantity</th>
                            <th>Available</th>
                            <th width="80"></th>
                        </tr>
                    </thead>
                    <tbody id="items-list">
                        <tr>
                            <td colspan="5" class="text-center text-muted">No items added yet</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Actions</h3>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button class="btn btn-success" onclick="finalizePullSheet()">
                        <i class="ti ti-check"></i> Build Show
                    </button>
                    <button class="btn btn-secondary" onclick="saveDraft()">
                        <i class="ti ti-device-floppy"></i> Save Draft
                    </button>
                    <a href="pull_sheets.php" class="btn btn-outline-secondary">
                        Cancel
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Item Modal -->
<div class="modal fade" id="item-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="modal-item-id">
                <div class="mb-3">
                    <label class="form-label">Item</label>
                    <div><strong id="modal-item-name"></strong></div>
                    <div class="text-muted small" id="modal-item-barcode"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Stock Status</label>
                    <div id="modal-stock-status"></div>
                    <div class="text-muted small">Available: <span id="modal-item-available"></span></div>
                </div>
                <div class="mb-3">
                    <label class="form-label required">Quantity</label>
                    <input type="number" id="modal-item-quantity" class="form-control" min="1" value="1">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="addItemToList()">Add Item</button>
            </div>
        </div>
    </div>
</div>

<!-- Item Select Modal -->
<div class="modal fade" id="item-select-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Select Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="list-group" id="item-list"></div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require 'layout.php';
?>
