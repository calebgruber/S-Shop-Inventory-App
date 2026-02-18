<?php
// Start output buffering to catch any warnings/errors for AJAX requests
ob_start();

require_once '../includes/functions.php';
requirePermission('change_orders');

$coId = $_GET['id'] ?? null;

// Handle AJAX requests BEFORE any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    // Clean any output that might have been generated
    if (ob_get_level()) {
        ob_clean();
    }
    header('Content-Type: application/json');
    
    if (!$coId) {
        echo json_encode(['success' => false, 'message' => 'Invalid change order']);
        exit;
    }
    
    try {
        if ($_POST['action'] === 'search_items') {
            $query = trim($_POST['query']);
            
            if (empty($query)) {
                echo json_encode(['success' => false, 'message' => 'Please enter a search term']);
                exit;
            }
            
            // First try exact barcode match
            $exactMatch = getItemByBarcode($query);
            if ($exactMatch) {
                echo json_encode([
                    'success' => true,
                    'single' => true,
                    'item' => [
                        'id' => $exactMatch['id'],
                        'name' => $exactMatch['name'],
                        'barcode' => $exactMatch['barcode'],
                        'in_stock' => $exactMatch['in_stock_quantity'],
                        'category' => $exactMatch['category_name'] ?? ''
                    ]
                ]);
                exit;
            }
            
            // Otherwise search for matches
            $items = searchItems($query);
            
            if (empty($items)) {
                echo json_encode(['success' => false, 'message' => 'No items found matching "' . $query . '"']);
                exit;
            }
            
            // If only one result, treat it like exact match
            if (count($items) === 1) {
                $item = $items[0];
                echo json_encode([
                    'success' => true,
                    'single' => true,
                    'item' => [
                        'id' => $item['id'],
                        'name' => $item['name'],
                        'barcode' => $item['barcode'],
                        'in_stock' => $item['in_stock_quantity'],
                        'category' => $item['category_name'] ?? ''
                    ]
                ]);
                exit;
            }
            
            // Multiple results - return them all
            $itemsData = [];
            foreach ($items as $item) {
                $itemsData[] = [
                    'id' => $item['id'],
                    'name' => $item['name'],
                    'barcode' => $item['barcode'],
                    'in_stock' => $item['in_stock_quantity'],
                    'category' => $item['category_name'] ?? ''
                ];
            }
            
            echo json_encode([
                'success' => true,
                'single' => false,
                'items' => $itemsData
            ]);
            exit;
        }
        
        if ($_POST['action'] === 'check_item') {
            $itemBarcode = $_POST['barcode'];
            $item = getItemByBarcode($itemBarcode);
            
            if (!$item) {
                echo json_encode(['success' => false, 'message' => 'Item not found']);
                exit;
            }
            
            echo json_encode([
                'success' => true,
                'item' => [
                    'id' => $item['id'],
                    'name' => $item['name'],
                    'barcode' => $item['barcode'],
                    'in_stock' => $item['in_stock_quantity']
                ]
            ]);
            exit;
        }
        
        if ($_POST['action'] === 'add_item') {
            $item = getItemByBarcode($_POST['barcode']);
            if (!$item) {
                echo json_encode(['success' => false, 'message' => 'Not found']);
                exit;
            }
            
            // Validate and normalize type parameter
            $type = strtolower(trim($_POST['type'] ?? ''));
            if (!in_array($type, ['add', 'remove'], true)) {
                logMessage("Invalid type parameter received: '$type'", 'WARNING');
                echo json_encode(['success' => false, 'message' => 'Invalid type parameter. Must be "add" or "remove"']);
                exit;
            }
            
            try {
                getDB()->query(
                    "INSERT INTO change_order_items (change_order_id, item_id, type, quantity) VALUES (?, ?, ?, ?)",
                    [$coId, $item['id'], $type, (int)$_POST['quantity']]
                );
                logMessage("Change order item added: CO=$coId, Item={$item['id']}, Type=$type, Qty={$_POST['quantity']}", 'INFO');
            } catch (Exception $e) {
                logException($e, "Error adding item to change order");
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
                exit;
            }
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        if ($_POST['action'] === 'remove_item') {
            getDB()->query(
                "DELETE FROM change_order_items WHERE change_order_id = ? AND item_id = ?",
                [$coId, $_POST['item_id']]
            );
            echo json_encode(['success' => true]);
            exit;
        }
        
        if ($_POST['action'] === 'save_draft') {
            echo json_encode(['success' => true, 'message' => 'Draft saved']);
            exit;
        }
        
        if ($_POST['action'] === 'finalize') {
            $changeOrder = getChangeOrderById($coId);
            $currentUser = getCurrentUser();
            $items = getChangeOrderItems($coId);
            
            // Check if approval is required
            $requiresApproval = requiresApproval();
            
            // Get or create master pullsheet for this show
            $masterPullsheet = getDB()->fetchOne(
                "SELECT id FROM pullsheets WHERE show_id = ? ORDER BY created_at ASC LIMIT 1",
                [$changeOrder['show_id']]
            );
            
            // If no master pullsheet exists, create one
            if (!$masterPullsheet) {
                $barcode = generateUniqueBarcode('PS');
                $createdBy = $currentUser['name'] ?? 'Unknown';
                getDB()->query(
                    "INSERT INTO pullsheets (show_id, barcode, created_by, status) VALUES (?, ?, ?, 'draft')",
                    [$changeOrder['show_id'], $barcode, $createdBy]
                );
                $masterPullsheetId = getDB()->lastInsertId();
            } else {
                $masterPullsheetId = $masterPullsheet['id'];
            }
            
            // Process item changes
            foreach ($items as $item) {
                $qty = abs($item['quantity_change']);
                
                if ($item['type'] === 'add') {
                    // Add items to show (reserve them)
                    getDB()->query(
                        "INSERT INTO item_allocations (item_id, show_id, change_order_id, quantity, status) 
                         VALUES (?, ?, ?, ?, 'checked_out')",
                        [$item['item_id'], $changeOrder['show_id'], $coId, $qty]
                    );
                    updateItemStock($item['item_id'], -$qty);
                    
                    // Update master pullsheet: add or increase quantity
                    $existingItem = getDB()->fetchOne(
                        "SELECT id, quantity_needed FROM pullsheet_items 
                         WHERE pullsheet_id = ? AND item_id = ?",
                        [$masterPullsheetId, $item['item_id']]
                    );
                    
                    if ($existingItem) {
                        getDB()->query(
                            "UPDATE pullsheet_items SET quantity_needed = quantity_needed + ? WHERE id = ?",
                            [$qty, $existingItem['id']]
                        );
                    } else {
                        getDB()->query(
                            "INSERT INTO pullsheet_items (pullsheet_id, item_id, quantity_needed) VALUES (?, ?, ?)",
                            [$masterPullsheetId, $item['item_id'], $qty]
                        );
                    }
                } elseif ($item['type'] === 'remove') {
                    // Remove items from show (return them)
                    // Find the allocation to remove
                    $allocation = getDB()->fetchOne(
                        "SELECT id, quantity FROM item_allocations 
                         WHERE item_id = ? AND show_id = ? AND status = 'checked_out'
                         ORDER BY created_at DESC LIMIT 1",
                        [$item['item_id'], $changeOrder['show_id']]
                    );
                    
                    if ($allocation) {
                        if ($qty >= $allocation['quantity']) {
                            // Remove entire allocation
                            getDB()->query("DELETE FROM item_allocations WHERE id = ?", [$allocation['id']]);
                            updateItemStock($item['item_id'], $allocation['quantity']);
                        } else {
                            // Reduce allocation quantity
                            getDB()->query(
                                "UPDATE item_allocations SET quantity = quantity - ? WHERE id = ?",
                                [$qty, $allocation['id']]
                            );
                            updateItemStock($item['item_id'], $qty);
                        }
                    }
                    
                    // Update master pullsheet: reduce or remove quantity
                    $existingItem = getDB()->fetchOne(
                        "SELECT id, quantity_needed FROM pullsheet_items 
                         WHERE pullsheet_id = ? AND item_id = ?",
                        [$masterPullsheetId, $item['item_id']]
                    );
                    
                    if ($existingItem) {
                        $newQty = $existingItem['quantity_needed'] - $qty;
                        if ($newQty <= 0) {
                            // Remove item from pullsheet if quantity is 0 or less
                            getDB()->query(
                                "DELETE FROM pullsheet_items WHERE id = ?",
                                [$existingItem['id']]
                            );
                        } else {
                            // Reduce quantity
                            getDB()->query(
                                "UPDATE pullsheet_items SET quantity_needed = ? WHERE id = ?",
                                [$newQty, $existingItem['id']]
                            );
                        }
                    }
                }
            }
            
            if ($requiresApproval) {
                // Set to finalized but requires approval
                getDB()->query(
                    "UPDATE change_orders SET status = 'finalized', finalized_at = NOW(), 
                     requires_approval = TRUE, approval_status = 'pending' WHERE id = ?",
                    [$coId]
                );
                
                // Update pullsheet from change order
                updatePullsheetFromChangeOrder($coId);
                
                // Notify admins for approval
                createNotificationForAdmins(
                    'change_order_pending_approval',
                    "Change order for " . $changeOrder['show_name'] . " from " . htmlspecialchars($currentUser['name']) . " needs approval",
                    "/change-orders/view?id=" . $coId
                );
                
                echo json_encode(['success' => true, 'message' => 'Change order submitted for approval']);
            } else {
                // Admin doesn't need approval
                getDB()->query("UPDATE change_orders SET status = 'finalized', finalized_at = NOW() WHERE id = ?", [$coId]);
                
                // Update pullsheet from change order
                updatePullsheetFromChangeOrder($coId);
                
                // Create notification for all operations users
                createNotificationForOperations(
                    'change_order',
                    "Change order finalized for " . $changeOrder['show_name'],
                    "change_orders.php"
                );
                
                echo json_encode(['success' => true, 'message' => 'Change order finalized']);
            }
            
            // Generate PDF for the change order (non-blocking)
            try {
                $pdfPath = generateChangeOrderPDF($coId);
            } catch (Exception $e) {
                // PDF generation failure is not critical
                logException($e, 'Change order PDF generation failed');
            }
            exit;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// Regular page rendering starts here
// Flush output buffer for HTML rendering
if (ob_get_level()) {
    ob_end_flush();
}

$pageTitle = 'Edit Change Order';
require_once '../includes/header.php';

$currentUser = getCurrentUser();
$isDesigner = $currentUser['role'] === 'designer';

if (!$coId) redirect('change_orders');

$co = getChangeOrderById($coId);
if (!$co) {
    setAlert('Change order not found', 'danger');
    redirect('change_orders');
}

// Check permission for designers
if ($isDesigner && !canAccessShow($currentUser['id'], $co['show_id'])) {
    setAlert('You do not have permission to edit this change order', 'danger');
    redirect('change_orders');
}

$items = getChangeOrderItems($coId);
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Change Order - <?php echo htmlspecialchars($co['show_name']); ?></h3>
            </div>
            <div class="card-body">
                <?php if ($co['status'] === 'draft'): ?>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="input-group">
                                <input type="text" class="form-control barcode-autofocus" id="itemBarcode" placeholder="Scan or search item barcode...">
                                <button class="btn btn-primary" type="button" id="searchBtn">
                                    <i class="ti ti-search"></i> Search
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="btn-group w-100" role="group">
                                <button class="btn btn-outline-secondary" id="saveDraftBtn">
                                    <i class="ti ti-device-floppy"></i> Save Draft
                                </button>
                                <button class="btn btn-success" id="finalizeBtn">
                                    <i class="ti ti-check"></i> Finalize
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <table class="table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Type</th>
                            <th>Quantity</th>
                            <?php if ($co['status'] === 'draft'): ?>
                            <th class="w-1">Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody id="itemsTable">
                        <?php foreach ($items as $item): ?>
                            <tr data-item-id="<?php echo $item['item_id']; ?>">
                                <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                <td><span class="badge bg-<?php echo $item['type'] === 'add' ? 'success' : 'danger'; ?>"><?php echo ucfirst($item['type']); ?></span></td>
                                <td><?php echo abs($item['quantity_change']); ?></td>
                                <?php if ($co['status'] === 'draft'): ?>
                                <td>
                                    <button class="btn btn-sm btn-danger remove-item-btn" data-item-id="<?php echo $item['item_id']; ?>">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if ($co['status'] === 'draft'): ?>
<!-- Search Results Modal -->
<div class="modal fade" id="searchResultsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Search Results</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="list-group" id="searchResultsList">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Item to Change Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="itemInfo"></div>
                <div class="mb-3">
                    <label class="form-label">Type</label>
                    <select class="form-select" id="typeInput">
                        <option value="add">Add</option>
                        <option value="remove">Remove</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Quantity</label>
                    <input type="number" class="form-control" id="qtyInput" value="1" min="1">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmBtn">Add Item</button>
            </div>
        </div>
    </div>
</div>

<script>
// Wrap in DOMContentLoaded to ensure elements exist
document.addEventListener('DOMContentLoaded', function() {
    console.log('Change order edit page loaded');
    
let selectedItem = null;
const modalEl = document.getElementById('addModal');
const searchResultsModalEl = document.getElementById('searchResultsModal');
const barcodeInput = document.getElementById('itemBarcode');
const searchBtn = document.getElementById('searchBtn');

if (!modalEl) {
    console.error('Modal element not found');
    return;
}

if (!searchResultsModalEl) {
    console.error('Search results modal element not found');
    return;
}

if (!barcodeInput) {
    console.error('Barcode input not found');
    return;
}

const modal = new bootstrap.Modal(modalEl);
const searchResultsModal = new bootstrap.Modal(searchResultsModalEl);
console.log('Modals initialized successfully');

if (searchBtn) {
    searchBtn.addEventListener('click', searchItem);
    console.log('Search button handler attached');
}

// Fix Enter key handler
barcodeInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        e.stopPropagation();
        console.log('Enter key pressed, searching...');
        searchItem();
    }
});
console.log('Barcode input Enter key handler attached');

function searchItem() {
    const query = barcodeInput.value.trim();
    if (!query) return;
    
    console.log('Searching for:', query);
    
    fetch('?id=<?php echo htmlspecialchars($coId, ENT_QUOTES, 'UTF-8'); ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'ajax=1&action=search_items&query=' + encodeURIComponent(query)
    })
    .then(r => r.json())
    .then(data => {
        console.log('Search result:', data);
        if (data.success) {
            if (data.single) {
                // Single result - show add modal directly
                showAddItemModal(data.item);
            } else {
                // Multiple results - show selection modal
                showSearchResults(data.items);
            }
        } else {
            alert(data.message || 'No items found');
            playErrorSound();
        }
        barcodeInput.value = '';
        barcodeInput.focus();
    })
    .catch(err => {
        console.error('Search error:', err);
        alert('Error searching for items');
        playErrorSound();
    });
}

function showSearchResults(items) {
    const listEl = document.getElementById('searchResultsList');
    listEl.innerHTML = '';
    
    items.forEach(item => {
        const itemEl = document.createElement('button');
        itemEl.type = 'button';
        itemEl.className = 'list-group-item list-group-item-action';
        itemEl.innerHTML = `
            <div class="d-flex w-100 justify-content-between align-items-center">
                <div>
                    <h6 class="mb-1">${escapeHtml(item.name)}</h6>
                    <small class="text-muted">Barcode: ${escapeHtml(item.barcode)}</small>
                    ${item.category ? `<br><small class="text-muted">Category: ${escapeHtml(item.category)}</small>` : ''}
                </div>
                <span class="badge ${item.in_stock > 0 ? 'bg-success' : 'bg-danger'}">
                    ${item.in_stock} in stock
                </span>
            </div>
        `;
        itemEl.addEventListener('click', () => {
            searchResultsModal.hide();
            showAddItemModal(item);
        });
        listEl.appendChild(itemEl);
    });
    
    searchResultsModal.show();
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}


function showAddItemModal(item) {
    if (!modal) {
        console.error('Modal not initialized');
        return;
    }
    console.log('Showing modal for item:', item);
    selectedItem = item;
    const infoHtml = `
        <div class="mb-3">
            <strong>${item.name}</strong><br>
            <small class="text-muted">Barcode: ${item.barcode}</small><br>
            <span class="badge ${item.in_stock > 0 ? 'bg-success' : 'bg-danger'}">
                ${item.in_stock} in stock
            </span>
        </div>
    `;
    document.getElementById('itemInfo').innerHTML = infoHtml;
    document.getElementById('qtyInput').value = 1;
    document.getElementById('qtyInput').focus();
    modal.show();
    console.log('Modal should be visible now');
}

// Function to add item (called by button or Enter key)
function addItemToOrder() {
    if (!selectedItem) return;
    
    fetch('?id=<?php echo htmlspecialchars($coId, ENT_QUOTES, 'UTF-8'); ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `ajax=1&action=add_item&barcode=${selectedItem.barcode}&quantity=${document.getElementById('qtyInput').value}&type=${document.getElementById('typeInput').value}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            playSuccessSound();
            modal.hide();
            window.location.href = window.location.href;
        } else {
            alert(data.message || 'Error adding item');
            playErrorSound();
        }
    })
    .catch(err => {
        console.error('Add error:', err);
        alert('Error adding item');
        playErrorSound();
    });
}

// Button click handler
document.getElementById('confirmBtn').addEventListener('click', addItemToOrder);

// Enter key handler on quantity input
document.getElementById('qtyInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        addItemToOrder();
    }
});

document.getElementById('saveDraftBtn').addEventListener('click', () => {
    fetch('?id=<?php echo htmlspecialchars($coId, ENT_QUOTES, 'UTF-8'); ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'ajax=1&action=save_draft'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            playSuccessSound();
            // Stay on the same page with a success alert
            window.location.href = window.location.href;
        } else {
            alert(data.message || 'Error saving draft');
            playErrorSound();
        }
    })
    .catch(err => {
        console.error('Save error:', err);
        alert('Error saving draft');
        playErrorSound();
    });
});

document.getElementById('finalizeBtn').addEventListener('click', () => {
    if (!confirm('Finalize this change order? This action cannot be undone.')) return;
    
    fetch('?id=<?php echo htmlspecialchars($coId, ENT_QUOTES, 'UTF-8'); ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'ajax=1&action=finalize'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            playSuccessSound();
            // Redirect to change order view page
            window.location.href = '/change-orders/view?id=<?php echo htmlspecialchars($coId, ENT_QUOTES, 'UTF-8'); ?>&finalized=1';
        } else {
            alert(data.message || 'Error finalizing');
            playErrorSound();
        }
    })
    .catch(err => {
        console.error('Finalize error:', err);
        alert('Error finalizing');
        playErrorSound();
    });
});

// Remove item button handlers
document.querySelectorAll('.remove-item-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const itemId = this.getAttribute('data-item-id');
        if (!confirm('Remove this item from the change order?')) return;
        
        fetch('?id=<?php echo htmlspecialchars($coId, ENT_QUOTES, 'UTF-8'); ?>', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'ajax=1&action=remove_item&item_id=' + encodeURIComponent(itemId)
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                playSuccessSound();
                window.location.href = window.location.href;
            } else {
                alert('Error removing item');
                playErrorSound();
            }
        })
        .catch(err => {
            console.error('Remove error:', err);
            alert('Error removing item');
            playErrorSound();
        });
    });
});

}); // End DOMContentLoaded
</script>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
