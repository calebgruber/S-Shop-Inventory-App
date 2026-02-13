<?php
require_once 'includes/functions.php';
requirePermission('pullsheets');

$pullsheetId = $_GET['id'] ?? null;

// Handle AJAX requests BEFORE any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    if (!$pullsheetId) {
        echo json_encode(['success' => false, 'message' => 'Invalid pullsheet']);
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
            $itemBarcode = $_POST['barcode'];
            $quantity = (int)$_POST['quantity'];
            
            $item = getItemByBarcode($itemBarcode);
            if (!$item) {
                echo json_encode(['success' => false, 'message' => 'Item not found']);
                exit;
            }
            
            // Check if already in pullsheet
            $existing = getDB()->fetchOne(
                "SELECT id FROM pullsheet_items WHERE pullsheet_id = ? AND item_id = ?",
                [$pullsheetId, $item['id']]
            );
            
            if ($existing) {
                echo json_encode(['success' => false, 'message' => 'Item already in pullsheet']);
                exit;
            }
            
            getDB()->query(
                "INSERT INTO pullsheet_items (pullsheet_id, item_id, quantity_needed) VALUES (?, ?, ?)",
                [$pullsheetId, $item['id'], $quantity]
            );
            
            echo json_encode([
                'success' => true, 
                'message' => 'Item added',
                'item' => [
                    'id' => $item['id'],
                    'name' => $item['name'],
                    'barcode' => $item['barcode'],
                    'in_stock' => $item['in_stock_quantity'],
                    'quantity' => $quantity
                ]
            ]);
            exit;
        }
        
        if ($_POST['action'] === 'remove_item') {
            getDB()->query(
                "DELETE FROM pullsheet_items WHERE pullsheet_id = ? AND item_id = ?",
                [$pullsheetId, $_POST['item_id']]
            );
            echo json_encode(['success' => true]);
            exit;
        }
        
        if ($_POST['action'] === 'save_draft') {
            echo json_encode(['success' => true, 'message' => 'Draft saved']);
            exit;
        }
        
        if ($_POST['action'] === 'finalize') {
            $pullsheet = getPullsheetById($pullsheetId);
            $currentUser = getCurrentUser();
            
            // Check if all items are available
            $items = getPullsheetItems($pullsheetId);
            $unavailableItems = [];
            
            foreach ($items as $item) {
                if ($item['in_stock_quantity'] < $item['quantity_needed']) {
                    $unavailableItems[] = $item['item_name'] . ' (Need: ' . $item['quantity_needed'] . ', Available: ' . $item['in_stock_quantity'] . ')';
                }
            }
            
            if (!empty($unavailableItems)) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Cannot finalize: Some items are not available',
                    'unavailable_items' => $unavailableItems,
                    'can_save_draft' => true
                ]);
                exit;
            }
            
            // Check if approval is required (Designer or Production Audio)
            $requiresApproval = requiresApproval();
            
            // Mark items as reserved
            foreach ($items as $item) {
                getDB()->query(
                    "INSERT INTO item_allocations (item_id, show_id, pullsheet_id, quantity, status) 
                     VALUES (?, ?, ?, ?, 'reserved')",
                    [$item['item_id'], $pullsheet['show_id'], $pullsheetId, $item['quantity_needed']]
                );
                
                updateItemStock($item['item_id'], -$item['quantity_needed']);
            }
            
            if ($requiresApproval) {
                // Set to finalized but requires approval
                getDB()->query(
                    "UPDATE pullsheets SET status = 'finalized', finalized_at = NOW(), 
                     requires_approval = TRUE, approval_status = 'pending' WHERE id = ?",
                    [$pullsheetId]
                );
                
                // Notify admins for approval
                createNotificationForAdmins(
                    'pullsheet_pending_approval',
                    "Pullsheet for " . ($pullsheet['show_name'] ?? 'Student Requests') . " from " . htmlspecialchars($currentUser['name']) . " needs approval",
                    "pullsheet_view.php?id=" . $pullsheetId
                );
                
                echo json_encode(['success' => true, 'message' => 'Shop Order submitted for approval']);
            } else {
                // Admin doesn't need approval
                getDB()->query(
                    "UPDATE pullsheets SET status = 'finalized', finalized_at = NOW() WHERE id = ?",
                    [$pullsheetId]
                );
                
                // Create notification for users with pick_mode permission
                $pullsheet = getPullsheetById($pullsheetId);
                $showName = $pullsheet['show_name'] ?? 'Student Requests';
                createNotificationForDesigners(
                    'pending_pick',
                    "New shop order ready for picking: " . $showName,
                    "pick_mode.php?pullsheet=" . $pullsheet['barcode']
                );
                
                echo json_encode(['success' => true, 'message' => 'Shop Order finalized']);
            }
            exit;
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// Regular page rendering starts here
$pageTitle = 'Edit Shop Order';
require_once 'includes/header.php';

$currentUser = getCurrentUser();
$isDesigner = $currentUser['role'] === 'designer';

if (!$pullsheetId) {
    redirect('pullsheets');
}

$pullsheet = getPullsheetById($pullsheetId);
if (!$pullsheet) {
    setAlert('Shop Order not found', 'danger');
    redirect('pullsheets');
}

// Check permission for designers
if ($isDesigner && !canAccessShow($currentUser['id'], $pullsheet['show_id'])) {
    setAlert('You do not have permission to edit this pullsheet', 'danger');
    redirect('pullsheets');
}

if ($pullsheet['status'] !== 'draft') {
    redirect('pullsheet_view.php?id=' . $pullsheetId);
}

$items = getPullsheetItems($pullsheetId);
$allItems = getAllItems();
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Edit Shop Order - <?php echo htmlspecialchars($pullsheet['show_name'] ?? 'Student Requests'); ?></h3>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" class="form-control barcode-autofocus" id="itemBarcodeInput" 
                                   placeholder="Scan or search item barcode...">
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
                                <i class="ti ti-check"></i> Build Show (Finalize)
                            </button>
                        </div>
                    </div>
                </div>
                
                <div id="itemsList">
                    <h4>Items in Shop Order</h4>
                    <?php if (empty($items)): ?>
                        <p class="text-muted">No items added yet. Scan or search to add items.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-vcenter">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Barcode</th>
                                        <th>Qty Needed</th>
                                        <th>In Stock</th>
                                        <th>Status</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="itemsTableBody">
                                    <?php foreach ($items as $item): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                            <td><code><?php echo htmlspecialchars($item['item_barcode']); ?></code></td>
                                            <td><?php echo $item['quantity_needed']; ?></td>
                                            <td><?php echo $item['in_stock_quantity']; ?></td>
                                            <td>
                                                <?php if ($item['quantity_needed'] <= $item['in_stock_quantity']): ?>
                                                    <span class="badge bg-success">Available</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Insufficient Stock</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-danger remove-item-btn" data-item-id="<?php echo $item['item_id']; ?>">
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

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

<!-- Add Item Modal -->
<div class="modal fade" id="addItemModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Item to Shop Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="itemInfo"></div>
                <div class="mb-3">
                    <label class="form-label">Quantity Needed</label>
                    <input type="number" class="form-control" id="quantityInput" min="1" value="1">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmAddBtn">Add Item</button>
            </div>
        </div>
    </div>
</div>

<script>
// Wrap everything in DOMContentLoaded to ensure elements exist
document.addEventListener('DOMContentLoaded', function() {
    console.log('Shop Order edit page loaded');
    
    let selectedItem = null;
    const modalEl = document.getElementById('addItemModal');
    const searchResultsModalEl = document.getElementById('searchResultsModal');
    const barcodeInput = document.getElementById('itemBarcodeInput');
    const searchBtn = document.getElementById('searchBtn');
    const saveDraftBtn = document.getElementById('saveDraftBtn');
    const finalizeBtn = document.getElementById('finalizeBtn');
    
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
    
    // Search button click handler
    if (searchBtn) {
        searchBtn.addEventListener('click', searchItem);
        console.log('Search button handler attached');
    }
    
    // Enter key handler for barcode input
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
    
    fetch('?id=<?php echo $pullsheetId; ?>', {
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
    document.getElementById('quantityInput').value = 1;
    document.getElementById('quantityInput').focus();
    modal.show();
    console.log('Modal should be visible now');
}

document.getElementById('confirmAddBtn').addEventListener('click', function() {
    if (!selectedItem) return;
    
    const quantity = parseInt(document.getElementById('quantityInput').value);
    
    fetch('?id=<?php echo $pullsheetId; ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `ajax=1&action=add_item&barcode=${selectedItem.barcode}&quantity=${quantity}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            playSuccessSound();
            modal.hide();
            window.location.href = window.location.href;
        } else {
            alert(data.message);
            playErrorSound();
        }
    });
});

document.querySelectorAll('.remove-item-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        if (!confirm('Remove this item from pullsheet?')) return;
        
        const itemId = this.dataset.itemId;
        fetch('?id=<?php echo $pullsheetId; ?>', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `ajax=1&action=remove_item&item_id=${itemId}`
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                window.location.href = window.location.href;
            }
        });
    });
});

document.getElementById('saveDraftBtn').addEventListener('click', function() {
    fetch('?id=<?php echo $pullsheetId; ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'ajax=1&action=save_draft'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            playSuccessSound();
            window.location.href = 'pullsheets.php?saved=1';
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

document.getElementById('finalizeBtn').addEventListener('click', function() {
    if (!confirm('Finalize this pullsheet? This will reserve all items and generate a barcode for picking.')) return;
    
    fetch('?id=<?php echo $pullsheetId; ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'ajax=1&action=finalize'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            playSuccessSound();
            window.location.href = 'pullsheet_view.php?id=<?php echo $pullsheetId; ?>&finalized=1';
        } else {
            alert(data.message || 'Error finalizing pullsheet');
            playErrorSound();
        }
    })
    .catch(err => {
        console.error('Finalize error:', err);
        alert('Error finalizing pullsheet');
        playErrorSound();
    });
});

}); // End DOMContentLoaded
</script>

<?php require_once 'includes/footer.php'; ?>
