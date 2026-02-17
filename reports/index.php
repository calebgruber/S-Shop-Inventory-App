<?php
require_once 'includes/functions.php';

// Only admins can view reports
requireRole('admin');

$pageTitle = 'Reports';
require_once 'includes/header.php';

$reportType = $_GET['type'] ?? 'inventory';
$categoryFilter = $_GET['category'] ?? 'all';
$subcategoryFilter = $_GET['subcategory'] ?? 'all';
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
            <a href="?type=by_space" class="btn btn-<?php echo $reportType === 'by_space' ? 'primary' : 'outline-primary'; ?>">By Space</a>
            <a href="?type=by_category" class="btn btn-<?php echo $reportType === 'by_category' ? 'primary' : 'outline-primary'; ?>">By Category</a>
        </div>
    </div>
</div>

<?php if ($reportType === 'inventory'): ?>
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-3" id="filterForm">
                <input type="hidden" name="type" value="inventory">
                <div class="col-md-4">
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
                <div class="col-md-4">
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
            foreach ($shows as $show): 
                $allocations = getDB()->fetchAll(
                    "SELECT i.name, ia.quantity, ia.status 
                     FROM item_allocations ia 
                     JOIN items i ON ia.item_id = i.id 
                     WHERE ia.show_id = ?",
                    [$show['id']]
                );
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
                                        <th>Quantity</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($allocations as $alloc): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($alloc['name']); ?></td>
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
                <p class="text-muted text-center">No items allocated to shows</p>
            <?php endif; ?>
        </div>
    </div>

<?php elseif ($reportType === 'by_space'): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Items by Theatre Space</h3>
            <div class="card-actions">
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="ti ti-printer"></i> Print
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="accordion" id="spacesAccordion">
            <?php 
            $spaceIndex = 0;
            foreach ($spaces as $space): 
                $allocations = getDB()->fetchAll(
                    "SELECT i.name, ia.quantity, s.name as show_name 
                     FROM item_allocations ia 
                     JOIN items i ON ia.item_id = i.id 
                     LEFT JOIN shows s ON ia.show_id = s.id 
                     WHERE ia.theatre_space_id = ? AND ia.status = 'checked_out'",
                    [$space['id']]
                );
                if (empty($allocations)) continue;
                $spaceIndex++;
            ?>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading-space-<?php echo $space['id']; ?>">
                        <button class="accordion-button <?php echo $spaceIndex > 1 ? 'collapsed' : ''; ?>" type="button" 
                                data-bs-toggle="collapse" data-bs-target="#collapse-space-<?php echo $space['id']; ?>" 
                                aria-expanded="<?php echo $spaceIndex === 1 ? 'true' : 'false'; ?>">
                            <strong><?php echo htmlspecialchars($space['name']); ?></strong>
                            <span class="badge bg-primary ms-2"><?php echo count($allocations); ?> items</span>
                        </button>
                    </h2>
                    <div id="collapse-space-<?php echo $space['id']; ?>" 
                         class="accordion-collapse collapse <?php echo $spaceIndex === 1 ? 'show' : ''; ?>" 
                         data-bs-parent="#spacesAccordion">
                        <div class="accordion-body">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Quantity</th>
                                        <th>Show</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($allocations as $alloc): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($alloc['name']); ?></td>
                                            <td><?php echo $alloc['quantity']; ?></td>
                                            <td><?php echo htmlspecialchars($alloc['show_name'] ?? 'N/A'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
            <?php if ($spaceIndex === 0): ?>
                <p class="text-muted text-center">No items allocated to theatre spaces</p>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
