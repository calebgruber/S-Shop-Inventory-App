<?php
$pageTitle = 'Items';
require_once 'includes/header.php';

$currentUser = getCurrentUser();
$canEdit = isAdmin(); // Only admins can edit items

// Handle delete request - only admins
if ($canEdit && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    try {
        $deleteId = (int)$_POST['delete_id'];
        getDB()->query("DELETE FROM items WHERE id = ?", [$deleteId]);
        setAlert('Item deleted successfully');
        redirect();
    } catch (Exception $e) {
        setAlert('Error deleting item: ' . $e->getMessage(), 'danger');
    }
}

$items = getAllItems();
?>

<?php if ($canEdit): ?>
<div class="row mb-3">
    <div class="col">
        <a href="item_edit.php" class="btn btn-primary">
            <i class="ti ti-plus"></i> Add New Item
        </a>
    </div>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">All Items <?php if (!$canEdit): ?><span class="badge bg-info ms-2">Read-Only</span><?php endif; ?></h3>
                <div class="ms-auto">
                    <input type="text" class="form-control barcode-autofocus" id="searchInput" placeholder="Search items...">
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table" id="itemsTable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Barcode</th>
                            <th>Category</th>
                            <th>Type</th>
                            <th>In Stock</th>
                            <th>Total</th>
                            <?php if ($canEdit): ?>
                            <th class="w-1">Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td><code><?php echo htmlspecialchars($item['barcode']); ?></code></td>
                                <td><?php echo htmlspecialchars($item['category_name'] ?? 'N/A'); ?></td>
                                <td><span class="badge"><?php echo ucfirst($item['tracking_type']); ?></span></td>
                                <td><?php echo $item['in_stock_quantity']; ?></td>
                                <td><?php echo $item['total_quantity']; ?></td>
                                <?php if ($canEdit): ?>
                                <td>
                                    <div class="btn-group">
                                        <a href="item_edit.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="ti ti-edit"></i>
                                        </a>
                                        <a href="item_barcodes.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="ti ti-barcode"></i>
                                        </a>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this item?');">
                                            <input type="hidden" name="delete_id" value="<?php echo $item['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('searchInput').addEventListener('keyup', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('#itemsTable tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
