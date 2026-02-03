<?php
require_once 'includes/functions.php';

// Check permissions BEFORE including header
if (!hasPermission('student_requests')) {
    setAlert('You do not have permission to access student requests', 'danger');
    redirect('index');
}

$pageTitle = 'Student Requests';
require_once 'includes/header.php';

$db = getDB();
$currentUser = getCurrentUser();
$isStudent = $currentUser['role'] === 'student';
$isDesigner = $currentUser['role'] === 'designer';
$isProductionAudio = $currentUser['role'] === 'production_audio';
$canManageRequests = isAdmin(); // Only admins can manage/approve requests

// Handle request operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create' && !$canManageRequests) {
        // Students, designers, and production audio can create requests
        $itemId = $_POST['item_id'] ?? 0;
        $quantity = (int)($_POST['quantity'] ?? 1);
        $reason = trim($_POST['reason'] ?? '');
        
        if (empty($reason)) {
            setAlert('Please provide a reason for your request', 'danger');
        } else {
            $item = getItemById($itemId);
            if (!$item) {
                setAlert('Item not found', 'danger');
            } else {
                // Generate request ID
                $requestId = 'REQ-' . strtoupper(substr(uniqid(), -8));
                
                // Create request
                $db->query(
                    "INSERT INTO student_requests (request_id, student_id, item_id, quantity, reason, status) 
                     VALUES (?, ?, ?, ?, ?, 'pending')",
                    [$requestId, $currentUser['id'], $itemId, $quantity, $reason]
                );
                
                // Notify admins and designers
                createNotificationForDesigners(
                    'student_request',
                    "New student request: " . $item['name'] . " (Qty: " . $quantity . ")",
                    "student_requests.php"
                );
                
                setAlert('Request created successfully with ID: ' . $requestId, 'success');
                redirect();
            }
        }
    } elseif ($action === 'approve' && $canManageRequests) {
        $requestId = $_POST['request_id'] ?? 0;
        
        // Get request details for notification
        $request = $db->fetchOne(
            "SELECT sr.*, i.name as item_name 
             FROM student_requests sr
             LEFT JOIN items i ON sr.item_id = i.id
             WHERE sr.id = ?",
            [$requestId]
        );
        
        $db->query(
            "UPDATE student_requests SET status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ?",
            [$currentUser['id'], $requestId]
        );
        
        // Notify the student
        if ($request) {
            createNotification(
                $request['student_id'],
                'student_request_approved',
                "Your request for " . $request['item_name'] . " (Qty: " . $request['quantity'] . ") has been approved",
                "student_requests.php"
            );
        }
        
        setAlert('Request approved', 'success');
        redirect();
    } elseif ($action === 'reject' && $canManageRequests) {
        $requestId = $_POST['request_id'] ?? 0;
        $reason = trim($_POST['rejection_reason'] ?? '');
        
        // Get request details for notification
        $request = $db->fetchOne(
            "SELECT sr.*, i.name as item_name 
             FROM student_requests sr
             LEFT JOIN items i ON sr.item_id = i.id
             WHERE sr.id = ?",
            [$requestId]
        );
        
        $db->query(
            "UPDATE student_requests SET status = 'rejected', approved_by = ?, approved_at = NOW() WHERE id = ?",
            [$currentUser['id'], $requestId]
        );
        
        // Notify the student
        if ($request) {
            createNotification(
                $request['student_id'],
                'student_request_rejected',
                "Your request for " . $request['item_name'] . " (Qty: " . $request['quantity'] . ") has been rejected" . ($reason ? ": $reason" : ""),
                "student_requests.php"
            );
        }
        
        setAlert('Request rejected', 'success');
        redirect();
    } elseif ($action === 'fulfill' && $canManageRequests) {
        $requestId = $_POST['request_id'] ?? 0;
        
        $db->query("UPDATE student_requests SET status = 'fulfilled' WHERE id = ?", [$requestId]);
        
        setAlert('Request marked as fulfilled', 'success');
        redirect();
    } elseif ($action === 'delete' && $isStudent) {
        $requestId = $_POST['request_id'] ?? 0;
        
        // Students can only delete their own pending requests
        $request = $db->fetchOne(
            "SELECT * FROM student_requests WHERE id = ? AND student_id = ? AND status = 'pending'",
            [$requestId, $currentUser['id']]
        );
        
        if ($request) {
            $db->query("DELETE FROM student_requests WHERE id = ?", [$requestId]);
            setAlert('Request deleted', 'success');
        } else {
            setAlert('Cannot delete this request', 'danger');
        }
        redirect();
    } elseif ($action === 'create_pullsheet' && $canManageRequests) {
        $selectedRequests = $_POST['selected_requests'] ?? [];
        
        if (empty($selectedRequests)) {
            setAlert('Please select at least one approved request', 'danger');
        } else {
            // Generate pullsheet barcode
            $barcode = 'PULL-' . strtoupper(substr(uniqid(), -8));
            
            // Create pullsheet (with null show_id for student requests)
            $db->query(
                "INSERT INTO pullsheets (show_id, barcode, created_by, status) VALUES (NULL, ?, ?, 'draft')",
                [$barcode, $currentUser['name']]
            );
            $pullsheetId = $db->lastInsertId();
            
            // Add items to pullsheet and update requests
            foreach ($selectedRequests as $requestId) {
                $request = $db->fetchOne("SELECT * FROM student_requests WHERE id = ?", [$requestId]);
                if ($request && $request['status'] === 'approved') {
                    // Check if item already exists in pullsheet, if so increase quantity
                    $existing = $db->fetchOne(
                        "SELECT * FROM pullsheet_items WHERE pullsheet_id = ? AND item_id = ?",
                        [$pullsheetId, $request['item_id']]
                    );
                    
                    if ($existing) {
                        $db->query(
                            "UPDATE pullsheet_items SET quantity_needed = quantity_needed + ? WHERE id = ?",
                            [$request['quantity'], $existing['id']]
                        );
                    } else {
                        $db->query(
                            "INSERT INTO pullsheet_items (pullsheet_id, item_id, quantity_needed) VALUES (?, ?, ?)",
                            [$pullsheetId, $request['item_id'], $request['quantity']]
                        );
                    }
                    
                    // Update request with pullsheet_id
                    $db->query(
                        "UPDATE student_requests SET pullsheet_id = ? WHERE id = ?",
                        [$pullsheetId, $requestId]
                    );
                }
            }
            
            setAlert('Pullsheet created successfully: ' . $barcode, 'success');
            header('Location: pullsheet_edit?id=' . $pullsheetId);
            exit;
        }
    }
}

// Get filter parameters
$statusFilter = $_GET['status'] ?? 'all';
$categoryFilter = $_GET['category'] ?? 'all';

// Build query for requests list - only admins can see/manage all requests
if (!$canManageRequests) {
    // Students, designers, and production audio don't see the requests table - only the create form
    $requests = [];
} else {
    // Admins see all requests
    $query = "SELECT sr.*, i.name as item_name, i.barcode, i.in_stock_quantity, c.name as category_name,
              u.full_name as student_name, a.full_name as approved_by_name
              FROM student_requests sr
              LEFT JOIN items i ON sr.item_id = i.id
              LEFT JOIN categories c ON i.category_id = c.id
              LEFT JOIN users u ON sr.student_id = u.id
              LEFT JOIN users a ON sr.approved_by = a.id
              WHERE 1=1";
    $params = [];
    
    if ($statusFilter !== 'all') {
        $query .= " AND sr.status = ?";
        $params[] = $statusFilter;
    }

    if ($categoryFilter !== 'all') {
        $query .= " AND i.category_id = ?";
        $params[] = $categoryFilter;
    }

    $query .= " ORDER BY sr.created_at DESC";
    
    $requests = $db->fetchAll($query, $params);
}
$categories = getAllCategories();
$allItems = getAllItems();

// Count approved requests for pullsheet generation
$approvedCount = 0;
foreach ($requests as $request) {
    if ($request['status'] === 'approved' && !$request['pullsheet_id']) {
        $approvedCount++;
    }
}
?>

<div class="row mb-4">
    <div class="col-12">
        <?php if (!$canManageRequests): ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#browseItemsModal">
            <i class="ti ti-plus icon"></i> New Request
        </button>
        <?php else: ?>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createPullsheetModal" <?php echo $approvedCount === 0 ? 'disabled' : ''; ?>>
            <i class="ti ti-file-text icon"></i> Generate Pullsheet from Approved Requests (<?php echo $approvedCount; ?>)
        </button>
        <?php endif; ?>
    </div>
</div>

<?php if ($canManageRequests): ?>
<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Status</label>
                <select class="form-select" name="status" onchange="this.form.submit()">
                    <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                    <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="approved" <?php echo $statusFilter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    <option value="fulfilled" <?php echo $statusFilter === 'fulfilled' ? 'selected' : ''; ?>>Fulfilled</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Category</label>
                <select class="form-select" name="category" onchange="this.form.submit()">
                    <option value="all" <?php echo $categoryFilter === 'all' ? 'selected' : ''; ?>>All Categories</option>
                    <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['id']; ?>" <?php echo $categoryFilter == $category['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($category['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">&nbsp;</label>
                <a href="student_requests" class="btn btn-secondary w-100">Clear Filters</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Student Requests</h3>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Request ID</th>
                    <?php if (!$isStudent): ?>
                    <th>Student</th>
                    <?php endif; ?>
                    <th>Item</th>
                    <th>Category</th>
                    <th>Quantity</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th class="w-1">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($requests)): ?>
                <tr>
                    <td colspan="<?php echo $isStudent ? '7' : '8'; ?>" class="text-center text-muted">No requests found</td>
                </tr>
                <?php else: ?>
                <?php foreach ($requests as $request): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($request['request_id']); ?></strong></td>
                    <?php if (!$isStudent): ?>
                    <td><?php echo htmlspecialchars($request['student_name']); ?></td>
                    <?php endif; ?>
                    <td><?php echo htmlspecialchars($request['item_name']); ?></td>
                    <td><?php echo htmlspecialchars($request['category_name'] ?? 'N/A'); ?></td>
                    <td><?php echo $request['quantity']; ?></td>
                    <td>
                        <?php
                        $statusColors = [
                            'pending' => 'warning',
                            'approved' => 'success',
                            'rejected' => 'danger',
                            'fulfilled' => 'info'
                        ];
                        $color = $statusColors[$request['status']] ?? 'secondary';
                        ?>
                        <span class="badge bg-<?php echo $color; ?>">
                            <?php echo ucfirst($request['status']); ?>
                        </span>
                    </td>
                    <td><?php echo date('m/d/Y', strtotime($request['created_at'])); ?></td>
                    <td>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#viewRequestModal<?php echo $request['id']; ?>">
                                <i class="ti ti-eye icon"></i>
                            </button>
                            <?php if ($canManageRequests && $request['status'] === 'pending'): ?>
                            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#approveRequestModal<?php echo $request['id']; ?>">
                                <i class="ti ti-check icon"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#rejectRequestModal<?php echo $request['id']; ?>">
                                <i class="ti ti-x icon"></i>
                            </button>
                            <?php endif; ?>
                            <?php if ($canManageRequests && $request['status'] === 'approved' && !$request['pullsheet_id']): ?>
                            <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#fulfillRequestModal<?php echo $request['id']; ?>">
                                <i class="ti ti-circle-check icon"></i>
                            </button>
                            <?php endif; ?>
                            <?php if ($isStudent && $request['status'] === 'pending'): ?>
                            <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteRequestModal<?php echo $request['id']; ?>">
                                <i class="ti ti-trash icon"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Browse Items Modal (for students and designers) -->
<?php if ($isStudent || $isDesigner): ?>
<div class="modal fade" id="browseItemsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Browse Items</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <input type="text" id="itemSearch" class="form-control" placeholder="Search items...">
                </div>
                <div class="list-group" id="itemsList">
                    <?php foreach ($allItems as $item): ?>
                    <a href="#" class="list-group-item list-group-item-action" 
                       onclick="selectItemForRequest(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['name'], ENT_QUOTES); ?>', <?php echo $item['in_stock_quantity']; ?>); return false;">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                            <small class="<?php echo $item['in_stock_quantity'] > 0 ? 'text-success' : 'text-danger'; ?>">
                                <?php echo $item['in_stock_quantity']; ?> available
                            </small>
                        </div>
                        <small class="text-muted"><?php echo htmlspecialchars($item['category_name'] ?? 'Uncategorized'); ?> - <?php echo htmlspecialchars($item['barcode']); ?></small>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Request Modal -->
<div class="modal fade" id="createRequestModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="item_id" id="selectedItemId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Selected Item</label>
                        <input type="text" class="form-control" id="selectedItemName" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Available Quantity</label>
                        <input type="text" class="form-control" id="selectedItemStock" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Quantity Needed</label>
                        <input type="number" class="form-control" name="quantity" value="1" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Reason for Request</label>
                        <textarea class="form-control" name="reason" rows="4" placeholder="Please provide a detailed reason for this request..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Item search functionality
document.getElementById('itemSearch')?.addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const items = document.querySelectorAll('#itemsList .list-group-item');
    
    items.forEach(item => {
        const text = item.textContent.toLowerCase();
        if (text.includes(searchTerm)) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });
});

function selectItemForRequest(itemId, itemName, stockQuantity) {
    document.getElementById('selectedItemId').value = itemId;
    document.getElementById('selectedItemName').value = itemName;
    document.getElementById('selectedItemStock').value = stockQuantity + ' available';
    
    // Close browse modal and open create modal
    const browseModal = bootstrap.Modal.getInstance(document.getElementById('browseItemsModal'));
    browseModal.hide();
    
    const createModal = new bootstrap.Modal(document.getElementById('createRequestModal'));
    createModal.show();
}
</script>
<?php endif; ?>

<!-- Create Pullsheet Modal (for admins/designers) -->
<?php if ($canManageRequests && $approvedCount > 0): ?>
<div class="modal fade" id="createPullsheetModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Generate Pullsheet from Approved Requests</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="create_pullsheet">
                <div class="modal-body">
                    <p>Select approved requests to include in the pullsheet:</p>
                    <div class="list-group">
                        <?php foreach ($requests as $request): ?>
                        <?php if ($request['status'] === 'approved' && !$request['pullsheet_id']): ?>
                        <label class="list-group-item">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <input type="checkbox" class="form-check-input" name="selected_requests[]" value="<?php echo $request['id']; ?>" checked>
                                </div>
                                <div class="col">
                                    <strong><?php echo htmlspecialchars($request['item_name']); ?></strong>
                                    <br>
                                    <small class="text-muted">
                                        Student: <?php echo htmlspecialchars($request['student_name']); ?> | 
                                        Qty: <?php echo $request['quantity']; ?> | 
                                        Request: <?php echo htmlspecialchars($request['request_id']); ?>
                                    </small>
                                </div>
                            </div>
                        </label>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Create Pullsheet</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modals for each request -->
<?php foreach ($requests as $request): ?>
<!-- View Request Modal -->
<div class="modal fade" id="viewRequestModal<?php echo $request['id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Request Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <dl class="row">
                    <dt class="col-sm-4">Request ID:</dt>
                    <dd class="col-sm-8"><?php echo htmlspecialchars($request['request_id']); ?></dd>
                    
                    <?php if (!$isStudent): ?>
                    <dt class="col-sm-4">Student:</dt>
                    <dd class="col-sm-8"><?php echo htmlspecialchars($request['student_name']); ?></dd>
                    <?php endif; ?>
                    
                    <dt class="col-sm-4">Item:</dt>
                    <dd class="col-sm-8"><?php echo htmlspecialchars($request['item_name']); ?></dd>
                    
                    <dt class="col-sm-4">Barcode:</dt>
                    <dd class="col-sm-8"><?php echo htmlspecialchars($request['barcode']); ?></dd>
                    
                    <dt class="col-sm-4">Category:</dt>
                    <dd class="col-sm-8"><?php echo htmlspecialchars($request['category_name'] ?? 'N/A'); ?></dd>
                    
                    <dt class="col-sm-4">Quantity:</dt>
                    <dd class="col-sm-8"><?php echo $request['quantity']; ?></dd>
                    
                    <dt class="col-sm-4">Available:</dt>
                    <dd class="col-sm-8">
                        <span class="<?php echo $request['in_stock_quantity'] >= $request['quantity'] ? 'text-success' : 'text-danger'; ?>">
                            <?php echo $request['in_stock_quantity']; ?> in stock
                        </span>
                    </dd>
                    
                    <dt class="col-sm-4">Status:</dt>
                    <dd class="col-sm-8">
                        <span class="badge bg-<?php echo $statusColors[$request['status']] ?? 'secondary'; ?>">
                            <?php echo ucfirst($request['status']); ?>
                        </span>
                    </dd>
                    
                    <dt class="col-sm-4">Reason:</dt>
                    <dd class="col-sm-8"><?php echo nl2br(htmlspecialchars($request['reason'])); ?></dd>
                    
                    <dt class="col-sm-4">Created:</dt>
                    <dd class="col-sm-8"><?php echo date('m/d/Y H:i', strtotime($request['created_at'])); ?></dd>
                    
                    <?php if ($request['approved_by']): ?>
                    <dt class="col-sm-4">Reviewed By:</dt>
                    <dd class="col-sm-8"><?php echo htmlspecialchars($request['approved_by_name']); ?></dd>
                    
                    <dt class="col-sm-4">Reviewed At:</dt>
                    <dd class="col-sm-8"><?php echo date('m/d/Y H:i', strtotime($request['approved_at'])); ?></dd>
                    <?php endif; ?>
                    
                    <?php if ($request['pullsheet_id']): ?>
                    <dt class="col-sm-4">Pullsheet:</dt>
                    <dd class="col-sm-8">
                        <a href="pullsheet_view?id=<?php echo $request['pullsheet_id']; ?>" class="btn btn-sm btn-primary">
                            View Pullsheet
                        </a>
                    </dd>
                    <?php endif; ?>
                </dl>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php if ($canManageRequests && $request['status'] === 'pending'): ?>
<!-- Approve Request Modal -->
<div class="modal fade" id="approveRequestModal<?php echo $request['id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Approve Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="approve">
                <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                <div class="modal-body">
                    <p>Approve request <strong><?php echo htmlspecialchars($request['request_id']); ?></strong>?</p>
                    <div class="alert alert-info">
                        <strong>Item:</strong> <?php echo htmlspecialchars($request['item_name']); ?><br>
                        <strong>Quantity:</strong> <?php echo $request['quantity']; ?><br>
                        <strong>Available:</strong> <?php echo $request['in_stock_quantity']; ?><br>
                        <strong>Student:</strong> <?php echo htmlspecialchars($request['student_name']); ?>
                    </div>
                    <?php if ($request['in_stock_quantity'] < $request['quantity']): ?>
                    <div class="alert alert-warning">
                        <i class="ti ti-alert-triangle icon"></i>
                        Warning: Not enough items in stock!
                    </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Approve Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Request Modal -->
<div class="modal fade" id="rejectRequestModal<?php echo $request['id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                <div class="modal-body">
                    <p>Reject request <strong><?php echo htmlspecialchars($request['request_id']); ?></strong>?</p>
                    <div class="mb-3">
                        <label class="form-label">Reason (optional)</label>
                        <textarea class="form-control" name="rejection_reason" rows="3" placeholder="Explain why this request is being rejected..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Request</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($canManageRequests && $request['status'] === 'approved' && !$request['pullsheet_id']): ?>
<!-- Fulfill Request Modal -->
<div class="modal fade" id="fulfillRequestModal<?php echo $request['id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Mark as Fulfilled</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="fulfill">
                <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                <div class="modal-body">
                    <p>Mark request <strong><?php echo htmlspecialchars($request['request_id']); ?></strong> as fulfilled?</p>
                    <p class="text-muted">This indicates the items have been provided to the student.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info">Mark as Fulfilled</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($isStudent && $request['status'] === 'pending'): ?>
<!-- Delete Request Modal -->
<div class="modal fade" id="deleteRequestModal<?php echo $request['id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                <div class="modal-body">
                    <p>Are you sure you want to delete request <strong><?php echo htmlspecialchars($request['request_id']); ?></strong>?</p>
                    <p class="text-danger">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Request</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php endforeach; ?>

<?php require_once 'includes/footer.php'; ?>
