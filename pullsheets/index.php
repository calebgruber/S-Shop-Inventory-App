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
        redirect('/pullsheets/edit?id=' . $pullsheetId);
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

<!-- Action Buttons -->
<div class="row mb-3">
    <div class="col-12">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createPullsheetModal">
            <i class="ti ti-plus"></i> Create New Shop Order
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
                    <h5 class="modal-title">Create New Shop Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Show (Optional)</label>
                        <select name="show_id" class="form-select">
                            <option value="">No Show (Standalone Shop Order)</option>
                            <?php foreach ($shows as $show): ?>
                                <option value="<?php echo $show['id']; ?>"><?php echo htmlspecialchars($show['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-hint">You can create a shop order without a show if needed</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="create_pullsheet" class="btn btn-primary">Create Shop Order</button>
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
        <p class="empty-title">No shop orders yet</p>
        <p class="empty-subtitle text-muted">Click "Create New Shop Order" to get started</p>
    </div>
<?php else: ?>
    <!-- Search and Filter Bar -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-4">
                    <div class="input-icon">
                        <span class="input-icon-addon">
                            <i class="ti ti-search"></i>
                        </span>
                        <input type="text" id="searchInput" class="form-control" placeholder="Search by barcode or creator...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select id="statusFilter" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="draft">Draft</option>
                        <option value="pending_approval">Pending Approval</option>
                        <option value="approved">Approved</option>
                        <option value="finalized">Finalized</option>
                        <option value="picked">Picked</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select id="showFilter" class="form-select">
                        <option value="">All Shows</option>
                        <?php foreach ($pullsheetsByShow as $showId => $showData): ?>
                            <option value="<?php echo $showId; ?>"><?php echo htmlspecialchars($showData['show_name']); ?></option>
                        <?php endforeach; ?>
                        <?php if (!empty($pullsheetsNoShow)): ?>
                            <option value="no-show">Not Attached to Show</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="button" id="clearFilters" class="btn btn-secondary w-100">
                        <i class="ti ti-x"></i> Clear
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Desktop Table View -->
    <div class="card d-none d-md-block" id="desktopTableCard">
        <div class="table-responsive">
            <table class="table table-vcenter table-hover card-table">
                <thead>
                    <tr>
                        <th>Barcode</th>
                        <th>Show</th>
                        <th>Status</th>
                        <th>Created By</th>
                        <th>Created Date</th>
                        <th>Last Modified</th>
                        <th class="w-1">Actions</th>
                    </tr>
                </thead>
                <tbody id="pullsheetsTableBody">
                    <?php foreach ($pullsheets as $ps): ?>
                        <tr class="pullsheet-row" 
                            data-barcode="<?php echo htmlspecialchars($ps['barcode']); ?>"
                            data-creator="<?php echo htmlspecialchars($ps['created_by'] ?? ''); ?>"
                            data-status="<?php echo htmlspecialchars($ps['status']); ?>"
                            data-show-id="<?php echo $ps['show_id'] ?: 'no-show'; ?>">
                            <td>
                                <span class="text-muted">
                                    <i class="ti ti-barcode"></i>
                                    <?php echo htmlspecialchars($ps['barcode']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($ps['show_name']): ?>
                                    <span class="badge bg-blue-lt"><?php echo htmlspecialchars($ps['show_name']); ?></span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php $badge = getStatusBadge($ps); ?>
                                <span class="badge <?php echo $badge['class']; ?>">
                                    <?php echo $badge['text']; ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($ps['created_by'] ?? 'N/A'); ?></td>
                            <td>
                                <span class="text-muted">
                                    <?php echo date('M d, Y', strtotime($ps['created_at'])); ?>
                                </span>
                            </td>
                            <td>
                                <span class="text-muted small" title="<?php echo date('M d, Y g:i A', strtotime($ps['updated_at'])); ?>">
                                    <?php echo timeAgo($ps['updated_at']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-list flex-nowrap">
                                    <?php if ($ps['status'] === 'draft'): ?>
                                        <a href="pullsheet_edit?id=<?php echo $ps['id']; ?>" 
                                           class="btn btn-sm btn-primary" 
                                           title="Edit">
                                            <i class="ti ti-edit"></i>
                                        </a>
                                    <?php else: ?>
                                        <a href="/pullsheets/view?id=<?php echo $ps['id']; ?>" 
                                           class="btn btn-sm btn-info" 
                                           title="View">
                                            <i class="ti ti-eye"></i>
                                        </a>
                                    <?php endif; ?>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this shop order? This cannot be undone.');">
                                        <input type="hidden" name="delete_id" value="<?php echo $ps['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Mobile Card View -->
    <div class="d-md-none" id="mobilePullsheets">
        <?php foreach ($pullsheets as $ps): ?>
            <div class="card mb-3 pullsheet-card" 
                 data-barcode="<?php echo htmlspecialchars($ps['barcode']); ?>"
                 data-creator="<?php echo htmlspecialchars($ps['created_by'] ?? ''); ?>"
                 data-status="<?php echo htmlspecialchars($ps['status']); ?>"
                 data-show-id="<?php echo $ps['show_id'] ?: 'no-show'; ?>">
                <div class="card-body">
                    <div class="row align-items-center mb-2">
                        <div class="col">
                            <h3 class="card-title mb-1">
                                <i class="ti ti-barcode"></i> <?php echo htmlspecialchars($ps['barcode']); ?>
                            </h3>
                            <?php if ($ps['show_name']): ?>
                                <div class="text-muted small">
                                    <span class="badge bg-blue-lt"><?php echo htmlspecialchars($ps['show_name']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-auto">
                            <?php $badge = getStatusBadge($ps); ?>
                            <span class="badge <?php echo $badge['class']; ?>">
                                <?php echo $badge['text']; ?>
                            </span>
                        </div>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted d-block">Created by: <?php echo htmlspecialchars($ps['created_by'] ?? 'N/A'); ?></small>
                        <small class="text-muted d-block">Date: <?php echo date('M d, Y', strtotime($ps['created_at'])); ?></small>
                    </div>
                    <div class="btn-list">
                        <?php if ($ps['status'] === 'draft'): ?>
                            <a href="pullsheet_edit?id=<?php echo $ps['id']; ?>" class="btn btn-sm btn-primary">
                                <i class="ti ti-edit"></i> Edit
                            </a>
                        <?php else: ?>
                            <a href="/pullsheets/view?id=<?php echo $ps['id']; ?>" class="btn btn-sm btn-info">
                                <i class="ti ti-eye"></i> View
                            </a>
                        <?php endif; ?>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this shop order? This cannot be undone.');">
                            <input type="hidden" name="delete_id" value="<?php echo $ps['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-danger">
                                <i class="ti ti-trash"></i> Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- No Results Message -->
    <div id="noResults" class="card d-none">
        <div class="empty">
            <div class="empty-icon">
                <i class="ti ti-search"></i>
            </div>
            <p class="empty-title">No shop orders found</p>
            <p class="empty-subtitle text-muted">Try adjusting your search or filters</p>
        </div>
    </div>
<?php endif; ?>

<style>
.table-hover tbody tr:hover {
    background-color: rgba(32, 107, 196, 0.06);
    cursor: pointer;
}

.btn-list {
    gap: 0.25rem;
}

.pullsheet-card {
    transition: all 0.2s ease-in-out;
}

.pullsheet-card:hover {
    box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}

.input-icon .form-control:focus {
    border-color: #206bc4;
}

@media (max-width: 767.98px) {
    .btn-list {
        display: flex;
        gap: 0.5rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const showFilter = document.getElementById('showFilter');
    const clearFiltersBtn = document.getElementById('clearFilters');
    
    const tableRows = document.querySelectorAll('.pullsheet-row');
    const mobileCards = document.querySelectorAll('.pullsheet-card');
    const noResults = document.getElementById('noResults');
    const desktopTable = document.getElementById('desktopTableCard');
    const mobileContainer = document.getElementById('mobilePullsheets');

    function filterPullsheets() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        const statusValue = statusFilter.value.toLowerCase();
        const showValue = showFilter.value;
        
        let visibleCount = 0;

        // Filter desktop table rows
        tableRows.forEach(row => {
            const barcode = row.dataset.barcode.toLowerCase();
            const creator = (row.dataset.creator || '').toLowerCase();
            const status = row.dataset.status.toLowerCase();
            const showId = row.dataset.showId;
            
            const matchesSearch = !searchTerm || barcode.includes(searchTerm) || creator.includes(searchTerm);
            const matchesStatus = !statusValue || status === statusValue;
            const matchesShow = !showValue || showId === showValue;
            
            if (matchesSearch && matchesStatus && matchesShow) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        // Filter mobile cards
        mobileCards.forEach(card => {
            const barcode = card.dataset.barcode.toLowerCase();
            const creator = (card.dataset.creator || '').toLowerCase();
            const status = card.dataset.status.toLowerCase();
            const showId = card.dataset.showId;
            
            const matchesSearch = !searchTerm || barcode.includes(searchTerm) || creator.includes(searchTerm);
            const matchesStatus = !statusValue || status === statusValue;
            const matchesShow = !showValue || showId === showValue;
            
            if (matchesSearch && matchesStatus && matchesShow) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });

        // Show/hide no results message
        if (visibleCount === 0) {
            noResults.classList.remove('d-none');
            if (desktopTable) {
                desktopTable.classList.add('d-none');
                desktopTable.classList.remove('d-md-block');
            }
            if (mobileContainer) {
                mobileContainer.classList.add('d-none');
            }
        } else {
            noResults.classList.add('d-none');
            if (desktopTable) {
                desktopTable.classList.remove('d-none');
                desktopTable.classList.add('d-md-block');
            }
            if (mobileContainer) {
                mobileContainer.classList.remove('d-none');
            }
        }
    }

    function clearFilters() {
        searchInput.value = '';
        statusFilter.value = '';
        showFilter.value = '';
        filterPullsheets();
    }

    searchInput.addEventListener('input', filterPullsheets);
    statusFilter.addEventListener('change', filterPullsheets);
    showFilter.addEventListener('change', filterPullsheets);
    clearFiltersBtn.addEventListener('click', clearFilters);
});
</script>

<?php require_once '../includes/footer.php'; ?>
