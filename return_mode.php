<?php
require_once 'config.php';
require_once 'db.php';
require_once 'functions.php';

$db = getDB();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'scan_barcode') {
        $barcode = trim($_POST['barcode'] ?? '');
        $returnerName = trim($_POST['returner_name'] ?? '');
        
        // Try to find if it's a pull sheet or change order barcode
        $stmt = $db->prepare("SELECT id, barcode, show_id, 'pull_sheet' as type FROM pull_sheets WHERE barcode = ?
                              UNION ALL
                              SELECT id, barcode, show_id, 'change_order' as type FROM change_orders WHERE barcode = ?");
        $stmt->bind_param("ss", $barcode, $barcode);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($doc = $result->fetch_assoc()) {
            // Get items for this document
            $items = [];
            if ($doc['type'] === 'pull_sheet') {
                $stmt = $db->prepare("SELECT psi.*, i.name, i.barcode 
                                      FROM pull_sheet_items psi 
                                      INNER JOIN items i ON psi.item_id = i.id 
                                      WHERE psi.pull_sheet_id = ?");
                $stmt->bind_param("i", $doc['id']);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $items[] = [
                        'id' => $row['item_id'],
                        'name' => $row['name'],
                        'barcode' => $row['barcode'],
                        'quantity' => $row['quantity_picked']
                    ];
                }
            } else {
                $stmt = $db->prepare("SELECT coi.*, i.name, i.barcode 
                                      FROM change_order_items coi 
                                      INNER JOIN items i ON coi.item_id = i.id 
                                      WHERE coi.change_order_id = ? AND coi.action_type = 'add'");
                $stmt->bind_param("i", $doc['id']);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $items[] = [
                        'id' => $row['item_id'],
                        'name' => $row['name'],
                        'barcode' => $row['barcode'],
                        'quantity' => $row['quantity_processed']
                    ];
                }
            }
            
            echo json_encode([
                'success' => true,
                'type' => 'document',
                'document' => $doc,
                'items' => $items
            ]);
            exit;
        }
        
        // Try to find if it's an item barcode
        $stmt = $db->prepare("SELECT * FROM items WHERE barcode = ?");
        $stmt->bind_param("s", $barcode);
        $stmt->execute();
        $item = $stmt->get_result()->fetch_assoc();
        
        if ($item) {
            echo json_encode([
                'success' => true,
                'type' => 'item',
                'item' => $item
            ]);
            exit;
        }
        
        echo json_encode(['success' => false, 'message' => 'Barcode not found']);
        exit;
    }
    
    if ($_POST['action'] === 'return_item') {
        $itemId = (int)$_POST['item_id'];
        $quantity = (int)$_POST['quantity'];
        $returnerName = trim($_POST['returner_name'] ?? '');
        $showId = isset($_POST['show_id']) ? (int)$_POST['show_id'] : null;
        
        // Return inventory
        updateItemQuantity($itemId, $quantity, 'return');
        logTransaction($itemId, 'return', $quantity, $returnerName, null, null, $showId);
        
        echo json_encode(['success' => true, 'message' => 'Item returned successfully']);
        exit;
    }
}

$pageTitle = "Return Mode - " . APP_NAME;
$hideHeader = true;

$additionalCSS = '<style>
body { background: var(--tblr-body-bg); }
.fullscreen-mode { padding: 20px; }
.return-card { font-size: 1.2rem; margin-bottom: 15px; }
.return-card .card-body { padding: 20px; }
.return-card h3 { font-size: 1.5rem; margin-bottom: 10px; }
.barcode-input { font-size: 1.5rem; padding: 15px; }
</style>';

$additionalJS = <<<'JS'
<script>
let returnerName = '';
let isFullscreen = false;
let currentDocument = null;
let pendingItems = [];

document.addEventListener('DOMContentLoaded', function() {
    // Show name entry modal
    const nameModal = new bootstrap.Modal(document.getElementById('name-modal'));
    nameModal.show();
});

function startReturning() {
    returnerName = document.getElementById('returner-name').value.trim();
    if (!returnerName) {
        alert('Please enter your name');
        return;
    }
    
    bootstrap.Modal.getInstance(document.getElementById('name-modal')).hide();
    document.getElementById('barcode-input').focus();
}

function toggleFullscreen() {
    const container = document.getElementById('return-container');
    if (!isFullscreen) {
        container.classList.add('fullscreen-mode');
        document.getElementById('fullscreen-btn').innerHTML = '<i class="ti ti-minimize"></i> Exit Fullscreen';
        isFullscreen = true;
    } else {
        container.classList.remove('fullscreen-mode');
        document.getElementById('fullscreen-btn').innerHTML = '<i class="ti ti-maximize"></i> Fullscreen';
        isFullscreen = false;
    }
}

function scanBarcode(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        const barcode = e.target.value.trim();
        if (barcode) {
            processBarcode(barcode);
            e.target.value = '';
        }
    }
}

function processBarcode(barcode) {
    fetch('return_mode.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `ajax=1&action=scan_barcode&barcode=${encodeURIComponent(barcode)}&returner_name=${encodeURIComponent(returnerName)}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            if (data.type === 'document') {
                playSuccessSound();
                currentDocument = data.document;
                pendingItems = data.items;
                showNotification('Document scanned: ' + data.document.barcode, 'success');
                renderPendingItems();
            } else if (data.type === 'item') {
                if (pendingItems.length === 0) {
                    // Single item return
                    showItemReturnModal(data.item, null);
                } else {
                    // Check if item is in pending items
                    const pendingItem = pendingItems.find(i => i.barcode === data.item.barcode);
                    if (pendingItem) {
                        returnItem(data.item.id, 1, currentDocument.show_id);
                        // Remove from pending
                        pendingItem.quantity--;
                        if (pendingItem.quantity <= 0) {
                            pendingItems = pendingItems.filter(i => i.barcode !== data.item.barcode);
                        }
                        renderPendingItems();
                        playSuccessSound();
                        showNotification('Item returned: ' + data.item.name, 'success');
                    } else {
                        playErrorSound();
                        showNotification('Item not in current document', 'danger');
                    }
                }
            }
        } else {
            playErrorSound();
            showNotification('Error: ' + data.message, 'danger');
        }
        document.getElementById('barcode-input').focus();
    });
}

function showItemReturnModal(item, showId) {
    document.getElementById('return-item-name').textContent = item.name;
    document.getElementById('return-item-barcode').textContent = item.barcode;
    document.getElementById('return-item-id').value = item.id;
    document.getElementById('return-item-quantity').value = '1';
    document.getElementById('return-show-id').value = showId || '';
    
    const modal = new bootstrap.Modal(document.getElementById('item-return-modal'));
    modal.show();
    
    setTimeout(() => {
        document.getElementById('return-item-quantity').focus();
        document.getElementById('return-item-quantity').select();
    }, 500);
}

function confirmReturn() {
    const itemId = document.getElementById('return-item-id').value;
    const quantity = document.getElementById('return-item-quantity').value;
    const showId = document.getElementById('return-show-id').value;
    
    returnItem(itemId, quantity, showId);
    bootstrap.Modal.getInstance(document.getElementById('item-return-modal')).hide();
}

function returnItem(itemId, quantity, showId) {
    fetch('return_mode.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `ajax=1&action=return_item&item_id=${itemId}&quantity=${quantity}&show_id=${showId || ''}&returner_name=${encodeURIComponent(returnerName)}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            playSuccessSound();
            showNotification(data.message, 'success');
        } else {
            playErrorSound();
            showNotification('Error returning item', 'danger');
        }
    });
}

function renderPendingItems() {
    const container = document.getElementById('pending-items');
    
    if (pendingItems.length === 0) {
        container.innerHTML = '<div class="alert alert-success">All items returned!</div>';
        currentDocument = null;
        return;
    }
    
    container.innerHTML = '';
    
    const header = document.createElement('div');
    header.className = 'alert alert-info mb-3';
    header.innerHTML = `<strong>Returning items for: ${escapeHtml(currentDocument.barcode)}</strong>`;
    container.appendChild(header);
    
    const row = document.createElement('div');
    row.className = 'row';
    
    pendingItems.forEach(item => {
        const div = document.createElement('div');
        div.className = 'col-md-6 col-lg-4';
        div.innerHTML = `
            <div class="card return-card item-card-yellow">
                <div class="card-body">
                    <h3 class="card-title">${escapeHtml(item.name)}</h3>
                    <div class="text-muted mb-2">${escapeHtml(item.barcode)}</div>
                    <div class="h2 mb-0">Qty: ${item.quantity}</div>
                </div>
            </div>
        `;
        row.appendChild(div);
    });
    
    container.appendChild(row);
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} position-fixed top-0 start-50 translate-middle-x mt-3`;
    notification.style.zIndex = '10000';
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
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

<div id="return-container">
    <div class="container-xl">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="m-0">Return Mode</h1>
                <div class="text-muted">Scan pull sheet/change order or individual items</div>
            </div>
            <div>
                <button id="fullscreen-btn" class="btn btn-primary me-2" onclick="toggleFullscreen()">
                    <i class="ti ti-maximize"></i> Fullscreen
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="ti ti-x"></i> Exit
                </a>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-body">
                <label class="form-label">Scan Barcode</label>
                <input type="text" id="barcode-input" class="form-control barcode-input auto-focus" 
                       placeholder="Scan document or item barcode" onkeypress="scanBarcode(event)">
                <small class="form-hint">Scan pull sheet/change order first, then scan items to return</small>
            </div>
        </div>
        
        <div id="pending-items">
            <!-- Pending items will be rendered here -->
        </div>
    </div>
</div>

<!-- Name Entry Modal -->
<div class="modal fade" id="name-modal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Enter Your Name</h5>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label required">Returner Name</label>
                    <input type="text" id="returner-name" class="form-control" 
                           placeholder="Enter your name" 
                           onkeypress="if(event.key==='Enter') startReturning()">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="startReturning()">
                    Start Returning
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Item Return Modal -->
<div class="modal fade" id="item-return-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Return Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="return-item-id">
                <input type="hidden" id="return-show-id">
                <div class="mb-3">
                    <label class="form-label">Item</label>
                    <div><strong id="return-item-name"></strong></div>
                    <div class="text-muted small" id="return-item-barcode"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label required">Quantity</label>
                    <input type="number" id="return-item-quantity" class="form-control" min="1" value="1">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="confirmReturn()">Return Item</button>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require 'layout.php';
?>
