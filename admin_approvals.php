<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

requireLogin();
requireRole('admin'); // Only admins can access this page

$currentUser = getCurrentUser();
$db = getDB();

// Handle signature submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    ob_start();
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'approve') {
        $type = $_POST['type'] ?? ''; // 'pullsheet' or 'change_order'
        $id = intval($_POST['id'] ?? 0);
        $signatureData = $_POST['signature_data'] ?? '';
        
        if (!$signatureData) {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Signature is required']);
            exit;
        }
        
        if (!$id || !in_array($type, ['pullsheet', 'change_order'])) {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            exit;
        }
        
        try {
            // Update approval status
            if ($type === 'pullsheet') {
                $db->query(
                    "UPDATE pullsheets SET approval_status = 'approved', approved_at = NOW(), approved_by = ? WHERE id = ?",
                    [$currentUser['id'], $id]
                );
                
                // Store signature
                $db->query(
                    "INSERT INTO signatures (user_id, pullsheet_id, signature_data, first_name, last_name, created_at) VALUES (?, ?, ?, ?, ?, NOW())",
                    [$currentUser['id'], $id, $signatureData, $currentUser['first_name'], $currentUser['last_name']]
                );
            } else {
                $db->query(
                    "UPDATE change_orders SET approval_status = 'approved', approved_at = NOW(), approved_by = ? WHERE id = ?",
                    [$currentUser['id'], $id]
                );
                
                // Store signature
                $db->query(
                    "INSERT INTO signatures (user_id, change_order_id, signature_data, first_name, last_name, created_at) VALUES (?, ?, ?, ?, ?, NOW())",
                    [$currentUser['id'], $id, $signatureData, $currentUser['first_name'], $currentUser['last_name']]
                );
            }
            
            ob_end_clean();
            echo json_encode(['success' => true, 'message' => 'Operation approved successfully']);
            exit;
            
        } catch (Exception $e) {
            error_log("Approval error: " . $e->getMessage());
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Failed to approve operation']);
            exit;
        }
    }
    
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

// Get pending approvals
$pendingPullsheets = $db->fetchAll(
    "SELECT p.*, s.name as show_name, u.name as user_name, u.email as user_email
     FROM pullsheets p
     LEFT JOIN shows s ON p.show_id = s.id
     LEFT JOIN users u ON p.picked_by = u.name
     WHERE p.approval_status = 'pending' AND p.status = 'picked'
     ORDER BY p.picked_at DESC"
);

$pendingChangeOrders = $db->fetchAll(
    "SELECT co.*, s.name as show_name, u.name as user_name, u.email as user_email
     FROM change_orders co
     LEFT JOIN shows s ON co.show_id = s.id
     LEFT JOIN users u ON co.processed_by = u.name
     WHERE co.approval_status = 'pending' AND co.status = 'processed'
     ORDER BY co.processed_at DESC"
);

include 'includes/header.php';
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">
                    <i class="ti ti-signature"></i> Admin Approvals
                </h2>
                <div class="text-muted mt-1">Review and approve operations requiring admin signature</div>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        
        <?php if (empty($pendingPullsheets) && empty($pendingChangeOrders)): ?>
        <div class="empty">
            <div class="empty-icon">
                <i class="ti ti-check icon"></i>
            </div>
            <p class="empty-title">All caught up!</p>
            <p class="empty-subtitle text-muted">
                There are no operations pending your approval at this time.
            </p>
        </div>
        <?php else: ?>
        
        <!-- Pending Pullsheets -->
        <?php if (!empty($pendingPullsheets)): ?>
        <div class="card mb-3">
            <div class="card-header">
                <h3 class="card-title"><i class="ti ti-package me-2"></i>Pending Shop Orders</h3>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <?php foreach ($pendingPullsheets as $ps): ?>
                    <div class="list-group-item">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="avatar" style="background-image: url(<?php echo !empty($ps['user_email']) ? 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($ps['user_email']))) . '?d=identicon' : ''; ?>)"></span>
                            </div>
                            <div class="col">
                                <div class="text-truncate">
                                    <strong><?php echo htmlspecialchars($ps['show_name'] ?? 'Unknown Show'); ?></strong>
                                </div>
                                <div class="text-muted">
                                    Barcode: <?php echo htmlspecialchars($ps['barcode']); ?> • 
                                    Picked by: <?php echo htmlspecialchars($ps['picked_by']); ?> • 
                                    <?php echo date('M j, Y g:i A', strtotime($ps['picked_at'])); ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <button class="btn btn-primary approve-btn" 
                                        data-type="pullsheet" 
                                        data-id="<?php echo $ps['id']; ?>"
                                        data-title="Shop Order <?php echo htmlspecialchars($ps['barcode']); ?>">
                                    <i class="ti ti-signature"></i> Sign & Approve
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Pending Change Orders -->
        <?php if (!empty($pendingChangeOrders)): ?>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="ti ti-arrows-exchange me-2"></i>Pending Change Orders</h3>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <?php foreach ($pendingChangeOrders as $co): ?>
                    <div class="list-group-item">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="avatar" style="background-image: url(<?php echo !empty($co['user_email']) ? 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($co['user_email']))) . '?d=identicon' : ''; ?>)"></span>
                            </div>
                            <div class="col">
                                <div class="text-truncate">
                                    <strong><?php echo htmlspecialchars($co['show_name'] ?? 'Unknown Show'); ?></strong>
                                </div>
                                <div class="text-muted">
                                    Barcode: <?php echo htmlspecialchars($co['barcode']); ?> • 
                                    Processed by: <?php echo htmlspecialchars($co['processed_by']); ?> • 
                                    <?php echo date('M j, Y g:i A', strtotime($co['processed_at'])); ?>
                                </div>
                            </div>
                            <div class="col-auto">
                                <button class="btn btn-primary approve-btn" 
                                        data-type="change_order" 
                                        data-id="<?php echo $co['id']; ?>"
                                        data-title="Change Order <?php echo htmlspecialchars($co['barcode']); ?>">
                                    <i class="ti ti-signature"></i> Sign & Approve
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php endif; ?>
    </div>
</div>

<!-- Signature Modal -->
<div class="modal fade" id="signatureModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Admin Signature Required</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3">Please sign to approve <strong id="approvalTitle"></strong></p>
                
                <div class="mb-3">
                    <label class="form-label">Your Name</label>
                    <input type="text" class="form-control" id="adminName" 
                           value="<?php echo htmlspecialchars($currentUser['name']); ?>" 
                           readonly>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Signature</label>
                    <div class="signature-pad-container">
                        <canvas id="signatureCanvas" width="400" height="150" 
                                style="border: 1px solid #ccc; background: white; width: 100%; cursor: crosshair;"></canvas>
                    </div>
                    <div class="mt-2">
                        <button type="button" class="btn btn-sm btn-secondary" id="clearSignature">
                            <i class="ti ti-eraser"></i> Clear
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="submitSignature">
                    <i class="ti ti-check"></i> Approve
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Signature pad functionality
    const canvas = document.getElementById('signatureCanvas');
    const ctx = canvas.getContext('2d');
    let drawing = false;
    let currentApproval = null;
    
    // Set canvas background to white
    ctx.fillStyle = 'white';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    
    canvas.addEventListener('mousedown', (e) => {
        drawing = true;
        ctx.beginPath();
        const rect = canvas.getBoundingClientRect();
        ctx.moveTo(e.clientX - rect.left, e.clientY - rect.top);
    });
    
    canvas.addEventListener('mousemove', (e) => {
        if (!drawing) return;
        const rect = canvas.getBoundingClientRect();
        ctx.lineTo(e.clientX - rect.left, e.clientY - rect.top);
        ctx.strokeStyle = 'black';
        ctx.lineWidth = 2;
        ctx.stroke();
    });
    
    canvas.addEventListener('mouseup', () => {
        drawing = false;
    });
    
    canvas.addEventListener('mouseleave', () => {
        drawing = false;
    });
    
    // Touch support
    canvas.addEventListener('touchstart', (e) => {
        e.preventDefault();
        drawing = true;
        const rect = canvas.getBoundingClientRect();
        const touch = e.touches[0];
        ctx.beginPath();
        ctx.moveTo(touch.clientX - rect.left, touch.clientY - rect.top);
    });
    
    canvas.addEventListener('touchmove', (e) => {
        e.preventDefault();
        if (!drawing) return;
        const rect = canvas.getBoundingClientRect();
        const touch = e.touches[0];
        ctx.lineTo(touch.clientX - rect.left, touch.clientY - rect.top);
        ctx.strokeStyle = 'black';
        ctx.lineWidth = 2;
        ctx.stroke();
    });
    
    canvas.addEventListener('touchend', () => {
        drawing = false;
    });
    
    // Clear signature
    document.getElementById('clearSignature').addEventListener('click', () => {
        ctx.fillStyle = 'white';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
    });
    
    // Open modal when approve button clicked
    document.querySelectorAll('.approve-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            currentApproval = {
                type: btn.dataset.type,
                id: btn.dataset.id,
                title: btn.dataset.title
            };
            document.getElementById('approvalTitle').textContent = currentApproval.title;
            
            // Clear signature
            ctx.fillStyle = 'white';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            
            const modal = new bootstrap.Modal(document.getElementById('signatureModal'));
            modal.show();
        });
    });
    
    // Submit signature
    document.getElementById('submitSignature').addEventListener('click', () => {
        if (!currentApproval) return;
        
        // Get signature data
        const signatureData = canvas.toDataURL();
        
        // Check if signature is empty (just white background)
        const emptyCanvas = document.createElement('canvas');
        emptyCanvas.width = canvas.width;
        emptyCanvas.height = canvas.height;
        const emptyCtx = emptyCanvas.getContext('2d');
        emptyCtx.fillStyle = 'white';
        emptyCtx.fillRect(0, 0, emptyCanvas.width, emptyCanvas.height);
        
        if (signatureData === emptyCanvas.toDataURL()) {
            alert('Please provide a signature');
            return;
        }
        
        // Show loading
        if (typeof showLoading === 'function') showLoading();
        
        // Submit approval
        fetch('admin_approvals', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                ajax: '1',
                action: 'approve',
                type: currentApproval.type,
                id: currentApproval.id,
                signature_data: signatureData
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Approved successfully!');
                location.reload();
            } else {
                alert(data.message || 'Failed to approve');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred');
        });
    });
</script>

<?php include 'includes/footer.php'; ?>
