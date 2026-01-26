<?php
require_once 'config.php';
require_once 'db.php';
require_once 'functions.php';

$db = getDB();

$changeOrderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$showId = isset($_GET['show_id']) ? (int)$_GET['show_id'] : 0;
$errors = [];
$changeOrder = null;
$items = [];

// Load existing change order or create new
if ($changeOrderId > 0) {
    $stmt = $db->prepare("SELECT co.*, s.name as show_name 
                          FROM change_orders co 
                          INNER JOIN shows s ON co.show_id = s.id 
                          WHERE co.id = ?");
    $stmt->bind_param("i", $changeOrderId);
    $stmt->execute();
    $result = $stmt->get_result();
    $changeOrder = $result->fetch_assoc();
    
    if (!$changeOrder || $changeOrder['status'] !== 'draft') {
        redirectTo('change_orders.php');
    }
    
    $showId = $changeOrder['show_id'];
    
    // Load items
    $stmt = $db->prepare("SELECT coi.*, i.name, i.barcode, i.available_quantity 
                          FROM change_order_items coi 
                          INNER JOIN items i ON coi.item_id = i.id 
                          WHERE coi.change_order_id = ?");
    $stmt->bind_param("i", $changeOrderId);
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
        $stmt = $db->prepare("SELECT * FROM items 
                              WHERE barcode = ? OR name LIKE ? 
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
        $items = json_decode($_POST['items'], true);
        
        if ($changeOrderId > 0) {
            $id = $changeOrderId;
        } else {
            $barcode = generatePDF417Barcode('changeorder');
            $stmt = $db->prepare("INSERT INTO change_orders (show_id, barcode, created_by, status) 
                                  VALUES (?, ?, ?, 'draft')");
            $stmt->bind_param("iss", $showId, $barcode, $createdBy);
            $stmt->execute();
            $id = $stmt->insert_id;
        }
        
        // Clear existing items
        $stmt = $db->prepare("DELETE FROM change_order_items WHERE change_order_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        // Add items
        foreach ($items as $item) {
            $stmt = $db->prepare("INSERT INTO change_order_items (change_order_id, item_id, action_type, quantity) 
                                  VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iisi", $id, $item['item_id'], $item['action_type'], $item['quantity']);
            $stmt->execute();
        }
        
        echo json_encode(['success' => true, 'id' => $id]);
        exit;
    }
    
    if ($_POST['action'] === 'finalize') {
        $id = (int)$_POST['change_order_id'];
        $showId = (int)$_POST['show_id'];
        $createdBy = trim($_POST['created_by'] ?? '');
        $items = json_decode($_POST['items'], true);
        
        if ($id === 0) {
            $barcode = generatePDF417Barcode('changeorder');
            $stmt = $db->prepare("INSERT INTO change_orders (show_id, barcode, created_by, status, finalized_at) 
                                  VALUES (?, ?, ?, 'finalized', NOW())");
            $stmt->bind_param("iss", $showId, $barcode, $createdBy);
            $stmt->execute();
            $id = $stmt->insert_id;
        } else {
            $stmt = $db->prepare("UPDATE change_orders 
                                  SET status = 'finalized', finalized_at = NOW() 
                                  WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            
            // Clear existing items
            $stmt = $db->prepare("DELETE FROM change_order_items WHERE change_order_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
        }
        
        // Add items and handle inventory
        foreach ($items as $item) {
            $stmt = $db->prepare("INSERT INTO change_order_items (change_order_id, item_id, action_type, quantity) 
                                  VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iisi", $id, $item['item_id'], $item['action_type'], $item['quantity']);
            $stmt->execute();
            
            // Handle inventory based on action type
            if ($item['action_type'] === 'add') {
                updateItemQuantity($item['item_id'], $item['quantity'], 'reserve');
                logTransaction($item['item_id'], 'reserve', $item['quantity'], $createdBy, null, $id, $showId);
            }
        }
        
        echo json_encode(['success' => true, 'id' => $id]);
        exit;
    }
}

// Get shows for dropdown
$shows = $db->query("SELECT id, name FROM shows WHERE status = 'active' ORDER BY name");

$pageTitle = ($changeOrderId > 0 ? "Edit" : "Create") . " Change Order - " . APP_NAME;
$pageHeader = ($changeOrderId > 0 ? "Edit" : "Create") . " Change Order";
$pageActions = '<a href="change_orders.php" class="btn btn-secondary"><i class="ti ti-arrow-left"></i> Back</a>';

$additionalJS = <<<JS
<script>
let changeOrderItems = [];

document.addEventListener('DOMContentLoaded', function() {
    // Load existing items if editing
    const existingItems = <?php echo json_encode($items); ?>;
    if (existingItems && existingItems.length > 0) {
        existingItems.forEach(item => {
            changeOrderItems.push({
                item_id: item.item_id,
                name: item.name,
                barcode: item.barcode,
                action_type: item.action_type,
                quantity: item.quantity,
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
    
    fetch('change_order_create.php', {
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
            // Don't show alert for auto-search, only clear input
            if (search.length < 3) return;
            alert('No items found');
        }
        document.getElementById('item-search').value = '';
        document.getElementById('item-search').focus();
    })
    .catch(error => {
        console.error('Search error:', error);
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
    document.getElementById('modal-action-type').value = 'add';
    
    const modal = new bootstrap.Modal(document.getElementById('item-modal'));
    modal.show();
    
    setTimeout(() => {
        document.getElementById('modal-action-type').focus();
    }, 500);
}

function addItemToList() {
    const itemId = parseInt(document.getElementById('modal-item-id').value);
    const quantity = parseInt(document.getElementById('modal-item-quantity').value);
    const actionType = document.getElementById('modal-action-type').value;
    const name = document.getElementById('modal-item-name').textContent;
    const barcode = document.getElementById('modal-item-barcode').textContent;
    const available = parseInt(document.getElementById('modal-item-available').textContent);
    
    if (quantity <= 0) {
        alert('Invalid quantity');
        return;
    }
    
    if (actionType === 'add' && quantity > available) {
        alert('Quantity exceeds available stock');
        return;
    }
    
    changeOrderItems.push({
        item_id: itemId, 
        name, 
        barcode, 
        action_type: actionType, 
        quantity, 
        available
    });
    
    renderItems();
    bootstrap.Modal.getInstance(document.getElementById('item-modal')).hide();
    document.getElementById('item-search').focus();
}

function removeItem(index) {
    if (confirm('Remove this item?')) {
        changeOrderItems.splice(index, 1);
        renderItems();
    }
}

function renderItems() {
    const tbody = document.getElementById('items-list');
    tbody.innerHTML = '';
    
    changeOrderItems.forEach((item, index) => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${escapeHtml(item.name)}</td>
            <td>${escapeHtml(item.barcode)}</td>
            <td>
                <span class="badge bg-${item.action_type === 'add' ? 'success' : 'danger'}">
                    ${item.action_type === 'add' ? 'Add' : 'Remove'}
                </span>
            </td>
            <td>${item.quantity}</td>
            <td>
                <button class="btn btn-sm btn-danger" onclick="removeItem(${index})">
                    <i class="ti ti-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });
    
    document.getElementById('items-count').textContent = changeOrderItems.length;
}

function saveDraft() {
    const showId = document.getElementById('show_id').value;
    const createdBy = document.getElementById('created_by').value;
    
    if (!showId) {
        alert('Please select a show');
        return;
    }
    
    if (changeOrderItems.length === 0) {
        alert('Please add at least one item');
        return;
    }
    
    fetch('change_order_create.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `ajax=1&action=save_draft&show_id=${showId}&created_by=${encodeURIComponent(createdBy)}&items=${encodeURIComponent(JSON.stringify(changeOrderItems))}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Draft saved successfully');
            window.location.href = 'change_order_view.php?id=' + data.id;
        }
    });
}

function finalizeChangeOrder() {
    const showId = document.getElementById('show_id').value;
    const createdBy = document.getElementById('created_by').value;
    
    if (!showId) {
        alert('Please select a show');
        return;
    }
    
    if (changeOrderItems.length === 0) {
        alert('Please add at least one item');
        return;
    }
    
    if (!confirm('Finalize this change order? This will affect inventory and cannot be undone.')) {
        return;
    }
    
    const changeOrderId = <?php echo $changeOrderId; ?>;
    
    fetch('change_order_create.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `ajax=1&action=finalize&change_order_id=${changeOrderId}&show_id=${showId}&created_by=${encodeURIComponent(createdBy)}&items=${encodeURIComponent(JSON.stringify(changeOrderItems))}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Change order finalized successfully');
            window.location.href = 'change_order_view.php?id=' + data.id;
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

ob_start();
?>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label required">Show</label>
                        <select id="show_id" class="form-select" <?php echo $changeOrderId > 0 ? 'disabled' : ''; ?>>
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
                               value="<?php echo sanitize($changeOrder['created_by'] ?? ''); ?>"
                               placeholder="Your name">
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title">Add/Remove Items</h3>
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
                            <th>Action</th>
                            <th>Quantity</th>
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
                    <button class="btn btn-success" onclick="finalizeChangeOrder()">
                        <i class="ti ti-check"></i> Finalize Change Order
                    </button>
                    <button class="btn btn-secondary" onclick="saveDraft()">
                        <i class="ti ti-device-floppy"></i> Save Draft
                    </button>
                    <a href="change_orders.php" class="btn btn-outline-secondary">
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
                <h5 class="modal-title">Add/Remove Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="modal-item-id">
                <div class="mb-3">
                    <label class="form-label">Item</label>
                    <div><strong id="modal-item-name"></strong></div>
                    <div class="text-muted small" id="modal-item-barcode"></div>
                    <div class="text-muted small">Available: <span id="modal-item-available"></span></div>
                </div>
                <div class="mb-3">
                    <label class="form-label required">Action</label>
                    <select id="modal-action-type" class="form-select">
                        <option value="add">Add to Show</option>
                        <option value="remove">Remove from Show</option>
                    </select>
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
