<?php
/**
 * Edit Change Order
 * Edit an existing draft change order
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/helpers.php';

requireAuth();

$pageName = 'Edit Change Order';
$currentUser = getCurrentUser();
$userId = $currentUser['id'];
$userRole = $currentUser['role'];
$errors = [];

$changeOrderId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$changeOrderId) {
    $_SESSION['error_message'] = 'Change order ID is required.';
    header('Location: index.php');
    exit;
}

// Get change order
$changeOrder = getChangeOrderById($changeOrderId);

if (!$changeOrder) {
    $_SESSION['error_message'] = 'Change order not found.';
    header('Location: index.php');
    exit;
}

// Check if user can edit
if (!canEditChangeOrder($changeOrderId, $userId, $userRole)) {
    $_SESSION['error_message'] = 'You do not have permission to edit this change order.';
    header('Location: view.php?id=' . $changeOrderId);
    exit;
}

// Get available shows for this user
$shows = getShowsForUser($userId, $userRole);

// Get existing items
$existingItems = getChangeOrderItems($changeOrderId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $showId = !empty($_POST['show_id']) ? intval($_POST['show_id']) : null;
    $action = $_POST['action'] ?? 'draft';
    $itemsJson = $_POST['items'] ?? '[]';
    
    // Validate show
    if (empty($showId)) {
        $errors[] = 'Please select a show.';
    }
    
    // Validate items
    $items = json_decode($itemsJson, true);
    if (empty($items) || !is_array($items)) {
        $errors[] = 'Please add at least one item to the change order.';
    }
    
    // Validate each item
    foreach ($items as $item) {
        if (empty($item['item_id']) || empty($item['quantity']) || $item['quantity'] < 1 || empty($item['action'])) {
            $errors[] = 'Invalid item data.';
            break;
        }
        if (!in_array($item['action'], ['add', 'remove'])) {
            $errors[] = 'Invalid action type.';
            break;
        }
    }
    
    if (empty($errors)) {
        // Determine status based on action and role
        if ($action === 'submit') {
            $status = 'pending_approval';
        } else {
            $status = 'draft';
        }
        
        // Update change order
        $query = "UPDATE change_orders SET show_id = ?, status = ? WHERE id = ?";
        $result = executeQuery($query, [$showId, $status, $changeOrderId], 'isi');
        
        if ($result) {
            // Delete existing items
            executeQuery("DELETE FROM change_order_items WHERE change_order_id = ?", [$changeOrderId], 'i');
            
            // Insert new items
            $itemsInserted = true;
            foreach ($items as $item) {
                $itemQuery = "INSERT INTO change_order_items (change_order_id, item_id, quantity, action) 
                             VALUES (?, ?, ?, ?)";
                $itemResult = executeQuery($itemQuery, [
                    $changeOrderId,
                    $item['item_id'],
                    $item['quantity'],
                    $item['action']
                ], 'iiis');
                
                if (!$itemResult) {
                    $itemsInserted = false;
                    break;
                }
            }
            
            if ($itemsInserted) {
                $_SESSION['success_message'] = 'Change order updated successfully!';
                header('Location: view.php?id=' . $changeOrderId);
                exit;
            } else {
                $errors[] = 'Failed to update items.';
            }
        } else {
            $errors[] = 'Failed to update change order. Please try again.';
        }
    }
}

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="index.php">Change Orders</a></li>
                            <li class="breadcrumb-item"><a href="view.php?id=<?php echo $changeOrderId; ?>"><?php echo htmlspecialchars($changeOrder['barcode']); ?></a></li>
                            <li class="breadcrumb-item active">Edit</li>
                        </ol>
                    </nav>
                </div>
                <h2 class="page-title">Edit Change Order: <?php echo htmlspecialchars($changeOrder['barcode']); ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <div class="d-flex">
                <div><i class="ti ti-alert-circle me-2"></i></div>
                <div>
                    <h4 class="alert-title">Error</h4>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-8">
                <form method="POST" id="changeOrderForm">
                    <input type="hidden" name="items" id="itemsInput">
                    <input type="hidden" name="action" id="actionInput" value="draft">
                    
                    <div class="card mb-3">
                        <div class="card-header">
                            <h3 class="card-title">Show Selection</h3>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label required">Show</label>
                                <select class="form-select" name="show_id" id="showSelect" required>
                                    <option value="">-- Select a Show --</option>
                                    <?php foreach ($shows as $show): ?>
                                    <option value="<?php echo $show['id']; ?>" 
                                            <?php echo $changeOrder['show_id'] == $show['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($show['name']); ?>
                                        <?php if ($show['theatre_space_name']): ?>
                                            (<?php echo htmlspecialchars($show['theatre_space_name']); ?>)
                                        <?php endif; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-hint">Select the show for this change order.</small>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header">
                            <h3 class="card-title">Add Items</h3>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Search Items</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="itemSearch" 
                                           placeholder="Search by name or barcode..." autocomplete="off">
                                    <button class="btn" type="button" id="clearSearch">
                                        <i class="ti ti-x"></i>
                                    </button>
                                </div>
                                <div id="searchResults" class="dropdown-menu w-100" style="max-height: 300px; overflow-y: auto;"></div>
                            </div>
                            
                            <div id="selectedItems" class="border rounded p-3">
                                <h4 class="mb-3">Selected Items</h4>
                                <div id="itemsList">
                                    <p class="text-muted text-center">Loading items...</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-footer">
                            <div class="d-flex">
                                <a href="view.php?id=<?php echo $changeOrderId; ?>" class="btn btn-link">Cancel</a>
                                <button type="submit" class="btn btn-secondary ms-auto me-2" onclick="setAction('draft')">
                                    <i class="ti ti-device-floppy icon"></i>
                                    Save Draft
                                </button>
                                <button type="submit" class="btn btn-primary" onclick="setAction('submit')">
                                    <i class="ti ti-send icon"></i>
                                    Submit for Approval
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ti ti-help icon me-2"></i>
                            Help
                        </h3>
                    </div>
                    <div class="card-body">
                        <h4>Editing a Change Order</h4>
                        <ol class="mb-3">
                            <li>Modify the show if needed</li>
                            <li>Add or remove items as required</li>
                            <li>Adjust quantities and actions for existing items</li>
                            <li>Save as draft or submit for approval</li>
                        </ol>
                        
                        <h4>Draft vs Submit</h4>
                        <p class="text-muted small mb-2">
                            <strong>Save Draft:</strong> Keep as draft to continue editing later.
                        </p>
                        <p class="text-muted small">
                            <strong>Submit:</strong> 
                            Submit for admin approval. Once submitted, you cannot edit the change order.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quantity and Action Modal -->
<div class="modal fade" id="quantityModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Specify Quantity and Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="modalItemInfo" class="mb-3">
                    <strong id="modalItemName"></strong>
                    <p class="text-muted small mb-0">
                        Barcode: <span id="modalItemBarcode"></span><br>
                        Available: <span id="modalItemStock"></span>
                    </p>
                </div>
                <div class="mb-3">
                    <label class="form-label required">Action</label>
                    <div>
                        <label class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="itemAction" value="add" checked>
                            <span class="form-check-label text-success">
                                <i class="ti ti-plus"></i> Add (Increase inventory)
                            </span>
                        </label>
                        <label class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="itemAction" value="remove">
                            <span class="form-check-label text-danger">
                                <i class="ti ti-minus"></i> Remove (Decrease inventory)
                            </span>
                        </label>
                    </div>
                    <small class="form-hint">Add increases stock, Remove decreases stock</small>
                </div>
                <div class="mb-3">
                    <label class="form-label required">Quantity</label>
                    <input type="number" class="form-control" id="quantityInput" min="1" value="1">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="addItemBtn">Add Item</button>
            </div>
        </div>
    </div>
</div>

<script>
let selectedItems = [];
let currentItem = null;
let searchTimeout = null;

// Pre-load existing items
const existingItems = <?php echo json_encode($existingItems); ?>;

document.addEventListener('DOMContentLoaded', function() {
    // Load existing items
    existingItems.forEach(item => {
        selectedItems.push({
            item_id: item.item_id,
            name: item.item_name,
            barcode: item.item_barcode,
            in_stock: item.in_stock_quantity,
            quantity: item.quantity,
            action: item.action
        });
    });
    updateItemsList();
    updateItemsInput();
    
    const itemSearch = document.getElementById('itemSearch');
    const searchResults = document.getElementById('searchResults');
    const clearSearch = document.getElementById('clearSearch');
    const quantityModal = new bootstrap.Modal(document.getElementById('quantityModal'));
    const quantityInput = document.getElementById('quantityInput');
    const addItemBtn = document.getElementById('addItemBtn');
    
    // Search functionality
    itemSearch.addEventListener('input', function() {
        const query = this.value.trim();
        
        clearTimeout(searchTimeout);
        
        if (query.length < 2) {
            searchResults.classList.remove('show');
            return;
        }
        
        searchTimeout = setTimeout(() => {
            fetch(`api/search-items-for-change-order.php?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(items => {
                    displaySearchResults(items);
                })
                .catch(error => {
                    console.error('Search error:', error);
                });
        }, 300);
    });
    
    clearSearch.addEventListener('click', function() {
        itemSearch.value = '';
        searchResults.classList.remove('show');
    });
    
    // Close results when clicking outside
    document.addEventListener('click', function(e) {
        if (!itemSearch.contains(e.target) && !searchResults.contains(e.target)) {
            searchResults.classList.remove('show');
        }
    });
    
    // Add item button in modal
    addItemBtn.addEventListener('click', function() {
        const quantity = parseInt(quantityInput.value);
        const itemAction = document.querySelector('input[name="itemAction"]:checked').value;
        
        if (!quantity || quantity < 1) {
            alert('Please enter a valid quantity.');
            return;
        }
        
        if (currentItem) {
            addItemToList(currentItem, quantity, itemAction);
            quantityModal.hide();
            itemSearch.value = '';
            searchResults.classList.remove('show');
        }
    });
    
    // Handle enter key in quantity input
    quantityInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            addItemBtn.click();
        }
    });
});

function displaySearchResults(items) {
    const searchResults = document.getElementById('searchResults');
    
    if (items.length === 0) {
        searchResults.innerHTML = '<div class="dropdown-item text-muted">No items found</div>';
        searchResults.classList.add('show');
        return;
    }
    
    let html = '';
    items.forEach(item => {
        // Check if already added
        const alreadyAdded = selectedItems.some(si => si.item_id === item.id);
        const disabledClass = alreadyAdded ? 'disabled' : '';
        
        html += `
            <a href="#" class="dropdown-item item-search-result ${disabledClass}" 
               data-item-id="${item.id}" 
               data-item-name="${escapeHtml(item.name)}" 
               data-item-barcode="${escapeHtml(item.barcode)}" 
               data-item-stock="${item.in_stock}">
                <div>
                    <strong>${escapeHtml(item.name)}</strong>
                    <br>
                    <small class="text-muted">
                        ${escapeHtml(item.barcode)} | In Stock: ${item.in_stock}
                        ${alreadyAdded ? ' | <span class="text-primary">Already added</span>' : ''}
                    </small>
                </div>
            </a>
        `;
    });
    
    searchResults.innerHTML = html;
    searchResults.classList.add('show');
    
    // Add click handlers
    searchResults.querySelectorAll('.item-search-result:not(.disabled)').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const itemId = parseInt(this.dataset.itemId);
            const itemName = this.dataset.itemName;
            const barcode = this.dataset.itemBarcode;
            const inStock = parseInt(this.dataset.itemStock);
            selectItem(itemId, itemName, barcode, inStock);
        });
    });
}

function selectItem(itemId, itemName, barcode, inStock) {
    // Check if already added
    if (selectedItems.some(si => si.item_id === itemId)) {
        return;
    }
    
    currentItem = {
        item_id: itemId,
        name: itemName,
        barcode: barcode,
        in_stock: inStock
    };
    
    // Show quantity modal
    document.getElementById('modalItemName').textContent = itemName;
    document.getElementById('modalItemBarcode').textContent = barcode;
    document.getElementById('modalItemStock').textContent = inStock;
    document.getElementById('quantityInput').value = 1;
    document.querySelector('input[name="itemAction"][value="add"]').checked = true;
    
    const quantityModal = new bootstrap.Modal(document.getElementById('quantityModal'));
    quantityModal.show();
    
    // Focus on quantity input
    setTimeout(() => {
        document.getElementById('quantityInput').focus();
        document.getElementById('quantityInput').select();
    }, 500);
}

function addItemToList(item, quantity, action) {
    const newItem = {
        item_id: item.item_id,
        name: item.name,
        barcode: item.barcode,
        in_stock: item.in_stock,
        quantity: quantity,
        action: action
    };
    
    selectedItems.push(newItem);
    updateItemsList();
    updateItemsInput();
}

function removeItem(index) {
    if (confirm('Remove this item from the change order?')) {
        selectedItems.splice(index, 1);
        updateItemsList();
        updateItemsInput();
    }
}

function updateItemsList() {
    const itemsList = document.getElementById('itemsList');
    
    if (selectedItems.length === 0) {
        itemsList.innerHTML = '<p class="text-muted text-center">No items added yet. Search and add items above.</p>';
        return;
    }
    
    let html = '<div class="list-group list-group-flush">';
    selectedItems.forEach((item, index) => {
        const actionBadge = item.action === 'add' 
            ? '<span class="badge bg-success me-2"><i class="ti ti-plus"></i> Add</span>' 
            : '<span class="badge bg-danger me-2"><i class="ti ti-minus"></i> Remove</span>';
        
        const stockWarning = (item.action === 'remove' && item.quantity > item.in_stock) 
            ? '<span class="badge bg-warning ms-2">Insufficient Stock</span>' : '';
        
        html += `
            <div class="list-group-item">
                <div class="row align-items-center">
                    <div class="col">
                        ${actionBadge}
                        <strong>${escapeHtml(item.name)}</strong>
                        <br>
                        <small class="text-muted">
                            ${item.barcode} | Qty: ${item.quantity} | Available: ${item.in_stock}
                        </small>
                        ${stockWarning}
                    </div>
                    <div class="col-auto">
                        <button type="button" class="btn btn-sm btn-danger" onclick="removeItem(${index})">
                            <i class="ti ti-trash icon"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
    });
    html += '</div>';
    
    itemsList.innerHTML = html;
}

function updateItemsInput() {
    document.getElementById('itemsInput').value = JSON.stringify(selectedItems);
}

function setAction(action) {
    document.getElementById('actionInput').value = action;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
