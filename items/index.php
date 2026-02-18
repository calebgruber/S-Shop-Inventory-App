<?php
$pageTitle = 'Items';
require_once '../includes/header.php';
requirePermission('items');

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

// Get filter parameters
$filterCategory = isset($_GET['category']) && $_GET['category'] !== '' ? (int)$_GET['category'] : null;
$filterSubcategory = isset($_GET['subcategory']) && $_GET['subcategory'] !== '' ? (int)$_GET['subcategory'] : null;

// Get all categories and subcategories for filters
$categories = getAllCategories();
$allSubcategories = getAllSubcategories();

// Get filtered items
$items = getAllItemsFiltered($filterCategory, $filterSubcategory);
?>

<?php if ($canEdit): ?>
<div class="row mb-3">
    <div class="col">
        <a href="/items/edit" class="btn btn-primary">
            <i class="ti ti-plus"></i> Add New Item
        </a>
    </div>
</div>
<?php endif; ?>

<!-- Filters -->
<div class="row mb-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">
                    <i class="ti ti-filter"></i> Filters
                </h4>
            </div>
            <div class="card-body">
                <form method="GET" action="/items/" id="filterForm">
                    <div class="row">
                        <div class="col-md-4">
                            <label class="form-label">Category</label>
                            <select name="category" id="categoryFilter" class="form-select">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                            <?php echo $filterCategory == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Subcategory</label>
                            <select name="subcategory" id="subcategoryFilter" class="form-select">
                                <option value="">All Subcategories</option>
                                <?php foreach ($allSubcategories as $subcategory): ?>
                                    <option value="<?php echo $subcategory['id']; ?>" 
                                            data-category="<?php echo $subcategory['category_id']; ?>"
                                            <?php echo $filterSubcategory == $subcategory['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($subcategory['name']); ?> (<?php echo htmlspecialchars($subcategory['category_name']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="ti ti-search"></i> Apply Filters
                            </button>
                            <a href="/items/" class="btn btn-secondary">
                                <i class="ti ti-x"></i> Clear
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    All Items 
                    <?php if (!$canEdit): ?>
                        <span class="badge bg-info ms-2">Read-Only</span>
                    <?php endif; ?>
                    <?php if ($filterCategory || $filterSubcategory): ?>
                        <span class="badge bg-primary ms-2">Filtered</span>
                    <?php endif; ?>
                </h3>
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
                            <th>Subcategory</th>
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
                                <td><?php echo htmlspecialchars($item['subcategory_name'] ?? 'N/A'); ?></td>
                                <td><span class="badge"><?php echo ucfirst($item['tracking_type']); ?></span></td>
                                <td><?php echo $item['in_stock_quantity']; ?></td>
                                <td><?php echo $item['total_quantity']; ?></td>
                                <?php if ($canEdit): ?>
                                <td>
                                    <div class="btn-group">
                                        <a href="item_edit?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-primary">
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
// Search functionality
document.getElementById('searchInput').addEventListener('keyup', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('#itemsTable tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});

// Dynamic subcategory filtering based on selected category
const categoryFilter = document.getElementById('categoryFilter');
const subcategoryFilter = document.getElementById('subcategoryFilter');

// Store all subcategory options
const allSubcategoryOptions = Array.from(subcategoryFilter.options).slice(1); // Skip "All Subcategories" option

function updateSubcategoryOptions() {
    const selectedCategory = categoryFilter.value;
    
    // Clear current options except the first one
    subcategoryFilter.innerHTML = '<option value="">All Subcategories</option>';
    
    if (selectedCategory === '') {
        // Show all subcategories
        allSubcategoryOptions.forEach(option => {
            subcategoryFilter.appendChild(option.cloneNode(true));
        });
    } else {
        // Show only subcategories for selected category
        allSubcategoryOptions.forEach(option => {
            if (option.dataset.category === selectedCategory) {
                subcategoryFilter.appendChild(option.cloneNode(true));
            }
        });
    }
}

// Update subcategories when category changes
categoryFilter.addEventListener('change', function() {
    updateSubcategoryOptions();
});

// Initialize on page load
updateSubcategoryOptions();
</script>

<?php require_once '../includes/footer.php'; ?>
