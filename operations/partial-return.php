<?php
require_once '../includes/functions.php';
requirePermission('returns');

$pageTitle = 'Partial Return';
require_once '../includes/header.php';

$pullsheet = null;
$pullsheetItems = [];
$selectedItems = [];

// Handle pullsheet barcode scan
if (isset($_GET['pullsheet_barcode'])) {
    $barcode = $_GET['pullsheet_barcode'];
    $pullsheet = getDB()->fetchOne(
        "SELECT p.*, s.title as show_title FROM pullsheets p 
         LEFT JOIN shows s ON p.show_id = s.id 
         WHERE p.barcode = ?",
        [$barcode]
    );
    
    if ($pullsheet) {
        // Get all items in this pullsheet
        $pullsheetItems = getDB()->fetchAll(
            "SELECT pi.*, i.name as item_name, i.barcode as item_barcode, i.category, i.subcategory 
             FROM pullsheet_items pi
             JOIN items i ON pi.item_id = i.id
             WHERE pi.pullsheet_id = ?
             ORDER BY i.name",
            [$pullsheet['id']]
        );
    } else {
        setAlert('Pullsheet not found with barcode: ' . htmlspecialchars($barcode), 'danger');
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_return'])) {
    try {
        $pullsheetId = $_POST['pullsheet_id'] ?? null;
        $returnItems = $_POST['return_items'] ?? [];
        
        if (!$pullsheetId || empty($returnItems)) {
            throw new Exception('No items selected for return');
        }
        
        $items = [];
        foreach ($returnItems as $itemId => $quantity) {
            if ($quantity > 0) {
                $items[] = [
                    'item_id' => $itemId,
                    'quantity' => $quantity
                ];
            }
        }
        
        if (empty($items)) {
            throw new Exception('No items selected for return');
        }
        
        $currentUser = getCurrentUser();
        $changeOrderId = processPartialReturn($pullsheetId, $items, $currentUser['id']);
        
        if ($changeOrderId) {
            setAlert('Partial return processed successfully. Change order #' . $changeOrderId . ' created.');
            redirect('/');
        } else {
            throw new Exception('Failed to process partial return');
        }
        
    } catch (Exception $e) {
        setAlert($e->getMessage(), 'danger');
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h2 class="card-title mb-4">
                        <i class="ti ti-corner-down-left"></i> Partial Return
                    </h2>
                    
                    <?php if (!$pullsheet): ?>
                        <!-- Step 1: Scan pullsheet barcode -->
                        <div class="alert alert-info">
                            <h5><i class="ti ti-info-circle"></i> Instructions</h5>
                            <ol class="mb-0">
                                <li>Scan the shop order barcode to load all items</li>
                                <li>Select which items to return by adjusting quantities</li>
                                <li>Submit to return items to inventory and create a change order</li>
                            </ol>
                        </div>
                        
                        <form method="GET" class="mb-4">
                            <div class="row">
                                <div class="col-md-8">
                                    <label for="pullsheet_barcode" class="form-label">Scan Shop Order Barcode</label>
                                    <input type="text" 
                                           id="pullsheet_barcode" 
                                           name="pullsheet_barcode" 
                                           class="form-control form-control-lg" 
                                           placeholder="Scan or enter shop order barcode"
                                           autofocus
                                           required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="submit" class="btn btn-primary btn-lg w-100">
                                        <i class="ti ti-search"></i> Load Shop Order
                                    </button>
                                </div>
                            </div>
                        </form>
                    <?php else: ?>
                        <!-- Step 2: Select items to return -->
                        <div class="alert alert-success">
                            <h5><i class="ti ti-check-circle"></i> Shop Order Loaded</h5>
                            <p class="mb-0">
                                <strong>Show:</strong> <?php echo htmlspecialchars($pullsheet['show_title']); ?><br>
                                <strong>Barcode:</strong> <?php echo htmlspecialchars($pullsheet['barcode']); ?><br>
                                <strong>Items:</strong> <?php echo count($pullsheetItems); ?>
                            </p>
                        </div>
                        
                        <?php if (empty($pullsheetItems)): ?>
                            <div class="alert alert-warning">
                                <i class="ti ti-alert-circle"></i> This shop order has no items.
                            </div>
                            <a href="/operations/partial-return" class="btn btn-secondary">
                                <i class="ti ti-arrow-left"></i> Start Over
                            </a>
                        <?php else: ?>
                            <form method="POST" id="returnForm">
                                <input type="hidden" name="pullsheet_id" value="<?php echo $pullsheet['id']; ?>">
                                
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Item</th>
                                                <th>Barcode</th>
                                                <th>Category</th>
                                                <th>In Pullsheet</th>
                                                <th width="200">Return Quantity</th>
                                            </tr>
                                        </thead>
                                        <tbody id="itemsTable">
                                            <?php foreach ($pullsheetItems as $item): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                                    <td>
                                                        <code><?php echo htmlspecialchars($item['item_barcode']); ?></code>
                                                    </td>
                                                    <td>
                                                        <small class="text-muted">
                                                            <?php echo htmlspecialchars($item['category'] ?? 'N/A'); ?>
                                                            <?php if ($item['subcategory']): ?>
                                                                / <?php echo htmlspecialchars($item['subcategory']); ?>
                                                            <?php endif; ?>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-info"><?php echo $item['quantity']; ?></span>
                                                    </td>
                                                    <td>
                                                        <input type="number" 
                                                               name="return_items[<?php echo $item['item_id']; ?>]" 
                                                               class="form-control return-quantity" 
                                                               min="0" 
                                                               max="<?php echo $item['quantity']; ?>" 
                                                               value="0"
                                                               data-item-name="<?php echo htmlspecialchars($item['item_name']); ?>"
                                                               data-item-barcode="<?php echo htmlspecialchars($item['item_barcode']); ?>">
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center mt-4">
                                    <a href="/operations/partial-return" class="btn btn-secondary">
                                        <i class="ti ti-arrow-left"></i> Start Over
                                    </a>
                                    <div>
                                        <span class="me-3">Selected Items: <strong id="selectedCount">0</strong></span>
                                        <button type="submit" name="process_return" class="btn btn-success btn-lg" id="submitBtn" disabled>
                                            <i class="ti ti-check"></i> Process Return
                                        </button>
                                    </div>
                                </div>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Barcode scanner for items -->
<div class="modal fade" id="scannerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Scan Item Barcode</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="text" id="itemBarcodeInput" class="form-control form-control-lg" placeholder="Scan item barcode" autofocus>
                <div class="alert alert-info mt-3">
                    Scan an item's barcode to set its return quantity to the maximum available.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const returnQuantityInputs = document.querySelectorAll('.return-quantity');
    const selectedCountSpan = document.getElementById('selectedCount');
    const submitBtn = document.getElementById('submitBtn');
    
    function updateSelectedCount() {
        let count = 0;
        returnQuantityInputs.forEach(input => {
            if (parseInt(input.value) > 0) {
                count++;
            }
        });
        selectedCountSpan.textContent = count;
        submitBtn.disabled = count === 0;
    }
    
    returnQuantityInputs.forEach(input => {
        input.addEventListener('input', updateSelectedCount);
    });
    
    // Barcode scanning for items
    document.addEventListener('keypress', function(e) {
        // Only listen when not in an input field
        if (e.target.tagName === 'INPUT') return;
        
        // Show scanner modal
        const modal = new bootstrap.Modal(document.getElementById('scannerModal'));
        modal.show();
        document.getElementById('itemBarcodeInput').focus();
    });
    
    document.getElementById('itemBarcodeInput')?.addEventListener('change', function(e) {
        const barcode = e.target.value.trim();
        if (!barcode) return;
        
        // Find item by barcode
        returnQuantityInputs.forEach(input => {
            if (input.dataset.itemBarcode === barcode) {
                input.value = input.max;
                updateSelectedCount();
                input.focus();
                input.select();
                bootstrap.Modal.getInstance(document.getElementById('scannerModal')).hide();
            }
        });
        
        e.target.value = '';
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
