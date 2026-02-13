<?php
// Start output buffering FIRST to catch any warnings/errors
ob_start();

session_start();
require_once __DIR__ . '/includes/functions.php';

$pickSession = $_SESSION['pick_session'] ?? null;

// Log for debugging
error_log("Pick mode: REQUEST_METHOD=" . $_SERVER['REQUEST_METHOD'] . ", POST keys=" . implode(',', array_keys($_POST ?? [])));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    error_log("Pick mode: AJAX request detected, action=" . ($_POST['action'] ?? 'none'));
    header('Content-Type: application/json');
    
    // Suppress error display for AJAX (errors still logged)
    ini_set('display_errors', '0');
    
    try {
        // Check authentication for AJAX requests
        $user = getCurrentUser();
        if (!$user || !isset($user['id'])) {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Session expired. Please log in again.', 'redirect' => 'login']);
            exit;
        }
        
        // Check permission for AJAX requests (return JSON instead of redirecting)
        if (!hasPermission('operations')) {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'You do not have permission to access this feature.', 'redirect' => 'index']);
            exit;
        }
        
        if ($_POST['action'] === 'start_pick') {
            $barcode = trim($_POST['barcode']);
            $pickerName = $user['name'] ?? 'Unknown'; // Use logged-in user's name with fallback
            
            // Try shop order first
            $pullsheet = getPullsheetByBarcode($barcode);
            if ($pullsheet && $pullsheet['status'] === 'finalized') {
                // Check if approval is required and approved
                if ($pullsheet['requires_approval'] && $pullsheet['approval_status'] !== 'approved') {
                    ob_end_clean(); // Clear any buffered output
                    echo json_encode(['success' => false, 'message' => 'This shop order requires admin approval before it can be picked']);
                    exit;
                }
                
                $_SESSION['pick_session'] = [
                    'type' => 'pullsheet',
                    'id' => $pullsheet['id'],
                    'picker_name' => $pickerName,
                    'show_name' => $pullsheet['show_name'],
                    'items' => [],
                    'is_resuming_draft' => $pullsheet['is_partial'] ? true : false,
                    'draft_saved_at' => $pullsheet['partial_saved_at']
                ];
                
                foreach (getPullsheetItems($pullsheet['id']) as $item) {
                    $_SESSION['pick_session']['items'][$item['item_id']] = [
                        'name' => $item['item_name'],
                        'barcode' => $item['item_barcode'],
                        'needed' => $item['quantity_needed'],
                        'scanned' => $pullsheet['is_partial'] ? (int)$item['quantity_picked'] : 0
                    ];
                }
                
                ob_end_clean(); // Clear any buffered output
                echo json_encode([
                    'success' => true,
                    'is_resuming_draft' => $pullsheet['is_partial'] ? true : false,
                    'draft_saved_at' => $pullsheet['partial_saved_at']
                ]);
                exit;
            }
            
            // Try change order for picking
            $changeOrder = getChangeOrderByBarcode($barcode);
            if ($changeOrder && $changeOrder['status'] === 'finalized') {
                // Check if approval is required and approved
                if ($changeOrder['requires_approval'] && $changeOrder['approval_status'] !== 'approved') {
                    ob_end_clean(); // Clear any buffered output
                    echo json_encode(['success' => false, 'message' => 'This change order requires admin approval before it can be picked']);
                    exit;
                }
                
                // Check if this change order has items to add
                $itemsToAdd = getDB()->fetchAll(
                    "SELECT * FROM change_order_items WHERE change_order_id = ? AND type = 'add'",
                    [$changeOrder['id']]
                );
                
                if (empty($itemsToAdd)) {
                    ob_end_clean(); // Clear any buffered output
                    echo json_encode(['success' => false, 'message' => 'This change order has no items to pick']);
                    exit;
                }
                
                $_SESSION['pick_session'] = [
                    'type' => 'change_order',
                    'id' => $changeOrder['id'],
                    'picker_name' => $pickerName,
                    'show_name' => $changeOrder['show_name'],
                    'items' => [],
                    'is_resuming_draft' => $changeOrder['is_partial'] ? true : false,
                    'draft_saved_at' => $changeOrder['partial_saved_at']
                ];
                
                foreach (getChangeOrderItems($changeOrder['id']) as $item) {
                    if ($item['type'] === 'add') {
                        $_SESSION['pick_session']['items'][$item['item_id']] = [
                            'name' => $item['item_name'],
                            'barcode' => $item['item_barcode'],
                            'needed' => $item['quantity_change'],
                            'scanned' => $changeOrder['is_partial'] ? (int)$item['quantity_processed'] : 0
                        ];
                    }
                }
                
                ob_end_clean(); // Clear any buffered output
                echo json_encode([
                    'success' => true,
                    'is_resuming_draft' => $changeOrder['is_partial'] ? true : false,
                    'draft_saved_at' => $changeOrder['partial_saved_at']
                ]);
                exit;
            }
            
            ob_end_clean(); // Clear any buffered output
            echo json_encode(['success' => false, 'message' => 'Shop Order or change order not found or not finalized']);
            exit;
        }
        
        if ($_POST['action'] === 'scan_item') {
            $barcode = trim($_POST['barcode']);
            $item = getItemByBarcode($barcode);
            
            if ($item && isset($_SESSION['pick_session']['items'][$item['id']])) {
                $_SESSION['pick_session']['items'][$item['id']]['scanned']++;
                ob_end_clean(); // Clear any buffered output
                echo json_encode([
                    'success' => true,
                    'item' => $_SESSION['pick_session']['items'][$item['id']],
                    'itemId' => $item['id']
                ]);
                exit;
            }
            
            ob_end_clean(); // Clear any buffered output
            echo json_encode(['success' => false, 'message' => 'Item not in this pick list']);
            exit;
        }
        
        if ($_POST['action'] === 'adjust_item') {
            $itemId = (int)$_POST['item_id'];
            $adjustment = (int)$_POST['adjustment'];
            
            if (isset($_SESSION['pick_session']['items'][$itemId])) {
                $newCount = $_SESSION['pick_session']['items'][$itemId]['scanned'] + $adjustment;
                if ($newCount >= 0) {
                    $_SESSION['pick_session']['items'][$itemId]['scanned'] = $newCount;
                    ob_end_clean(); // Clear any buffered output
                    echo json_encode([
                        'success' => true,
                        'item' => $_SESSION['pick_session']['items'][$itemId]
                    ]);
                    exit;
                }
            }
            
            ob_end_clean(); // Clear any buffered output
            echo json_encode(['success' => false]);
            exit;
        }
        
        if ($_POST['action'] === 'save_draft') {
            $sessionType = $_SESSION['pick_session']['type'];
            $sessionId = $_SESSION['pick_session']['id'];
            
            if ($sessionType === 'pullsheet') {
                foreach ($_SESSION['pick_session']['items'] as $itemId => $data) {
                    getDB()->query(
                        "UPDATE pullsheet_items SET quantity_picked = ? WHERE pullsheet_id = ? AND item_id = ?",
                        [$data['scanned'], $sessionId, $itemId]
                    );
                }
                getDB()->query(
                    "UPDATE pullsheets SET is_partial = 1, partial_saved_at = NOW() WHERE id = ?",
                    [$sessionId]
                );
            } elseif ($sessionType === 'change_order') {
                foreach ($_SESSION['pick_session']['items'] as $itemId => $data) {
                    getDB()->query(
                        "UPDATE change_order_items SET quantity_processed = ? WHERE change_order_id = ? AND item_id = ? AND type = 'add'",
                        [$data['scanned'], $sessionId, $itemId]
                    );
                }
                getDB()->query(
                    "UPDATE change_orders SET is_partial = 1, partial_saved_at = NOW() WHERE id = ?",
                    [$sessionId]
                );
            }
            
            // Don't clear session for draft - keep it alive for continued picking
            // unset($_SESSION['pick_session']); // REMOVED - draft should keep session
            ob_end_clean(); // Clear any buffered output
            echo json_encode(['success' => true, 'message' => 'Draft saved successfully']);
            exit;
        }
        
        if ($_POST['action'] === 'complete_pick') {
            $sessionType = $_SESSION['pick_session']['type'];
            $sessionId = $_SESSION['pick_session']['id'];
            
            // Get signature data if production audio is completing
            $signatureData = $_POST['signature_data'] ?? null;
            $adminFirstName = $_POST['admin_first_name'] ?? null;
            $adminLastName = $_POST['admin_last_name'] ?? null;
            
            // Check if signature is required
            $currentUser = getCurrentUser();
            $requiresSignature = requiresSignature($currentUser['id']);
            
            if ($requiresSignature && (!$signatureData || !$adminFirstName || !$adminLastName)) {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Admin signature is required to complete this operation']);
                exit;
            }
            
            if ($sessionType === 'pullsheet') {
                foreach ($_SESSION['pick_session']['items'] as $itemId => $data) {
                    getDB()->query(
                        "UPDATE pullsheet_items SET quantity_picked = ? WHERE pullsheet_id = ? AND item_id = ?",
                        [$data['scanned'], $sessionId, $itemId]
                    );
                    getDB()->query(
                        "UPDATE item_allocations SET status = 'checked_out' WHERE pullsheet_id = ? AND item_id = ?",
                        [$sessionId, $itemId]
                    );
                }
                getDB()->query(
                    "UPDATE pullsheets SET status = 'picked', picked_at = NOW(), picked_by = ?, is_partial = 0, partial_saved_at = NULL WHERE id = ?",
                    [$_SESSION['pick_session']['picker_name'], $sessionId]
                );
                
                // Store signature if provided
                if ($signatureData && $adminFirstName && $adminLastName) {
                    getDB()->query(
                        "INSERT INTO signatures (user_id, pullsheet_id, signature_data, first_name, last_name) VALUES (?, ?, ?, ?, ?)",
                        [$currentUser['id'], $sessionId, $signatureData, $adminFirstName, $adminLastName]
                    );
                }
            } elseif ($sessionType === 'change_order') {
                foreach ($_SESSION['pick_session']['items'] as $itemId => $data) {
                    getDB()->query(
                        "UPDATE change_order_items SET quantity_processed = ? WHERE change_order_id = ? AND item_id = ? AND type = 'add'",
                        [$data['scanned'], $sessionId, $itemId]
                    );
                }
                getDB()->query(
                    "UPDATE change_orders SET status = 'processed', processed_at = NOW(), processed_by = ?, is_partial = 0, partial_saved_at = NULL WHERE id = ?",
                    [$_SESSION['pick_session']['picker_name'], $sessionId]
                );
                
                // Store signature if provided
                if ($signatureData && $adminFirstName && $adminLastName) {
                    getDB()->query(
                        "INSERT INTO signatures (user_id, change_order_id, signature_data, first_name, last_name) VALUES (?, ?, ?, ?, ?)",
                        [$currentUser['id'], $sessionId, $signatureData, $adminFirstName, $adminLastName]
                    );
                }
            }
            
            unset($_SESSION['pick_session']);
            ob_end_clean(); // Clear any buffered output
            echo json_encode(['success' => true]);
            exit;
        }
    } catch (Exception $e) {
        // Log the error for debugging
        error_log("Pick mode error in action handler: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
        ob_end_clean(); // Clear any buffered output
        echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
        exit;
    } catch (Throwable $t) {
        // Catch any other errors (PHP 7+)
        error_log("Pick mode fatal error: " . $t->getMessage() . " in " . $t->getFile() . " on line " . $t->getLine());
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'A system error occurred. Please contact support.']);
        exit;
    }
}

// For non-AJAX requests, check permission normally
// Only check permission for non-AJAX page loads (AJAX has its own check inside)
if (!($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax']))) {
    requirePermission('operations');
}

// Flush output buffer for HTML pages
if (ob_get_level()) {
    ob_end_flush();
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pick Mode - Sound Shop Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta19/dist/css/tabler.min.css" rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" rel="stylesheet"/>
<?php $currentUser = getCurrentUser(); ?>
    <script>
        // Apply saved theme immediately to prevent flash
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-bs-theme', savedTheme);
    </script>
    <style>
        :root {
            --modern-radius: 2px;
        }
        
        .fullscreen-container {
            min-height: 100vh;
            background: var(--tblr-body-bg);
            padding: 2rem;
        }
        
        .item-card {
            transition: all 0.3s ease;
            border-radius: var(--modern-radius);
        }
        
        .item-card-incomplete {
            border-left: 4px solid #d63939;
        }
        
        .item-card-complete {
            border-left: 4px solid #2fb344;
        }
        
        .item-card-overage {
            border-left: 4px solid #f59f00;
        }
        
        .scan-input {
            font-size: 1.3rem;
            padding: 1rem;
            border-radius: var(--modern-radius);
        }
        
        .item-count {
            font-size: 2.5rem;
            font-weight: bold;
        }
        
        .card, .btn, .form-control, .alert {
            border-radius: var(--modern-radius);
        }
    </style>
</head>
<body>
    <audio id="successSound"><source src="assets/sounds/success.mp3" type="audio/mpeg"></audio>
    <audio id="errorSound"><source src="assets/sounds/error.mp3" type="audio/mpeg"></audio>
    
    <div class="fullscreen-container">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="mb-0">
                    <i class="ti ti-scan"></i> Pick Mode
                    <?php if ($pickSession): ?>
                        <small class="text-muted">- <?php echo htmlspecialchars($pickSession['show_name']); ?></small>
                    <?php endif; ?>
                </h1>
                <a href="index" class="btn btn-secondary">
                    <i class="ti ti-x"></i> Exit
                </a>
            </div>
            
            <?php if (!$pickSession): ?>
                <!-- Start Pick Session -->
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Start Picking Session</h3>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Scan Pullsheet or Change Order Barcode</label>
                                    <input type="text" class="form-control scan-input" id="pullsheetBarcode" 
                                           placeholder="Scan barcode..." autofocus>
                                </div>
                                
                                <button class="btn btn-primary w-100" id="startBtn">
                                    <i class="ti ti-play"></i> Start Pick
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Active Pick Session -->
                <?php if ($pickSession['is_resuming_draft']): ?>
                    <div class="alert alert-info alert-dismissible fade show mb-4">
                        <i class="ti ti-clock"></i> 
                        <strong>Resuming Draft:</strong> Partial pick from <?php echo date('M j, Y g:i A', strtotime($pickSession['draft_saved_at'])); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <div class="mb-4">
                    <input type="text" class="form-control scan-input" id="itemScan" 
                           placeholder="Scan item barcode..." autofocus>
                </div>
                
                <div class="row g-3 mb-4">
                    <?php foreach ($pickSession['items'] as $itemId => $item): 
                        $status = $item['scanned'] < $item['needed'] ? 'incomplete' : 
                                 ($item['scanned'] == $item['needed'] ? 'complete' : 'overage');
                    ?>
                        <div class="col-md-4">
                            <div class="card item-card item-card-<?php echo $status; ?>" data-item-id="<?php echo $itemId; ?>">
                                <div class="card-body">
                                    <h4 class="card-title"><?php echo htmlspecialchars($item['name']); ?></h4>
                                    <div class="item-count mb-2">
                                        <span class="scanned-count"><?php echo $item['scanned']; ?></span>
                                        <span class="text-muted">/ <?php echo $item['needed']; ?></span>
                                    </div>
                                    <div class="text-muted mb-2">
                                        <small>Barcode: <code><?php echo htmlspecialchars($item['barcode']); ?></code></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="row g-2">
                    <div class="col-md-6">
                        <button class="btn btn-warning w-100 btn-lg" id="saveDraftBtn">
                            <i class="ti ti-device-floppy"></i> Save as Draft
                        </button>
                    </div>
                    <div class="col-md-6">
                        <button class="btn btn-success w-100 btn-lg" id="completeBtn" disabled>
                            <i class="ti ti-check"></i> Complete Pick
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Extra Items Modal -->
    <div class="modal fade" id="extraItemsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">
                        <i class="ti ti-alert-triangle"></i> Extra Items Detected
                    </h5>
                </div>
                <div class="modal-body">
                    <p class="mb-3"><strong id="extraItemName"></strong> has <span id="extraCount"></span> extra item(s).</p>
                    <p>Please remove the extra items and confirm.</p>
                    <div class="alert alert-warning">
                        <i class="ti ti-info-circle"></i> Needed: <strong id="extraNeeded"></strong> | Scanned: <strong id="extraScanned"></strong>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="confirmRemovalBtn">
                        <i class="ti ti-check"></i> I've Removed the Extras
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Signature Modal -->
    <div class="modal fade" id="signatureModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Admin Signature Required</h5>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">An administrator must sign to authorize this pick operation.</p>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Admin First Name</label>
                            <input type="text" class="form-control" id="adminFirstName" placeholder="First Name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Admin Last Name</label>
                            <input type="text" class="form-control" id="adminLastName" placeholder="Last Name" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Signature</label>
                        <div class="signature-pad-container">
                            <canvas id="signatureCanvas" width="400" height="150" style="border: 1px solid #ccc; background: white; width: 100%; cursor: crosshair;"></canvas>
                        </div>
                        <div class="mt-2">
                            <button type="button" class="btn btn-sm btn-secondary" id="clearSignature">
                                <i class="ti ti-eraser"></i> Clear
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="window.location.href = window.location.href">Cancel</button>
                    <button type="button" class="btn btn-success" id="completeWithSignature">
                        <i class="ti ti-check"></i> Complete with Signature
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta19/dist/js/tabler.min.js"></script>
    <script>
        const playSuccess = () => document.getElementById('successSound')?.play()?.catch(() => {});
        const playError = () => document.getElementById('errorSound')?.play()?.catch(() => {});
        
        const post = (data, callback) => {
            fetch('pick_mode.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: data
            })
            .then(r => {
                if (!r.ok) {
                    throw new Error('Network response was not ok: ' + r.status);
                }
                // Get response as text first for debugging
                return r.text();
            })
            .then(text => {
                // Log raw response for debugging
                console.log('Raw response:', text.substring(0, 200));
                
                // Try to parse as JSON
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error('JSON parse error:', e);
                    console.error('Response was:', text);
                    throw new Error('Server returned invalid JSON. Response: ' + text.substring(0, 100));
                }
                
                // Check for session expiration redirect
                if (data.redirect) {
                    alert(data.message || 'Session expired. Please log in again.');
                    window.location.href = data.redirect;
                    return;
                }
                callback(data);
            })
            .catch(err => {
                console.error('Request failed:', err);
                playError();
                alert('Request failed: ' + err.message);
            });
        };
        
        <?php if (!$pickSession): ?>
            // Start pick session
            const startPick = () => {
                const barcode = document.getElementById('pullsheetBarcode').value.trim();
                
                if (!barcode) {
                    alert('Please scan a barcode');
                    return;
                }
                
                post(`ajax=1&action=start_pick&barcode=${encodeURIComponent(barcode)}`, data => {
                    if (data.success) {
                        playSuccess();
                        if (data.is_resuming_draft) {
                            // Store draft info in sessionStorage for display after reload
                            sessionStorage.setItem('showDraftNotice', 'true');
                        }
                        window.location.href = window.location.href;
                    } else {
                        playError();
                        alert(data.message || 'Not found');
                    }
                });
            };
            
            document.getElementById('startBtn').addEventListener('click', startPick);
            document.getElementById('pullsheetBarcode').addEventListener('keydown', e => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    startPick();
                }
            });
        <?php else: ?>
            // Active pick session
            const itemScan = document.getElementById('itemScan');
            let extraItemModal;
            let currentExtraItemId = null;
            
            // Initialize modal
            document.addEventListener('DOMContentLoaded', () => {
                extraItemModal = new bootstrap.Modal(document.getElementById('extraItemsModal'), {
                    backdrop: 'static',
                    keyboard: false
                });
            });
            
            itemScan.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const barcode = this.value.trim();
                    this.value = '';
                    
                    if (!barcode) return;
                    
                    post(`ajax=1&action=scan_item&barcode=${encodeURIComponent(barcode)}`, data => {
                        if (data.success) {
                            playSuccess();
                            updateItemCard(data.itemId, data.item);
                            
                            // Check if overage
                            if (data.item.scanned > data.item.needed) {
                                showExtraItemModal(data.itemId, data.item);
                            }
                        } else {
                            playError();
                            // Show alert for item not in pullsheet/change order
                            const alertDiv = document.createElement('div');
                            alertDiv.className = 'alert alert-danger alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3';
                            alertDiv.style.zIndex = '9999';
                            alertDiv.innerHTML = `
                                <i class="ti ti-x"></i> <strong>Item Not Found:</strong> ${data.message || 'This item is not in the current pick list'}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            `;
                            document.body.appendChild(alertDiv);
                            setTimeout(() => alertDiv.remove(), 5000);
                        }
                    });
                }
            });
            
            function showExtraItemModal(itemId, item) {
                currentExtraItemId = itemId;
                const extras = item.scanned - item.needed;
                
                document.getElementById('extraItemName').textContent = item.name;
                document.getElementById('extraCount').textContent = extras;
                document.getElementById('extraNeeded').textContent = item.needed;
                document.getElementById('extraScanned').textContent = item.scanned;
                
                extraItemModal.show();
                playError();
            }
            
            document.getElementById('confirmRemovalBtn').addEventListener('click', () => {
                if (currentExtraItemId) {
                    // Get the item to see how many extras
                    const card = document.querySelector(`[data-item-id="${currentExtraItemId}"]`);
                    const scannedSpan = card.querySelector('.scanned-count');
                    const needed = parseInt(card.querySelector('.text-muted').textContent.match(/\d+/)[0]);
                    const scanned = parseInt(scannedSpan.textContent);
                    const toRemove = scanned - needed;
                    
                    // Adjust to remove extras
                    post(`ajax=1&action=adjust_item&item_id=${currentExtraItemId}&adjustment=${-toRemove}`, data => {
                        if (data.success) {
                            updateItemCard(currentExtraItemId, data.item);
                            extraItemModal.hide();
                            currentExtraItemId = null;
                            playSuccess();
                        }
                    });
                }
            });
            
            function updateItemCard(itemId, item) {
                const card = document.querySelector(`[data-item-id="${itemId}"]`);
                if (!card) return;
                
                card.querySelector('.scanned-count').textContent = item.scanned;
                
                let status = 'incomplete';
                if (item.scanned === item.needed) status = 'complete';
                else if (item.scanned > item.needed) status = 'overage';
                
                card.className = `card item-card item-card-${status}`;
                
                checkAllComplete();
            }
            
            function checkAllComplete() {
                const cards = document.querySelectorAll('[data-item-id]');
                const completeCards = document.querySelectorAll('.item-card-complete');
                const overageCards = document.querySelectorAll('.item-card-overage');
                const incompleteCards = document.querySelectorAll('.item-card-incomplete');
                
                // Enable button only if all items are complete (green) and no overages or incompletes
                const allComplete = (completeCards.length === cards.length) && 
                                  overageCards.length === 0 && 
                                  incompleteCards.length === 0;
                                  
                document.getElementById('completeBtn').disabled = !allComplete;
            }
            
            document.getElementById('completeBtn').addEventListener('click', () => {
                // Check if signature is required for this user
                const requiresSignature = <?php echo requiresSignature($currentUser['id']) ? 'true' : 'false'; ?>;
                
                if (requiresSignature) {
                    // Show signature modal
                    const signatureModal = new bootstrap.Modal(document.getElementById('signatureModal'));
                    signatureModal.show();
                } else {
                    // Complete without signature
                    if (!confirm('Complete this pick? All items will be marked as checked out.')) return;
                    
                    post('ajax=1&action=complete_pick', data => {
                        if (data.success) {
                            playSuccess();
                            alert('Pick completed successfully!');
                            location.href = 'index.php';
                        } else {
                            playError();
                            alert(data.message || 'Failed to complete');
                        }
                    });
                }
            });
            
            document.getElementById('saveDraftBtn').addEventListener('click', () => {
                if (!confirm('Save current progress as draft? You can resume later.')) return;
                
                post('ajax=1&action=save_draft', data => {
                    if (data.success) {
                        playSuccess();
                        alert('Draft saved successfully!');
                        location.href = 'index.php';
                    } else {
                        playError();
                        alert(data.message || 'Failed to save draft');
                    }
                });
            });
            
            checkAllComplete();
        <?php endif; ?>
        
        // Signature Pad Implementation
        const canvas = document.getElementById('signatureCanvas');
        if (canvas) {
            const ctx = canvas.getContext('2d');
            let isDrawing = false;
            let lastX = 0;
            let lastY = 0;
            
            // Get canvas position for accurate drawing
            function getCanvasPos(e) {
                const rect = canvas.getBoundingClientRect();
                const scaleX = canvas.width / rect.width;
                const scaleY = canvas.height / rect.height;
                
                const clientX = e.touches ? e.touches[0].clientX : e.clientX;
                const clientY = e.touches ? e.touches[0].clientY : e.clientY;
                
                return {
                    x: (clientX - rect.left) * scaleX,
                    y: (clientY - rect.top) * scaleY
                };
            }
            
            function startDrawing(e) {
                e.preventDefault();
                isDrawing = true;
                const pos = getCanvasPos(e);
                lastX = pos.x;
                lastY = pos.y;
            }
            
            function draw(e) {
                if (!isDrawing) return;
                e.preventDefault();
                
                const pos = getCanvasPos(e);
                
                ctx.beginPath();
                ctx.moveTo(lastX, lastY);
                ctx.lineTo(pos.x, pos.y);
                ctx.strokeStyle = '#000';
                ctx.lineWidth = 2;
                ctx.lineCap = 'round';
                ctx.lineJoin = 'round';
                ctx.stroke();
                
                lastX = pos.x;
                lastY = pos.y;
            }
            
            function stopDrawing() {
                isDrawing = false;
            }
            
            // Mouse events
            canvas.addEventListener('mousedown', startDrawing);
            canvas.addEventListener('mousemove', draw);
            canvas.addEventListener('mouseup', stopDrawing);
            canvas.addEventListener('mouseout', stopDrawing);
            
            // Touch events for mobile
            canvas.addEventListener('touchstart', startDrawing);
            canvas.addEventListener('touchmove', draw);
            canvas.addEventListener('touchend', stopDrawing);
            
            // Clear button
            document.getElementById('clearSignature')?.addEventListener('click', () => {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                // Fill with white background
                ctx.fillStyle = '#fff';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
            });
            
            // Initialize with white background
            ctx.fillStyle = '#fff';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            
            // Complete with signature button
            document.getElementById('completeWithSignature')?.addEventListener('click', () => {
                const firstName = document.getElementById('adminFirstName').value.trim();
                const lastName = document.getElementById('adminLastName').value.trim();
                
                if (!firstName || !lastName) {
                    alert('Please enter admin first and last name');
                    return;
                }
                
                // Check if signature is drawn (canvas is not blank)
                const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                const data = imageData.data;
                let isBlank = true;
                
                for (let i = 0; i < data.length; i += 4) {
                    // Check if pixel is not white
                    if (data[i] !== 255 || data[i+1] !== 255 || data[i+2] !== 255) {
                        isBlank = false;
                        break;
                    }
                }
                
                if (isBlank) {
                    alert('Please draw your signature');
                    return;
                }
                
                // Get signature as base64 PNG
                const signatureData = canvas.toDataURL('image/png');
                
                // Send completion with signature
                post('ajax=1&action=complete_pick&signature_data=' + encodeURIComponent(signatureData) + 
                     '&admin_first_name=' + encodeURIComponent(firstName) +
                     '&admin_last_name=' + encodeURIComponent(lastName), data => {
                    if (data.success) {
                        playSuccess();
                        alert('Pick completed successfully with signature!');
                        location.href = 'index.php';
                    } else {
                        playError();
                        alert(data.message || 'Failed to complete');
                    }
                });
            });
        }
    </script>
</body>
</html>
