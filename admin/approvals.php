<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();
requireRole('admin');

$currentUser = getCurrentUser();
$db = getDB();
$signatureMode = getSetting('signature_mode', 'draw');

// ── AJAX: approve + generate pick receipt ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    ob_start();
    header('Content-Type: application/json');

    $action        = $_POST['action'] ?? '';
    $type          = $_POST['type'] ?? '';
    $id            = (int)($_POST['id'] ?? 0);
    $signatureData = $_POST['signature_data'] ?? '';

    if ($action !== 'approve') {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit;
    }

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
        $nameParts = explode(' ', $currentUser['name'] ?? '', 2);
        $firstName = $nameParts[0] ?? '';
        $lastName  = $nameParts[1] ?? '';

        if ($type === 'pullsheet') {
            $db->query(
                "UPDATE pullsheets SET approval_status = 'approved', approved_at = NOW(), approved_by = ?
                 WHERE id = ? AND approval_status = 'pending'",
                [$currentUser['id'], $id]
            );
            $db->query(
                "INSERT INTO signatures (user_id, pullsheet_id, signature_data, first_name, last_name, created_at)
                 VALUES (?, ?, ?, ?, ?, NOW())",
                [$currentUser['id'], $id, $signatureData, $firstName, $lastName]
            );

            // Generate pick receipt PDF and save to uploads
            $pdfData     = generatePickReceiptPDF($id);
            $receiptFile = null;
            if ($pdfData) {
                $receiptFile = 'pick_receipt_' . $id . '_' . time() . '.pdf';
                file_put_contents(UPLOAD_DIR . $receiptFile, $pdfData);
            }

            ob_end_clean();
            echo json_encode([
                'success'      => true,
                'message'      => 'Shop order approved successfully',
                'receipt_url'  => $receiptFile ? '/uploads/' . $receiptFile : null,
            ]);
        } else {
            $db->query(
                "UPDATE change_orders SET approval_status = 'approved', approved_at = NOW(), approved_by = ?
                 WHERE id = ? AND approval_status = 'pending'",
                [$currentUser['id'], $id]
            );
            $db->query(
                "INSERT INTO signatures (user_id, change_order_id, signature_data, first_name, last_name, created_at)
                 VALUES (?, ?, ?, ?, ?, NOW())",
                [$currentUser['id'], $id, $signatureData, $firstName, $lastName]
            );

            ob_end_clean();
            echo json_encode(['success' => true, 'message' => 'Change order approved successfully']);
        }
    } catch (Exception $e) {
        error_log("Approval error: " . $e->getMessage());
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Failed to approve: ' . $e->getMessage()]);
    }
    exit;
}

// ── Fetch pending items ─────────────────────────────────────────────────────
$pendingPullsheets = $db->fetchAll(
    "SELECT p.*, s.name as show_name, u.full_name as user_name, u.email as user_email
     FROM pullsheets p
     LEFT JOIN shows s ON p.show_id = s.id
     LEFT JOIN users u ON p.created_by = u.id
     WHERE p.approval_status = 'pending' AND p.status = 'picked'
     ORDER BY p.picked_at DESC"
);

// Attach items to each pullsheet
foreach ($pendingPullsheets as &$ps) {
    $ps['items'] = $db->fetchAll(
        "SELECT pi.*, i.name as item_name, i.barcode as item_barcode,
                c.name as category_name
         FROM pullsheet_items pi
         JOIN items i ON pi.item_id = i.id
         LEFT JOIN categories c ON i.category_id = c.id
         WHERE pi.pullsheet_id = ?
         ORDER BY c.name, i.name",
        [$ps['id']]
    );
}
unset($ps);

$pendingChangeOrders = $db->fetchAll(
    "SELECT co.*, s.name as show_name, u.full_name as user_name, u.email as user_email
     FROM change_orders co
     LEFT JOIN shows s ON co.show_id = s.id
     LEFT JOIN users u ON co.created_by = u.id
     WHERE co.requires_approval = 1 AND co.approval_status = 'pending'
     ORDER BY co.created_at DESC"
);

// Attach items to each change order
foreach ($pendingChangeOrders as &$co) {
    $co['items'] = getChangeOrderItems($co['id']);
}
unset($co);

include '../includes/header.php';
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">
                    <i class="ti ti-signature"></i> Admin Approvals
                </h2>
                <div class="text-muted mt-1">
                    Review picked items and sign off — approvals generate a final pick receipt PDF.
                    Signature mode: <strong><?php echo $signatureMode === 'type' ? 'Type name (auto-generated)' : 'Draw pad'; ?></strong>
                    <a href="/settings/" class="ms-1 small">(change in Settings)</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">

    <?php if (empty($pendingPullsheets) && empty($pendingChangeOrders)): ?>
        <div class="empty">
            <div class="empty-icon"><i class="ti ti-check icon"></i></div>
            <p class="empty-title">All caught up!</p>
            <p class="empty-subtitle text-muted">There are no operations pending your approval.</p>
        </div>
    <?php else: ?>

    <!-- ── Pending Shop Orders ─────────────────────────────────── -->
    <?php if (!empty($pendingPullsheets)): ?>
    <h3 class="mb-3"><i class="ti ti-package me-2"></i>Pending Shop Orders</h3>
    <?php foreach ($pendingPullsheets as $ps): ?>
    <div class="card mb-4" id="ps-card-<?php echo $ps['id']; ?>">
        <div class="card-header">
            <div class="row align-items-center w-100">
                <div class="col">
                    <h4 class="card-title mb-0">
                        <i class="ti ti-package me-1 text-blue"></i>
                        <?php echo htmlspecialchars($ps['show_name'] ?? 'Unknown Show'); ?>
                        <span class="badge bg-orange-lt ms-2">Pending Approval</span>
                    </h4>
                    <div class="text-muted small mt-1">
                        Barcode: <code><?php echo htmlspecialchars($ps['barcode']); ?></code> &nbsp;|&nbsp;
                        Picked by: <?php echo htmlspecialchars($ps['picked_by'] ?? $ps['user_name'] ?? '—'); ?> &nbsp;|&nbsp;
                        <?php echo $ps['picked_at'] ? date('M j, Y g:i A', strtotime($ps['picked_at'])) : 'N/A'; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <!-- Items picked -->
            <?php if (!empty($ps['items'])): ?>
            <div class="table-responsive">
                <table class="table table-sm table-vcenter mb-0">
                    <thead>
                        <tr class="bg-light">
                            <th>Item</th>
                            <th>Barcode</th>
                            <th class="text-center">Needed</th>
                            <th class="text-center">Picked</th>
                            <th class="text-center">+/−</th>
                            <th>Category</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ps['items'] as $item):
                            $needed   = (int)($item['quantity_needed'] ?? 0);
                            $picked   = (int)($item['quantity_picked'] ?? 0);
                            $variance = $picked - $needed;
                            $varClass = $variance < 0 ? 'text-danger' : ($variance > 0 ? 'text-warning' : 'text-success');
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                            <td><code><?php echo htmlspecialchars($item['item_barcode']); ?></code></td>
                            <td class="text-center"><span class="badge bg-blue-lt"><?php echo $needed; ?></span></td>
                            <td class="text-center"><span class="badge bg-green-lt"><?php echo $picked; ?></span></td>
                            <td class="text-center <?php echo $varClass; ?> fw-bold">
                                <?php echo ($variance >= 0 ? '+' : '') . $variance; ?>
                            </td>
                            <td class="text-muted small"><?php echo htmlspecialchars($item['category_name'] ?? '—'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="p-3 text-muted"><i class="ti ti-info-circle me-1"></i>No items on this shop order.</div>
            <?php endif; ?>

            <!-- Inline signature area -->
            <div class="p-3 border-top bg-light-lt">
                <div class="row g-3 align-items-start">
                    <div class="col-md-5">
                        <label class="form-label fw-bold">Admin Name</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($currentUser['name'] ?? ''); ?>" readonly>
                    </div>
                    <div class="col-md-7">
                        <label class="form-label fw-bold">
                            <?php echo $signatureMode === 'type' ? 'Type Your Name to Generate Signature' : 'Draw Your Signature'; ?>
                        </label>
                        <?php if ($signatureMode === 'type'): ?>
                        <!-- Type-name mode -->
                        <input type="text"
                               class="form-control sig-type-input"
                               placeholder="Type your full name…"
                               data-id="<?php echo $ps['id']; ?>"
                               data-type="pullsheet"
                               autocomplete="off">
                        <canvas class="sig-preview-canvas d-block mt-2 border rounded bg-white"
                                id="sig-canvas-ps-<?php echo $ps['id']; ?>"
                                width="400" height="100"
                                style="width:100%;max-width:400px;height:100px;"></canvas>
                        <small class="text-muted">A cursive signature will be generated from your name.</small>
                        <?php else: ?>
                        <!-- Draw-pad mode -->
                        <canvas id="sig-canvas-ps-<?php echo $ps['id']; ?>"
                                width="420" height="120"
                                class="sig-draw-canvas border rounded bg-white"
                                style="width:100%;max-width:420px;height:120px;cursor:crosshair;"
                                data-id="<?php echo $ps['id']; ?>"
                                data-type="pullsheet"></canvas>
                        <div class="mt-1">
                            <button type="button" class="btn btn-sm btn-secondary sig-clear-btn"
                                    data-canvas="sig-canvas-ps-<?php echo $ps['id']; ?>">
                                <i class="ti ti-eraser"></i> Clear
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="mt-3 d-flex gap-2 align-items-center">
                    <button type="button" class="btn btn-success approve-submit-btn"
                            data-id="<?php echo $ps['id']; ?>"
                            data-type="pullsheet"
                            data-canvas="sig-canvas-ps-<?php echo $ps['id']; ?>">
                        <i class="ti ti-check"></i> Approve &amp; Generate Receipt
                    </button>
                    <span class="spinner-border spinner-border-sm d-none approve-spinner"></span>
                    <span class="approve-result text-success fw-bold"></span>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

    <!-- ── Pending Change Orders ───────────────────────────────── -->
    <?php if (!empty($pendingChangeOrders)): ?>
    <h3 class="mb-3 mt-4"><i class="ti ti-arrows-exchange me-2"></i>Pending Change Orders</h3>
    <?php foreach ($pendingChangeOrders as $co): ?>
    <div class="card mb-4" id="co-card-<?php echo $co['id']; ?>">
        <div class="card-header">
            <div class="row align-items-center w-100">
                <div class="col">
                    <h4 class="card-title mb-0">
                        <i class="ti ti-arrows-exchange me-1 text-orange"></i>
                        <?php echo htmlspecialchars($co['show_name'] ?? 'Unknown Show'); ?>
                        <?php echo !empty($co['is_partial_return']) ? '<span class="badge bg-orange-lt ms-1">Partial Return</span>' : ''; ?>
                        <span class="badge bg-orange-lt ms-1">Pending Approval</span>
                    </h4>
                    <div class="text-muted small mt-1">
                        Barcode: <code><?php echo htmlspecialchars($co['barcode']); ?></code> &nbsp;|&nbsp;
                        Submitted by: <?php echo htmlspecialchars($co['user_name'] ?? '—'); ?> &nbsp;|&nbsp;
                        <?php echo date('M j, Y g:i A', strtotime($co['created_at'])); ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <!-- Change order items -->
            <?php if (!empty($co['items'])): ?>
            <div class="table-responsive">
                <table class="table table-sm table-vcenter mb-0">
                    <thead>
                        <tr class="bg-light">
                            <th>Item</th>
                            <th>Barcode</th>
                            <th class="text-center">Type</th>
                            <th class="text-center">Qty</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($co['items'] as $item):
                            $qty = $item['quantity_change'] ?? $item['quantity'] ?? 0;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                            <td><code><?php echo htmlspecialchars($item['item_barcode']); ?></code></td>
                            <td class="text-center">
                                <?php if ($item['type'] === 'add'): ?>
                                    <span class="badge bg-green-lt">Add</span>
                                <?php else: ?>
                                    <span class="badge bg-red-lt">Remove</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center"><?php echo (int)$qty; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="p-3 text-muted"><i class="ti ti-info-circle me-1"></i>No items on this change order.</div>
            <?php endif; ?>

            <!-- Inline signature area -->
            <div class="p-3 border-top bg-light-lt">
                <div class="row g-3 align-items-start">
                    <div class="col-md-5">
                        <label class="form-label fw-bold">Admin Name</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($currentUser['name'] ?? ''); ?>" readonly>
                    </div>
                    <div class="col-md-7">
                        <label class="form-label fw-bold">
                            <?php echo $signatureMode === 'type' ? 'Type Your Name to Generate Signature' : 'Draw Your Signature'; ?>
                        </label>
                        <?php if ($signatureMode === 'type'): ?>
                        <input type="text"
                               class="form-control sig-type-input"
                               placeholder="Type your full name…"
                               data-id="<?php echo $co['id']; ?>"
                               data-type="change_order"
                               autocomplete="off">
                        <canvas class="sig-preview-canvas d-block mt-2 border rounded bg-white"
                                id="sig-canvas-co-<?php echo $co['id']; ?>"
                                width="400" height="100"
                                style="width:100%;max-width:400px;height:100px;"></canvas>
                        <small class="text-muted">A cursive signature will be generated from your name.</small>
                        <?php else: ?>
                        <canvas id="sig-canvas-co-<?php echo $co['id']; ?>"
                                width="420" height="120"
                                class="sig-draw-canvas border rounded bg-white"
                                style="width:100%;max-width:420px;height:120px;cursor:crosshair;"
                                data-id="<?php echo $co['id']; ?>"
                                data-type="change_order"></canvas>
                        <div class="mt-1">
                            <button type="button" class="btn btn-sm btn-secondary sig-clear-btn"
                                    data-canvas="sig-canvas-co-<?php echo $co['id']; ?>">
                                <i class="ti ti-eraser"></i> Clear
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="mt-3 d-flex gap-2 align-items-center">
                    <button type="button" class="btn btn-success approve-submit-btn"
                            data-id="<?php echo $co['id']; ?>"
                            data-type="change_order"
                            data-canvas="sig-canvas-co-<?php echo $co['id']; ?>">
                        <i class="ti ti-check"></i> Approve
                    </button>
                    <span class="spinner-border spinner-border-sm d-none approve-spinner"></span>
                    <span class="approve-result text-success fw-bold"></span>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

    <?php endif; ?>
    </div><!-- /container-xl -->
</div><!-- /page-body -->

<script>
(function() {
    const SIG_MODE = <?php echo json_encode($signatureMode); ?>;

    // ── Draw-pad support ────────────────────────────────────────────────────
    document.querySelectorAll('.sig-draw-canvas').forEach(canvas => {
        const ctx = canvas.getContext('2d');
        ctx.fillStyle = 'white';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        let drawing = false;

        function pos(e) {
            const r = canvas.getBoundingClientRect();
            const scaleX = canvas.width  / r.width;
            const scaleY = canvas.height / r.height;
            const src = e.touches ? e.touches[0] : e;
            return [(src.clientX - r.left) * scaleX, (src.clientY - r.top) * scaleY];
        }

        canvas.addEventListener('mousedown', e => { drawing = true; ctx.beginPath(); const [x,y]=pos(e); ctx.moveTo(x,y); });
        canvas.addEventListener('mousemove', e => { if (!drawing) return; ctx.lineWidth=2; ctx.strokeStyle='#1a1a2e'; const [x,y]=pos(e); ctx.lineTo(x,y); ctx.stroke(); });
        canvas.addEventListener('mouseup',    () => drawing = false);
        canvas.addEventListener('mouseleave', () => drawing = false);
        canvas.addEventListener('touchstart', e => { e.preventDefault(); drawing=true; ctx.beginPath(); const [x,y]=pos(e); ctx.moveTo(x,y); }, {passive:false});
        canvas.addEventListener('touchmove',  e => { e.preventDefault(); if(!drawing)return; ctx.lineWidth=2; ctx.strokeStyle='#1a1a2e'; const [x,y]=pos(e); ctx.lineTo(x,y); ctx.stroke(); }, {passive:false});
        canvas.addEventListener('touchend',   () => drawing = false);
    });

    // ── Clear button ────────────────────────────────────────────────────────
    document.querySelectorAll('.sig-clear-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const canvas = document.getElementById(btn.dataset.canvas);
            const ctx = canvas.getContext('2d');
            ctx.fillStyle = 'white';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
        });
    });

    // ── Type-name mode: render cursive text onto canvas ────────────────────
    function renderTypedSignature(canvas, name) {
        const ctx      = canvas.getContext('2d');
        const baseFontSize = Math.min(42, canvas.height * 0.55);
        ctx.fillStyle  = 'white';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        if (!name.trim()) return;
        ctx.font         = 'italic ' + baseFontSize + 'px "Georgia", cursive, serif';
        ctx.fillStyle    = '#1a1a2e';
        ctx.textBaseline = 'middle';
        // Scale down if text is too wide
        const metrics = ctx.measureText(name);
        if (metrics.width > canvas.width - 20) {
            const scale = (canvas.width - 20) / metrics.width;
            ctx.font = 'italic ' + Math.floor(baseFontSize * scale) + 'px "Georgia", cursive, serif';
        }
        ctx.fillText(name, 10, canvas.height / 2);
    }

    document.querySelectorAll('.sig-type-input').forEach(input => {
        // Resolve canvas for both pullsheet (ps-) and change_order (co-) IDs
        const canvas = document.getElementById('sig-canvas-ps-' + input.dataset.id)
                    || document.getElementById('sig-canvas-co-' + input.dataset.id);
        if (!canvas) return;
        // Initial clear
        const ctx = canvas.getContext('2d');
        ctx.fillStyle = 'white';
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        input.addEventListener('input', () => renderTypedSignature(canvas, input.value));
    });

    // ── Approve button ──────────────────────────────────────────────────────
    document.querySelectorAll('.approve-submit-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const id       = btn.dataset.id;
            const type     = btn.dataset.type;
            const canvasId = btn.dataset.canvas;
            const canvas   = document.getElementById(canvasId);
            if (!canvas) { alert('Signature canvas not found'); return; }

            const ctx = canvas.getContext('2d');

            // Check empty (compare to blank white canvas)
            const blank = document.createElement('canvas');
            blank.width  = canvas.width;
            blank.height = canvas.height;
            blank.getContext('2d').fillStyle = 'white';
            blank.getContext('2d').fillRect(0, 0, blank.width, blank.height);

            const sigData = canvas.toDataURL();
            if (sigData === blank.toDataURL()) {
                alert('Please provide a signature before approving.');
                return;
            }

            // UI: loading
            const cardId  = type === 'pullsheet' ? 'ps-card-' + id : 'co-card-' + id;
            const card    = document.getElementById(cardId);
            const spinner = btn.parentElement.querySelector('.approve-spinner');
            const result  = btn.parentElement.querySelector('.approve-result');
            btn.disabled  = true;
            spinner.classList.remove('d-none');
            result.textContent = '';

            try {
                const resp = await fetch('/admin/approvals', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: new URLSearchParams({
                        ajax: '1',
                        action: 'approve',
                        type:   type,
                        id:     id,
                        signature_data: sigData
                    })
                });
                const data = await resp.json();

                spinner.classList.add('d-none');

                if (data.success) {
                    result.textContent = '✓ Approved!';
                    // For pullsheets: show PDF download link then fade card out
                    if (data.receipt_url) {
                        const link       = document.createElement('a');
                        link.href        = data.receipt_url;
                        link.target      = '_blank';
                        link.className   = 'btn btn-sm btn-primary ms-3';
                        link.download    = '';
                        link.innerHTML   = '<i class="ti ti-file-download me-1"></i>Download Pick Receipt PDF';
                        btn.parentElement.appendChild(link);
                    }
                    setTimeout(() => {
                        if (card) {
                            card.style.transition = 'opacity 0.6s';
                            card.style.opacity    = '0';
                            setTimeout(() => card.remove(), 650);
                        }
                    }, 3500);
                } else {
                    btn.disabled = false;
                    alert(data.message || 'Failed to approve');
                }
            } catch (err) {
                spinner.classList.add('d-none');
                btn.disabled = false;
                console.error(err);
                alert('An error occurred while submitting approval.');
            }
        });
    });
})();
</script>

<?php include '../includes/footer.php'; ?>
