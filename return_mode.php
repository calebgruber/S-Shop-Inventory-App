<?php
session_start();
require_once __DIR__ . '/includes/functions.php';

$returnSession = $_SESSION['return_session'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    try {
        if ($_POST['action'] === 'start_return') {
            $barcode = trim($_POST['barcode']);
            $returnerName = trim($_POST['returner_name']);
            
            // Try pullsheet first
            $pullsheet = getPullsheetByBarcode($barcode);
            if ($pullsheet && $pullsheet['status'] === 'picked') {
                $_SESSION['return_session'] = [
                    'type' => 'pullsheet',
                    'id' => $pullsheet['id'],
                    'returner_name' => $returnerName,
                    'show_name' => $pullsheet['show_name'],
                    'items' => [],
                    'is_resuming_draft' => $pullsheet['is_partial'] ? true : false,
                    'draft_saved_at' => $pullsheet['partial_saved_at']
                ];
                
                foreach (getPullsheetItems($pullsheet['id']) as $item) {
                    $_SESSION['return_session']['items'][$item['item_id']] = [
                        'name' => $item['item_name'],
                        'barcode' => $item['item_barcode'],
                        'needed' => $item['quantity_picked'],
                        'scanned' => 0
                    ];
                }
                
                echo json_encode([
                    'success' => true,
                    'is_resuming_draft' => $pullsheet['is_partial'] ? true : false,
                    'draft_saved_at' => $pullsheet['partial_saved_at']
                ]);
                exit;
            }
            
            // Try change order for return
            $changeOrder = getChangeOrderByBarcode($barcode);
            if ($changeOrder && ($changeOrder['status'] === 'processed' || $changeOrder['status'] === 'finalized')) {
                // Check if this change order has items to remove
                $itemsToRemove = getDB()->fetchAll(
                    "SELECT * FROM change_order_items WHERE change_order_id = ? AND type = 'remove'",
                    [$changeOrder['id']]
                );
                
                if (empty($itemsToRemove)) {
                    echo json_encode(['success' => false, 'message' => 'This change order has no items to return']);
                    exit;
                }
                
                $_SESSION['return_session'] = [
                    'type' => 'change_order',
                    'id' => $changeOrder['id'],
                    'returner_name' => $returnerName,
                    'show_name' => $changeOrder['show_name'],
                    'items' => [],
                    'is_resuming_draft' => $changeOrder['is_partial'] ? true : false,
                    'draft_saved_at' => $changeOrder['partial_saved_at']
                ];
                
                foreach (getChangeOrderItems($changeOrder['id']) as $item) {
                    if ($item['type'] === 'remove') {
                        $_SESSION['return_session']['items'][$item['item_id']] = [
                            'name' => $item['item_name'],
                            'barcode' => $item['item_barcode'],
                            'needed' => abs($item['quantity_change']),
                            'scanned' => 0
                        ];
                    }
                }
                
                echo json_encode([
                    'success' => true,
                    'is_resuming_draft' => $changeOrder['is_partial'] ? true : false,
                    'draft_saved_at' => $changeOrder['partial_saved_at']
                ]);
                exit;
            }
            
            echo json_encode(['success' => false, 'message' => 'Pullsheet or change order not found or not ready for return']);
            exit;
        }
        
        if ($_POST['action'] === 'scan_item') {
            $barcode = trim($_POST['barcode']);
            $item = getItemByBarcode($barcode);
            
            if ($item && isset($_SESSION['return_session']['items'][$item['id']])) {
                $_SESSION['return_session']['items'][$item['id']]['scanned']++;
                echo json_encode([
                    'success' => true,
                    'item' => $_SESSION['return_session']['items'][$item['id']],
                    'itemId' => $item['id']
                ]);
                exit;
            }
            
            echo json_encode(['success' => false, 'message' => 'Item not in this return list']);
            exit;
        }
        
        if ($_POST['action'] === 'adjust_item') {
            $itemId = (int)$_POST['item_id'];
            $adjustment = (int)$_POST['adjustment'];
            
            if (isset($_SESSION['return_session']['items'][$itemId])) {
                $newCount = $_SESSION['return_session']['items'][$itemId]['scanned'] + $adjustment;
                if ($newCount >= 0) {
                    $_SESSION['return_session']['items'][$itemId]['scanned'] = $newCount;
                    echo json_encode([
                        'success' => true,
                        'item' => $_SESSION['return_session']['items'][$itemId]
                    ]);
                    exit;
                }
            }
            
            echo json_encode(['success' => false]);
            exit;
        }
        
        if ($_POST['action'] === 'save_draft') {
            $sessionType = $_SESSION['return_session']['type'];
            $sessionId = $_SESSION['return_session']['id'];
            
            if ($sessionType === 'pullsheet') {
                // Note: For returns, we save the "to be returned" count
                // The logic here depends on the return workflow
                // Assuming we're tracking partial returns similarly to picks
                getDB()->query(
                    "UPDATE pullsheets SET is_partial = 1, partial_saved_at = NOW() WHERE id = ?",
                    [$sessionId]
                );
            } elseif ($sessionType === 'change_order') {
                getDB()->query(
                    "UPDATE change_orders SET is_partial = 1, partial_saved_at = NOW() WHERE id = ?",
                    [$sessionId]
                );
            }
            
            unset($_SESSION['return_session']);
            echo json_encode(['success' => true]);
            exit;
        }
        
        if ($_POST['action'] === 'complete_return') {
            $sessionType = $_SESSION['return_session']['type'];
            $sessionId = $_SESSION['return_session']['id'];
            
            if ($sessionType === 'pullsheet') {
                foreach ($_SESSION['return_session']['items'] as $itemId => $data) {
                    // Return items to stock
                    updateItemStock($itemId, $data['scanned']);
                    
                    // Remove allocation
                    getDB()->query(
                        "DELETE FROM item_allocations WHERE pullsheet_id = ? AND item_id = ?",
                        [$sessionId, $itemId]
                    );
                }
                
                getDB()->query(
                    "UPDATE pullsheets SET status = 'completed', is_partial = 0, partial_saved_at = NULL WHERE id = ?",
                    [$sessionId]
                );
            } elseif ($sessionType === 'change_order') {
                foreach ($_SESSION['return_session']['items'] as $itemId => $data) {
                    // Return items to stock
                    updateItemStock($itemId, $data['scanned']);
                }
                
                getDB()->query(
                    "UPDATE change_orders SET status = 'completed', is_partial = 0, partial_saved_at = NULL WHERE id = ?",
                    [$sessionId]
                );
            }
            
            unset($_SESSION['return_session']);
            echo json_encode(['success' => true]);
            exit;
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Return Mode - Sound Shop Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta19/dist/css/tabler.min.css" rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" rel="stylesheet"/>
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
                    <i class="ti ti-arrow-back-up"></i> Return Mode
                    <?php if ($returnSession): ?>
                        <small class="text-muted">- <?php echo htmlspecialchars($returnSession['show_name']); ?></small>
                    <?php endif; ?>
                </h1>
                <a href="index.php" class="btn btn-secondary">
                    <i class="ti ti-x"></i> Exit
                </a>
            </div>
            
            <?php if (!$returnSession): ?>
                <!-- Start Return Session -->
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Start Return Session</h3>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label">Your Name</label>
                                    <input type="text" class="form-control" id="returnerName" placeholder="Enter your name" autofocus>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Scan Pullsheet or Change Order Barcode</label>
                                    <input type="text" class="form-control scan-input" id="pullsheetBarcode" 
                                           placeholder="Scan barcode...">
                                </div>
                                
                                <button class="btn btn-primary w-100" id="startBtn">
                                    <i class="ti ti-play"></i> Start Return
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Active Return Session -->
                <?php if ($returnSession['is_resuming_draft']): ?>
                    <div class="alert alert-info alert-dismissible fade show mb-4">
                        <i class="ti ti-clock"></i> 
                        <strong>Resuming Draft:</strong> Partial return from <?php echo date('M j, Y g:i A', strtotime($returnSession['draft_saved_at'])); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <div class="mb-4">
                    <input type="text" class="form-control scan-input" id="itemScan" 
                           placeholder="Scan item barcode to return..." autofocus>
                </div>
                
                <div class="row g-3 mb-4">
                    <?php foreach ($returnSession['items'] as $itemId => $item): 
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
                            <i class="ti ti-check"></i> Complete Return
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
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta19/dist/js/tabler.min.js"></script>
    <script>
        const playSuccess = () => document.getElementById('successSound')?.play()?.catch(() => {});
        const playError = () => document.getElementById('errorSound')?.play()?.catch(() => {});
        
        const post = (data, callback) => {
            fetch('return_mode.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: data
            })
            .then(r => r.json())
            .then(callback)
            .catch(err => {
                console.error('Request failed:', err);
                playError();
                alert('Request failed');
            });
        };
        
        <?php if (!$returnSession): ?>
            // Start return session
            const startReturn = () => {
                const name = document.getElementById('returnerName').value.trim();
                const barcode = document.getElementById('pullsheetBarcode').value.trim();
                
                if (!name || !barcode) {
                    alert('Please enter both name and barcode');
                    return;
                }
                
                post(`ajax=1&action=start_return&returner_name=${encodeURIComponent(name)}&barcode=${encodeURIComponent(barcode)}`, data => {
                    if (data.success) {
                        playSuccess();
                        if (data.is_resuming_draft) {
                            // Store draft info in sessionStorage for display after reload
                            sessionStorage.setItem('showDraftNotice', 'true');
                        }
                        location.reload();
                    } else {
                        playError();
                        alert(data.message || 'Not found');
                    }
                });
            };
            
            document.getElementById('startBtn').addEventListener('click', startReturn);
            document.getElementById('pullsheetBarcode').addEventListener('keydown', e => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    startReturn();
                }
            });
        <?php else: ?>
            // Active return session
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
                                <i class="ti ti-x"></i> <strong>Item Not Found:</strong> ${data.message || 'This item is not in the current return list'}
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
                if (!confirm('Complete this return? All items will be returned to shop stock.')) return;
                
                post('ajax=1&action=complete_return', data => {
                    if (data.success) {
                        playSuccess();
                        alert('Return completed successfully!');
                        location.href = 'index.php';
                    } else {
                        playError();
                        alert(data.message || 'Failed to complete');
                    }
                });
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
    </script>
</body>
</html>
