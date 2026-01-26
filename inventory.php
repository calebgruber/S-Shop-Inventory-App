<?php
require_once 'config.php';
require_once 'db.php';
require_once 'functions.php';

$db = getDB();

// Get filter parameters
$categoryFilter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query
$sql = "SELECT i.*, c.name as category_name 
        FROM items i 
        LEFT JOIN categories c ON i.category_id = c.id 
        WHERE 1=1";

$params = [];
$types = '';

if ($categoryFilter > 0) {
    $sql .= " AND i.category_id = ?";
    $params[] = $categoryFilter;
    $types .= 'i';
}

if ($searchQuery !== '') {
    $sql .= " AND (i.name LIKE ? OR i.barcode LIKE ?)";
    $searchLike = "%$searchQuery%";
    $params[] = $searchLike;
    $params[] = $searchLike;
    $types .= 'ss';
}

$sql .= " ORDER BY i.name";

$stmt = $db->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$items = $stmt->get_result();

// Get categories for filter
$categories = $db->query("SELECT * FROM categories ORDER BY name");

$pageTitle = "Inventory - " . APP_NAME;
$pageHeader = "Inventory";
$pageActions = '<a href="inventory_create.php" class="btn btn-primary"><i class="ti ti-plus"></i> Add Item</a>';

ob_start();
?>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" 
                       value="<?php echo sanitize($searchQuery); ?>" 
                       placeholder="Search by name or barcode">
            </div>
            <div class="col-md-4">
                <label class="form-label">Category</label>
                <select name="category" class="form-select">
                    <option value="">All Categories</option>
                    <?php while ($cat = $categories->fetch_assoc()): ?>
                    <option value="<?php echo $cat['id']; ?>" 
                        <?php echo $categoryFilter == $cat['id'] ? 'selected' : ''; ?>>
                        <?php echo sanitize($cat['name']); ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="ti ti-search"></i> Search
                </button>
                <a href="inventory.php" class="btn btn-secondary">
                    Clear
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Barcode</th>
                    <th>Category</th>
                    <th>Total Qty</th>
                    <th>Available</th>
                    <th>Status</th>
                    <th width="120"></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($items->num_rows > 0): ?>
                    <?php while ($item = $items->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo sanitize($item['name']); ?></td>
                        <td>
                            <span class="font-monospace"><?php echo sanitize($item['barcode']); ?></span>
                        </td>
                        <td><?php echo $item['category_name'] ? sanitize($item['category_name']) : '-'; ?></td>
                        <td><?php echo $item['total_quantity']; ?></td>
                        <td><?php echo $item['available_quantity']; ?></td>
                        <td>
                            <?php if ($item['available_quantity'] === 0): ?>
                            <span class="badge bg-danger">Out of Stock</span>
                            <?php elseif ($item['available_quantity'] < 5): ?>
                            <span class="badge bg-warning">Low Stock</span>
                            <?php else: ?>
                            <span class="badge bg-success">In Stock</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="inventory_edit.php?id=<?php echo $item['id']; ?>" 
                                   class="btn btn-primary" title="Edit">
                                    <i class="ti ti-edit"></i>
                                </a>
                                <a href="barcode_generator.php?type=code128&data=<?php echo urlencode($item['barcode']); ?>&download=1" 
                                   class="btn btn-success" title="Download Barcode" target="_blank">
                                    <i class="ti ti-barcode"></i>
                                </a>
                                <a href="inventory_delete.php?id=<?php echo $item['id']; ?>" 
                                   class="btn btn-danger" title="Delete"
                                   onclick="return confirm('Are you sure you want to delete this item?');">
                                    <i class="ti ti-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            No items found
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$content = ob_get_clean();
require 'layout.php';
?>
