<?php
require_once '../includes/functions.php';

// Only admins can view reports
requireRole('admin');

$pageTitle = 'Reports';
require_once '../includes/header.php';

$reportType = $_GET['type'] ?? 'inventory';
$categoryFilter = $_GET['category'] ?? 'all';
$subcategoryFilter = $_GET['subcategory'] ?? 'all';
$showFilter = $_GET['show'] ?? 'all';
$stockFilter = $_GET['stock'] ?? 'all'; // New filter for stock status
$items = getAllItems();
$shows = getAllShows();
$spaces = getAllTheatreSpaces();
$categories = getAllCategories();

// Get subcategories for the selected category
$subcategories = [];
if ($categoryFilter !== 'all') {
    $subcategories = getDB()->fetchAll(
        "SELECT * FROM subcategories WHERE category_id = ? ORDER BY name",
        [$categoryFilter]
    );
}
?>

<div class="row mb-4">
    <div class="col-12">
        <div class="btn-group" role="group">
            <a href="?type=inventory" class="btn btn-<?php echo $reportType === 'inventory' ? 'primary' : 'outline-primary'; ?>">Inventory</a>
            <a href="?type=by_show" class="btn btn-<?php echo $reportType === 'by_show' ? 'primary' : 'outline-primary'; ?>">By Show</a>
        </div>
    </div>
</div>

<?php if ($reportType === 'inventory'): ?>
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-3" id="filterForm">
                <input type="hidden" name="type" value="inventory">
                <div class="col-md-3">
                    <label class="form-label">Filter by Category</label>
                    <select class="form-select" name="category" id="categorySelect" onchange="document.getElementById('filterForm').submit()">
                        <option value="all" <?php echo $categoryFilter === 'all' ? 'selected' : ''; ?>>All Categories</option>
                        <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" <?php echo $categoryFilter == $category['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if (!empty($subcategories)): ?>
                <div class="col-md-3">
                    <label class="form-label">Filter by Subcategory</label>
                    <select class="form-select" name="subcategory" onchange="this.form.submit()">
                        <option value="all" <?php echo $subcategoryFilter === 'all' ? 'selected' : ''; ?>>All Subcategories</option>
                        <?php foreach ($subcategories as $subcategory): ?>
                        <option value="<?php echo $subcategory['id']; ?>" <?php echo $subcategoryFilter == $subcategory['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($subcategory['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="col-md-3">
                    <label class="form-label">Filter by Stock Status</label>
                    <select class="form-select" name="stock" onchange="this.form.submit()">
                        <option value="all" <?php echo $stockFilter === 'all' ? 'selected' : ''; ?>>All Items</option>
                        <option value="in_stock" <?php echo $stockFilter === 'in_stock' ? 'selected' : ''; ?>>In Stock</option>
                        <option value="out_of_stock" <?php echo $stockFilter === 'out_of_stock' ? 'selected' : ''; ?>>Out of Stock</option>
                        <option value="low_stock" <?php echo $stockFilter === 'low_stock' ? 'selected' : ''; ?>>Low Stock (≤5)</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <a href="?type=inventory" class="btn btn-secondary w-100">Clear Filters</a>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Inventory Report</h3>
            <div class="card-actions">
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="ti ti-printer"></i> Print
                </button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-vcenter card-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Category</th>
                        <?php if ($categoryFilter !== 'all'): ?>
                        <th>Subcategory</th>
                        <?php endif; ?>
                        <th>Barcode</th>
                        <th>In Stock</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    // Group items by category and subcategory
                    $itemsByCategory = [];
                    foreach ($items as $item) {
                        // Apply category filter
                        if ($categoryFilter !== 'all' && $item['category_id'] != $categoryFilter) {
                            continue;
                        }
                        // Apply subcategory filter
                        if ($subcategoryFilter !== 'all' && $item['subcategory_id'] != $subcategoryFilter) {
                            continue;
                        }
                        // Apply stock filter
                        if ($stockFilter !== 'all') {
                            $inStock = (int)$item['in_stock_quantity'];
                            if ($stockFilter === 'out_of_stock' && $inStock > 0) {
                                continue;
                            }
                            if ($stockFilter === 'in_stock' && $inStock <= 0) {
                                continue;
                            }
                            if ($stockFilter === 'low_stock' && $inStock > 5) {
                                continue;
                            }
                        }
                        $catName = $item['category_name'] ?? 'Uncategorized';
                        if (!isset($itemsByCategory[$catName])) {
                            $itemsByCategory[$catName] = [];
                        }
                        $itemsByCategory[$catName][] = $item;
                    }
                    ksort($itemsByCategory);
                    
                    foreach ($itemsByCategory as $catName => $catItems): ?>
                        <tr class="table-active">
                            <td colspan="<?php echo $categoryFilter !== 'all' ? '6' : '5'; ?>" style="padding-left: 0;"><strong><?php echo htmlspecialchars($catName); ?></strong></td>
                        </tr>
                        <?php foreach ($catItems as $item): ?>
                        <tr>
                            <td style="padding-left: 2rem;"><?php echo htmlspecialchars($item['name']); ?></td>
                            <td><?php echo htmlspecialchars($item['category_name'] ?? 'N/A'); ?></td>
                            <?php if ($categoryFilter !== 'all'): ?>
                            <td><?php echo htmlspecialchars($item['subcategory_name'] ?? 'N/A'); ?></td>
                            <?php endif; ?>
                            <td><code><?php echo htmlspecialchars($item['barcode']); ?></code></td>
                            <td><?php echo $item['in_stock_quantity']; ?></td>
                            <td><?php echo $item['total_quantity']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($reportType === 'by_show'): ?>
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-3" id="showFilterForm">
                <input type="hidden" name="type" value="by_show">
                <div class="col-md-3">
                    <label class="form-label">Filter by Show</label>
                    <select class="form-select" name="show" onchange="this.form.submit()">
                        <option value="all" <?php echo $showFilter === 'all' ? 'selected' : ''; ?>>All Shows</option>
                        <option value="null" <?php echo $showFilter === 'null' ? 'selected' : ''; ?>>No Show (General Inventory)</option>
                        <?php foreach ($shows as $show): ?>
                        <option value="<?php echo $show['id']; ?>" <?php echo $showFilter == $show['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($show['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Filter by Category</label>
                    <select class="form-select" name="category" id="categorySelectShow" onchange="this.form.submit()">
                        <option value="all" <?php echo $categoryFilter === 'all' ? 'selected' : ''; ?>>All Categories</option>
                        <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" <?php echo $categoryFilter == $category['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if (!empty($subcategories)): ?>
                <div class="col-md-3">
                    <label class="form-label">Filter by Subcategory</label>
                    <select class="form-select" name="subcategory" onchange="this.form.submit()">
                        <option value="all" <?php echo $subcategoryFilter === 'all' ? 'selected' : ''; ?>>All Subcategories</option>
                        <?php foreach ($subcategories as $subcategory): ?>
                        <option value="<?php echo $subcategory['id']; ?>" <?php echo $subcategoryFilter == $subcategory['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($subcategory['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <a href="?type=by_show" class="btn btn-secondary w-100">Clear Filters</a>
                </div>
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Items by Show</h3>
            <div class="card-actions">
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="ti ti-printer"></i> Print
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="accordion" id="showsAccordion">
            <?php 
            $showIndex = 0;
            $showsList = ($showFilter === 'all') ? $shows : ($showFilter === 'null' ? [['id' => null, 'name' => 'No Show (General Inventory)']] : array_filter($shows, function($s) use ($showFilter) { return $s['id'] == $showFilter; }));
            
            foreach ($showsList as $show): 
                // Build query with filters
                if ($show['id'] === null) {
                    // For items without a show, query pullsheet_items directly
                    $query = "SELECT i.name, i.id as item_id, pi.quantity_needed as quantity, 'in_pullsheet' as status, c.name as category_name, sc.name as subcategory_name, ps.barcode as pullsheet_barcode
                             FROM pullsheet_items pi
                             JOIN pullsheets ps ON pi.pullsheet_id = ps.id
                             JOIN items i ON pi.item_id = i.id 
                             LEFT JOIN categories c ON i.category_id = c.id
                             LEFT JOIN subcategories sc ON i.subcategory_id = sc.id
                             WHERE ps.show_id IS NULL";
                    $params = [];
                } else {
                    $query = "SELECT i.name, i.id as item_id, ia.quantity, ia.status, c.name as category_name, sc.name as subcategory_name
                             FROM item_allocations ia 
                             JOIN items i ON ia.item_id = i.id 
                             LEFT JOIN categories c ON i.category_id = c.id
                             LEFT JOIN subcategories sc ON i.subcategory_id = sc.id
                             WHERE ia.show_id = ?";
                    $params = [$show['id']];
                }
                
                // Add category filter
                if ($categoryFilter !== 'all') {
                    $query .= " AND i.category_id = ?";
                    $params[] = $categoryFilter;
                }
                
                // Add subcategory filter
                if ($subcategoryFilter !== 'all') {
                    $query .= " AND i.subcategory_id = ?";
                    $params[] = $subcategoryFilter;
                }
                
                $query .= " ORDER BY c.name, sc.name, i.name";
                
                $allocations = getDB()->fetchAll($query, $params);
                if (empty($allocations)) continue;
                $showIndex++;
            ?>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading-show-<?php echo $show['id']; ?>">
                        <button class="accordion-button <?php echo $showIndex > 1 ? 'collapsed' : ''; ?>" type="button" 
                                data-bs-toggle="collapse" data-bs-target="#collapse-show-<?php echo $show['id']; ?>" 
                                aria-expanded="<?php echo $showIndex === 1 ? 'true' : 'false'; ?>">
                            <strong><?php echo htmlspecialchars($show['name']); ?></strong>
                            <span class="badge bg-primary ms-2"><?php echo count($allocations); ?> items</span>
                        </button>
                    </h2>
                    <div id="collapse-show-<?php echo $show['id']; ?>" 
                         class="accordion-collapse collapse <?php echo $showIndex === 1 ? 'show' : ''; ?>" 
                         data-bs-parent="#showsAccordion">
                        <div class="accordion-body">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Category</th>
                                        <th>Subcategory</th>
                                        <th>Quantity</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($allocations as $alloc): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($alloc['name']); ?></td>
                                            <td><?php echo htmlspecialchars($alloc['category_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($alloc['subcategory_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo $alloc['quantity']; ?></td>
                                            <td><span class="badge"><?php echo ucfirst($alloc['status']); ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
            <?php if ($showIndex === 0): ?>
                <p class="text-muted text-center">No items found matching the selected filters</p>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>
