<?php
require_once 'config.php';
require_once 'db.php';
require_once 'functions.php';

$db = getDB();

$pullSheetId = isset($_GET['pull_sheet_id']) ? (int)$_GET['pull_sheet_id'] : 0;
$changeOrderId = isset($_GET['change_order_id']) ? (int)$_GET['change_order_id'] : 0;
$barcode = isset($_GET['barcode']) ? trim($_GET['barcode']) : '';

// If barcode is provided, look up the document
if (!empty($barcode) && $pullSheetId === 0 && $changeOrderId === 0) {
    // Try to find pull sheet
    $stmt = $db->prepare("SELECT id FROM pull_sheets WHERE barcode = ?");
    $stmt->bind_param("s", $barcode);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $pullSheetId = $row['id'];
    } else {
        // Try to find change order
        $stmt = $db->prepare("SELECT id FROM change_orders WHERE barcode = ?");
        $stmt->bind_param("s", $barcode);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $changeOrderId = $row['id'];
        }
    }
}

$document = null;
$items = [];
$documentType = '';

if ($pullSheetId > 0) {
    $stmt = $db->prepare("SELECT ps.*, s.name as show_name 
                          FROM pull_sheets ps 
                          INNER JOIN shows s ON ps.show_id = s.id 
                          WHERE ps.id = ?");
    $stmt->bind_param("i", $pullSheetId);
    $stmt->execute();
    $document = $stmt->get_result()->fetch_assoc();
    $documentType = 'pull_sheet';
    
    if ($document) {
        $stmt = $db->prepare("SELECT psi.*, i.name, i.barcode 
                              FROM pull_sheet_items psi 
                              INNER JOIN items i ON psi.item_id = i.id 
                              WHERE psi.pull_sheet_id = ?");
        $stmt->bind_param("i", $pullSheetId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
    }
} elseif ($changeOrderId > 0) {
    $stmt = $db->prepare("SELECT co.*, s.name as show_name 
                          FROM change_orders co 
                          INNER JOIN shows s ON co.show_id = s.id 
                          WHERE co.id = ?");
    $stmt->bind_param("i", $changeOrderId);
    $stmt->execute();
    $document = $stmt->get_result()->fetch_assoc();
    $documentType = 'change_order';
    
    if ($document) {
        $stmt = $db->prepare("SELECT coi.*, i.name, i.barcode 
                              FROM change_order_items coi 
                              INNER JOIN items i ON coi.item_id = i.id 
                              WHERE coi.change_order_id = ? AND coi.action_type = 'add'");
        $stmt->bind_param("i", $changeOrderId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
    }
}

// If no document found, show search interface (fullscreen)
if (!$document) {
    $pageTitle = 'Pick Mode - ' . APP_NAME;
    $hideHeader = true; // Hide the regular header for fullscreen mode
    
    ob_start();
    ?>
    <div id="pick-start-container" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 9999; background: var(--tblr-body-bg); overflow-y: auto;">
        <div class="container-xl">
            <div class="d-flex justify-content-between align-items-center mb-4 pt-4">
                <div>
                    <h1 class="m-0">Pick Mode</h1>
                    <div class="text-muted">Scan pull sheet/change order barcode to begin</div>
                </div>
                <div>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="ti ti-x"></i> Exit
                    </a>
                </div>
            </div>
            
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-body text-center py-5">
                            <div class="mb-4">
                                <?php echo PICK_MODE_INSTRUCTION_SVG; ?>
                            </div>
                            <h3 class="mb-3">Scan PDF Barcode to Start</h3>
                            <p class="text-muted">Scan the barcode from the pull sheet or change order PDF document</p>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-body">
                            <form method="GET" action="pick_mode.php" id="barcode-form">
                                <div class="mb-3">
                                    <label class="form-label">Pull Sheet / Change Order Barcode</label>
                                    <input type="text" name="barcode" id="barcode-input" class="form-control form-control-lg auto-focus" 
                                           placeholder="Scan barcode here..." required autofocus>
                                </div>
                                <button type="submit" class="btn btn-success btn-lg w-100">
                                    <i class="ti ti-scan me-2"></i>Start Picking
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="card mt-3">
                        <div class="card-header">
                            <h4 class="card-title">Recent Pull Sheets</h4>
                        </div>
                        <div class="card-body">
                            <div class="list-group">
                                <?php
                                $recentDocs = $db->query("
                                    SELECT ps.id, ps.barcode, ps.status, s.name as show_name, 'pull_sheet' as doc_type
                                    FROM pull_sheets ps
                                    JOIN shows s ON ps.show_id = s.id
                                    WHERE ps.status IN ('finalized', 'picked')
                                    ORDER BY ps.created_at DESC
                                    LIMIT 5
                                ")->fetch_all(MYSQLI_ASSOC);
                                
                                if (count($recentDocs) > 0):
                                    foreach ($recentDocs as $doc):
                                ?>
                                <a href="pick_mode.php?pull_sheet_id=<?php echo $doc['id']; ?>" class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h5 class="mb-1"><?php echo sanitize($doc['show_name']); ?></h5>
                                        <small class="badge bg-info"><?php echo ucfirst($doc['status']); ?></small>
                                    </div>
                                    <p class="mb-1"><small><?php echo sanitize($doc['barcode']); ?></small></p>
                                </a>
                                <?php 
                                    endforeach;
                                else:
                                ?>
                                <div class="text-muted text-center py-3">No recent pull sheets</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        // Auto-focus the barcode input
        document.addEventListener('DOMContentLoaded', function() {
            const barcodeInput = document.getElementById('barcode-input');
            if (barcodeInput) {
                barcodeInput.focus();
                barcodeInput.select();
            }
        });
    </script>
    <?php
    $content = ob_get_clean();
    include 'layout.php';
    exit;
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'scan_item') {
        $barcode = trim($_POST['barcode'] ?? '');
        $pickerName = trim($_POST['picker_name'] ?? '');
        
        // Find the item
        $stmt = $db->prepare("SELECT * FROM items WHERE barcode = ?");
        $stmt->bind_param("s", $barcode);
        $stmt->execute();
        $item = $stmt->get_result()->fetch_assoc();
        
        if (!$item) {
            echo json_encode(['success' => false, 'message' => 'Item not found']);
            exit;
        }
        
        // Check if item is in the document
        $found = false;
        $quantityNeeded = 0;
        $quantityPicked = 0;
        
        if ($documentType === 'pull_sheet') {
            $stmt = $db->prepare("SELECT * FROM pull_sheet_items 
                                  WHERE pull_sheet_id = ? AND item_id = ?");
            $stmt->bind_param("ii", $pullSheetId, $item['id']);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($psItem = $result->fetch_assoc()) {
                $found = true;
                $quantityNeeded = $psItem['quantity_needed'];
                $quantityPicked = $psItem['quantity_picked'];
            }
        } else {
            $stmt = $db->prepare("SELECT * FROM change_order_items 
                                  WHERE change_order_id = ? AND item_id = ? AND action_type = 'add'");
            $stmt->bind_param("ii", $changeOrderId, $item['id']);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($coItem = $result->fetch_assoc()) {
                $found = true;
                $quantityNeeded = $coItem['quantity'];
                $quantityPicked = $coItem['quantity_processed'];
            }
        }
        
        if (!$found) {
            echo json_encode(['success' => false, 'message' => 'Item not in this document']);
            exit;
        }
        
        if ($quantityPicked >= $quantityNeeded) {
            echo json_encode(['success' => false, 'message' => 'Item already fully picked']);
            exit;
        }
        
        // Update quantity
        $newQuantity = $quantityPicked + 1;
        
        if ($documentType === 'pull_sheet') {
            $stmt = $db->prepare("UPDATE pull_sheet_items 
                                  SET quantity_picked = ? 
                                  WHERE pull_sheet_id = ? AND item_id = ?");
            $stmt->bind_param("iii", $newQuantity, $pullSheetId, $item['id']);
            $stmt->execute();
            
            // Log transaction
            logTransaction($item['id'], 'pick', 1, $pickerName, $pullSheetId, null, $document['show_id']);
        } else {
            $stmt = $db->prepare("UPDATE change_order_items 
                                  SET quantity_processed = ? 
                                  WHERE change_order_id = ? AND item_id = ?");
            $stmt->bind_param("iii", $newQuantity, $changeOrderId, $item['id']);
            $stmt->execute();
            
            // Log transaction
            logTransaction($item['id'], 'pick', 1, $pickerName, null, $changeOrderId, $document['show_id']);
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Item picked successfully',
            'item' => $item,
            'picked' => $newQuantity,
            'needed' => $quantityNeeded
        ]);
        exit;
    }
    
    if ($_POST['action'] === 'get_items_status') {
        $itemsStatus = [];
        
        if ($documentType === 'pull_sheet') {
            $stmt = $db->prepare("SELECT psi.*, i.name, i.barcode 
                                  FROM pull_sheet_items psi 
                                  INNER JOIN items i ON psi.item_id = i.id 
                                  WHERE psi.pull_sheet_id = ?");
            $stmt->bind_param("i", $pullSheetId);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $itemsStatus[] = [
                    'id' => $row['item_id'],
                    'name' => $row['name'],
                    'barcode' => $row['barcode'],
                    'needed' => $row['quantity_needed'],
                    'picked' => $row['quantity_picked']
                ];
            }
        } else {
            $stmt = $db->prepare("SELECT coi.*, i.name, i.barcode 
                                  FROM change_order_items coi 
                                  INNER JOIN items i ON coi.item_id = i.id 
                                  WHERE coi.change_order_id = ? AND coi.action_type = 'add'");
            $stmt->bind_param("i", $changeOrderId);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $itemsStatus[] = [
                    'id' => $row['item_id'],
                    'name' => $row['name'],
                    'barcode' => $row['barcode'],
                    'needed' => $row['quantity'],
                    'picked' => $row['quantity_processed']
                ];
            }
        }
        
        echo json_encode(['success' => true, 'items' => $itemsStatus]);
        exit;
    }
}

$pageTitle = "Pick Mode - " . APP_NAME;
$hideHeader = true;

$additionalCSS = '<style>
body { background: var(--tblr-body-bg); }
.fullscreen-mode { padding: 20px; }
.pick-card { font-size: 1.2rem; margin-bottom: 15px; }
.pick-card .card-body { padding: 20px; }
.pick-card h3 { font-size: 1.5rem; margin-bottom: 10px; }
.barcode-input { font-size: 1.5rem; padding: 15px; }
</style>';

$additionalJS = <<<'JS'
<script>
let pickerName = '';
let isFullscreen = false;

document.addEventListener('DOMContentLoaded', function() {
    // Show name entry modal
    const nameModal = new bootstrap.Modal(document.getElementById('name-modal'));
    nameModal.show();
    
    // Refresh items periodically
    setInterval(refreshItems, 3000);
});

function startPicking() {
    pickerName = document.getElementById('picker-name').value.trim();
    if (!pickerName) {
        alert('Please enter your name');
        return;
    }
    
    bootstrap.Modal.getInstance(document.getElementById('name-modal')).hide();
    document.getElementById('barcode-input').focus();
}

function toggleFullscreen() {
    const container = document.getElementById('pick-container');
    if (!isFullscreen) {
        container.classList.add('fullscreen-mode');
        document.getElementById('fullscreen-btn').innerHTML = '<i class="ti ti-minimize"></i> Exit Fullscreen';
        isFullscreen = true;
    } else {
        container.classList.remove('fullscreen-mode');
        document.getElementById('fullscreen-btn').innerHTML = '<i class="ti ti-maximize"></i> Fullscreen';
        isFullscreen = false;
    }
}

function scanBarcode(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        const barcode = e.target.value.trim();
        if (barcode) {
            processBarcode(barcode);
            e.target.value = '';
        }
    }
}

function processBarcode(barcode) {
    const docType = '<?php echo $documentType; ?>';
    const docId = <?php echo $pullSheetId > 0 ? $pullSheetId : $changeOrderId; ?>;
    
    fetch('pick_mode.php?' + (docType === 'pull_sheet' ? 'pull_sheet_id=' : 'change_order_id=') + docId, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `ajax=1&action=scan_item&barcode=${encodeURIComponent(barcode)}&picker_name=${encodeURIComponent(pickerName)}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            playSuccessSound();
            showNotification('Success: ' + data.message, 'success');
            refreshItems();
        } else {
            playErrorSound();
            showNotification('Error: ' + data.message, 'danger');
        }
        document.getElementById('barcode-input').focus();
    });
}

function refreshItems() {
    const docType = '<?php echo $documentType; ?>';
    const docId = <?php echo $pullSheetId > 0 ? $pullSheetId : $changeOrderId; ?>;
    
    fetch('pick_mode.php?' + (docType === 'pull_sheet' ? 'pull_sheet_id=' : 'change_order_id=') + docId, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'ajax=1&action=get_items_status'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            renderItems(data.items);
        }
    });
}

function renderItems(items) {
    const container = document.getElementById('items-container');
    container.innerHTML = '';
    
    items.forEach(item => {
        const picked = item.picked;
        const needed = item.needed;
        const percentage = (picked / needed) * 100;
        
        let cardClass = 'item-card-red';
        if (percentage === 100) {
            cardClass = 'item-card-green';
        } else if (percentage > 0) {
            cardClass = 'item-card-yellow';
        }
        
        const div = document.createElement('div');
        div.className = 'col-md-6 col-lg-4';
        div.innerHTML = `
            <div class="card pick-card ${cardClass}">
                <div class="card-body">
                    <h3 class="card-title">${escapeHtml(item.name)}</h3>
                    <div class="text-muted mb-2">${escapeHtml(item.barcode)}</div>
                    <div class="progress mb-2">
                        <div class="progress-bar" role="progressbar" style="width: ${percentage}%"></div>
                    </div>
                    <div class="h2 mb-0">${picked} / ${needed}</div>
                </div>
            </div>
        `;
        container.appendChild(div);
    });
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} position-fixed top-0 start-50 translate-middle-x mt-3`;
    notification.style.zIndex = '10000';
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Initial render
refreshItems();
</script>
JS;

ob_start();
?>

<div id="pick-container">
    <div class="container-xl">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="m-0">Pick Mode</h1>
                <div class="text-muted">
                    <?php echo sanitize($document['show_name']); ?> - 
                    <?php echo sanitize($document['barcode']); ?>
                </div>
            </div>
            <div>
                <button id="fullscreen-btn" class="btn btn-primary me-2" onclick="toggleFullscreen()">
                    <i class="ti ti-maximize"></i> Fullscreen
                </button>
                <a href="<?php echo $documentType === 'pull_sheet' ? 'pull_sheet_view.php?id=' . $pullSheetId : 'change_order_view.php?id=' . $changeOrderId; ?>" 
                   class="btn btn-secondary">
                    <i class="ti ti-x"></i> Exit
                </a>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-body">
                <label class="form-label">Scan Item Barcode</label>
                <input type="text" id="barcode-input" class="form-control barcode-input auto-focus" 
                       placeholder="Scan barcode here" onkeypress="scanBarcode(event)">
            </div>
        </div>
        
        <div class="row" id="items-container">
            <!-- Items will be rendered here -->
        </div>
    </div>
</div>

<!-- Name Entry Modal -->
<div class="modal fade" id="name-modal" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Enter Your Name</h5>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label required">Picker Name</label>
                    <input type="text" id="picker-name" class="form-control" 
                           placeholder="Enter your name" 
                           onkeypress="if(event.key==='Enter') startPicking()">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="startPicking()">
                    Start Picking
                </button>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require 'layout.php';
?>
