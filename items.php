<?php
$pageTitle = 'Items';
require_once 'includes/header.php';

$items = getAllItems();
?>

<div class="row mb-3">
    <div class="col">
        <a href="item_edit.php" class="btn btn-primary">
            <i class="ti ti-plus"></i> Add New Item
        </a>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">All Items</h3>
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
                            <th class="w-1">Actions</th>
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
                                <td>
                                    <div class="btn-group">
                                        <a href="item_edit.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="ti ti-edit"></i>
                                        </a>
                                        <a href="item_barcodes.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-info">
                                            <i class="ti ti-barcode"></i>
                                        </a>
                                    </div>
                                </td>
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
