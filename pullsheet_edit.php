<?php
require_once 'includes/functions.php';

$pullsheetId = $_GET['id'] ?? null;

// Handle AJAX requests BEFORE any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    if (!$pullsheetId) {
        echo json_encode(['success' => false, 'message' => 'Invalid pullsheet']);
        exit;
    }
    
    try {
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
            
            // Mark items as reserved
            $items = getPullsheetItems($pullsheetId);
            foreach ($items as $item) {
                getDB()->query(
                    "INSERT INTO item_allocations (item_id, show_id, pullsheet_id, quantity, status) 
                     VALUES (?, ?, ?, ?, 'reserved')",
                    [$item['item_id'], $pullsheet['show_id'], $pullsheetId, $item['quantity_needed']]
                );
                
                updateItemStock($item['item_id'], -$item['quantity_needed']);
            }
            
            getDB()->query(
                "UPDATE pullsheets SET status = 'finalized', finalized_at = NOW() WHERE id = ?",
                [$pullsheetId]
            );
            
            echo json_encode(['success' => true, 'message' => 'Pullsheet finalized']);
            exit;
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// Regular page rendering starts here
$pageTitle = 'Edit Pullsheet';
require_once 'includes/header.php';

if (!$pullsheetId) {
    redirect('pullsheets.php');
}

$pullsheet = getPullsheetById($pullsheetId);
if (!$pullsheet) {
    setAlert('Pullsheet not found', 'danger');
    redirect('pullsheets.php');
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
                <h3 class="card-title">Edit Pullsheet - <?php echo htmlspecialchars($pullsheet['show_name']); ?></h3>
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
                    <h4>Items in Pullsheet</h4>
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

<!-- Add Item Modal -->
<div class="modal fade" id="addItemModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Item to Pullsheet</h5>
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
    console.log('Pullsheet edit page loaded');
    
    let selectedItem = null;
    const modalEl = document.getElementById('addItemModal');
    const barcodeInput = document.getElementById('itemBarcodeInput');
    const searchBtn = document.getElementById('searchBtn');
    const saveDraftBtn = document.getElementById('saveDraftBtn');
    const finalizeBtn = document.getElementById('finalizeBtn');
    
    if (!modalEl) {
        console.error('Modal element not found');
        return;
    }
    
    if (!barcodeInput) {
        console.error('Barcode input not found');
        return;
    }
    
    const modal = new bootstrap.Modal(modalEl);
    console.log('Modal initialized successfully');
    
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
    const barcode = barcodeInput.value.trim();
    if (!barcode) return;
    
    console.log('Searching for barcode:', barcode);
    
    fetch('?id=<?php echo $pullsheetId; ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'ajax=1&action=check_item&barcode=' + encodeURIComponent(barcode)
    })
    .then(r => r.json())
    .then(data => {
        console.log('Search result:', data);
        if (data.success) {
            showAddItemModal(data.item);
        } else {
            alert(data.message || 'Item not found');
            playErrorSound();
        }
        barcodeInput.value = '';
        barcodeInput.focus();
    })
    .catch(err => {
        console.error('Search error:', err);
        alert('Error searching for item');
        playErrorSound();
    });
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
            location.reload();
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
                location.reload();
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
