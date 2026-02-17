<?php
$pageTitle = 'Shop Orders';
require_once '../includes/header.php';
requirePermission('pullsheets');

$currentUser = getCurrentUser();
$isDesigner = $currentUser['role'] === 'designer';
$isProductionAudio = $currentUser['role'] === 'production_audio';

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    try {
        $db = getDB();
        $deleteId = (int)$_POST['delete_id'];
        
        // Get shop order details
        $pullsheet = getPullsheetById($deleteId);
        if (!$pullsheet) {
            throw new Exception('Shop Order not found');
        }
        
        // Check permission for designers and production audio
        if (($isDesigner || $isProductionAudio) && !canAccessShow($currentUser['id'], $pullsheet['show_id'])) {
            throw new Exception('You do not have permission to delete this pullsheet');
        }
        
        // Start transaction
        $db->query("START TRANSACTION");
        
        // If shop order was finalized, unreserve the items
        if ($pullsheet['status'] === 'finalized' || $pullsheet['status'] === 'picked') {
            $items = getPullsheetItems($deleteId);
            foreach ($items as $item) {
                // Return items to stock - use quantity_picked if available, otherwise quantity_needed
                $quantityToReturn = ($pullsheet['status'] === 'picked') ? $item['quantity_picked'] : $item['quantity_needed'];
                $db->query(
                    "UPDATE items SET in_stock_quantity = in_stock_quantity + ? WHERE id = ?",
                    [$quantityToReturn, $item['item_id']]
                );
                logMessage("Unreserved {$quantityToReturn} of item ID {$item['item_id']} from shop order ID $deleteId", 'INFO');
            }
        }
        
        // Delete shop order items and pullsheet
        $db->query("DELETE FROM pullsheet_items WHERE pullsheet_id = ?", [$deleteId]);
        $db->query("DELETE FROM pullsheets WHERE id = ?", [$deleteId]);
        
        $db->query("COMMIT");
        
        logMessage("Pullsheet ID $deleteId deleted by user ID {$currentUser['id']}", 'INFO');
        setAlert('Shop Order deleted successfully and items returned to stock');
        redirect();
    } catch (Exception $e) {
        if (isset($db)) {
            $db->query("ROLLBACK");
        }
        logException($e, 'Error deleting pullsheet');
        setAlert('Error: ' . $e->getMessage(), 'danger');
    }
}

// Handle create shop order request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_pullsheet'])) {
    try {
        $showId = !empty($_POST['show_id']) ? (int)$_POST['show_id'] : null;
        
        // Check permission for designers and production audio
        if (($isDesigner || $isProductionAudio) && $showId && !canAccessShow($currentUser['id'], $showId)) {
            throw new Exception('You do not have permission to create shop order for this show');
        }
        
        $createdBy = $currentUser['name'] ?? 'Unknown'; // Use logged-in user's name with fallback
        $barcode = generateUniqueBarcode('PS');
        
        getDB()->query(
            "INSERT INTO pullsheets (show_id, barcode, created_by, status) VALUES (?, ?, ?, 'draft')",
            [$showId, $barcode, $createdBy]
        );
        
        $pullsheetId = getDB()->lastInsertId();
        setAlert('Shop Order created successfully');
        redirect('pullsheet_edit.php?id=' . $pullsheetId);
    } catch (Exception $e) {
        setAlert('Error: ' . $e->getMessage(), 'danger');
    }
}

// Get pullsheets filtered by show access for designers and production audio
if ($isDesigner || $isProductionAudio) {
    $assignedShows = getAssignedShows($currentUser['id']);
    $assignedShowIds = array_column($assignedShows, 'id');
    
    if (empty($assignedShowIds)) {
        $pullsheets = [];
    } else {
        $placeholders = implode(',', array_fill(0, count($assignedShowIds), '?'));
        $pullsheets = getDB()->fetchAll(
            "SELECT p.*, s.name as show_name 
             FROM pullsheets p 
             LEFT JOIN shows s ON p.show_id = s.id 
             WHERE p.show_id IN ($placeholders)
             ORDER BY p.created_at DESC",
            $assignedShowIds
        );
    }
    
    // Filter shows for dropdown
    $shows = $assignedShows;
} else {
    // Admins see all pullsheets and shows
    $pullsheets = getDB()->fetchAll("SELECT p.*, s.name as show_name 
        FROM pullsheets p 
        LEFT JOIN shows s ON p.show_id = s.id 
        ORDER BY p.created_at DESC");
    
    $shows = getDB()->fetchAll("SELECT id, name FROM shows ORDER BY name ASC");
}

// Group pullsheets by show
$pullsheetsByShow = [];
$pullsheetsNoShow = [];

foreach ($pullsheets as $pullsheet) {
    if ($pullsheet['show_id']) {
        $showId = $pullsheet['show_id'];
        if (!isset($pullsheetsByShow[$showId])) {
            $pullsheetsByShow[$showId] = [
                'show_name' => $pullsheet['show_name'],
                'pullsheets' => []
            ];
        }
        $pullsheetsByShow[$showId]['pullsheets'][] = $pullsheet;
    } else {
        $pullsheetsNoShow[] = $pullsheet;
    }
}
?>

<div class="row mb-3">
    <div class="col-md-8">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createPullsheetModal">
            <i class="ti ti-plus"></i> Create New Pullsheet
        </button>
        <button type="button" class="btn btn-success ms-2" data-bs-toggle="modal" data-bs-target="#importCSVModal">
            <i class="ti ti-file-import"></i> Import from CSV
        </button>
    </div>
</div>

<!-- Import CSV Modal -->
<div class="modal fade" id="importCSVModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="import_csv.php" enctype="multipart/form-data">
                <input type="hidden" name="redirect_to" value="pullsheets">
                <div class="modal-header">
                    <h5 class="modal-title">Import Orders from CSV</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <h4 class="alert-title"><i class="ti ti-info-circle"></i> CSV Import Instructions</h4>
                        <p>Upload a CSV file to import shop orders and/or change orders in bulk.</p>
                        <ul class="mb-2">
                            <li>Download the template from <a href="settings" target="_blank">Settings</a> page</li>
                            <li>Fill in order details (order type, show name, item barcodes, quantities)</li>
                            <li>Upload the completed CSV file here</li>
                        </ul>
                        <p class="mb-0"><strong>Note:</strong> All rows with the same order type and show name will be grouped into one order.</p>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label required">CSV File</label>
                        <input type="file" class="form-control" name="csv_file" accept=".csv" required>
                        <small class="form-hint">Select a CSV file following the template format</small>
                    </div>
                    
                    <div class="card bg-light">
                        <div class="card-body">
                            <h5>Expected CSV Format:</h5>
                            <table class="table table-sm table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th>Order Type</th>
                                        <th>Show Name</th>
                                        <th>Item Barcode</th>
                                        <th>Item Name</th>
                                        <th>Quantity</th>
                                        <th>Type</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>shop_order</td>
                                        <td>My Show</td>
                                        <td>PS-001</td>
                                        <td>Microphone</td>
                                        <td>5</td>
                                        <td>(blank)</td>
                                    </tr>
                                    <tr>
                                        <td>change_order</td>
                                        <td>My Show</td>
                                        <td>PS-002</td>
                                        <td>Cable</td>
                                        <td>3</td>
                                        <td>add</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="ti ti-upload"></i> Import CSV
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Create Pullsheet Modal -->
<div class="modal fade" id="createPullsheetModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Pullsheet</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Show (Optional)</label>
                        <select name="show_id" class="form-select">
                            <option value="">No Show (Standalone Pullsheet)</option>
                            <?php foreach ($shows as $show): ?>
                                <option value="<?php echo $show['id']; ?>"><?php echo htmlspecialchars($show['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-hint">You can create a shop order without a show if needed</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="create_pullsheet" class="btn btn-primary">Create Pullsheet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if (empty($pullsheets)): ?>
    <div class="empty">
        <div class="empty-icon">
            <i class="ti ti-file-text icon"></i>
        </div>
        <p class="empty-title">No pullsheets yet</p>
        <p class="empty-subtitle text-muted">Click "Create New Pullsheet" to get started</p>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body">
            <div class="accordion" id="pullsheetsAccordion">
                <?php 
                $accordionIndex = 0;
                
                // Show pullsheets grouped by show
                foreach ($pullsheetsByShow as $showId => $showData): 
                    $accordionIndex++;
                ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading-show-<?php echo $showId; ?>">
                            <button class="accordion-button <?php echo $accordionIndex > 1 ? 'collapsed' : ''; ?>" type="button" 
                                    data-bs-toggle="collapse" data-bs-target="#collapse-show-<?php echo $showId; ?>" 
                                    aria-expanded="<?php echo $accordionIndex === 1 ? 'true' : 'false'; ?>">
                                <strong><?php echo htmlspecialchars($showData['show_name']); ?></strong>
                                <span class="badge bg-primary ms-2"><?php echo count($showData['pullsheets']); ?> pullsheet<?php echo count($showData['pullsheets']) !== 1 ? 's' : ''; ?></span>
                            </button>
                        </h2>
                        <div id="collapse-show-<?php echo $showId; ?>" 
                             class="accordion-collapse collapse <?php echo $accordionIndex === 1 ? 'show' : ''; ?>" 
                             data-bs-parent="#pullsheetsAccordion">
                            <div class="accordion-body">
                                <div class="row">
                                    <?php foreach ($showData['pullsheets'] as $pullsheet): ?>
                                        <div class="col-md-6 col-lg-4 mb-3">
                                            <div class="card">
                                                <div class="card-header">
                                                    <h3 class="card-title">Shop Order</h3>
                                                    <div class="card-actions">
                                                        <?php $badge = getStatusBadge($pullsheet); ?>

                                                        <span class="badge <?php echo $badge['class']; ?>">

                                                            <?php echo $badge['text']; ?>

                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="card-body">
                                                    <div class="mb-2">
                                                        <small class="text-muted">Created by:</small>
                                                        <div><?php echo htmlspecialchars($pullsheet['created_by'] ?? 'N/A'); ?></div>
                                                    </div>
                                                    <div class="mb-2">
                                                        <small class="text-muted">Created:</small>
                                                        <div><?php echo date('m/d/Y g:i A', strtotime($pullsheet['created_at'])); ?></div>
                                                    </div>
                                                    <?php if ($pullsheet['picked_by']): ?>
                                                        <div class="mb-2">
                                                            <small class="text-muted">Picked by:</small>
                                                            <div><?php echo htmlspecialchars($pullsheet['picked_by']); ?></div>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="card-footer">
                                                    <div class="d-flex gap-2">
                                                        <a href="pullsheet_view?id=<?php echo $pullsheet['id']; ?>" class="btn btn-sm btn-primary">
                                                            <i class="ti ti-eye"></i> View
                                                        </a>
                                                        <?php if ($pullsheet['status'] === 'draft'): ?>
                                                            <a href="pullsheet_edit?id=<?php echo $pullsheet['id']; ?>" class="btn btn-sm btn-info">
                                                                <i class="ti ti-edit"></i> Edit
                                                            </a>
                                                        <?php endif; ?>
                                                        <form method="POST" class="d-inline ms-auto" onsubmit="return confirm('Are you sure you want to delete this pullsheet? This cannot be undone.');">
                                                            <input type="hidden" name="delete_id" value="<?php echo $pullsheet['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                                <i class="ti ti-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <!-- Pullsheets not attached to any show -->
                <?php if (!empty($pullsheetsNoShow)): 
                    $accordionIndex++;
                ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="heading-no-show">
                            <button class="accordion-button <?php echo $accordionIndex > 1 ? 'collapsed' : ''; ?>" type="button" 
                                    data-bs-toggle="collapse" data-bs-target="#collapse-no-show" 
                                    aria-expanded="<?php echo $accordionIndex === 1 ? 'true' : 'false'; ?>">
                                <strong>Not Attached to Show</strong>
                                <span class="badge bg-secondary ms-2"><?php echo count($pullsheetsNoShow); ?> pullsheet<?php echo count($pullsheetsNoShow) !== 1 ? 's' : ''; ?></span>
                            </button>
                        </h2>
                        <div id="collapse-no-show" 
                             class="accordion-collapse collapse <?php echo $accordionIndex === 1 ? 'show' : ''; ?>" 
                             data-bs-parent="#pullsheetsAccordion">
                            <div class="accordion-body">
                                <div class="row">
                                    <?php foreach ($pullsheetsNoShow as $pullsheet): ?>
                                        <div class="col-md-6 col-lg-4 mb-3">
                                            <div class="card">
                                                <div class="card-header">
                                                    <h3 class="card-title">Shop Order</h3>
                                                    <div class="card-actions">
                                                        <?php $badge = getStatusBadge($pullsheet); ?>

                                                        <span class="badge <?php echo $badge['class']; ?>">

                                                            <?php echo $badge['text']; ?>

                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="card-body">
                                                    <div class="mb-2">
                                                        <small class="text-muted">Created by:</small>
                                                        <div><?php echo htmlspecialchars($pullsheet['created_by'] ?? 'N/A'); ?></div>
                                                    </div>
                                                    <div class="mb-2">
                                                        <small class="text-muted">Created:</small>
                                                        <div><?php echo date('m/d/Y g:i A', strtotime($pullsheet['created_at'])); ?></div>
                                                    </div>
                                                    <?php if ($pullsheet['picked_by']): ?>
                                                        <div class="mb-2">
                                                            <small class="text-muted">Picked by:</small>
                                                            <div><?php echo htmlspecialchars($pullsheet['picked_by']); ?></div>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="card-footer">
                                                    <div class="d-flex gap-2">
                                                        <a href="pullsheet_view?id=<?php echo $pullsheet['id']; ?>" class="btn btn-sm btn-primary">
                                                            <i class="ti ti-eye"></i> View
                                                        </a>
                                                        <?php if ($pullsheet['status'] === 'draft'): ?>
                                                            <a href="pullsheet_edit?id=<?php echo $pullsheet['id']; ?>" class="btn btn-sm btn-info">
                                                                <i class="ti ti-edit"></i> Edit
                                                            </a>
                                                        <?php endif; ?>
                                                        <form method="POST" class="d-inline ms-auto" onsubmit="return confirm('Are you sure you want to delete this pullsheet? This cannot be undone.');">
                                                            <input type="hidden" name="delete_id" value="<?php echo $pullsheet['id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                                <i class="ti ti-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
