<?php
session_start();
require_once __DIR__ . '/includes/functions.php';

$pickSession = $_SESSION['pick_session'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    try {
        if ($_POST['action'] === 'start_pick') {
            $pullsheet = getPullsheetByBarcode($_POST['barcode']);
            if ($pullsheet && $pullsheet['status'] === 'finalized') {
                $_SESSION['pick_session'] = ['type' => 'pullsheet', 'id' => $pullsheet['id'], 'picker_name' => $_POST['picker_name'], 'items' => []];
                foreach (getPullsheetItems($pullsheet['id']) as $item) {
                    $_SESSION['pick_session']['items'][$item['item_id']] = ['name' => $item['item_name'], 'barcode' => $item['item_barcode'], 'needed' => $item['quantity_needed'], 'scanned' => 0];
                }
                echo json_encode(['success' => true]);
                exit;
            }
            echo json_encode(['success' => false, 'message' => 'Not found']);
            exit;
        }
        if ($_POST['action'] === 'scan_item') {
            $item = getItemByBarcode($_POST['barcode']);
            if ($item && isset($_SESSION['pick_session']['items'][$item['id']])) {
                $_SESSION['pick_session']['items'][$item['id']]['scanned']++;
                echo json_encode(['success' => true, 'item' => $_SESSION['pick_session']['items'][$item['id']], 'itemId' => $item['id']]);
                exit;
            }
            echo json_encode(['success' => false, 'message' => 'Not in list']);
            exit;
        }
        if ($_POST['action'] === 'complete_pick') {
            foreach ($_SESSION['pick_session']['items'] as $itemId => $data) {
                getDB()->query("UPDATE pullsheet_items SET quantity_picked = ? WHERE pullsheet_id = ? AND item_id = ?", [$data['scanned'], $_SESSION['pick_session']['id'], $itemId]);
                getDB()->query("UPDATE item_allocations SET status = 'checked_out' WHERE pullsheet_id = ? AND item_id = ?", [$_SESSION['pick_session']['id'], $itemId]);
            }
            getDB()->query("UPDATE pullsheets SET status = 'picked', picked_at = NOW(), picked_by = ? WHERE id = ?", [$_SESSION['pick_session']['picker_name'], $_SESSION['pick_session']['id']]);
            unset($_SESSION['pick_session']);
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
<html><head><meta charset="UTF-8"><title>Pick Mode</title>
<link href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta19/dist/css/tabler.min.css" rel="stylesheet"/>
<style>.item-card-incomplete{border-left:5px solid #d63939}.item-card-complete{border-left:5px solid #2fb344}.large-input{font-size:1.5rem;padding:1rem}</style>
</head><body style="padding:2rem">
<audio id="successSound"><source src="assets/sounds/success.mp3" type="audio/mpeg"></audio>
<audio id="errorSound"><source src="assets/sounds/error.mp3" type="audio/mpeg"></audio>
<div class="container-xl"><div class="d-flex justify-content-between mb-4"><h1>Pick Mode</h1><a href="index.php" class="btn btn-secondary">Exit</a></div>
<?php if (!$pickSession): ?>
<div class="row justify-content-center"><div class="col-md-6"><div class="card"><div class="card-body">
<div class="mb-3"><label>Your Name</label><input type="text" class="form-control large-input" id="pickerName" autofocus></div>
<div class="mb-3"><label>Scan Pullsheet</label><input type="text" class="form-control large-input" id="pullsheetBarcode"></div>
<button class="btn btn-primary btn-lg w-100" id="startBtn">Start Pick</button>
</div></div></div></div>
<?php else: ?>
<div class="mb-4"><input type="text" class="form-control large-input" id="itemScan" placeholder="Scan item..." autofocus></div>
<div class="row">
<?php foreach ($pickSession['items'] as $itemId => $item): $s = $item['scanned'] == $item['needed'] ? 'complete' : 'incomplete'; ?>
<div class="col-md-4 mb-3"><div class="card item-card-<?php echo $s; ?>" data-item-id="<?php echo $itemId; ?>">
<div class="card-body"><h4><?php echo htmlspecialchars($item['name']); ?></h4>
<div class="h1"><span class="scanned"><?php echo $item['scanned']; ?></span> / <?php echo $item['needed']; ?></div>
</div></div></div>
<?php endforeach; ?>
</div>
<button class="btn btn-success btn-lg w-100" id="completeBtn">Complete Pick</button>
<?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta19/dist/js/tabler.min.js"></script>
<script>
const ps=()=>document.getElementById('successSound').play().catch(()=>{});
const pe=()=>document.getElementById('errorSound').play().catch(()=>{});
const post=(a,cb)=>fetch('pick_mode.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:a}).then(r=>r.json()).then(cb);
<?php if (!$pickSession): ?>
document.getElementById('startBtn').addEventListener('click',()=>{
const n=document.getElementById('pickerName').value,b=document.getElementById('pullsheetBarcode').value;
if(!n||!b)return;
post(`ajax=1&action=start_pick&picker_name=${encodeURIComponent(n)}&barcode=${encodeURIComponent(b)}`,d=>{
if(d.success){ps();location.reload()}else{pe();alert(d.message)}
});
});
<?php else: ?>
document.getElementById('itemScan').addEventListener('keypress',function(e){
if(e.key==='Enter'){
const b=this.value.trim();this.value='';
post(`ajax=1&action=scan_item&barcode=${encodeURIComponent(b)}`,d=>{
if(d.success){
ps();
const c=document.querySelector(`[data-item-id="${d.itemId}"]`);
if(c){c.querySelector('.scanned').textContent=d.item.scanned;
c.className='card item-card-'+(d.item.scanned==d.item.needed?'complete':'incomplete');}
document.getElementById('completeBtn').disabled=![...document.querySelectorAll('.item-card-complete')].length==document.querySelectorAll('[data-item-id]').length;
}else{pe();alert(d.message)}
});
}
});
document.getElementById('completeBtn').addEventListener('click',()=>{
if(!confirm('Complete?'))return;
post('ajax=1&action=complete_pick',d=>{if(d.success){alert('Done!');location.href='index.php'}});
});
<?php endif; ?>
</script>
</body></html>
