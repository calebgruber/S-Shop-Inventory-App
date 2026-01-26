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
                    'items' => []
                ];
                
                foreach (getPullsheetItems($pullsheet['id']) as $item) {
                    $_SESSION['return_session']['items'][$item['item_id']] = [
                        'name' => $item['item_name'],
                        'barcode' => $item['item_barcode'],
                        'needed' => $item['quantity_picked'],
                        'scanned' => 0
                    ];
                }
                
                echo json_encode(['success' => true]);
                exit;
            }
            
            // Try change order
            $changeOrder = getChangeOrderByBarcode($barcode);
            if ($changeOrder && $changeOrder['status'] === 'processed') {
                $_SESSION['return_session'] = [
                    'type' => 'change_order',
                    'id' => $changeOrder['id'],
                    'returner_name' => $returnerName,
                    'show_name' => $changeOrder['show_name'],
                    'items' => []
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
                
                echo json_encode(['success' => true]);
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
                    "UPDATE pullsheets SET status = 'completed' WHERE id = ?",
                    [$sessionId]
                );
            } elseif ($sessionType === 'change_order') {
                foreach ($_SESSION['return_session']['items'] as $itemId => $data) {
                    // Return items to stock
                    updateItemStock($itemId, $data['scanned']);
                }
                
                getDB()->query(
                    "UPDATE change_orders SET status = 'completed' WHERE id = ?",
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Return Mode - Sound Shop Inventory</title>
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta19/dist/css/tabler.min.css" rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" rel="stylesheet"/>
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
                                    <div class="btn-group btn-group-sm w-100">
                                        <button class="btn btn-outline-danger adjust-btn" data-item-id="<?php echo $itemId; ?>" data-adj="-1">
                                            <i class="ti ti-minus"></i>
                                        </button>
                                        <button class="btn btn-outline-success adjust-btn" data-item-id="<?php echo $itemId; ?>" data-adj="1">
                                            <i class="ti ti-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <button class="btn btn-success w-100 btn-lg" id="completeBtn">
                    <i class="ti ti-check"></i> Complete Return
                </button>
            <?php endif; ?>
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
                        } else {
                            playError();
                            alert(data.message || 'Item not in list');
                        }
                    });
                }
            });
            
            // Adjust buttons
            document.querySelectorAll('.adjust-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const itemId = this.dataset.itemId;
                    const adj = this.dataset.adj;
                    
                    post(`ajax=1&action=adjust_item&item_id=${itemId}&adjustment=${adj}`, data => {
                        if (data.success) {
                            updateItemCard(itemId, data.item);
                        }
                    });
                });
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
                
                document.getElementById('completeBtn').disabled = 
                    (completeCards.length !== cards.length) || overageCards.length > 0;
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
            
            checkAllComplete();
        <?php endif; ?>
    </script>
</body>
</html>
