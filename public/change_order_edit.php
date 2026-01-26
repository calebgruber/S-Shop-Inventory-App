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
                        <div class="col-md-8">
                            <input type="text" class="form-control barcode-autofocus" id="itemBarcode" placeholder="Scan item...">
                        </div>
                        <div class="col-md-4">
                            <button class="btn btn-success w-100" id="finalizeBtn">Finalize</button>
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
<div class="modal fade" id="addModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5>Add Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="itemInfo"></div>
                <div class="mb-3">
                    <label>Type</label>
                    <select class="form-select" id="typeInput">
                        <option value="add">Add</option>
                        <option value="remove">Remove</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label>Quantity</label>
                    <input type="number" class="form-control" id="qtyInput" value="1" min="1">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" id="confirmBtn">Add</button>
            </div>
        </div>
    </div>
</div>

<script>
let selectedItem = null;
const modal = new bootstrap.Modal(document.getElementById('addModal'));

document.getElementById('itemBarcode').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        const barcode = this.value.trim();
        if (!barcode) return;
        
        fetch('items.php?ajax=1&barcode=' + barcode)
            .then(r => r.json())
            .then(item => {
                if (item) {
                    selectedItem = item;
                    document.getElementById('itemInfo').innerHTML = `<strong>${item.name}</strong>`;
                    modal.show();
                }
            });
    }
});

document.getElementById('confirmBtn').addEventListener('click', () => {
    if (!selectedItem) return;
    
    fetch('?id=<?php echo $coId; ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `ajax=1&action=add_item&barcode=${selectedItem.barcode}&quantity=${document.getElementById('qtyInput').value}&type=${document.getElementById('typeInput').value}`
    }).then(r => r.json()).then(d => {
        if (d.success) {
            modal.hide();
            location.reload();
        }
    });
});

document.getElementById('finalizeBtn').addEventListener('click', () => {
    if (!confirm('Finalize?')) return;
    
    fetch('?id=<?php echo $coId; ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'ajax=1&action=finalize'
    }).then(r => r.json()).then(d => {
        if (d.success) location.reload();
    });
});
</script>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
