<?php
$pageTitle = 'Edit Change Order';
require_once 'includes/header.php';

$coId = $_GET['id'] ?? null;
if (!$coId) redirect('change_orders.php');

$co = getChangeOrderById($coId);
if (!$co) {
    setAlert('Change order not found', 'danger');
    redirect('change_orders.php');
}

$items = getChangeOrderItems($coId);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
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
            
            getDB()->query(
                "INSERT INTO change_order_items (change_order_id, item_id, quantity_change, type) VALUES (?, ?, ?, ?)",
                [$coId, $item['id'], (int)$_POST['quantity'], $_POST['type']]
            );
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        if ($_POST['action'] === 'save_draft') {
            echo json_encode(['success' => true, 'message' => 'Draft saved']);
            exit;
        }
        
        if ($_POST['action'] === 'finalize') {
            getDB()->query("UPDATE change_orders SET status = 'finalized', finalized_at = NOW() WHERE id = ?", [$coId]);
            echo json_encode(['success' => true]);
            exit;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}
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
                        </tr>
                    </thead>
                    <tbody id="itemsTable">
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                <td><span class="badge bg-<?php echo $item['type'] === 'add' ? 'success' : 'danger'; ?>"><?php echo ucfirst($item['type']); ?></span></td>
                                <td><?php echo abs($item['quantity_change']); ?></td>
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
let selectedItem = null;
const modalEl = document.getElementById('addModal');
if (!modalEl) {
    console.error('Modal element not found');
}
const modal = modalEl ? new bootstrap.Modal(modalEl) : null;
const barcodeInput = document.getElementById('itemBarcode');

document.getElementById('searchBtn').addEventListener('click', searchItem);

// Fix Enter key handler
document.getElementById('itemBarcode').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        e.stopPropagation();
        searchItem();
    }
});

function searchItem() {
    const barcode = barcodeInput.value.trim();
    if (!barcode) return;
    
    console.log('Searching for barcode:', barcode);
    
    fetch('?id=<?php echo $coId; ?>', {
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
    
    fetch('?id=<?php echo $coId; ?>', {
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
    fetch('?id=<?php echo $coId; ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'ajax=1&action=save_draft'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            playSuccessSound();
            window.location.href = 'change_orders.php?saved=1';
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
    
    fetch('?id=<?php echo $coId; ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'ajax=1&action=finalize'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            playSuccessSound();
            window.location.href = 'change_orders.php?finalized=1';
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
</script>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
