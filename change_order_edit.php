<?php
require_once 'includes/functions.php';

$coId = $_GET['id'] ?? null;

// Handle AJAX requests BEFORE any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    if (!$coId) {
        echo json_encode(['success' => false, 'message' => 'Invalid change order']);
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
                    "INSERT INTO change_order_items (change_order_id, item_id, quantity_change, type) VALUES (?, ?, ?, ?)",
                    [$coId, $item['id'], (int)$_POST['quantity'], $type]
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
            $items = getChangeOrderItems($coId);
            
            // Process item changes
            foreach ($items as $item) {
                if ($item['type'] === 'add') {
                    // Add items to show (reserve them)
                    getDB()->query(
                        "INSERT INTO item_allocations (item_id, show_id, change_order_id, quantity, status) 
                         VALUES (?, ?, ?, ?, 'checked_out')",
                        [$item['item_id'], $changeOrder['show_id'], $coId, abs($item['quantity_change'])]
                    );
                    updateItemStock($item['item_id'], -abs($item['quantity_change']));
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
                        $qtyToRemove = abs($item['quantity_change']);
                        if ($qtyToRemove >= $allocation['quantity']) {
                            // Remove entire allocation
                            getDB()->query("DELETE FROM item_allocations WHERE id = ?", [$allocation['id']]);
                            updateItemStock($item['item_id'], $allocation['quantity']);
                        } else {
                            // Reduce allocation quantity
                            getDB()->query(
                                "UPDATE item_allocations SET quantity = quantity - ? WHERE id = ?",
                                [$qtyToRemove, $allocation['id']]
                            );
                            updateItemStock($item['item_id'], $qtyToRemove);
                        }
                    }
                }
            }
            
            getDB()->query("UPDATE change_orders SET status = 'finalized', finalized_at = NOW() WHERE id = ?", [$coId]);
            
            // Create notification
            createNotificationForDesigners(
                'change_order',
                "Change order finalized for " . $changeOrder['show_name'],
                "change_orders.php"
            );
            
            // Generate PDF for the change order
            try {
                $pdfPath = generateChangeOrderPDF($coId);
                echo json_encode(['success' => true, 'pdf_path' => $pdfPath]);
            } catch (Exception $e) {
                echo json_encode(['success' => true, 'warning' => 'Change order finalized but PDF generation failed: ' . $e->getMessage()]);
            }
            exit;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// Regular page rendering starts here
$pageTitle = 'Edit Change Order';
require_once 'includes/header.php';

$currentUser = getCurrentUser();
$isDesigner = $currentUser['role'] === 'designer';

if (!$coId) redirect('change_orders.php');

$co = getChangeOrderById($coId);
if (!$co) {
    setAlert('Change order not found', 'danger');
    redirect('change_orders.php');
}

// Check permission for designers
if ($isDesigner && !canAccessShow($currentUser['id'], $co['show_id'])) {
    setAlert('You do not have permission to edit this change order', 'danger');
    redirect('change_orders.php');
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
const barcodeInput = document.getElementById('itemBarcode');
const searchBtn = document.getElementById('searchBtn');

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
    const barcode = barcodeInput.value.trim();
    if (!barcode) return;
    
    console.log('Searching for barcode:', barcode);
    
    fetch('?id=<?php echo htmlspecialchars($coId, ENT_QUOTES, 'UTF-8'); ?>', {
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
    document.getElementById('qtyInput').value = 1;
    document.getElementById('qtyInput').focus();
    modal.show();
    console.log('Modal should be visible now');
}

document.getElementById('confirmBtn').addEventListener('click', () => {
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
            location.reload();
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
            location.reload();
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
            window.location.href = 'change_order_view.php?id=<?php echo htmlspecialchars($coId, ENT_QUOTES, 'UTF-8'); ?>&finalized=1';
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
                location.reload();
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

<?php require_once 'includes/footer.php'; ?>
