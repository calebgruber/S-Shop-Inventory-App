<?php
require_once '../includes/functions.php';
requirePermission('change_orders');

$currentUser = getCurrentUser();
$isDesigner = $currentUser['role'] === 'designer' || $currentUser['role'] === 'pa';

// Get show_id if provided (from show page button)
$showId = $_GET['show_id'] ?? null;
$pullsheetId = $_GET['pullsheet_id'] ?? null;

// Get available pullsheets
$query = "SELECT p.id, p.barcode, p.show_id, p.status, s.name as show_name 
          FROM pullsheets p 
          LEFT JOIN shows s ON p.show_id = s.id";

if ($isDesigner) {
    // Designers only see their assigned shows
    $query .= " INNER JOIN show_assignments sa ON s.id = sa.show_id WHERE sa.user_id = ?";
    $pullsheets = getDB()->query($query, [$currentUser['id']])->fetchAll();
} else {
    $pullsheets = getDB()->query($query)->fetchAll();
}

$pageTitle = 'Create Change Order';
require_once '../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $selectedPullsheetId = $_POST['pullsheet_id'] ?? null;
        
        if (!$selectedPullsheetId) {
            throw new Exception('You must select a shop order to create a change order for.');
        }
        
        // Validate pullsheet exists
        $pullsheet = getDB()->query("SELECT * FROM pullsheets WHERE id = ?", [$selectedPullsheetId])->fetch();
        if (!$pullsheet) {
            throw new Exception('Selected shop order not found.');
        }
        
        // Check permission for designers
        if ($isDesigner && $pullsheet['show_id']) {
            if (!canAccessShow($currentUser['id'], $pullsheet['show_id'])) {
                throw new Exception('You do not have permission to create change orders for this shop order.');
            }
        }
        
        $barcode = generateUniqueBarcode('CO');
        $createdBy = $currentUser['name'] ?? 'Unknown';
        
        getDB()->query(
            "INSERT INTO change_orders (show_id, pullsheet_id, barcode, created_by, status) VALUES (?, ?, ?, ?, 'draft')",
            [$pullsheet['show_id'], $selectedPullsheetId, $barcode, $createdBy]
        );
        
        setAlert('Change order created for shop order ' . htmlspecialchars($pullsheet['barcode']));
        redirect('/change-orders/edit?id=' . getDB()->lastInsertId());
    } catch (Exception $e) {
        setAlert($e->getMessage(), 'danger');
    }
}
?>

<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Create Change Order</h3>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="alert alert-info mb-3">
                        <i class="ti ti-info-circle"></i>
                        <strong>Important:</strong> Change orders must be tied to a shop order. Select the shop order you want to modify below.
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label required">Select Shop Order</label>
                        <select name="pullsheet_id" class="form-select" required>
                            <option value="">-- Select a Shop Order --</option>
                            <?php foreach ($pullsheets as $ps): ?>
                                <option value="<?php echo $ps['id']; ?>" 
                                        <?php echo ($pullsheetId && $ps['id'] == $pullsheetId) ? 'selected' : ''; ?>>
                                    <?php 
                                    echo htmlspecialchars($ps['barcode']);
                                    if ($ps['show_name']) {
                                        echo ' - ' . htmlspecialchars($ps['show_name']);
                                    } else {
                                        echo ' - General Inventory';
                                    }
                                    echo ' (' . htmlspecialchars($ps['status']) . ')';
                                    ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-hint">Change orders modify an existing shop order by adding or removing items.</small>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="ti ti-alert-triangle"></i>
                        You'll be able to add items to add/remove on the next screen.
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-plus"></i>
                            Create Change Order
                        </button>
                        <a href="/change-orders/" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
