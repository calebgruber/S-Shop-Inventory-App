<?php
/**
 * Inventory List/Browse
 * Display all inventory items with search, filter, and pagination
 */

require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/includes/helpers.php';

requireAuth();

$pageName = 'Inventory';
$currentUser = getCurrentUser();

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 30;
$offset = ($page - 1) * $perPage;

// Search and filters
$search = $_GET['search'] ?? '';
$categoryFilter = $_GET['category'] ?? '';
$subcategoryFilter = $_GET['subcategory'] ?? '';

// Build query
$where = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where[] = '(i.name LIKE ? OR i.barcode LIKE ? OR i.description LIKE ?)';
    $searchParam = '%' . $search . '%';
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= 'sss';
}

if (!empty($categoryFilter)) {
    $where[] = 'i.category_id = ?';
    $params[] = $categoryFilter;
    $types .= 'i';
}

if (!empty($subcategoryFilter)) {
    $where[] = 'i.subcategory_id = ?';
    $params[] = $subcategoryFilter;
    $types .= 'i';
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$countQuery = "SELECT COUNT(*) as total FROM items i $whereClause";
$countResult = executeQuery($countQuery, $params, $types);
$totalItems = 0;
if ($countResult) {
    $countRow = fetchAssoc($countResult);
    $totalItems = $countRow['total'];
}

$totalPages = ceil($totalItems / $perPage);

// Get items
$query = "SELECT i.*, 
          c.name as category_name, 
          s.name as subcategory_name
          FROM items i
          LEFT JOIN categories c ON i.category_id = c.id
          LEFT JOIN subcategories s ON i.subcategory_id = s.id
          $whereClause
          ORDER BY i.name ASC
          LIMIT ? OFFSET ?";

$params[] = $perPage;
$params[] = $offset;
$types .= 'ii';

$itemsResult = executeQuery($query, $params, $types);

$items = [];
if ($itemsResult) {
    while ($row = fetchAssoc($itemsResult)) {
        $items[] = $row;
    }
}

// Get all categories for filter dropdown
$categories = getAllCategories();

// Get statistics
$statsResult = executeQuery('SELECT 
    COUNT(*) as total,
    SUM(total_quantity) as total_qty,
    SUM(in_stock_quantity) as in_stock_qty
    FROM items');
$stats = $statsResult ? fetchAssoc($statsResult) : ['total' => 0, 'total_qty' => 0, 'in_stock_qty' => 0];

include dirname(__DIR__) . '/includes/header.php';
?>

<!-- Page Header -->
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">
                    <i class="ti ti-package me-2"></i>
                    Inventory
                </h2>
                <div class="text-muted mt-1"><?php echo number_format($stats['total']); ?> total items</div>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <?php if (hasRole(['admin', 'designer', 'production_audio'])): ?>
                    <a href="<?php echo BASE_URL; ?>inventory/categories.php" class="btn btn-outline-primary">
                        <i class="ti ti-category me-2"></i>
                        Categories
                    </a>
                    <a href="<?php echo BASE_URL; ?>inventory/add.php" class="btn btn-primary">
                        <i class="ti ti-plus me-2"></i>
                        Add Item
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Page Body -->
<div class="page-body">
    <div class="container-xl">
        <!-- Statistics Cards -->
        <div class="row row-deck row-cards mb-3">
            <div class="col-sm-6 col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Total Items</div>
                        </div>
                        <div class="h1 mb-0"><?php echo number_format($stats['total']); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Total Quantity</div>
                        </div>
                        <div class="h1 mb-0"><?php echo number_format($stats['total_qty']); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">In Stock</div>
                        </div>
                        <div class="h1 mb-0"><?php echo number_format($stats['in_stock_qty']); ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Search and Filter -->
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" action="" class="row g-2">
                    <div class="col-md-5">
                        <input type="text" class="form-control" name="search" placeholder="Search by name, barcode, or description..." value="<?php echo htmlspecialchars($search); ?>" autofocus>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" name="category" id="category-filter">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $categoryFilter == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" name="subcategory" id="subcategory-filter">
                            <option value="">All Subcategories</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="ti ti-search me-1"></i>
                            Search
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Items Table -->
        <div class="card">
            <div class="table-responsive">
                <table class="table table-vcenter card-table table-striped">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Barcode</th>
                            <th>Category</th>
                            <th>Type</th>
                            <th class="text-end">Quantity</th>
                            <th class="text-end">In Stock</th>
                            <th>Location</th>
                            <th class="w-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <?php if (!empty($search) || !empty($categoryFilter)): ?>
                                    No items found matching your criteria.
                                <?php else: ?>
                                    No items in inventory yet. <a href="<?php echo BASE_URL; ?>inventory/add.php">Add your first item</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php if (!empty($item['photo_path']) && file_exists(BASE_PATH . '/' . $item['photo_path'])): ?>
                                    <img src="<?php echo BASE_URL . htmlspecialchars($item['photo_path']); ?>" 
                                         alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                         class="avatar me-2">
                                    <?php else: ?>
                                    <span class="avatar me-2">
                                        <i class="ti ti-package"></i>
                                    </span>
                                    <?php endif; ?>
                                    <div>
                                        <div class="fw-bold"><?php echo htmlspecialchars($item['name']); ?></div>
                                        <?php if (!empty($item['description'])): ?>
                                        <div class="text-muted small"><?php echo htmlspecialchars(substr($item['description'], 0, 60)); ?><?php echo strlen($item['description']) > 60 ? '...' : ''; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="text-monospace"><?php echo htmlspecialchars($item['barcode']); ?></td>
                            <td>
                                <?php if ($item['category_name']): ?>
                                <div><?php echo htmlspecialchars($item['category_name']); ?></div>
                                <?php if ($item['subcategory_name']): ?>
                                <div class="text-muted small"><?php echo htmlspecialchars($item['subcategory_name']); ?></div>
                                <?php endif; ?>
                                <?php else: ?>
                                <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $item['tracking_type'] === 'serial' ? 'info' : 'secondary'; ?>">
                                    <?php echo ucfirst($item['tracking_type']); ?>
                                </span>
                            </td>
                            <td class="text-end"><?php echo number_format($item['total_quantity']); ?></td>
                            <td class="text-end">
                                <span class="text-<?php echo $item['in_stock_quantity'] > 0 ? 'success' : 'danger'; ?>">
                                    <?php echo number_format($item['in_stock_quantity']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($item['location'] ?? '—'); ?></td>
                            <td>
                                <div class="btn-group">
                                    <a href="<?php echo BASE_URL; ?>inventory/view.php?id=<?php echo $item['id']; ?>" 
                                       class="btn btn-sm btn-icon" title="View">
                                        <i class="ti ti-eye"></i>
                                    </a>
                                    <?php if (hasRole(['admin', 'designer', 'production_audio'])): ?>
                                    <a href="<?php echo BASE_URL; ?>inventory/edit.php?id=<?php echo $item['id']; ?>" 
                                       class="btn btn-sm btn-icon" title="Edit">
                                        <i class="ti ti-edit"></i>
                                    </a>
                                    <?php endif; ?>
                                    <?php if (hasRole('admin')): ?>
                                    <button type="button" class="btn btn-sm btn-icon" 
                                            onclick="confirmDelete(<?php echo $item['id']; ?>, '<?php echo addslashes($item['name']); ?>')" 
                                            title="Delete">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="card-footer d-flex align-items-center">
                <p class="m-0 text-muted">
                    Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $perPage, $totalItems); ?> of <?php echo $totalItems; ?> items
                </p>
                <ul class="pagination m-0 ms-auto">
                    <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($categoryFilter) ? '&category=' . $categoryFilter : ''; ?>">
                            <i class="ti ti-chevron-left"></i> Prev
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($categoryFilter) ? '&category=' . $categoryFilter : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($categoryFilter) ? '&category=' . $categoryFilter : ''; ?>">
                            Next <i class="ti ti-chevron-right"></i>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Delete confirmation
function confirmDelete(itemId, itemName) {
    if (confirm('Are you sure you want to delete "' + itemName + '"?\n\nThis action cannot be undone.')) {
        // Create form and submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?php echo BASE_URL; ?>inventory/delete.php';
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'id';
        input.value = itemId;
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
}

// Load subcategories when category changes
document.getElementById('category-filter').addEventListener('change', function() {
    const categoryId = this.value;
    const subcategorySelect = document.getElementById('subcategory-filter');
    
    // Clear subcategories
    subcategorySelect.innerHTML = '<option value="">All Subcategories</option>';
    
    if (!categoryId) {
        return;
    }
    
    // Fetch subcategories
    fetch('<?php echo BASE_URL; ?>api/get-subcategories.php?category_id=' + categoryId)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.subcategories) {
                data.subcategories.forEach(sub => {
                    const option = document.createElement('option');
                    option.value = sub.id;
                    option.textContent = sub.name;
                    if (sub.id == '<?php echo $subcategoryFilter; ?>') {
                        option.selected = true;
                    }
                    subcategorySelect.appendChild(option);
                });
            }
        })
        .catch(error => console.error('Error loading subcategories:', error));
});

// Load subcategories on page load if category is selected
window.addEventListener('DOMContentLoaded', function() {
    const categoryId = document.getElementById('category-filter').value;
    if (categoryId) {
        document.getElementById('category-filter').dispatchEvent(new Event('change'));
    }
});
</script>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>
