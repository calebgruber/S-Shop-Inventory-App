<?php
require_once '../includes/functions.php';
requirePermission('returns');

$pageTitle = 'Partial Return';
require_once '../includes/header.php';

$pullsheet = null;
$pullsheetItems = [];
// Keyed by item_barcode for fast JS lookup
$pullsheetItemsByBarcode = [];

// Step 1: Scan shop order barcode
if (isset($_GET['pullsheet_barcode'])) {
    $barcode = trim($_GET['pullsheet_barcode']);
    $pullsheet = getDB()->fetchOne(
        "SELECT p.*, s.name as show_title FROM pullsheets p
         LEFT JOIN shows s ON p.show_id = s.id
         WHERE p.barcode = ?",
        [$barcode]
    );

    if ($pullsheet) {
        $pullsheetItems = getDB()->fetchAll(
            "SELECT pi.*, i.name as item_name, i.barcode as item_barcode,
                    c.name as category, sc.name as subcategory,
                    (pi.quantity_needed - COALESCE(pi.quantity_returned, 0)) as quantity_returnable
             FROM pullsheet_items pi
             JOIN items i ON pi.item_id = i.id
             LEFT JOIN categories c ON i.category_id = c.id
             LEFT JOIN subcategories sc ON i.subcategory_id = sc.id
             WHERE pi.pullsheet_id = ?
             ORDER BY i.name",
            [$pullsheet['id']]
        );
        // Only expose items that still have something to return
        foreach ($pullsheetItems as $pi) {
            if ((int)$pi['quantity_returnable'] > 0) {
                $pullsheetItemsByBarcode[$pi['item_barcode']] = $pi;
            }
        }
        if (empty($pullsheetItemsByBarcode)) {
            setAlert('All items on this shop order have already been returned.', 'warning');
            $pullsheet = null; // Reset so we show the scan form again
        }
    } else {
        setAlert('Shop order not found: ' . htmlspecialchars($barcode), 'danger');
    }
}

// Step 2: Process the return (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_return'])) {
    try {
        $pullsheetId  = (int)($_POST['pullsheet_id'] ?? 0);
        $returnItems  = $_POST['return_items'] ?? [];

        if (!$pullsheetId || empty($returnItems)) {
            throw new Exception('No items selected for return');
        }

        $items = [];
        foreach ($returnItems as $itemId => $quantity) {
            $quantity = (int)$quantity;
            if ($quantity > 0) {
                $items[] = ['item_id' => (int)$itemId, 'quantity' => $quantity];
            }
        }

        if (empty($items)) {
            throw new Exception('No items selected for return');
        }

        $currentUser    = getCurrentUser();
        $changeOrderId  = processPartialReturn($pullsheetId, $items, $currentUser['id']);

        if ($changeOrderId) {
            $msg = 'Partial return processed. Change order #' . $changeOrderId . ' created.';
            if ($currentUser['role'] === 'production_audio') {
                $msg .= ' An admin must approve before stock is finalised.';
            }
            setAlert($msg);
            redirect('/change-orders/view?id=' . $changeOrderId);
        } else {
            throw new Exception('Failed to process partial return');
        }

    } catch (Exception $e) {
        logException($e, 'Partial return submission');
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
                    <!-- ── Step 1: scan shop-order barcode ── -->
                    <div class="alert alert-info">
                        <h5><i class="ti ti-info-circle"></i> How it works</h5>
                        <ol class="mb-0">
                            <li>Scan the <strong>shop order barcode</strong> to identify the order.</li>
                            <li>Scan each <strong>item barcode</strong> you are returning — it will appear in the list automatically.</li>
                            <li>Adjust quantities if needed, then click <strong>Process Return</strong>.</li>
                        </ol>
                        <?php $user = getCurrentUser(); if ($user['role'] === 'production_audio'): ?>
                        <div class="mt-2 text-warning fw-bold">
                            <i class="ti ti-lock"></i> As a Production Audio user, your partial returns require admin approval before stock counts update.
                        </div>
                        <?php endif; ?>
                    </div>

                    <form method="GET" class="mb-4">
                        <div class="row g-2">
                            <div class="col-md-8">
                                <label for="pullsheet_barcode" class="form-label fw-bold">Shop Order Barcode</label>
                                <input type="text"
                                       id="pullsheet_barcode"
                                       name="pullsheet_barcode"
                                       class="form-control form-control-lg"
                                       placeholder="Scan or type the shop order barcode"
                                       autofocus required>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary btn-lg w-100">
                                    <i class="ti ti-search"></i> Load Shop Order
                                </button>
                            </div>
                        </div>
                    </form>

                    <?php else: ?>
                    <!-- ── Step 2: scan items ── -->
                    <div class="alert alert-success mb-3">
                        <h5 class="mb-1"><i class="ti ti-check-circle"></i> Shop Order Loaded</h5>
                        <p class="mb-0">
                            <strong>Show:</strong> <?php echo htmlspecialchars($pullsheet['show_title'] ?? 'N/A'); ?>&nbsp;&nbsp;
                            <strong>Barcode:</strong> <?php echo htmlspecialchars($pullsheet['barcode']); ?>&nbsp;&nbsp;
                            <strong><?php echo count($pullsheetItemsByBarcode); ?> item type(s) returnable</strong>
                            <span class="text-muted small ms-2">(already fully returned items are excluded)</span>
                        </p>
                    </div>

                    <?php if (empty($pullsheetItems)): ?>
                        <div class="alert alert-warning"><i class="ti ti-alert-circle"></i> This shop order has no items.</div>
                        <a href="/operations/partial-return" class="btn btn-secondary"><i class="ti ti-arrow-left"></i> Start Over</a>
                    <?php else: ?>

                    <!-- Item barcode scanner -->
                    <div class="card mb-3 border-primary">
                        <div class="card-body py-2">
                            <div class="row g-2 align-items-center">
                                <div class="col">
                                    <label for="itemScanInput" class="form-label mb-0 fw-bold">
                                        <i class="ti ti-scan"></i> Scan Item Barcode to Add to Return
                                    </label>
                                    <input type="text"
                                           id="itemScanInput"
                                           class="form-control form-control-lg mt-1"
                                           placeholder="Scan item barcode…"
                                           autofocus autocomplete="off">
                                </div>
                            </div>
                            <div id="scanFeedback" class="mt-2" style="min-height:1.5em"></div>
                        </div>
                    </div>

                    <!-- Items being returned (dynamically built) -->
                    <div id="returnTableWrapper" class="table-responsive mb-3" style="display:none">
                        <table class="table table-hover table-vcenter" id="returnTable">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Barcode</th>
                                    <th style="width:130px">Qty to Return</th>
                                    <th style="width:80px">Max</th>
                                    <th style="width:60px"></th>
                                </tr>
                            </thead>
                            <tbody id="returnTableBody"></tbody>
                        </table>
                    </div>

                    <!-- Hidden POST form — populated by JS -->
                    <form method="POST" id="returnForm">
                        <input type="hidden" name="pullsheet_id" value="<?php echo $pullsheet['id']; ?>">
                        <div id="hiddenReturnInputs"></div>

                        <div class="d-flex justify-content-between align-items-center">
                            <a href="/operations/partial-return" class="btn btn-secondary">
                                <i class="ti ti-arrow-left"></i> Start Over
                            </a>
                            <div class="d-flex align-items-center gap-3">
                                <span>Items to return: <strong id="returnCount">0</strong></span>
                                <button type="submit" name="process_return" class="btn btn-success btn-lg" id="submitBtn" disabled>
                                    <i class="ti ti-check"></i> Process Return
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Pullsheet item data for JS lookup (keyed by barcode) -->
                    <script>
                    const PULLSHEET_ITEMS = <?php echo json_encode($pullsheetItemsByBarcode, JSON_HEX_TAG); ?>;
                    // returnList: barcode -> { item_id, name, barcode, qty, max_qty }
                    const returnList = {};

                    const scanInput    = document.getElementById('itemScanInput');
                    const feedback     = document.getElementById('scanFeedback');
                    const tableWrapper = document.getElementById('returnTableWrapper');
                    const tableBody    = document.getElementById('returnTableBody');
                    const hiddenInputs = document.getElementById('hiddenReturnInputs');
                    const submitBtn    = document.getElementById('submitBtn');
                    const returnCount  = document.getElementById('returnCount');

                    function showFeedback(msg, type) {
                        // Use textContent to avoid XSS from any user-derived msg content
                        const span = document.createElement('span');
                        span.className = 'badge bg-' + escHtml(type) + ' fs-6';
                        span.textContent = msg;
                        feedback.innerHTML = '';
                        feedback.appendChild(span);
                        setTimeout(() => { feedback.innerHTML = ''; }, 3000);
                    }

                    function renderTable() {
                        tableBody.innerHTML = '';
                        hiddenInputs.innerHTML = '';
                        const keys = Object.keys(returnList);

                        keys.forEach(bc => {
                            const entry = returnList[bc];
                            // Cast numeric values to integers for safety
                            const qty    = parseInt(entry.qty, 10) || 1;
                            const maxQty = parseInt(entry.max_qty, 10) || 1;
                            const tr = document.createElement('tr');
                            tr.innerHTML =
                                '<td>' + escHtml(entry.name) + '</td>' +
                                '<td><code>' + escHtml(entry.barcode) + '</code></td>' +
                                '<td><input type="number" class="form-control qty-input" ' +
                                     'data-barcode="' + escHtml(bc) + '" ' +
                                     'value="' + qty + '" min="1" max="' + maxQty + '"></td>' +
                                '<td><span class="badge bg-info">' + maxQty + '</span></td>' +
                                '<td><button type="button" class="btn btn-sm btn-danger remove-btn" data-barcode="' + escHtml(bc) + '">' +
                                     '<i class="ti ti-trash"></i></button></td>';
                            tableBody.appendChild(tr);

                            // Hidden input for form submission
                            const hidden = document.createElement('input');
                            hidden.type  = 'hidden';
                            hidden.name  = 'return_items[' + entry.item_id + ']';
                            hidden.value = qty;
                            hiddenInputs.appendChild(hidden);
                        });

                        tableWrapper.style.display = keys.length ? '' : 'none';
                        returnCount.textContent = keys.length;
                        submitBtn.disabled = keys.length === 0;

                        // Wire up qty change
                        tableBody.querySelectorAll('.qty-input').forEach(inp => {
                            inp.addEventListener('change', function() {
                                const bc = this.dataset.barcode;
                                let v = parseInt(this.value) || 1;
                                v = Math.max(1, Math.min(v, returnList[bc].max_qty));
                                this.value = v;
                                returnList[bc].qty = v;
                                renderTable();
                            });
                        });
                        // Wire up remove
                        tableBody.querySelectorAll('.remove-btn').forEach(btn => {
                            btn.addEventListener('click', function() {
                                delete returnList[this.dataset.barcode];
                                renderTable();
                            });
                        });
                    }

                    function escHtml(str) {
                        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
                    }

                    function handleScan(barcode) {
                        barcode = barcode.trim();
                        if (!barcode) return;

                        const pi = PULLSHEET_ITEMS[barcode];
                        if (!pi) {
                            showFeedback('Item not found on this shop order: ' + escHtml(barcode), 'danger');
                            return;
                        }

                        if (returnList[barcode]) {
                            // Already in list — increment
                            if (returnList[barcode].qty < returnList[barcode].max_qty) {
                                returnList[barcode].qty++;
                                showFeedback('+1 ' + pi.item_name + ' (' + returnList[barcode].qty + '/' + returnList[barcode].max_qty + ')', 'success');
                            } else {
                                showFeedback('Max quantity already reached for ' + pi.item_name, 'warning');
                            }
                        } else {
                            returnList[barcode] = {
                                item_id:  pi.item_id,
                                name:     pi.item_name,
                                barcode:  pi.item_barcode,
                                qty:      1,
                                max_qty:  pi.quantity_returnable
                            };
                            showFeedback('Added: ' + pi.item_name, 'success');
                        }
                        renderTable();
                    }

                    // Auto-submit on Enter / barcode scanner CR
                    scanInput.addEventListener('keydown', function(e) {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            handleScan(this.value);
                            this.value = '';
                        }
                    });
                    // Also trigger on paste (some scanners emit paste instead of keydown)
                    scanInput.addEventListener('change', function() {
                        if (this.value.trim()) {
                            handleScan(this.value);
                            this.value = '';
                        }
                    });
                    </script>

                    <?php endif; ?>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
